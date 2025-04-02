FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    freetype* \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Set working directory to /app
WORKDIR /app

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Symfony project files
COPY ./app /app

# Accept .env file as a build argument
ARG ENV_FILE=.env
COPY ${ENV_FILE} /app/.env.local
ENV COMPOSER_MEMORY_LIMIT=-1

RUN composer install --optimize-autoloader

# Set permissions
RUN chmod -R 777 /app
