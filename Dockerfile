FROM dunglas/frankenphp:php8.3.30-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        gd \
        zip \
        exif \
        pcntl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install Node.js 22.x
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy package files and install Node dependencies
COPY package.json package-lock.json ./
RUN npm ci --omit=dev

# Copy application code
COPY . .

# Run Laravel optimizations and build assets
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && npm run build \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose port (FrankenPHP will use PORT env var)
EXPOSE 8080

# Create startup script
RUN echo '#!/bin/sh\nphp artisan migrate --force\nexec /usr/local/bin/frankenphp run --config /etc/caddy/Caddyfile' > /app/start.sh \
    && chmod +x /app/start.sh

# Use www-data user for FrankenPHP
USER www-data

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

CMD ["/app/start.sh"]
