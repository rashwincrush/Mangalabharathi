#!/bin/bash

# Comprehensive Database Setup Script

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${YELLOW}[DATABASE SETUP]${NC} $1"
}

# Error logging function
error_log() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Success logging function
success_log() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Check MySQL installation
check_mysql_installed() {
    if ! command -v mysql &> /dev/null; then
        error_log "MySQL is not installed"
        return 1
    fi
    success_log "MySQL is installed"
    return 0
}

# Create database
create_database() {
    log "Attempting to create database"
    
    # Try creating database with root user and default password
    mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS managalabhrathi;" 2>/dev/null
    
    # If first attempt fails, try without password
    if [ $? -ne 0 ]; then
        mysql -u root -e "CREATE DATABASE IF NOT EXISTS managalabhrathi;"
    fi
    
    if [ $? -eq 0 ]; then
        success_log "Database 'managalabhrathi' created successfully"
        return 0
    else
        error_log "Failed to create database"
        return 1
    fi
}

# Grant privileges
grant_privileges() {
    log "Granting database privileges"
    
    # Try granting privileges with root user and default password
    mysql -u root -proot -e "GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'root'@'localhost' WITH GRANT OPTION;" 2>/dev/null
    mysql -u root -proot -e "FLUSH PRIVILEGES;" 2>/dev/null
    
    # If first attempt fails, try without password
    if [ $? -ne 0 ]; then
        mysql -u root -e "GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'root'@'localhost' WITH GRANT OPTION;"
        mysql -u root -e "FLUSH PRIVILEGES;"
    fi
    
    if [ $? -eq 0 ]; then
        success_log "Privileges granted successfully"
        return 0
    else
        error_log "Failed to grant privileges"
        return 1
    fi
}

# Main execution
main() {
    log "Starting database setup process"
    
    check_mysql_installed
    if [ $? -ne 0 ]; then
        error_log "MySQL is not properly installed"
        exit 1
    fi
    
    create_database
    if [ $? -ne 0 ]; then
        error_log "Database creation failed"
        exit 1
    fi
    
    grant_privileges
    if [ $? -ne 0 ]; then
        error_log "Privilege granting failed"
        exit 1
    fi
    
    # Optional: Import SQL if exists
    if [ -f "setup_local_database.sql" ]; then
        log "Importing local database setup"
        mysql -u root -proot managalabhrathi < setup_local_database.sql
    fi
    
    success_log "Database setup completed successfully"
}

# Run main function
main
