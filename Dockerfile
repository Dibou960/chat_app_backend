# Utiliser une image PHP avec Composer et extensions nécessaires
FROM php:8.2-cli

# Installer les dépendances
RUN apt-get update && apt-get install -y \
    ffmpeg \
    unzip \
    git \
    supervisor

# Installer les extensions PHP
RUN docker-php-ext-install pcntl pdo pdo_mysql

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers du projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Exposer le port Laravel
EXPOSE 10000

# Copier la config Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Démarrer Supervisor pour lancer Laravel et la Queue en parallèle
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
