#!/bin/bash

# Comprehensive MySQL Setup and Repair Script

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${YELLOW}[MySQL SETUP]${NC} $1"
}

success_log() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

error_log() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Ensure script is run with sudo
check_sudo() {
    if [[ $EUID -ne 0 ]]; then
        error_log "This script must be run with sudo"
        exit 1
    fi
}

# Install MySQL if not already installed
install_mysql() {
    log "Checking MySQL installation"
    
    # Check if Homebrew is installed
    if ! command -v brew &> /dev/null; then
        log "Installing Homebrew"
        /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    fi
    
    # Install MySQL
    if ! brew list mysql &> /dev/null; then
        log "Installing MySQL"
        brew install mysql
        brew services start mysql
        sleep 5
    fi
}

# Secure MySQL installation
secure_mysql() {
    log "Securing MySQL installation"
    
    # Remove anonymous users
    mysql -u root -e "DELETE FROM mysql.user WHERE User='';"
    
    # Disallow root login remotely
    mysql -u root -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    
    # Remove test database
    mysql -u root -e "DROP DATABASE IF EXISTS test;"
    mysql -u root -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    
    # Flush privileges
    mysql -u root -e "FLUSH PRIVILEGES;"
}

# Create database and user
create_database() {
    log "Creating database and configuring user"
    
    # Create database
    mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS managalabhrathi;
CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY 'root';
GRANT ALL PRIVILEGES ON managalabhrathi.* TO 'root'@'localhost';
FLUSH PRIVILEGES;

USE managalabhrathi;

# Create essential tables
CREATE TABLE IF NOT EXISTS team (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(255),
    bio TEXT,
    image_path VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE,
    location VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo_path VARCHAR(255)
);
EOF
    
    success_log "Database 'managalabhrathi' created and configured"
}

# Update PHP configuration files
update_php_config() {
    log "Updating PHP configuration files"
    
    # Config file path
    CONFIG_FILE="/Users/ashwin/CascadeProjects/managalabhrathi-trust/includes/config.php"
    DB_FILE="/Users/ashwin/CascadeProjects/managalabhrathi-trust/includes/db.php"
    
    # Create backup of existing files
    cp "$CONFIG_FILE" "${CONFIG_FILE}.backup_$(date +%Y%m%d_%H%M%S)"
    cp "$DB_FILE" "${DB_FILE}.backup_$(date +%Y%m%d_%H%M%S)"
    
    # Update config.php
    cat > "$CONFIG_FILE" << 'PHP'
<?php
// Prevent direct script access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    die('Direct access not permitted');
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'managalabhrathi');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
PHP

    # Update db.php
    cat > "$DB_FILE" << 'PHP'
<?php
// Enhanced Database Connection with Comprehensive Error Handling

class DatabaseException extends Exception {}

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                self::$connection = new mysqli(
                    DB_HOST, 
                    DB_USER, 
                    DB_PASS, 
                    DB_NAME, 
                    DB_PORT
                );

                if (self::$connection->connect_error) {
                    $error_message = sprintf(
                        "Connection failed: %s (Error Code: %d)\n" .
                        "Host: %s, User: %s, Database: %s, Port: %s",
                        self::$connection->connect_error, 
                        self::$connection->connect_errno,
                        DB_HOST,
                        DB_USER,
                        DB_NAME,
                        DB_PORT
                    );
                    
                    error_log($error_message);
                    throw new DatabaseException($error_message);
                }

                self::$connection->set_charset("utf8mb4");
            } catch (Exception $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                throw $e;
            }
        }

        return self::$connection;
    }
}

// Backward compatibility function
function get_db_connection() {
    return Database::getConnection();
}
PHP
    
    success_log "PHP configuration files updated"
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

# Main setup function
main() {
    log "Starting Comprehensive MySQL Setup"
    
    check_sudo
    install_mysql
    secure_mysql
    create_database
    update_php_config
    verify_connection
    
    success_log "MySQL setup completed successfully"
}

# Run main function
main
