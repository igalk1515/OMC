FROM php:8.2-apache

COPY . /var/www/html/

WORKDIR /var/www/html/

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql zip

# Copy composer.lock and composer.json
COPY composer.lock composer.json /var/www/html/

# Install dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install

# Copy source code to working directory
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
