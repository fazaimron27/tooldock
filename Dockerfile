# =============================================================================
# Tool Dock - Production Dockerfile
# =============================================================================
# Multi-stage build for Laravel + Inertia + React application
# Optimized for production with minimal image size and security
# =============================================================================

# -----------------------------------------------------------------------------
# Stage 1: Node.js Builder - Build frontend assets
# -----------------------------------------------------------------------------
FROM node:24-alpine AS node-builder

WORKDIR /app

# Copy package files first for better caching
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm ci --include=dev

# Copy source files needed for build
COPY vite.config.js tailwind.config.js postcss.config.js jsconfig.json ./
COPY resources ./resources
COPY Modules ./Modules

# Build frontend assets
RUN npm run build

# -----------------------------------------------------------------------------
# Stage 2: Composer Builder - Install PHP dependencies
# -----------------------------------------------------------------------------
FROM composer:2 AS composer-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Copy application source
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# -----------------------------------------------------------------------------
# Stage 3: Production Image
# -----------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS production

# Build arguments
ARG APP_ENV=production
ARG APP_DEBUG=false

# Environment variables
ENV APP_ENV=${APP_ENV} \
    APP_DEBUG=${APP_DEBUG} \
    PHP_OPCACHE_ENABLE=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_JIT=1255 \
    PHP_OPCACHE_JIT_BUFFER_SIZE=128M

# Install system dependencies
RUN apk add --no-cache \
    # Required for PostgreSQL
    postgresql-dev \
    postgresql-client \
    # Required for image processing (Intervention Image)
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    # Required for zip
    libzip-dev \
    zip \
    unzip \
    # Required for intl
    icu-dev \
    # Process management
    supervisor \
    # Nginx
    nginx \
    # Other utilities
    curl \
    git \
    shadow

# Install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    gd \
    zip \
    intl \
    opcache \
    pcntl \
    bcmath

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Create application user
RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www

# Set working directory
WORKDIR /var/www/html

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy Nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application from composer builder
COPY --from=composer-builder /app /var/www/html

# Copy built assets from node builder
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Create required directories
RUN mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/log/supervisor \
    /var/run/nginx

# Set permissions
RUN chown -R www:www /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copy and set entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/up || exit 1

# Start application
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
