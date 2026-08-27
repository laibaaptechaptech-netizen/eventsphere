#!/bin/bash
set -e

PORT="${PORT:-80}"

# Configure Apache to listen on dynamic Railway PORT
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Auto initialize database if script exists
if [ -f "/var/www/html/init_db.php" ]; then
    echo "[entrypoint] Running database auto-init check..."
    php /var/www/html/init_db.php || true
fi

echo "[entrypoint] Starting Apache on port ${PORT}..."
exec "$@"
