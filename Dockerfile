# Utilise l'image PHP-FPM
FROM php:8.2-fpm

# Installer les dépendances système, FFmpeg et les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    ffmpeg \
    build-essential \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers du projet
COPY . /var/www

# Installer les dépendances Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --prefer-dist --no-scripts --no-interaction

# Exécuter des commandes artisan pour générer des caches Laravel (productions)
RUN php artisan key:generate
RUN php artisan config:cache
RUN php artisan route:cache

# Donner les permissions nécessaires
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public

# Exposer le port 80 (pour Nginx)
EXPOSE 80

# Copier le fichier de configuration Nginx
COPY ./nginx/default.conf /etc/nginx/sites-available/default

# Copier le fichier de configuration Supervisor
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Lancer Supervisor pour démarrer Nginx, PHP-FPM et le queue-worker
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
