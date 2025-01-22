-- Update team_members table schema
-- Check and rename name column to full_name
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'managalabhrathi' 
               AND TABLE_NAME = 'team_members' 
               AND COLUMN_NAME = 'full_name');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE team_members CHANGE COLUMN name full_name VARCHAR(255) NOT NULL', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add linkedin_url column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'managalabhrathi' 
               AND TABLE_NAME = 'team_members' 
               AND COLUMN_NAME = 'linkedin_url');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE team_members ADD COLUMN linkedin_url VARCHAR(500) DEFAULT NULL', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add status column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'managalabhrathi' 
               AND TABLE_NAME = 'team_members' 
               AND COLUMN_NAME = 'status');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE team_members ADD COLUMN status ENUM("active", "inactive") DEFAULT "active"', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check and add updated_at column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = 'managalabhrathi' 
               AND TABLE_NAME = 'team_members' 
               AND COLUMN_NAME = 'updated_at');

SET @sql = IF(@exist = 0, 
    'ALTER TABLE team_members ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 
    'SELECT 1');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing records
UPDATE team_members 
SET 
    linkedin_url = COALESCE(linkedin_url, CONCAT('https://linkedin.com/in/', LOWER(REPLACE(full_name, ' ', '-')))),
    status = COALESCE(status, 'active'),
    display_order = COALESCE(display_order, id)
WHERE linkedin_url IS NULL;

-- Verify changes
DESCRIBE team_members;
