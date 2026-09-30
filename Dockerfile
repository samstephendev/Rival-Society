FROM php:8.2-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader

# Honour .htaccess and listen on Render's port
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
ENV PORT=10000
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html
EXPOSE 10000