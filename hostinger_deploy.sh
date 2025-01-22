#!/bin/bash

# Hostinger Deployment Script

# Deployment Configuration
REMOTE_HOST="145.223.17.199"
REMOTE_USER="u274792269"
REMOTE_PORT="65002"
REMOTE_PATH="/htdocs/mangalabharathitrust.in"

# Local scripts to deploy
LOCAL_DEBUG_SCRIPT="/Users/ashwin/CascadeProjects/Don't Delete/MB-Backups/16th Jan 10.30AM/login_troubleshoot.php"
LOCAL_ADMIN_DEBUG="/Users/ashwin/CascadeProjects/Don't Delete/MB-Backups/16th Jan 10.30AM/admin_login_debug.php"

# Deploy debug scripts
scp -P $REMOTE_PORT -i /Users/ashwin/.ssh/hostinger_key "$LOCAL_DEBUG_SCRIPT" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/login_troubleshoot.php"
scp -P $REMOTE_PORT -i /Users/ashwin/.ssh/hostinger_key "$LOCAL_ADMIN_DEBUG" "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/admin_login_debug.php"

# Execute debug scripts remotely
ssh -p $REMOTE_PORT -i /Users/ashwin/.ssh/hostinger_key "$REMOTE_USER@$REMOTE_HOST" << ENDSSH
cd $REMOTE_PATH
php login_troubleshoot.php
php admin_login_debug.php
ENDSSH

echo "Deployment and debugging complete."
