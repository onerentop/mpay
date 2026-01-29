#!/bin/sh
set -e

# Create env directory if not exists
mkdir -p /var/www/html/env

# If .env exists in persistent volume, link it
if [ -f /var/www/html/env/.env ]; then
    echo "Found existing .env in persistent volume, linking..."
    ln -sf /var/www/html/env/.env /var/www/html/.env
else
    echo "No .env found in persistent volume"
    # If .env exists in app directory, copy to persistent volume
    if [ -f /var/www/html/.env ]; then
        echo "Copying existing .env to persistent volume..."
        cp /var/www/html/.env /var/www/html/env/.env
        ln -sf /var/www/html/env/.env /var/www/html/.env
    fi
fi

# Ensure proper permissions
chmod -R 777 /var/www/html/runtime 2>/dev/null || true
chmod -R 777 /var/www/html/public/files 2>/dev/null || true
chmod -R 777 /var/www/html/config/extend 2>/dev/null || true
chmod -R 777 /var/www/html/extend 2>/dev/null || true
chmod -R 777 /var/www/html/env 2>/dev/null || true

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
