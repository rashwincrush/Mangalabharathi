# Backup and Restore Best Practices

## Database Backup Checklist

### Pre-Backup Preparation
1. **Database Schema Consistency**
   - Export complete database schema
   - Include:
     - Table structures
     - Stored procedures
     - Triggers
     - Views
     - Functions

2. **Backup Script Template**
```bash
#!/bin/bash
# Comprehensive MySQL Backup Script

# Timestamp for unique backup
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/path/to/backups"
DB_NAME="your_database_name"
DB_USER="your_database_user"

# Backup Schema
mysqldump -u $DB_USER -p \
    --no-data \
    --add-drop-table \
    --routines \
    --triggers \
    $DB_NAME > $BACKUP_DIR/schema_$TIMESTAMP.sql

# Backup Data
mysqldump -u $DB_USER -p \
    --no-create-info \
    --complete-insert \
    --extended-insert \
    $DB_NAME > $BACKUP_DIR/data_$TIMESTAMP.sql
```

### Restore Procedure Checklist
1. **Pre-Restore Validation**
   - Verify backup file integrity
   - Check database user permissions
   - Ensure compatible MySQL versions

2. **Restore Script Template**
```bash
#!/bin/bash
# Comprehensive Database Restore Script

DB_NAME="your_database_name"
DB_USER="your_database_user"
BACKUP_FILE="/path/to/backup/schema.sql"
DATA_FILE="/path/to/backup/data.sql"

# Drop existing database
mysql -u $DB_USER -p -e "DROP DATABASE IF EXISTS $DB_NAME"

# Create fresh database
mysql -u $DB_USER -p -e "CREATE DATABASE $DB_NAME"

# Restore Schema
mysql -u $DB_USER -p $DB_NAME < $BACKUP_FILE

# Restore Data
mysql -u $DB_USER -p $DB_NAME < $DATA_FILE
```

## Configuration Management

### Key Configuration Files to Backup
1. `config.php`
2. `.env` files
3. Database connection files
4. Security configuration
5. Environment-specific settings

### Configuration Restore Best Practices
- Use environment-specific templates
- Never hardcode sensitive information
- Use `.env` files for environment variables
- Implement config validation on restore

## CSRF Token Management
1. **Regenerate Tokens**
   - Always regenerate CSRF tokens after restore
   - Implement a token rotation mechanism

2. **CSRF Token Generation Function**
```php
function regenerate_csrf_token() {
    // Securely generate a new token
    $token = bin2hex(random_bytes(32));
    
    // Store in session
    $_SESSION['csrf_token'] = $token;
    
    // Optional: Log token regeneration
    error_log("CSRF Token Regenerated: " . date('Y-m-d H:i:s'));
    
    return $token;
}
```

## Consistent Styling Restoration
1. **CSS Variables Backup**
   - Backup `variables.css`
   - Create a version-controlled CSS variables file

2. **Styling Restoration Checklist**
   - Verify CSS file paths
   - Check for missing font files
   - Validate external library versions
   - Ensure responsive design integrity

## Database Connection Reliability
1. **Connection Error Handling**
```php
function establish_database_connection() {
    try {
        $conn = new PDO(
            "mysql:host={$CONFIG['DB_HOST']};dbname={$CONFIG['DB_NAME']}",
            $CONFIG['DB_USER'], 
            $CONFIG['DB_PASS']
        );
        
        // Set error mode and exception handling
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Additional connection validations
        $conn->query("SELECT 1");
        
        return $conn;
    } catch (PDOException $e) {
        // Detailed logging
        error_log("DB Connection Failed: " . $e->getMessage());
        
        // Graceful error handling
        die("Database connection could not be established.");
    }
}
```

## Automated Validation Script
```php
function validate_system_after_restore() {
    $checks = [
        'database_connection' => function() {
            return establish_database_connection() !== null;
        },
        'csrf_token_valid' => function() {
            return regenerate_csrf_token() !== null;
        },
        'css_variables_exist' => function() {
            return file_exists('/path/to/variables.css');
        },
        'required_tables_exist' => function() {
            // Check critical tables
            $requiredTables = ['users', 'events', 'donations'];
            // Implement table existence check
        }
    ];

    $results = [];
    foreach ($checks as $name => $check) {
        $results[$name] = $check();
    }

    return $results;
}
```

## Recommended Backup Workflow
1. Use version control (Git)
2. Implement database migrations
3. Use environment-specific configurations
4. Automate backup and validation processes
5. Implement comprehensive logging

## Security Recommendations
- Encrypt backup files
- Use secure, limited-access backup directories
- Implement backup rotation
- Use strong, unique passwords
- Regularly audit backup and restore processes
