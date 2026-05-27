#!/bin/sh

set -e

# 1. Detect Railway and set proper APP_URL
if [ -n "$RAILWAY_STATIC_URL" ]; then
  url="$RAILWAY_STATIC_URL"
  url="${url#http://}"
  url="${url#https://}"
  url="${url%%/*}"
  export APP_URL="https://$url"
  export ASSET_URL="https://$url"
  export REVERB_HOST="$url"
  export REVERB_PORT=443
  export REVERB_SCHEME=https
  export VITE_REVERB_HOST="$url"
  export VITE_REVERB_PORT=443
  export VITE_REVERB_SCHEME=https
  sed -i "s|APP_URL=.*|APP_URL=https://$url|" /app/.env
  sed -i "s|REVERB_HOST=.*|REVERB_HOST=$url|; s|REVERB_PORT=.*|REVERB_PORT=443|; s|REVERB_SCHEME=.*|REVERB_SCHEME=https|" /app/.env
elif [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
  url="$RAILWAY_PUBLIC_DOMAIN"
  url="${url#http://}"
  url="${url#https://}"
  url="${url%%/*}"
  export APP_URL="https://$url"
  export ASSET_URL="https://$url"
  export REVERB_HOST="$url"
  export REVERB_PORT=443
  export REVERB_SCHEME=https
  export VITE_REVERB_HOST="$url"
  export VITE_REVERB_PORT=443
  export VITE_REVERB_SCHEME=https
  sed -i "s|APP_URL=.*|APP_URL=https://$url|" /app/.env
  sed -i "s|REVERB_HOST=.*|REVERB_HOST=$url|; s|REVERB_PORT=.*|REVERB_PORT=443|; s|REVERB_SCHEME=.*|REVERB_SCHEME=https|" /app/.env
fi

# 2. Detect Railway MySQL database
MYSQL_URL="${MYSQL_URL:-${DATABASE_URL:-}}"
if [ -n "$MYSQL_URL" ]; then
  tmp="${MYSQL_URL#mysql://}"
  userpass="${tmp%%@*}"
  hostportdb="${tmp#*@}"
  db_user="${userpass%%:*}"
  db_pass="${userpass#*:}"
  db_hostpart="${hostportdb%%/*}"
  db_name="${hostportdb#*/}"
  db_host="${db_hostpart%%:*}"
  db_port="${db_hostpart#*:}"
  [ "$db_hostpart" = "$db_port" ] && db_port="3306"

  export DB_CONNECTION="mysql"
  export DB_HOST="$db_host"
  export DB_PORT="$db_port"
  export DB_DATABASE="$db_name"
  export DB_USERNAME="$db_user"
  export DB_PASSWORD="$db_pass"

  sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" /app/.env
  sed -i "/^DB_HOST=/d; /^DB_PORT=/d; /^DB_DATABASE=/d; /^DB_USERNAME=/d; /^DB_PASSWORD=/d" /app/.env
  {
    echo "DB_HOST=$db_host"
    echo "DB_PORT=$db_port"
    echo "DB_DATABASE=$db_name"
    echo "DB_USERNAME=$db_user"
    echo "DB_PASSWORD=$db_pass"
  } >> /app/.env
fi

# 3. Generate application encryption key if not already set
if [ -z "$APP_KEY" ] || grep -q '^APP_KEY=$' /app/.env 2>/dev/null; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# 4. Check for Vite manifest
echo "Checking for Vite manifest..."
if [ ! -f /app/public/build/manifest.json ]; then
  echo "Manifest not found, rebuilding assets..."
  npm run build || true
fi

# 5. Clear old accidental build-time bootstrap caches
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# 6. Safely run migrations while environment variables are completely fresh
echo "Running database migrations..."
php artisan migrate --force || true
php artisan db:seed --force || true

# 7. Cache optimizations for production runtime speed
echo "Caching configuration and routes..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 8. Spin up Reverb in the background right before the main engine starts
echo "Starting Reverb WebSocket server on port 8081..."
export REVERB_SERVER_HOST=0.0.0.0
php artisan reverb:start --host=0.0.0.0 --port=8081 &

# 9. Sanity check permissions for runtime uploads and caches
chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public

# 10. Hand off execution control to FrankenPHP
echo "Starting FrankenPHP web server..."
exec su -s /bin/sh www-data -c "/usr/local/bin/frankenphp run --config /etc/caddy/Caddyfile"