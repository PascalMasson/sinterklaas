# ==========================
# Base build configuration
# ==========================
ARG PHP_VERSION=8.4

FROM serversideup/php:${PHP_VERSION}-fpm-nginx-alpine AS base

# Re-declare ARGs inside the stage (required!)
ARG USER_ID=1000
ARG GROUP_ID=1000

USER root

RUN install-php-extensions intl gd exif bcmath excimer \
 && docker-php-serversideup-dep-install-alpine mysql-client execline \
 && sed -i '/X-Frame-Options/d' /etc/nginx/server-opts.d/security.conf \
 && mkdir -p /var/www/html/bootstrap/cache \
           /var/www/html/storage/framework/{sessions,views,cache} \
 && echo "Setting UID=${USER_ID} GID=${GROUP_ID}" \
 && docker-php-serversideup-set-id www-data ${USER_ID}:${GROUP_ID} \
 && docker-php-serversideup-set-file-permissions --owner ${USER_ID}:${GROUP_ID} --service nginx

COPY --chmod=755 ./docker/base/entrypoint.d/ /etc/entrypoint.d/
COPY ./docker/base/http.conf /etc/nginx/site-opts.d/

WORKDIR /var/www/html
USER www-data


# ==========================
# Development stage
# ==========================
FROM base AS development

USER root

# Install dev tools & extensions in one layer for caching efficiency
RUN install-php-extensions xdebug \
 && docker-php-serversideup-dep-install-alpine nodejs npm git autoconf make gcc g++ libc-dev libtool pkgconf zlib-dev \
 && wget -qO- https://get.pnpm.io/install.sh | ENV="$HOME/.shrc" SHELL="$(which sh)" sh -

# --- Configure PHP ---
COPY docker/dev/php/conf.d/*.ini /usr/local/etc/php/conf.d/

# --- Build SPX profiler ---
WORKDIR /usr/src
RUN git clone --depth=1 --branch=release/latest https://github.com/NoiseByNorthwest/php-spx.git \
 && cd php-spx \
 && phpize \
 && ./configure \
 && make -j"$(nproc)" \
 && make install \
 && echo "extension=spx.so" > /usr/local/etc/php/conf.d/spx.ini \
 && { \
      echo "spx.http_enabled=1"; \
      echo "spx.http_key=dev"; \
      echo "spx.http_trusted_proxies=REMOTE_ADDR"; \
      echo 'spx.http_ip_whitelist="*"'; \
    } >> /usr/local/etc/php/conf.d/spx.ini \
 && rm -rf /usr/src/php-spx /var/cache/apk/* /tmp/*

USER www-data
WORKDIR /var/www/html


# ==========================
# Production stage
# ==========================
FROM base AS production

# Copy only what’s required (optional step if using multi-stage build for app)
# COPY --from=builder /var/www/html /var/www/html

USER www-data


# ==========================
# Staging stage
# ==========================
FROM production AS staging
USER www-data
