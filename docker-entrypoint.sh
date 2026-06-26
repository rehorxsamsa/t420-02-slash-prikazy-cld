#!/bin/sh
set -e

# SQLite potřebuje zapisovat do data/ (vytvoří tam tasks.sqlite).
# Po namountování volume nastavíme vlastníka na uživatele Apache.
mkdir -p /var/www/html/data
chown -R www-data:www-data /var/www/html/data

# Předáme řízení původnímu příkazu (apache2-foreground).
exec "$@"
