FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy the rest of the application
COPY . .

# Install Node dependencies and build assets
RUN npm ci && npm run build

# Run composer post-install scripts
RUN composer run-script post-autoload-dump

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configure opcache for production
RUN echo "opcache.enable=1\nopcache.memory_consumption=256\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=0" \
    >> /usr/local/etc/php/conf.d/opcache.ini

# Increase PHP limits for file uploads and memory
RUN echo "upload_max_filesize=64M\npost_max_size=64M\nmemory_limit=512M\nmax_execution_time=300" \
    >> /usr/local/etc/php/conf.d/custom.ini

EXPOSE 9000

CMD ["php-fpm"]
