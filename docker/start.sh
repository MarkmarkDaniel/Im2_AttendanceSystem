#!/bin/sh
set -e

# Render tells the container which port to listen on
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Fall back to the address Render assigned when APP_URL is not set
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:${PORT}}}"

php artisan migrate --force

# First deploy only: an empty users table means nothing has been seeded yet
USER_COUNT="$(php artisan tinker --execute='echo \App\Models\User::count();' | tr -dc '0-9')"
if [ "${USER_COUNT}" = "0" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# The commands above ran as root; Apache needs to write here
chown -R www-data:www-data storage bootstrap/cache public/uploads

exec apache2-foreground
