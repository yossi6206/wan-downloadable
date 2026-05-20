FROM php:8.2-apache

RUN a2enmod rewrite && \
    sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-enabled/000-default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
