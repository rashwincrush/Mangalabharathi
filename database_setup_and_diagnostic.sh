#!/bin/bash

# Comprehensive Database Setup and Diagnostic Script

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

# Check if Homebrew is installed
check_homebrew() {
    if ! command -v brew &> /dev/null; then
        error_log "Homebrew is not installed. Installing Homebrew..."
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    else
        success_log "Homebrew is installed"
    fi
}

# Install MySQL via Homebrew
install_mysql() {
    log "Checking MySQL installation..."
    if ! brew list mysql &> /dev/null; then
        log "Installing MySQL via Homebrew..."
        brew install mysql
        brew services start mysql
        success_log "MySQL installed and started"
    else
        success_log "MySQL is already installed"
    fi
}

# Secure MySQL installation
secure_mysql_installation() {
    log "Securing MySQL installation..."
    
    # Set root password
    mysql_secure_installation <<EOF

y
root
root
y
y
y
y
EOF

    success_log "MySQL secured"
}

# Create database and set privileges
setup_database() {
    log "Setting up database..."
    
    # Create database
    mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS managalabhrathi;" 2>/dev/null
    
    # Grant privileges
    mysql -u root -proot -e "GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'root'@'localhost' WITH GRANT OPTION;" 2>/dev/null
    mysql -u root -proot -e "FLUSH PRIVILEGES;" 2>/dev/null
    
    success_log "Database 'managalabhrathi' created and privileges granted"
}

# Diagnostic function
diagnose_database() {
    log "Running database diagnostic..."
    
    # Check MySQL connection
    mysql -u root -proot -e "SELECT 1;" &> /dev/null
    if [ $? -eq 0 ]; then
        success_log "MySQL connection successful"
    else
        error_log "MySQL connection failed"
        return 1
    fi
    
    # Check database exists
    mysql -u root -proot -e "USE managalabhrathi;" &> /dev/null
    if [ $? -eq 0 ]; then
        success_log "Database 'managalabhrathi' exists and is accessible"
    else
        error_log "Database 'managalabhrathi' does not exist or is not accessible"
        return 1
    fi
}

# Main execution
main() {
    log "Starting comprehensive database setup and diagnostic..."
    
    check_homebrew
    install_mysql
    secure_mysql_installation
    setup_database
    diagnose_database
    
    if [ $? -eq 0 ]; then
        success_log "Database setup and diagnostic completed successfully!"
    else
        error_log "Database setup encountered issues. Please check the logs."
    fi
}

# Run main function
main
