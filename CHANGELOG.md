# Changelog - Controller Refactoring

## Monitoring & Observability (Aug 2026)

### Laravel Pulse

- Installed `laravel/pulse` with database storage (`pulse_entries`, `pulse_aggregates`, `pulse_values` tables via `2026_08_06_065119_create_pulse_tables.php`)
- Dashboard at `/pulse`, gated behind `admin.access` via the `Authorize` middleware + `Gate::define('viewPulse')`
- `pulse:check` scheduled every minute in `routes/console.php`; Docker starts the scheduler via `schedule:work`
- **Period filter fix** — removed the `SetPulseDefaultPeriod` middleware that forced `?period=24_hours`. Pulse's native UI represents **1h** by omitting the `period` param, so the middleware hijacked the 1h choice and snapped it back to 24h. With it gone, all four filters (1h / 6h / 24h / 7d) are selectable and the default is Pulse's native 1h. This also removed the Livewire reload-loop surface (the middleware previously ran as a Livewire persistent middleware on update requests).

### Slow-Query Demo

- `database/seeders/SlowDemoSeeder.php` — idempotent seeder targeting `SLOW_DEMO_ROWS` (default 500k) realistic rows in `user_activity_logs`, tagged `data.demo=true` for easy cleanup
- `AdminController::runSlowDemoQueries()` — when `SLOW_DEMO=true`, the admin dashboard runs three realistically badly-written queries (`ORDER BY RAND()`, `GROUP BY SUBSTRING(description,1,25)`, `description REGEXP ... AND user_agent LIKE '%bot%'`) so Pulse captures genuine `slow_query` entries (~1.3–2.6s each at 500k rows)
- Toggle via `SLOW_DEMO` env (default `false`); cleanup with `DELETE FROM user_activity_logs WHERE JSON_EXTRACT(data, "$.demo") = true;`

### Exception Tracking

- **Capture** — `app/Services/ExceptionTracker.php` registered as a `reportable()` hook in `AppServiceProvider`. Stores every reported exception (HTTP, queue, console): class, full message, 50-frame stack trace, file:line, request method/URL/IP, user ID, environment. Re-entrancy guard + silent failure means capture can never break or recurse the app
- **Schema** — `exceptions` table via `2026_08_06_140000_create_exceptions_table.php` (indexed on class + created_at, FK to users)
- **Admin page** — `/admin/exceptions` (Inertia/Vue, permission `exceptions.read`): search, class filter, date range, pagination; detail modal with expandable stack trace + copy-to-clipboard; "Throw test exception" button records a demo exception
- **Prune** — `app/Console/Commands/PruneExceptions.php` (`exceptions:prune --days=30`), scheduled weekly
- Published `config/inertia.php` with the correct `resources/js/Pages` path so `assertInertia` page-existence checks pass

### Migration Note

Run `php artisan migrate` — adds the Pulse tables and the `exceptions` table. No data migration for existing rows.

---

## FormRequest Validation Extraction

All inline `$request->validate([...])` calls extracted to dedicated FormRequest classes in `app/Http/Requests/`.

### New Files Created

| File | Used By |
|---|---|
| `app/Http/Requests/LoginRequest.php` | `AuthController@login` |
| `app/Http/Requests/RegisterRequest.php` | `AuthController@register` |
| `app/Http/Requests/AddressRequest.php` | `AddressController@store`, `@update` |
| `app/Http/Requests/ContactRequest.php` | `ContactController@store` |
| `app/Http/Requests/NewsletterRequest.php` | `NewsletterController@subscribe`, `@unsubscribe` |
| `app/Http/Requests/PasswordResetRequest.php` | `PasswordResetController@sendResetLink` |
| `app/Http/Requests/PasswordUpdateRequest.php` | `PasswordResetController@updatePassword` |
| `app/Http/Requests/PaymentRequest.php` | `PaymentController@createCheckoutSession` |
| `app/Http/Requests/ProductRequest.php` | `Admin\ProductController@store`, `@update` (combined + `prepareForValidation` for boolean casting) |
| `app/Http/Requests/CategoryRequest.php` | `Admin\CategoryController@store`, `@update` (combined) |
| `app/Http/Requests/CouponRequest.php` | `Admin\CouponController@store`, `@update` (combined) |
| `app/Http/Requests/UserRequest.php` | `Admin\UserController@store`, `@update` (combined, password required only on create) |
| `app/Http/Requests/RoleRequest.php` | `Admin\RoleController@store`, `@update` (combined) |
| `app/Http/Requests/SettingsRequest.php` | `Admin\SettingsController@update` |
| `app/Http/Requests/OrderStatusRequest.php` | `Admin\OrderController@update` |
| `app/Http/Requests/Api/PlaceOrderRequest.php` | `Api\OrderController@store` |
| `app/Http/Requests/Api/ReviewRequest.php` | `Api\ProductReviewController@store`, `@update` |
| `app/Http/Requests/Api/CartItemRequest.php` | `Api\CartController@add` |
| `app/Http/Requests/Api/CartQuantityRequest.php` | `Api\CartController@update` |
| `app/Http/Requests/Api/CouponCodeRequest.php` | `Api\CartController@applyCoupon` |
| `app/Http/Requests/Api/WishlistRequest.php` | `Api\WishlistController@add` |

### Key Design Decisions
- Store/update requests combined using `$this->route('param')` for `unique:table,column,ignore` rules
- `authorize()` returns `true` everywhere (auth handled by route middleware)
- `ProductRequest` includes `prepareForValidation()` to normalize boolean fields

---

## N+1 Query Fixes

### Critical Fixes

| File | Issue | Fix |
|---|---|---|
| `Api/CartController::getCart()` | One query per cart item (`Product::find()` in loop) | Preloaded all products via `Product::whereIn('id', $ids)->keyBy('id')` |
| `Api/WishlistController::getWishlist()` | `$product->reviews()->avg()` + `->count()` per item | Replaced with `withAvg()` and `withCount()` on the query |
| `Api/OrderController::store()` | `Product::find()` per item inside DB transaction | Preload via `Product::whereIn('id', ...)->keyBy('id')` |

### Moderate Fixes

| File | Issue | Fix |
|---|---|---|
| `Admin/AdminController::index()` | `Order::limit(10)->get()` without `->with()` | Added `->with('user:id,name')` |
| `ShopController::checkoutSuccess()` | `Order::with('items')` missing `items.product` | Changed to `->with('items.product')` |
| `Category::getPathAttribute()` | Lazy-loaded parent in `while` loop | Added `loadAncestors()` method + fallback to lazy load when ancestors not eager loaded |

---

## Service Extraction

### `app/Services/OrderService.php` (New)

Extracted from `Api/OrderController@store`:
- `placeOrder()` - order creation with validation, stock management, cart cleanup, notifications
- `saveShippingAddress()` - address persistence for authenticated users
- N+1 fix: preloads all products in one query instead of per-item

### `app/Services/PaymentService.php` (Refactored)

Extracted from `PaymentController`:
- `processCheckout()` - payment session creation with pending payment management
- `retryPayment()` - payment retry with attempt tracking
- `confirmPayment()` - payment confirmation + notification
- `handleCheckoutComplete()` / `handlePaymentFailed()` - webhook handlers
- `isOrderAlreadyPaid()` / `isOrderExpired()` - payment status checks
- `validateItemStock()` - stock validation extracted from controller
- `handlePayPalCapture()` / `handlePayPalCancel()` - PayPal flow handlers

---

## Other Improvements

### `app/Models/Order.php`
- `generateOrderNumber()`: Replaced `uniqid()` with `Str::random(8)` + uniqueness check loop to guarantee no collisions

### `app/Models/Category.php`
- Added `loadAncestors()` method to eager-load parent chain for the `path` accessor

### `app/Http/Controllers/Admin/OrderController.php`
- `update()`: Replaced inline stock restoration on cancel with `$order->cancel()` (which already existed on the model)

### `app/Http/Controllers/Admin/SettingsController.php`
- `update()`: Simplified to loop `$validated` keys through `Setting::set()` instead of individual calls

### Pre-existing Test Fix
- `tests/Feature/Admin/ActivityLogAdminRecordingTest.php`: Fixed Unicode `→` to ASCII `->` in 2 assertions to match actual controller output

---

## Files Changed

```
Created:  21 FormRequest classes + 1 Service (OrderService)
Modified: 22 Controller files + 4 Model/Service files + 1 Test file
```

## Verification

All 181 existing tests pass with 444 assertions.
