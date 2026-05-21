# Changelog - Controller Refactoring

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
