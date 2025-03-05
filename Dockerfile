# Utiliser une image PHP contenant Composer et les extensions nécessaires
FROM php:8.2-cli

# Installer les dépendances (FFmpeg, Git, Unzip, Supervisor)
RUN apt-get update && apt-get install -y \
    ffmpeg \
    unzip \
    git \
    
    # Installer les extensions PHP nécessaires
RUN docker-php-ext-install pcntl pdo pdo_mysql

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier les fichiers du projet
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader


# Ajouter le fichier de configuration Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Lancer Supervisor pour exécuter plusieurs commandes
# Exposer le port que votre application utilisera
EXPOSE 8000

# Commande pour démarrer l'application
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]