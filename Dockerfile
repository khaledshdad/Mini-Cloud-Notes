FROM php:8.2-apache

# 1. تثبيت الاعتمادات اللازمة لنظام التشغيل أولاً
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# 2. نسخ ملفات المشروع
COPY . /var/www/html/

# 3. إعطاء الصلاحيات اللازمة
RUN chown -R www-data:www-data /var/www/html
