# =============================================================================
# Dockerfile for IPMS Auth Service
# =============================================================================
# This Dockerfile builds a PHP 8.2 FPM container with all necessary extensions
# for the Laravel Auth service including JWT authentication and Redis support.
#
# Build context: services/auth-service/
# =============================================================================

FROM php:8.2-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    # Build dependencies
    $PHPIZE_DEPS \
    # System utilities
    bash \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    # MySQL client for database connectivity testing
    mariadb-client \
    # Install PHP extensions
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        sockets \
    # Install Redis PHP extension
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Switch to non-root user
USER www-data

# Expose port 8000 for Laravel's built-in server
EXPOSE 8000

# Start Laravel development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
