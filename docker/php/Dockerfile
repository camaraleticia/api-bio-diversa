FROM php:8.4-fpm-alpine

# Instalar dependências necessárias
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev

# Configurar GD antes de instalar
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Instalar extensões PHP
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    mbstring \
    exif \
    pcntl \
    gd

# Configurações de memória e tempo para scripts CLI (ex: treinamento do modelo)
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/zz-custom.ini \
 && echo "max_execution_time = 0" >> /usr/local/etc/php/conf.d/zz-custom.ini

# Instalar Composer (copia o binário oficial)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir diretório de trabalho
WORKDIR /var/www/html

# Corrigir permissões
RUN chown -R www-data:www-data /var/www/html

# Expor a porta usada pelo PHP-FPM
EXPOSE 9000

# Comando padrão do contêiner
CMD ["php-fpm"]