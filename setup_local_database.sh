#!/bin/bash

# Check if MySQL is installed
if ! command -v mysql &> /dev/null
then
    echo "MySQL is not installed. Please install MySQL first."
    exit 1
fi

# Database credentials
DB_USER="root"
DB_PASS=""
DB_NAME="u274792269_MB"

# Path to SQL setup script
SQL_SCRIPT="/Users/ashwin/CascadeProjects/managalabhrathi-trust/setup_local_db.sql"

# Create database and tables
mysql -u "$DB_USER" -p"$DB_PASS" < "$SQL_SCRIPT"

echo "Local database setup complete!"
