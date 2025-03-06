FROM php:8.2-fpm

# Installer les dépendances système et extensions PHP requises
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers de configuration Composer
COPY composer.lock composer.json /var/www/

# Installer Composer globalement
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les dépendances de l'application
RUN composer install --no-dev --prefer-dist --no-scripts --no-interaction

# Copier l'ensemble du code de l'application
COPY . /var/www

# Exposer le port 8080
EXPOSE 8080

# Lancer PHP-FPM
CMD ["php-fpm"]