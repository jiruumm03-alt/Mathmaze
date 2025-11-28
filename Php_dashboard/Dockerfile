# Use the official PHP image with Apache
FROM php:8.2-apache

# Enable mysqli and SSL extensions (for Aiven MySQL)
RUN docker-php-ext-install mysqli && a2enmod ssl

# Copy your project files into the container
COPY . /var/www/html/

# Expose Render’s port
EXPOSE 10000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]