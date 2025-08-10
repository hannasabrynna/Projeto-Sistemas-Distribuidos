# ===================================
# Stage 1: Composer (PHP dependencies)
# ===================================
FROM composer:2.7 AS composer-builder

WORKDIR /var/www

# Copiar apenas os arquivos de dependência primeiro para cachear a camada
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copiar o restante do código-fonte do Laravel
COPY . .

# ===================================
# Stage 2: Node (JS/CSS build)
# ===================================
FROM node:20 AS node-builder

WORKDIR /var/www

# Copiar os arquivos de dependência do Node
COPY package.json package-lock.json ./
RUN npm ci

# Copiar o código-fonte necessário para o build dos assets
COPY vite.config.* ./
COPY resources ./resources

# Construir os assets
RUN npm run build

# ===================================
# Stage 3: Apache + PHP (App final)
# ===================================
FROM php:8.4-apache AS app

# Instalar dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Ativar módulo rewrite do Apache
RUN a2enmod rewrite

# Configurar o VirtualHost do Apache para apontar para /public
RUN echo "<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/public\n\
    <Directory /var/www/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www

# Copiar o código-fonte da aplicação e as dependências do estágio do composer
COPY --from=composer-builder /var/www .

# Copiar os assets compilados do estágio do node
COPY --from=node-builder /var/www/public ./public

# Copiar o script de espera e torná-lo executável
COPY wait-for-db.sh /usr/local/bin/wait-for-db.sh
RUN chmod +x /usr/local/bin/wait-for-db.sh

# Ajustar permissões para o Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 80

# Definir o ENTRYPOINT para o nosso script de espera
ENTRYPOINT [ "wait-for-db.sh" ]

# O comando padrão que o ENTRYPOINT irá executar após o banco de dados estar pronto
CMD ["apache2-foreground"]