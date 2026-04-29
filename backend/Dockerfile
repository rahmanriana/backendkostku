# syntax=docker/dockerfile:1

FROM php:8.2-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash \
    curl \
    git \
    nginx \
    supervisor \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev

# PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    mbstring \
    zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app
COPY . /var/www/html

# Install PHP deps (production)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Nginx + supervisor config
COPY docker/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 10000

CMD ["/start.sh"]
