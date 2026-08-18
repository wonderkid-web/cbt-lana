FROM php:8.2-apache

# Ekstensi PHP yang dibutuhkan aplikasi:
# - mysqli    : koneksi database (config/database.php)
# - zip       : export XLSX (admin/backup.php, admin/laporan_full.php)
# - mbstring  : sudah bawaan php:8.2, dipakai mb_substr/mb_strlen
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        default-mysql-client \
    && docker-php-ext-install -j"$(nproc)" mysqli zip \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Konfigurasi PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/99-cbt.ini

# Konfigurasi Apache (halaman awal + proteksi folder sensitif)
COPY docker/zz-cbt.conf /etc/apache2/conf-available/zz-cbt.conf
RUN a2enconf zz-cbt

WORKDIR /var/www/html
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
