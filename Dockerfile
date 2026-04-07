FROM php:8.2-apache

# تثبيت PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# نسخ المشروع
COPY . /var/www/html/

EXPOSE 80
