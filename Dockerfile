# PHP 8.3 + Apache pro Todo aplikaci (čisté PHP OOP, SQLite přes PDO).
FROM php:8.3-apache

# pdo_sqlite pro PDO připojení k SQLite databázi (src/Core/Database.php).
# libsqlite3-dev poskytuje hlavičky potřebné pro kompilaci rozšíření.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Pretty URL přes .htaccess (RewriteRule -> public/index.php).
RUN a2enmod rewrite

# Front controller žije v public/, proto přesměrujeme DocumentRoot tam
# a povolíme .htaccess (AllowOverride All).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Entrypoint zajistí zapisovatelnost data/ (SQLite) i po namountování volume.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
