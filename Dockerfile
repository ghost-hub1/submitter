# Use official PHP Apache image
FROM php:8.2-apache

# Copy app files into web root
COPY . /var/www/html/

# Enable Apache mod_rewrite for routing and PHP
RUN a2enmod rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Change Apache listen port to 8000 (Koyeb default)
RUN sed -i 's/80/8000/' /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 8000 for Koyeb
EXPOSE 8000

# Run Apache
CMD ["apache2-foreground"]
