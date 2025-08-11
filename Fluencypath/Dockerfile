# -------------------------------
# Etapa 1: Builder
# -------------------------------
FROM composer:2 AS composer_build
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --prefer-dist --optimize-autoloader

# Node build (se tiver frontend)
FROM node:22-alpine AS node_build
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# -------------------------------
# Etapa 2: Runtime
# -------------------------------
FROM php:8.3-apache-bookworm

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
        libzip-dev unzip git netcat-openbsd default-mysql-client \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Ativar mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copiar aplicação (PHP)
COPY --from=composer_build /var/www/html /var/www/html

# Copiar build do frontend (se aplicável)
COPY --from=node_build /var/www/html/public /var/www/html/public

# Copiar script wait-for-db.sh
COPY wait-for-db.sh /usr/local/bin/wait-for-db.sh
RUN chmod +x /usr/local/bin/wait-for-db.sh

# Permissões
RUN chown -R www-data:www-data /var/www/html

ENTRYPOINT [ "wait-for-db.sh" ]

EXPOSE 80
CMD ["apache2-foreground"]
