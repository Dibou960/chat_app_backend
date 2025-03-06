# Utilise l'image officielle PHP avec FPM pour Windows
FROM php:8.2-fpm-windowsservercore

# Installer les dépendances système et extensions PHP requises
RUN powershell -Command \
    Install-WindowsFeature -Name Web-Server; \
    Install-Package -Name php8.2; \
    Install-Package -Name php8.2-fpm; \
    Install-Package -Name php8.2-mbstring; \
    Install-Package -Name php8.2-pdo_mysql; \
    Install-Package -Name php8.2-exif; \
    Install-Package -Name php8.2-pcntl; \
    Install-Package -Name php8.2-bcmath; \
    Install-Package -Name php8.2-gd

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

# Copier le fichier de configuration de Supervisor dans le conteneur
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Donner les permissions nécessaires aux dossiers de Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exposer le port (Render assignera une variable d'environnement $PORT si besoin)
EXPOSE 8000

# Lancer Supervisor qui va démarrer PHP-FPM et le worker de queue
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]