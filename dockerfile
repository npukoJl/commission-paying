# Используем официальный образ PHP с необходимыми расширениями
FROM php:8.3-cli

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libgmp-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    xml \
    zip \
    gmp \
    bcmath

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www

# Копируем файлы проекта
COPY . .

# Устанавливаем зависимости
RUN composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs

# Настраиваем права
RUN chmod -R 775 storage bootstrap/cache

# Порт для artisan serve
EXPOSE 8000

# Запускаем сервер
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
