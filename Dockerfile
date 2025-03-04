# Use a PHP image with Composer and necessary extensions
FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    ffmpeg \
    unzip \
    git \
    supervisor

# Install PHP extensions
RUN docker-php-ext-install pcntl pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose the port specified by Render
EXPOSE ${PORT}

# Add Supervisor configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start Supervisor
CMD ["supervisord", "-n"]
