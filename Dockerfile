FROM php:8.2-apache

# Enable apache mod_rewrite
RUN a2enmod rewrite

# Copy all project files into /var/www/html
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose the default Apache port
EXPOSE 80
