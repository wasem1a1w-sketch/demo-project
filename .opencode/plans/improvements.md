# Future Improvements

## High Impact
- [ ] **Payment Integration** — Stripe/PayPal UI selects a method but nothing processes. All orders end up `payment_status = pending`.
- [ ] **Product Reviews & Ratings** — No way for customers to leave reviews on product pages
- [ ] **Wishlist** — No save-for-later / favorites for logged-in users

## Medium Impact
- [ ] **Live Search / Autocomplete** — Search works but no instant dropdown suggestions
- [ ] **Admin Charts** — Dashboard shows raw numbers but no visual charts (bar/line for revenue, orders)
- [ ] **Invoice PDF Generation** — No downloadable order invoices for customers/admins
- [ ] **Email Notifications** — Mail is on `log` driver; real transactional emails would be valuable
- [ ] **Product Variants** — No size/color/options per product
- [ ] **Shipping & Tax Config** — Both hardcoded ($15 flat, 10% tax); admin-configurable would be better

## Nice to Have
- [ ] **SEO** — No meta tags, sitemap, or structured data (JSON-LD)
- [ ] **Bulk Operations** — No bulk product edit or order status change
- [ ] **Export/Import** — No CSV/Excel for products or orders
- [ ] **Contact Page** — Footer links to Contact/Shipping exist but routes don't
- [ ] **Newsletter** — Subscribe button in footer does nothing
- [ ] **Frontend Tests** — Only empty directories exist for Vitest
- [ ] **Multi-language** — No i18n support
- [ ] **Public API** — Sanctum installed but no token auth for external clients
