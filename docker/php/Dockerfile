ARG PHP_IMAGE_TAG
FROM php:${PHP_IMAGE_TAG}

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y \
        git \
        libzip-dev \
        wget \
        zip \
    && docker-php-ext-install \
        zip \
    && rm -rf /var/lib/apt/lists/*

ARG LIBRDKAFKA_TAG
RUN mkdir /tmp/librdkafka-sources \
    && cd /tmp/librdkafka-sources \
    && wget https://github.com/edenhill/librdkafka/archive/${LIBRDKAFKA_TAG}.tar.gz  -O - | tar -xz --strip-components=1 \
    && ./configure \
    && make  \
    && make install \
    && rm -rf /tmp/librdkafka-sources

ARG EXT_RDKAFKA_TAG
RUN mkdir /tmp/ext-rdkafka-sources \
    && cd /tmp/ext-rdkafka-sources \
    && wget https://github.com/arnaud-lb/php-rdkafka/archive/${EXT_RDKAFKA_TAG}.tar.gz  -O - | tar -xz --strip-components=1 \
    && phpize \
    && ./configure \
    && make all -j 5 \
    && make install \
    && docker-php-ext-enable rdkafka \
    && rm -rf /tmp/ext-rdkafka-sources

ARG XDEBUG_VERSION
RUN pecl install xdebug${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug

WORKDIR /var/www