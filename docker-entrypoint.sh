#!/bin/sh
set -e

# Match www-data UID and GID to the host owner of /var/www/html
TARGET_UID=$(stat -c '%u' /var/www/html)
TARGET_GID=$(stat -c '%g' /var/www/html)

if [ "$TARGET_UID" -ne 0 ]; then
    usermod -o -u "$TARGET_UID" www-data >/dev/null 2>&1 || true
    groupmod -o -g "$TARGET_GID" www-data >/dev/null 2>&1 || true
    chown -R www-data:www-data /var/lock/apache2 /var/log/apache2 /var/run/apache2 >/dev/null 2>&1 || true
fi

# Ensure bl-content directory exists and has full permissions
mkdir -p /var/www/html/bl-content
chmod -R 777 /var/www/html/bl-content 2>/dev/null || true

# Ensure .htaccess exists and is writable
if [ -f /var/www/html/.htaccess ]; then
    chmod 666 /var/www/html/.htaccess 2>/dev/null || true
fi

exec "$@"
