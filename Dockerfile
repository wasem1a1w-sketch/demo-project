FROM docker.io/dunglas/frankenphp:php8.3.30-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    xz-utils \
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
COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/local/bin/composer

# Install Node.js 22.x via official binary (avoids NodeSource repo issues)
ARG NODE_VERSION=22.22.2
RUN curl -fsSL https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-x64.tar.xz -o /tmp/node.tar.xz \
    && tar -xJf /tmp/node.tar.xz -C /usr/local --strip-components=1 \
    && rm /tmp/node.tar.xz \
    && node --version \
    && npm --version

WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copy package files and install Node dependencies (including dev for build)
COPY package.json ./
RUN npm install

# Copy application code
COPY . .

# Copy Caddyfile to expected location
COPY Caddyfile /etc/caddy/Caddyfile

# Run Laravel optimizations and build assets
RUN cp .env.example .env \
    && sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env \
    && sed -i '/DB_HOST/d;/DB_PORT/d;/DB_DATABASE/d;/DB_USERNAME/d;/DB_PASSWORD/d' .env \
    && touch database/database.sqlite \
    && php artisan key:generate --force \
    && php artisan migrate --force \
    && npm run build \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

# Expose port (FrankenPHP will use PORT env var)
EXPOSE 8080

# Create startup script
RUN echo '#!/bin/sh\nphp artisan migrate --force || true\nexec /usr/local/bin/frankenphp run --config /etc/caddy/Caddyfile' > /app/start.sh \
    && chmod +x /app/start.sh \
    && mkdir -p /data/caddy && chown -R www-data:www-data /data

# Use www-data user for FrankenPHP
USER www-data

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

CMD ["/app/start.sh"]
