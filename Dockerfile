# Usamos la imagen oficial de PHP con FPM
FROM php:8.3-fpm

# Instalamos las dependencias necesarias para Laravel y PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql zip pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalamos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecemos el directorio de trabajo
WORKDIR /var/www/html

# Copiamos composer files primero para aprovechar cache de Docker
COPY composer.json composer.lock ./

# Instalamos las dependencias de Laravel (con dev dependencies para desarrollo)
RUN composer install --optimize-autoloader --no-scripts

# Copiamos el resto de los archivos del proyecto
COPY . .

# Establecemos permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Ejecutamos composer con los scripts después de copiar todos los archivos
RUN composer dump-autoload --optimize

# Exponemos el puerto 9000 para PHP-FPM
EXPOSE 9000

# Comando para ejecutar el servidor PHP-FPM
CMD ["php-fpm"]
