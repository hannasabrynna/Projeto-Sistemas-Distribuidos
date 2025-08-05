FROM composer AS composer-builder

COPY ./composer.json /application/composer.json
COPY ./composer.lock /application/composer.lock
COPY ./php-bin.sh /application/php-bin.sh
COPY ./artisan /application/artisan

# Main application
COPY ./app /application/app

# Laravel
COPY ./bootstrap /application/bootstrap
COPY ./config /application/config
COPY ./routes /application/routes
COPY ./database/migrations /application/database/migrations
COPY ./resources/views /application/resources/views


# Issues with composer scripts referencing php
# https://frankenphp.dev/docs/known-issues/#composer-scripts-referencing-php
RUN cp /usr/local/bin/php /usr/local/bin/php-cli && \
    mv /application/php-bin.sh /usr/local/bin/php && \
    chmod +x /usr/local/bin/php

WORKDIR /application

# Install dependencies
RUN composer install --no-dev
# Create cache directories
RUN mkdir -p /application/storage/framework/cache/data && \
    mkdir -p /application/storage/framework/views
# Optimize Laravel
RUN php artisan optimize
# Create logs directory
RUN mkdir -p /application/storage/logs

FROM node AS node-builder

COPY ./package.json /application/package.json
COPY ./package-lock.json /application/package-lock.json
COPY ./vite.config.ts /application/vite.config.ts
COPY ./tsconfig.json /application/tsconfig.json
COPY ./components.json /application/components.json
COPY ./eslint.config.js /application/eslint.config.js
COPY ./public /application/public
COPY ./resources /application/resources
COPY --from=composer-builder /application/vendor /application/vendor

WORKDIR /application

RUN npm ci && npm run build

FROM dunglas/frankenphp AS server

ENV SERVER_NAME=portal-crud.local.host

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN docker-php-ext-install pcntl

COPY --from=composer-builder /application /application
COPY --from=node-builder /application/public /application/public
COPY ./entrypoint.sh /application/entrypoint.sh

WORKDIR /application

RUN chmod +x /application/entrypoint.sh

CMD ["/bin/bash", "-c", "/application/entrypoint.sh; php artisan octane:frankenphp --host=0.0.0.0 --port=80"]