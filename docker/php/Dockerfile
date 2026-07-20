FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    postgresql-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    opcache

# カバレッジ計測ドライバ（compose.override.yml でのみ有効化）
ARG INSTALL_PCOV=false
RUN if [ "$INSTALL_PCOV" = "true" ]; then \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del .build-deps; \
    fi

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
