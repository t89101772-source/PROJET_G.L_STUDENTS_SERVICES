# Dockerfile pour le backend PHP
FROM php:8.2-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer mod_rewrite pour Apache
# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Configurer Apache pour accepter les requêtes CORS
RUN echo "Header set Access-Control-Allow-Origin \"*\"" >> /etc/apache2/apache2.conf
RUN echo "Header set Access-Control-Allow-Methods \"GET, POST, PUT, DELETE, OPTIONS\"" >> /etc/apache2/apache2.conf
RUN echo "Header set Access-Control-Allow-Headers \"Content-Type, Authorization\"" >> /etc/apache2/apache2.conf

# Copier le code PHP
COPY . /var/www/html/

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80