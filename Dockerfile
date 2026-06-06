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

# Create entrypoint script inline to avoid "file not found" issues during build
RUN echo '#!/bin/bash\n\
\n\
# Start MySQL in the background\n\
/etc/init.d/mysql start &\n\
\n\
# Wait for MySQL to be ready\n\
until mysqladmin ping -h localhost --silent; do\n\
    echo "waiting for mysql to be connectable..."\n\
    sleep 2\n\
done\n\
\n\
# Create database and user if they dont exist\n\
DB_DATABASE=${DB_DATABASE:-school_photo_orders}\n\
DB_USERNAME=${DB_USERNAME:-root}\n\
DB_PASSWORD=${DB_PASSWORD:-}\n\
\n\
if [ -n "$DB_PASSWORD" ]; then\n\
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`;"\n\
    mysql -u root -e "CREATE USER IF NOT EXISTS \"$DB_USERNAME\"@\"localhost\" IDENTIFIED BY \"$DB_PASSWORD\";"\n\
    mysql -u root -e "GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO \"$DB_USERNAME\"@\"localhost\";"\n\
    mysql -u root -e "FLUSH PRIVILEGES;"\n\
else\n\
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`;"\n\
    mysql -u root -e "CREATE USER IF NOT EXISTS \"$DB_USERNAME\"@\"localhost\";"\n\
    mysql -u root -e "GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO \"$DB_USERNAME\"@\"localhost\";"\n\
    mysql -u root -e "FLUSH PRIVILEGES;"\n\
fi\n\
\n\
# Import SQL files\n\
if [ -f "/var/www/html/database/coolify-import.sql" ]; then\n\
    mysql -u root "$DB_DATABASE" < /var/www/html/database/coolify-import.sql\n\
fi\n\
\n\
if [ -f "/var/www/html/database/migrations.sql" ]; then\n\
    mysql -u root "$DB_DATABASE" < /var/www/html/database/migrations.sql\n\
fi\n\
\n\
# Start Apache in the foreground\n\
exec apache2-foreground' > /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
