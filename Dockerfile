# Stage 1: Build the Frontend Assets (Vue/Tailwind)
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Build the PHP Backend & Final Image
FROM php:8.3-fpm-alpine

# Install system dependencies, Nginx, and PHP extensions needed by Laravel
RUN apk add --no-cache \
    nginx \
    zip \
    unzip \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy the Laravel files
COPY . .

# Copy the compiled Vue/Tailwind assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set up Nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Set up Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Fix permissions so Laravel can write to logs and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data /var/log/supervisor

# Expose the port
EXPOSE 8000

# Entrypoint script to handle startup tasks
RUN printf "#!/bin/sh\n\
php artisan storage:link\n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf\n" > /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
