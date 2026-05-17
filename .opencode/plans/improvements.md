# Future Improvements

## High Impact
- [x] **Payment Integration** — Stripe/PayPal payment processing with webhook handling, retry logic, stock management, TDD (27 backend tests, 10 frontend tests passing)
- [x] **Product Reviews & Ratings** — Customers can leave reviews (pending admin approval), average rating shown on product cards and detail page, admin panel for approve/reject, admin notifications on submission
- [x] **Wishlist** — Save-for-later / favorites for logged-in users
- [x] **Activity Logs Page** — Admin activity log viewer with filtering by type and date range
- [x] **Coupon Admin CRUD** — Full admin management for coupons (create, edit, list, delete) with type, value, limits, dates

## Medium Impact
- [x] **Live Search / Autocomplete** — Shop page now supports live search and autocomplete suggestions
- [x] **Admin Charts** — Add admin dashboard charts with server-side aggregated metrics via `vue3-apexcharts` for revenue and orders trends
- [ ] **Invoice PDF Generation** — No downloadable order invoices for customers/admins
- [x] **Email Notifications** — Mail is on `log` driver; real transactional emails would be valuable
- [x] **Shipping & Tax Config** — Both hardcoded ($15 flat, 10% tax); admin-configurable would be better
- [x] **Rate Limiting** — API endpoints, login, register, password reset, and checkout now throttled via Laravel's built-in `RateLimiter`
- [x] **User Activity Logs** — Track admin actions (product edits, order updates, user changes) with an audit log for accountability
- [x] **Categories Edit UI** — Edit categories inline on the admin categories page (backend existed, frontend was missing)
- [x] **Product Detail Page** — Admin product detail/show view separate from edit
- [x] **User Detail Page** — Admin user detail/show view with orders, addresses, reviews

## Nice to Have
- [ ] **SEO** — No meta tags, sitemap, or structured data (JSON-LD)
- [ ] **Bulk Operations** — No bulk product edit or order status change
- [ ] **Export/Import** — No CSV/Excel for products or orders
- [x] **Contact Page** — Contact form with name/email/message, stored in DB, linked from footer
- [x] **Newsletter** — Subscribe/unsubscribe API endpoint, wired to footer form with success/error feedback
- [ ] **Frontend Tests** — Only empty directories exist for Vitest
- [ ] **Multi-language** — No i18n support
- [ ] **Public API** — Sanctum installed but no token auth for external clients
