FROM php:8.4-fpm

RUN apt-get update -y && \
    docker-php-ext-install pdo pdo_mysql && \
    apt-get install -y zip unzip libzip-dev libwebp-dev libjpeg-dev libpng-dev libfreetype6-dev libjpeg62-turbo-dev parallel pdftk tzdata

RUN cp /usr/share/zoneinfo/Asia/Taipei /etc/localtime && \
    echo "Asia/Taipei" > /etc/timezone

# Other extension
RUN docker-php-ext-install zip && \
    docker-php-ext-install bcmath && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    mv composer.phar /usr/local/bin/composer

EXPOSE 9000
