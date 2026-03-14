FROM php:8.2-fpm-bookworm

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    gettext-base \
    nodejs \
    npm \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libwebp-dev \
    libxpm-dev \
    zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
       bcmath \
       exif \
       gd \
       intl \
       mbstring \
       opcache \
       pdo \
       pdo_mysql \
       zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs \
    && npm ci \
    && npm run build \
    && mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]
