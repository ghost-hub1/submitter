# Use official PHP 8.2 Apache image
FROM php:8.2-apache

# Copy all files to Apache web root
COPY . /var/www/html/

# Enable mod_rewrite (for .htaccess, if you use it)
RUN a2enmod rewrite
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Change Apache listen port to 8000 (Koyeb expects this)
RUN sed -i 's/80/8000/' /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose the correct port for Koyeb
EXPOSE 8000

# Start Apache in foreground
CMD ["apache2-foreground"]
