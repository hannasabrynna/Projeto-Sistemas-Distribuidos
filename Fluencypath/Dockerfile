# -------------------------------
# Etapa 1: Instalar dependências do Composer
# -------------------------------
FROM composer:2.7 AS vendor_build
WORKDIR /var/www/html
COPY database/ ./database/
COPY composer.json composer.lock ./
# Instala as dependências sem gerar o autoloader, que faremos mais tarde.
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist

# -------------------------------
# Etapa 2: Compilar o Frontend (Assets)
# -------------------------------
FROM node:22-alpine AS frontend_build
WORKDIR /var/www/html
# Copia primeiro os ficheiros de dependência para aproveitar o cache
COPY package.json package-lock.json* ./
RUN npm ci
# Agora copia o resto dos ficheiros necessários para o build
COPY . .
RUN npm run build

# -------------------------------
# Etapa 3: Imagem Final de Runtime
# -------------------------------
FROM php:8.3-apache-bookworm
WORKDIR /var/www/html

# Instalar extensões PHP e dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev unzip git netcat-openbsd default-mysql-client \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Configurar Apache e ativar mod_rewrite
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Copiar o código-fonte da aplicação do contexto local
COPY . .

# Copiar as dependências e assets das etapas de build anteriores
COPY --from=vendor_build /var/www/html/vendor ./vendor
COPY --from=frontend_build /var/www/html/public/build ./public/build

# Copiar ficheiros de entrada e .htaccess
COPY wait-for-db.sh /usr/local/bin/wait-for-db.sh
COPY public/.htaccess ./public/.htaccess

# ===================================================================
# PASSO CRÍTICO: Otimização e Permissões para Produção
# Gerar o autoloader otimizado e limpar caches do Laravel.
# ===================================================================
RUN composer dump-autoload --optimize && \
    php artisan optimize && \
    php artisan config:clear && php artisan route:clear && php artisan view:clear

# Ajustar permissões para o servidor web
RUN chmod +x /usr/local/bin/wait-for-db.sh && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT [ "wait-for-db.sh" ]

EXPOSE 80
CMD ["apache2-foreground"]