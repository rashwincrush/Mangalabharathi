#!/bin/bash

# Comprehensive Project Repair Script

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${YELLOW}[PROJECT REPAIR]${NC} $1"
}

# Success logging function
success_log() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Error logging function
error_log() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check and install dependencies
install_dependencies() {
    log "Checking and installing project dependencies"
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        log "Installing PHP"
        brew install php
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        log "Installing Composer"
        brew install composer
    fi
    
    # Check MySQL
    if ! command -v mysql &> /dev/null; then
        log "Installing MySQL"
        brew install mysql
        brew services start mysql
    fi
    
    success_log "Dependencies installed successfully"
}

# Set up database
setup_database() {
    log "Setting up database"
    
    # Create database
    mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS managalabhrathi;" 2>/dev/null
    
    # Grant privileges
    mysql -u root -proot -e "GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'root'@'localhost' WITH GRANT OPTION;" 2>/dev/null
    mysql -u root -proot -e "FLUSH PRIVILEGES;" 2>/dev/null
    
    success_log "Database setup completed"
}

# Repair website configuration
repair_website_config() {
    log "Repairing website configuration"
    
    # Run PHP repair script
    php /Users/ashwin/CascadeProjects/managalabhrathi-trust/repair_website.php
    
    success_log "Website configuration repaired"
}

# Start PHP development server
start_server() {
    log "Starting PHP development server"
    
    # Kill any existing PHP servers on port 9090
    lsof -ti:9090 | xargs kill -9 2>/dev/null
    
    # Start PHP development server
    php -S localhost:9090 &
    
    success_log "PHP development server started on http://localhost:9090"
}

# Main repair function
main() {
    log "Starting Managalabhrathi Trust Project Repair"
    
    install_dependencies
    setup_database
    repair_website_config
    start_server
    
    success_log "Project repair completed successfully!"
    echo "Access your website at: http://localhost:9090"
}

# Run main function
main
