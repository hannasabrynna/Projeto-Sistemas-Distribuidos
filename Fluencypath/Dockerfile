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

# Configuração do Apache (mantive o seu comando original)
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
    AllowOverride All\n\ 
    Require all granted\n\
    </Directory>\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\ 
    </VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Ativar mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copiar aplicação (PHP)
COPY --from=composer_build /var/www/html /var/www/html

# ===================================================================
# Copia APENAS os assets compilados do frontend
# ===================================================================
COPY --from=node_build /var/www/html/public/build /var/www/html/public/build

# Copiar script wait-for-db.sh e .htaccess
COPY wait-for-db.sh /usr/local/bin/wait-for-db.sh
COPY public/.htaccess /var/www/html/public/.htaccess

RUN php artisan config:clear && php artisan route:clear && php artisan view:clear

# ===================================================================
# PASSO CRÍTICO: AJUSTE DE PERMISSÕES
# ===================================================================
RUN chmod +x /usr/local/bin/wait-for-db.sh && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT [ "wait-for-db.sh" ]

EXPOSE 80
CMD ["apache2-foreground"]