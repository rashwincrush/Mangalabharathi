#!/bin/bash

# MySQL Setup Script for Managalabhrathi Trust

# MySQL credentials
MYSQL_USER="root"
MYSQL_PASSWORD="root"
DATABASE_NAME="managalabhrathi"

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "MySQL is not installed. Please install MySQL first."
    exit 1
fi

# Create database if not exists
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -e "CREATE DATABASE IF NOT EXISTS $DATABASE_NAME;"

# Grant all privileges to root user
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -e "GRANT ALL PRIVILEGES ON $DATABASE_NAME.* TO '$MYSQL_USER'@'localhost';"
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -e "FLUSH PRIVILEGES;"

# Optional: Run database setup SQL
if [ -f "setup_local_database.sql" ]; then
    mysql -u $MYSQL_USER -p$MYSQL_PASSWORD $DATABASE_NAME < setup_local_database.sql
fi

echo "Database setup completed successfully!"
