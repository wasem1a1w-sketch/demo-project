# Laravel E-Commerce

A full-stack e-commerce application built with Laravel, Inertia, and Vue. Features product management, shopping cart, checkout flow, order tracking, and an admin dashboard.

## Live Demo

[demo-project-production-8ced.up.railway.app](https://demo-project-production-8ced.up.railway.app)

---

## Screenshots

| Homepage | Shop | Cart |
|----------|------|------|
| ![Homepage](screenshots/homepage.png) | ![Shop](screenshots/shop.png) | ![Cart](screenshots/cart.png) |

| Checkout | Dashboard | Products |
|----------|-----------|----------|
| ![Checkout](screenshots/checkout.png) | ![Dashboard](screenshots/dashboard.png) | ![Products](screenshots/products.png) |

| Login | Register | Addresses |
|-------|----------|-----------|
| ![Login](screenshots/login.png) | ![Register](screenshots/register.png) | ![Addresses](screenshots/addresses.png) |

| Orders (User) | Order Detail |
|---------------|--------------|
| ![Orders](screenshots/orders.png) | ![Order](screenshots/order.png) |


---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13 |
| Frontend | Vue 3 (Composition API) |
| Bridge | Inertia.js |
| Database | MySQL |
| Server | FrankenPHP (Caddy) |
| Containerization | Docker |
| Hosting | Railway |
| Build | Vite |

---

## Features

- **Authentication** — user registration, login, logout
- **Product Management** — create, edit, delete products with image uploads (main image + gallery, max 5)
- **Category Management** — organize products by category
- **Shopping Cart** — add/remove items, apply coupons, real-time totals
- **Checkout** — saved addresses, shipping info, payment method selection
- **Order Management** — place orders, view order history, admin status updates
- **Stock Tracking** — automatic stock decrement on order, restore on cancellation
- **Admin Dashboard** — manage products, orders, users, categories
- **Image Gallery** — separate main product image and gallery uploads, 3MB limit per image
- **Responsive Design** — mobile-friendly layout via Tailwind CSS

---

## Architecture

This project uses the **Laravel + Inertia + Vue** stack:

```
Browser → Laravel Route → Controller → Inertia → Vue Page
```

- **Laravel** handles routing, database, authentication, validation, and business logic
- **Inertia** acts as the bridge — first page load is a full HTML render; subsequent navigations send an `X-Inertia` header and Laravel returns only JSON (component name + props)
- **Vue** receives the component name and props, then renders the UI dynamically without full page reloads

For mutations (form submissions), Laravel returns a redirect. Inertia's client follows the redirect with a GET request, fetching fresh props from the server — keeping the server as the single source of truth.

See [`inertia.md`](inertia.md) for a detailed explanation of the data flow.

---

## Installation

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 22+
- MySQL 8.0+

### Setup

```bash
# Clone the repository
git clone https://github.com/wasem1a1w-sketch/demo-project.git
cd demo-project

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Create a MySQL database named 'myproject' (or update DB_DATABASE in .env)

# Run migrations and seeders
php artisan migrate --seed

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

The app will be available at `http://localhost:8000`. Default admin credentials:

- **Email:** `admin@admin.com`
- **Password:** `password`

---

## Docker Deployment

A `Dockerfile` and `Caddyfile` are included for containerized deployment:

```bash
# Build the image
docker build -t laravel-ecommerce .

# Run the container
docker run -p 8080:8080 laravel-ecommerce
```

The Docker setup uses **FrankenPHP** — a modern PHP application server built on Caddy. It handles PHP execution, static file serving, and TLS automatically. The `Caddyfile` configures the server to listen on port 8080 (configurable via `$PORT` environment variable for Railway).

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Application environment | `production` |
| `APP_KEY` | Laravel app key | auto-generated |
| `APP_URL` | Application URL | — |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_DATABASE` | Database name | `myproject` |

---

## Author

**Waseem Idries**
- GitHub: [@wasem1a1w-sketch](https://github.com/wasem1a1w-sketch/demo-project)
