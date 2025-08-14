# -------------------------------
# Etapa 1: Base PHP com dependências
# -------------------------------
FROM php:8.3-apache-bookworm AS base
# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    netcat-openbsd \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Configurar Apache
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
    </Directory>\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
    </VirtualHost>" > /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

# -------------------------------
# Etapa 2: Builder de dependências do Composer
# -------------------------------
FROM base AS composer_build
WORKDIR /var/www/html
COPY database/ ./database/
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# -------------------------------
# Etapa 3: Builder de recursos do Node.js (Frontend)
# -------------------------------
FROM node:22-alpine AS node_build
WORKDIR /var/www/html
# Copia todos os ficheiros necessários para a compilação
COPY . .
RUN npm install
RUN npm run build

# -------------------------------
# Etapa 4: Imagem Final de Runtime
# -------------------------------
FROM base AS runtime
WORKDIR /var/www/html

# Copiar a aplicação do builder do Composer
COPY --from=composer_build /var/www/html /var/www/html
COPY . /var/www/html

# Copiar os recursos do frontend compilados
# Assumindo que o Laravel Vite gera os ficheiros na pasta `public/build`
COPY --from=node_build /var/www/html/public/build /var/www/html/public/build

# Copiar ficheiros de entrada e permissões
COPY wait-for-db.sh /usr/local/bin/wait-for-db.sh
COPY public/.htaccess /var/www/html/public/.htaccess

# Limpar caches e otimizar
RUN php artisan config:clear && php artisan route:clear && php artisan view:clear

# Ajuste de permissões
RUN chmod +x /usr/local/bin/wait-for-db.sh && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT [ "wait-for-db.sh" ]

EXPOSE 80
CMD ["apache2-foreground"]