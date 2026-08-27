#!/bin/bash
# docker-entrypoint.sh
# Railway assigns a dynamic PORT via env var.
# Apache must be reconfigured to listen on that port before starting.

set -e

PORT=${PORT:-80}

# Patch Apache to listen on the Railway-assigned port
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/"           /etc/apache2/sites-available/000-default.conf

echo "[entrypoint] Apache will listen on port ${PORT}"
exec "$@"
