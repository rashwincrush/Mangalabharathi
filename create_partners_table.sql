USE mangalabharathi;

CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo_url VARCHAR(255),
    website_url VARCHAR(255),
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample data if the table is empty
INSERT INTO partners (name, description, logo_url, website_url, display_order, status)
SELECT * FROM (
    SELECT 'Sample Partner 1', 'A great organization', '/uploads/partners/logo1.png', 'https://example1.com', 1, 'active'
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM partners
) LIMIT 1;

INSERT INTO partners (name, description, logo_url, website_url, display_order, status)
SELECT * FROM (
    SELECT 'Sample Partner 2', 'Another amazing organization', '/uploads/partners/logo2.png', 'https://example2.com', 2, 'active'
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM partners LIMIT 1
) LIMIT 1;
