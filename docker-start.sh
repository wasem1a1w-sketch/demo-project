#!/bin/sh

set -e

# Detect Railway and set proper APP_URL
if [ -n "$RAILWAY_STATIC_URL" ]; then
  url="$RAILWAY_STATIC_URL"
  url="${url#http://}"
  url="${url#https://}"
  url="${url%%/*}"
  export APP_URL="https://$url"
  export ASSET_URL="https://$url"
  sed -i "s|APP_URL=.*|APP_URL=https://$url|" /app/.env
elif [ -n "$RAILWAY_PUBLIC_DOMAIN" ]; then
  url="$RAILWAY_PUBLIC_DOMAIN"
  url="${url#http://}"
  url="${url#https://}"
  url="${url%%/*}"
  export APP_URL="https://$url"
  export ASSET_URL="https://$url"
  sed -i "s|APP_URL=.*|APP_URL=https://$url|" /app/.env
fi

# Detect Railway MySQL database
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

# Start Reverb in single-container mode (not when using docker-compose with separate reverb service)
if [ "$REVERB_SERVER_HOST" = "0.0.0.0" ] || [ "$REVERB_SERVER_HOST" = "127.0.0.1" ] || [ -z "$REVERB_SERVER_HOST" ]; then
  echo "Starting Reverb WebSocket server..."
  export REVERB_SERVER_HOST=0.0.0.0
  php artisan reverb:start --host=0.0.0.0 --port=8081 &
  echo "Reverb started on port 8081 (PID: $!)"
fi

echo "Checking for Vite manifest..."
if [ ! -f /app/public/build/manifest.json ]; then
  echo "Manifest not found, rebuilding assets..."
  npm run build || true
fi

php artisan config:clear || true
php artisan view:clear || true
php artisan migrate --force || true
php artisan db:seed --force || true
php artisan view:cache || true

chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public

exec su -s /bin/sh www-data -c "/usr/local/bin/frankenphp run --config /etc/caddy/Caddyfile"
