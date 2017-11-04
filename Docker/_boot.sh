#!/bin/bash
printenv > /var/www/app/.env

php /var/www/app/artisan config:clear > /dev/null 2>&1 || true
php /var/www/app/artisan migrate --force > /dev/null 2>&1 || true
touch /var/www/app/storage/logs/laravel.log
chown -R ambientum:ambientum /var/www/app/storage > /dev/null 2>&1 || true
php /var/www/app/artisan config:cache > /dev/null 2>&1 || true
php /var/www/app/artisan route:cache > /dev/null 2>&1 || true
php /var/www/app/artisan optimize --force > /dev/null 2>&1 || true

# Starts FPM
nohup /usr/sbin/php-fpm -y /etc/php/7.1/fpm/php-fpm.conf -F -O 2>&1 &

# Starts nginx!
nginx