#!/bin/bash

# Get current script path and go up one level
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "Setting up permissions for directory: $PROJECT_DIR"

# Check if project directory exists
if [ ! -d "$PROJECT_DIR" ]; then
    echo "Warning: Project directory $PROJECT_DIR does not exist."
    exit 1
fi

# Function to set permissions with error handling
set_permissions() {
    local path="$1"
    local perms="$2"
    local owner="$3"
    
    if [ ! -e "$path" ]; then
        mkdir -p "$path"
        echo "Created directory: $path"
    fi
    
    chmod $perms "$path"
    chown $owner "$path"
    echo "Set permissions $perms and owner $owner for: $path"
}

# Main directories with 777 permissions
DIRS_777=(
    ""
    "app"
    "bootstrap/cache"
    "config"
    "database"
    "public"
    "resources"
    "routes"
    "storage"
    "storage/app"
    "storage/app/public"
    "storage/framework"
    "storage/framework/cache"
    "storage/framework/cache/data"
    "storage/framework/sessions" 
    "storage/framework/testing"
    "storage/framework/views"
    "storage/logs"
    "tests"
    "vendor"
)

# Directories with 755 permissions
DIRS_755=(
    "bootstrap"
    "database/factories"
    "database/migrations"
    "database/seeders"
    "resources/css"
    "resources/js"
    "resources/lang"
    "resources/views"
    "tests/Feature"
    "tests/Unit"
)

# Set permissions for directories
for dir in "${DIRS_777[@]}"; do
    set_permissions "$PROJECT_DIR/$dir" 777 "www-data:www-data"
done

for dir in "${DIRS_755[@]}"; do
    set_permissions "$PROJECT_DIR/$dir" 755 "www-data:www-data"
done

# Special cases
if [ ! -f "$PROJECT_DIR/.env" ] && [ -f "$PROJECT_DIR/.env.example" ]; then
    cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
    chmod 777 "$PROJECT_DIR/.env"
    chown www-data:www-data "$PROJECT_DIR/.env"
    echo "Created .env file from .env.example"
fi

# Create and setup log file
touch "$PROJECT_DIR/storage/logs/laravel.log"
chmod 777 "$PROJECT_DIR/storage/logs/laravel.log"
chown www-data:www-data "$PROJECT_DIR/storage/logs/laravel.log"

# Setup permissions for vendor/composer contents
if [ -d "$PROJECT_DIR/vendor/composer" ]; then
    chmod -R 777 $PROJECT_DIR/vendor/composer
    chown -R www-data:www-data $PROJECT_DIR/vendor/composer
fi

# Recursively set storage permissions
chmod -R 777 "$PROJECT_DIR/storage"
chown -R www-data:www-data "$PROJECT_DIR/storage"

echo "Permissions setup completed"