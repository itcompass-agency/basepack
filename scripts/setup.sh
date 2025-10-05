#!/bin/sh

setup_permissions() {
    echo "Setting up permissions..."
    
    # Execute prepare.sh script if it exists
    if [ -f ./scripts/prepare.sh ]; then
        chmod +x ./scripts/prepare.sh
        ./scripts/prepare.sh
    fi
    
    # Additional permissions setup inside container
    docker-compose exec -T laravel chown -R www-data:www-data /var/www/html
    docker-compose exec -T laravel chmod -R 777 /var/www/html/storage
    docker-compose exec -T laravel chmod -R 777 /var/www/html/bootstrap/cache
}

recreate_storage_link() {
    echo "Recreating storage link..."
    docker-compose exec -T laravel rm -f /var/www/html/public/storage
    docker-compose exec -T laravel php artisan storage:link
}

setup_dev() {
    rm -rf vendor composer.lock node_modules package-lock.json

    make build
    make start
    
    setup_permissions
    
    make composer-install
    make key-generate
    make cache-clear
    
    recreate_storage_link
    make migrate
    
    make npm-install
    make npm-run-dev
    
    # Final permissions check
    setup_permissions
    make restart
}

setup_prod() {
    rm -rf vendor composer.lock node_modules package-lock.json

    make build-prod
    make start-prod
    
    setup_permissions
    
    make composer-install-no-dev
    make key-generate
    make cache-clear
    
    recreate_storage_link
    make migrate
    
    make npm-install
    make npm-run-prod
    
    # Final permissions check
    setup_permissions
    make restart-prod
}

case "$1" in
    "prod")
        setup_prod
        ;;
    "dev")
        setup_dev
        ;;
    *)
        echo "Please specify environment: prod or dev"
        exit 1
        ;;
esac