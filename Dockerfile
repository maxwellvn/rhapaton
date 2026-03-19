FROM php:8.2-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libicu-dev \
    && docker-php-ext-install intl mysqli pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create writable directories for runtime data
RUN mkdir -p secure_data \
    && chown -R www-data:www-data secure_data \
    && chmod -R 775 secure_data

# Apache config: allow .htaccess overrides
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Set correct ownership
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
