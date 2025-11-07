FROM php:8.3-apache

# Aktiviere Apache mod_rewrite und andere wichtige Module
RUN a2enmod rewrite headers

# Installiere zusätzliche PHP-Extensions falls benötigt
RUN docker-php-ext-install pdo pdo_mysql

# Composer installieren
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache DocumentRoot auf /var/www/html/public setzen
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Erlaube .htaccess Overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# PHP-Einstellungen für Entwicklung
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN echo "display_errors = On" >> "$PHP_INI_DIR/php.ini"
RUN echo "error_reporting = E_ALL" >> "$PHP_INI_DIR/php.ini"
RUN echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini"
RUN echo "post_max_size = 10M" >> "$PHP_INI_DIR/php.ini"

# Arbeitsverzeichnis
WORKDIR /var/www/html

# Port exposieren
EXPOSE 80
