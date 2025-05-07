#!/bin/bash
set -e

# Default values for environment variables
SITE_DIR=${SITE_DIR:-"aibotsfortelegram.com"}
REPO_NAME=${REPO_NAME:-"aibotsfortelegram"}
WWW_DIR=${WWW_DIR:-"/var/www"}

echo "Deployment started ..."
echo "Deploying to site directory: $SITE_DIR"

echo "# Checkout main version of the app"
cd $WWW_DIR/$REPO_NAME && git checkout main

echo "# Install composer dependencies"
composer config --no-interaction allow-plugins.composer/installers true
composer update --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "# Install & Compile npm assets"
npm i && npm run build
echo "Build complete!"

echo "# Run fresh database migrations"
php artisan migrate --force
echo "# seeding the database"
php artisan db:seed

echo "Swapping Folders"
rm -rf ${WWW_DIR}/${REPO_NAME}_back
mv ${WWW_DIR}/${SITE_DIR} ${WWW_DIR}/${REPO_NAME}_back
mv ${WWW_DIR}/${REPO_NAME} ${WWW_DIR}/${SITE_DIR}

echo "# Handling storage directory"
[ -d "${WWW_DIR}/${REPO_NAME}_back/storage" ] && \
rm -r ${WWW_DIR}/${SITE_DIR}/storage
[ -d "${WWW_DIR}/${REPO_NAME}_back/storage" ] && \
    mv ${WWW_DIR}/${REPO_NAME}_back/storage ${WWW_DIR}/${SITE_DIR}/storage

# Create upload directory
mkdir -p ${WWW_DIR}/${SITE_DIR}/storage/app/public/uploads/
rm -rf ${WWW_DIR}/${SITE_DIR}/storage/app/filepond || :

echo "# Run post installation setup"
cd ${WWW_DIR}/${SITE_DIR} && {
    php artisan clear-compiled
    php artisan optimize
    php artisan storage:link
       # Create installation marker
    mkdir -p storage/app/public
    echo "{\"installed_at\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\", \"version\": \"$(php artisan --version | grep -oP 'Laravel Framework \K[\d.]+')\", \"app_version\": \"$(grep -oP "(?<='version' => ').*(?=')" config/app.php || echo '1.0.0')\"}" > storage/app/public/installed
    chgrp -R www-data storage bootstrap/cache
    chmod -R ug+rwx storage bootstrap/cache
}

echo "$SITE_DIR Deployment Finished"