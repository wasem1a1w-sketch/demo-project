# Test Coverage

## Summary

| Test Suite | Tests | Assertions | Runner |
|---|---|---|---|
| PHPUnit (Feature) | 239 | 597 | `php vendor/bin/phpunit` |
| Vitest (Frontend) | 29 | — | `npx vitest run tests/frontend/` |
| **Total** | **268** | | |

## New Tests Added

### Integration: Stripe + PayPal (4 files, 9 tests)

| File | Tests |
|---|---|
| `tests/Feature/StripeIntegrationTest.php` | 5 — mock fallback creates `cs_test_` session, mock retrieve returns paid, full session→confirm→paid flow, webhook signature rejection, webhook accepted with signature |
| `tests/Feature/PayPalIntegrationTest.php` | 4 — mock fallback creates `PAYPALID_` session, mock capture returns COMPLETED, full session→capture→paid flow, cancel→expired flow |

### UI: Checkout + Admin Product Add (2 files, 19 tests)

| File | Tests |
|---|---|
| `tests/frontend/CheckoutIndex.test.js` | 6 — empty cart state, form renders with items, Stripe default selection, Stripe full flow with redirect, empty cart error, failed payment error |
| `tests/frontend/AdminProductCreate.test.js` | 13 — all form fields rendered, category dropdown, default checkbox states, toggles, main image upload preview, >3MB error, remove main image, gallery upload up to 4, max 4 error, remove gallery image, FormData submit, cancel navigation |

### Acceptance: Full Flows (2 files, 5 tests)

| File | Tests |
|---|---|
| `tests/Feature/Acceptance/CheckoutAcceptanceTest.php` | 4 — Stripe full checkout (order→session→confirm→paid+stock+notification), PayPal full checkout, Offline checkout, Failed payment→stock restore→order cancel |
| `tests/Feature/Acceptance/AdminProductAcceptanceTest.php` | 1 — product lifecycle (create→view→edit→update→delete) with activity logging |

### Feature: Admin CRUD (6 files, 28 tests)

| File | Tests |
|---|---|
| `tests/Feature/Admin/ProductAdminTest.php` | 8 — list, create form, create, view, edit form, update, delete, activity logging, non-admin blocked |
| `tests/Feature/Admin/OrderAdminTest.php` | 4 — list, view, update status, non-admin blocked |
| `tests/Feature/Admin/UserAdminTest.php` | 6 — list, create, view, update, delete, non-admin blocked |
| `tests/Feature/Admin/ReviewAdminTest.php` | 5 — list, approve, reject, delete, non-admin blocked |
| `tests/Feature/Admin/SettingsAdminTest.php` | 3 — view, update, non-admin blocked |
| `tests/Feature/Admin/DashboardAdminTest.php` | 2 — access, non-admin blocked |

### Feature: General (4 files, 15 tests)

| File | Tests |
|---|---|
| `tests/Feature/OrderTest.php` | 3 — place order, order number generation, view own order |
| `tests/Feature/AddressTest.php` | 4 — list, create, update, delete addresses |
| `tests/Feature/ReviewTest.php` | 4 — create, list approved (public), update own, delete own |
| `tests/Feature/AuthTest.php` | 4 — register, login, logout, auth guard |

## Model Changes

- `app/Models/Address.php` — added `HasFactory` trait
- `app/Models/ProductReview.php` — added `HasFactory` trait
- `database/factories/ProductReviewFactory.php` — created

## Test Patterns Used

- **PHPUnit:** `RefreshDatabase`, `Factories`, `actingAs()`, `assertDatabaseHas`, `assertJsonStructure`, `Notification::fake`
- **Vitest:** `mount()`, `vi.mock()`, `createPinia()`, `setActivePinia()`, `globalThis.route` mock
- **Stripe/PayPal mock mode:** Tests validate the fallback behavior when no API keys are set
- **Admin RBAC:** Admin tests set up Spatie roles/permissions in `setUp()`
