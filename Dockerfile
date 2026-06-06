FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
EXPOSE 80
EXPOSE 3306

# Install MySQL server and client libraries
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libjpeg62-turbo-dev libpng-dev libwebp-dev \
        mysql-server \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql gd \
    && a2enmod rewrite \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

# Configure PHP settings
RUN { \
        echo "upload_max_filesize=20M"; \
        echo "post_max_size=512M"; \
        echo "max_file_uploads=5000"; \
        echo "memory_limit=512M"; \
        echo "max_execution_time=300"; \
        echo "date.timezone=Asia/Jakarta"; \
        echo "session.cookie_httponly=1"; \
        echo "session.cookie_samesite=Lax"; \
    } > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
COPY . /var/www/html

# Set up permissions
RUN mkdir -p public/uploads/logos public/uploads/photos storage/logs \
    && chown -R www-data:www-data public/uploads storage \
    && find public/uploads storage -type d -exec chmod 775 {} \;

# Create a script to initialize MySQL and start both services
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
