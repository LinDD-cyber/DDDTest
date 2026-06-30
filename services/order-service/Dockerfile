FROM php:8.4-cli-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache git unzip bash mysql-client
RUN docker-php-ext-install pdo pdo_mysql

# Get composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy files
COPY . .

# Disable security audit blocking (some dependencies have advisories)
RUN composer config policy.advisories.block false

# Run composer install
RUN composer install --no-interaction --optimize-autoloader --no-scripts

EXPOSE 8000

# Start server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
