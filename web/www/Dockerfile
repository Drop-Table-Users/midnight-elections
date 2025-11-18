# Laravel Web Application Dockerfile - Multi-stage build
FROM php:8.3-cli-alpine AS builder

# Install system dependencies and build tools
RUN apk add --no-cache \
    git \
    curl \
    bash \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    sqlite \
    sqlite-dev \
    ca-certificates \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Verify installations
RUN php --version && \
    composer --version && \
    node --version && \
    npm --version

# Set working directory
WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Copy package files for Node.js
COPY package*.json ./

# Install Node dependencies
RUN npm ci

# Copy application files (except laravel-midnight which will be mounted)
COPY . .

# Build frontend assets
RUN npm run build

# Production stage
FROM php:8.3-cli-alpine

# Install runtime dependencies
RUN apk add --no-cache \
    bash \
    curl \
    libpng \
    oniguruma \
    libxml2 \
    sqlite \
    sqlite-libs \
    ca-certificates \
    tini \
    nodejs \
    npm

# Install PHP extensions (same as builder)
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    sqlite-dev \
    autoconf \
    g++ \
    make && \
    docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd && \
    apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Copy application from builder
COPY --from=builder /app /app

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create necessary directories
RUN mkdir -p \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache \
    /app/database

# Create SQLite database file
RUN touch /app/database/database.sqlite

# Set proper permissions
RUN chown -R www-data:www-data \
    /app/storage \
    /app/bootstrap/cache \
    /app/database && \
    chmod -R 775 \
    /app/storage \
    /app/bootstrap/cache \
    /app/database

# Install sqlite3 CLI for database checks in entrypoint
RUN apk add --no-cache sqlite

# Switch to www-data user for running the application
USER www-data

# Expose port for Laravel development server
EXPOSE 8000

# Use custom entrypoint that handles migrations and seeding
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/docker-entrypoint.sh"]

# Start PHP development server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
