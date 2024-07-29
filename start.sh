#!/bin/bash

set -e

#echo "Composer install..."
(cd /var/www/html && composer install --no-dev --no-interaction --no-progress --optimize-autoloader --classmap-authoritative)
echo 'Clearing previous cache...'
(cd /var/www/html && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear)
echo "Caching configuration..."
(cd /var/www/html && php artisan config:cache && php artisan route:cache && php artisan view:cache)
echo "Starting Laravel application..."

# if supervisord is ready, run service
SUPERFILE=/etc/supervisord.conf
if [ -e $SUPERFILE ]; then
  /usr/bin/supervisord -n -c /etc/supervisord.conf
fi
