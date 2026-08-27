#!/bin/bash
set -e

PORT="${PORT:-80}"

# Cleanly set Apache to listen on dynamic Railway PORT
echo "Listen ${PORT}" > /etc/apache2/ports.conf

# Reconfigure default virtual host to listen on PORT
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:${PORT}>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Auto initialize database if script exists
if [ -f "/var/www/html/init_db.php" ]; then
    echo "[entrypoint] Running database auto-init check..."
    php /var/www/html/init_db.php || true
fi

echo "[entrypoint] Starting Apache on port ${PORT}..."
exec "$@"
