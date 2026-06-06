#!/bin/bash

# Start MySQL in the background
/etc/init.d/mysql start &

# Wait for MySQL to be ready
until mysqladmin ping -h localhost --silent; do
    echo 'waiting for mysql to be connectable...'
    sleep 2
done

# Create database and user if they don't exist
DB_DATABASE=${DB_DATABASE:-school_photo_orders}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

if [ -n "$DB_PASSWORD" ]; then
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`;"
    mysql -u root -e "CREATE USER IF NOT EXISTS \'$DB_USERNAME\'@\'localhost\' IDENTIFIED BY \'$DB_PASSWORD\';"
    mysql -u root -e "GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO \'$DB_USERNAME\'@\'localhost\';"
    mysql -u root -e "FLUSH PRIVILEGES;"
else
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`;"
    mysql -u root -e "CREATE USER IF NOT EXISTS \'$DB_USERNAME\'@\'localhost\';"
    mysql -u root -e "GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO \'$DB_USERNAME\'@\'localhost\';"
    mysql -u root -e "FLUSH PRIVILEGES;"
fi

# Import SQL files
if [ -f "/var/www/html/database/coolify-import.sql" ]; then
    mysql -u root "$DB_DATABASE" < /var/www/html/database/coolify-import.sql
fi

if [ -f "/var/www/html/database/migrations.sql" ]; then
    mysql -u root "$DB_DATABASE" < /var/www/html/database/migrations.sql
fi

# Start Apache in the foreground
exec apache2-foreground
