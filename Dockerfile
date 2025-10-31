FROM registry.opsum.nl/laravel-php:8.4-prod AS base
COPY docker/prod/hexon.conf /etc/nginx/server-opts.d/hexon.conf
WORKDIR /var/www/html

FROM base AS development

USER root
RUN install-php-extensions xdebug \
    && docker-php-serversideup-dep-install-alpine nodejs \
    && docker-php-serversideup-dep-install-alpine npm

COPY docker/dev/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/
COPY docker/dev/php/conf.d/error_reporting.ini /usr/local/etc/php/conf.d/

# Install SPX only for development images
WORKDIR /usr/src
RUN apk add --no-cache --virtual .spx-build-deps \
        git autoconf make gcc g++ libc-dev libtool pkgconf zlib-dev \
    && git clone https://github.com/NoiseByNorthwest/php-spx.git \
    && cd php-spx \
    && git checkout release/latest \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && echo "extension=spx.so" > /usr/local/etc/php/conf.d/spx.ini \
    && echo "spx.http_enabled=1" >> /usr/local/etc/php/conf.d/spx.ini \
    && echo "spx.http_key=dev" >> /usr/local/etc/php/conf.d/spx.ini \
    && echo "spx.http_trusted_proxies=REMOTE_ADDR" >> /usr/local/etc/php/conf.d/spx.ini \
    && echo "spx.http_ip_whitelist=\"*\"" >> /usr/local/etc/php/conf.d/spx.ini \
    && apk del .spx-build-deps \
    && rm -rf /usr/src/php-spx

USER www-data
WORKDIR /var/www/html

FROM base AS composer-deps

WORKDIR /var/www/html
COPY composer.json composer.lock auth.json ./
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize

FROM node:20-alpine AS frontend

WORKDIR /app
RUN apk add --no-cache libc6-compat
COPY package.json package-lock.json vite.config.js ./
COPY .npmrc .npmrc
RUN npm ci && rm -f .npmrc
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM base AS production

USER root
WORKDIR /var/www/html
COPY . .
COPY --from=composer-deps /var/www/html/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY --from=frontend /app/bundle-analysis.html ./bundle-analysis.html
COPY --from=frontend /app/bundle-analysis.json ./bundle-analysis.json
RUN rm -f public/hot auth.json .npmrc \
    && mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

FROM production AS staging

USER www-data
