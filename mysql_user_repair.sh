#!/bin/bash

# MySQL User Repair and Authentication Script

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${YELLOW}[MYSQL USER REPAIR]${NC} $1"
}

# Success logging function
success_log() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Error logging function
error_log() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check MySQL service
check_mysql_service() {
    log "Checking MySQL service status"
    
    # Check if MySQL is running
    if ! brew services list | grep -q "mysql.*started"; then
        log "Starting MySQL service"
        brew services start mysql
        sleep 5  # Wait for service to start
    fi
}

# Reset root user password
reset_root_password() {
    log "Resetting MySQL root user password"
    
    # Stop MySQL service
    brew services stop mysql
    
    # Start MySQL in safe mode
    mysqld_safe --skip-grant-tables &
    sleep 5
    
    # Connect and reset password
    mysql -u root << EOF
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
EOF
    
    # Stop safe mode MySQL
    pkill mysqld
    
    # Restart MySQL service
    brew services restart mysql
    sleep 5
    
    success_log "Root password reset completed"
}

# Create database with full privileges
create_database() {
    log "Creating and configuring database"
    
    # Try connecting with new password
    mysql -u root -proot << EOF
CREATE DATABASE IF NOT EXISTS managalabhrathi;
GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
    
    success_log "Database created and privileges granted"
}

# Verify database connection
verify_connection() {
    log "Verifying database connection"
    
    # Test connection
    mysql -u root -proot managalabhrathi -e "SELECT 1;" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        success_log "Database connection successful"
    else
        error_log "Database connection failed"
        return 1
    fi
}

# Main repair function
main() {
    log "Starting MySQL User and Database Repair"
    
    check_mysql_service
    reset_root_password
    create_database
    verify_connection
    
    success_log "MySQL repair completed successfully"
}

# Run main function
main
