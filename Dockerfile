FROM php:8.1-fpm

# Installer les dépendances système et PHP requises, y compris FFmpeg
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    libmariadb-dev-compat \  # Remplacer libmysqlclient-dev par libmariadb-dev-compat
    ffmpeg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les dépendances PHP
RUN composer install --no-interaction --prefer-dist

# Exposer le port que votre application utilisera
EXPOSE 8000

# Commande pour démarrer l'application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
