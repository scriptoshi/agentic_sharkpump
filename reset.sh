#!/bin/bash
set -e
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear
php artisan queue:flush
php artisan optimize
php artisan config:cache
chgrp -R www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
