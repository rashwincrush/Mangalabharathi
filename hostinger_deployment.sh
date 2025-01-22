#!/bin/bash

# Hostinger Deployment Configuration
REMOTE_HOST="145.223.17.199"
REMOTE_USER="u274792269"
REMOTE_PORT="65002"
REMOTE_PATH="/htdocs/mangalabharathitrust.in"

# Local project directory
LOCAL_PROJECT_DIR="/Users/ashwin/CascadeProjects/Don't Delete/MB-Backups/16th Jan 10.30AM"

# Deployment Debugging Script
DEPLOYMENT_DEBUG_SCRIPT="<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
\$db_config = [
    'host' => 'localhost',
    'username' => 'u274792269_MB2025',
    'password' => '1q2w3e4r',
    'database' => 'u274792269_MB'
];

// Test Database Connection
try {
    \$conn = new mysqli(
        \$db_config['host'], 
        \$db_config['username'], 
        \$db_config['password'], 
        \$db_config['database']
    );

    if (\$conn->connect_error) {
        throw new Exception('Database Connection Failed: ' . \$conn->connect_error);
    }

    echo \"✅ Database Connection Successful\\n\";
    echo \"Database: \" . \$db_config['database'] . \"\\n\";
    echo \"PHP Version: \" . phpversion() . \"\\n\";

    // Check Admin User
    \$admin_check = \$conn->query(\"SELECT * FROM users WHERE role = 'admin'\");
    if (\$admin_check->num_rows > 0) {
        echo \"\\n👤 Admin Users Found:\\n\";
        while (\$user = \$admin_check->fetch_assoc()) {
            echo \"Username: \" . \$user['username'] . \"\\n\";
        }
    } else {
        echo \"\\n❌ No Admin Users Found\\n\";
    }

    \$conn->close();
} catch (Exception \$e) {
    echo \"❌ Error: \" . \$e->getMessage() . \"\\n\";
}
?>"

# Create deployment debug script
echo "$DEPLOYMENT_DEBUG_SCRIPT" > "$LOCAL_PROJECT_DIR/hostinger_deployment_debug.php"

# SSH and SCP Commands with Error Handling
set -e

# Deploy project files
echo "🚀 Starting Hostinger Deployment..."

# Copy project files
rsync -avz -e "ssh -p $REMOTE_PORT" \
    --exclude '.git' \
    --exclude 'hostinger_deployment.sh' \
    --exclude '.DS_Store' \
    "$LOCAL_PROJECT_DIR/" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"

# Deploy deployment debug script
scp -P "$REMOTE_PORT" \
    "$LOCAL_PROJECT_DIR/hostinger_deployment_debug.php" \
    "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/hostinger_deployment_debug.php"

# Execute deployment debug script
ssh -p "$REMOTE_PORT" "$REMOTE_USER@$REMOTE_HOST" << ENDSSH
cd "$REMOTE_PATH"
php hostinger_deployment_debug.php
ENDSSH

echo "✅ Deployment Completed Successfully!"
