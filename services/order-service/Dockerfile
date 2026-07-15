FROM php:8.4-fpm

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    bash \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql sockets

# Get composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy shared package (to support composer path repository relative mapping)
COPY shared/ /shared/

WORKDIR /app

# Copy service files
COPY services/order-service/ .

# Disable security audit blocking (some dependencies have advisories)
RUN composer config policy.advisories.block false

# Run composer install
RUN composer install --no-interaction --optimize-autoloader --no-scripts

EXPOSE 8000

# Start server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
