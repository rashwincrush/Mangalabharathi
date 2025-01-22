<?php
// Use existing connection from setup.php
if (!isset($conn)) {
    die("Database connection not available");
}

try {
    // Drop existing tables in the correct order (child tables first)
    $drop_tables = [
        "DROP TABLE IF EXISTS event_media",
        "DROP TABLE IF EXISTS past_events",
        "DROP TABLE IF EXISTS team_members",
        "DROP TABLE IF EXISTS events",
        "DROP TABLE IF EXISTS categories",
        "DROP TABLE IF EXISTS team",
        "DROP TABLE IF EXISTS donations",
        "DROP TABLE IF EXISTS partners"
    ];

    foreach ($drop_tables as $drop_query) {
        $conn->query($drop_query);
    }

    // Create tables array
    $tables = [];

    // Categories table
    $tables[] = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(100) NOT NULL UNIQUE,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_display_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Events table
    $tables[] = "CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        location VARCHAR(255),
        category_id INT,
        status ENUM('draft', 'published', 'past', 'completed') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        INDEX idx_event_date (event_date),
        INDEX idx_status (status),
        INDEX idx_category_id (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Event Media table
    $tables[] = "CREATE TABLE IF NOT EXISTS event_media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        type ENUM('image', 'video') DEFAULT 'image',
        url VARCHAR(255) NOT NULL,
        caption TEXT,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        INDEX idx_event_id (event_id),
        INDEX idx_type (type),
        INDEX idx_display_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Past Events table
    $tables[] = "CREATE TABLE IF NOT EXISTS past_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        people_helped INT DEFAULT 0,
        volunteers INT DEFAULT 0,
        budget DECIMAL(10,2) DEFAULT 0.00,
        impact_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        INDEX idx_event_id (event_id),
        CONSTRAINT chk_positive_numbers CHECK (
            people_helped >= 0 AND
            volunteers >= 0 AND
            budget >= 0
        )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Team table
    $tables[] = "CREATE TABLE IF NOT EXISTS team (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        position VARCHAR(255) NOT NULL,
        bio TEXT,
        image_url VARCHAR(255),
        display_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_display_order (display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Team members table
    $tables[] = "CREATE TABLE IF NOT EXISTS team_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        position VARCHAR(255),
        bio TEXT,
        image_url VARCHAR(255),
        email VARCHAR(255),
        social_links JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Donations table
    $tables[] = "CREATE TABLE IF NOT EXISTS donations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_name VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        donation_date DATE NOT NULL,
        purpose VARCHAR(255),
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        payment_method VARCHAR(50),
        transaction_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_donation_date (donation_date),
        INDEX idx_status (status),
        INDEX idx_transaction_id (transaction_id),
        CONSTRAINT chk_positive_amount CHECK (amount > 0)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Partners table
    $tables[] = "CREATE TABLE IF NOT EXISTS partners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        logo_url VARCHAR(255),
        partnership_date DATE,
        description TEXT,
        website_url VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_display_order (display_order),
        INDEX idx_partnership_date (partnership_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Create tables
    foreach ($tables as $table_query) {
        if (!$conn->query($table_query)) {
            throw new Exception("Error creating table: " . $conn->error . "\nQuery: " . $table_query);
        }
    }

    // Add sample categories if table is empty
    $category_count = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
    if ($category_count == 0) {
        $sample_categories = [
            ['category' => 'Food Distribution', 'display_order' => 1],
            ['category' => 'Medical Camp', 'display_order' => 2],
            ['category' => 'Education', 'display_order' => 3],
            ['category' => 'Community Outreach', 'display_order' => 4],
            ['category' => 'Fundraising', 'display_order' => 5],
            ['category' => 'Healthcare', 'display_order' => 6]
        ];

        $stmt = $conn->prepare("INSERT INTO categories (category, display_order) VALUES (?, ?)");
        foreach ($sample_categories as $category) {
            $stmt->bind_param("si", $category['category'], $category['display_order']);
            $stmt->execute();
        }
        $stmt->close();
    }

    // Add sample events if table is empty
    $event_count = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
    if ($event_count == 0) {
        $sample_events = [
            [
                'title' => 'Annual Charity Drive 2024',
                'description' => 'Our annual charity drive was a huge success! We collected donations for underprivileged children and distributed educational materials.',
                'event_date' => '2024-03-15',
                'location' => 'Bangalore',
                'category_id' => 5,
                'status' => 'draft'
            ],
            [
                'title' => 'Educational Workshop',
                'description' => 'A workshop focused on teaching basic computer skills to underprivileged children.',
                'event_date' => '2024-04-20',
                'location' => 'Mysore',
                'category_id' => 3,
                'status' => 'published'
            ],
            [
                'title' => 'Health Camp 2024',
                'description' => 'Free health checkup camp for senior citizens.',
                'event_date' => '2024-05-10',
                'location' => 'Bangalore',
                'category_id' => 6,
                'status' => 'published'
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, category_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sample_events as $event) {
            $stmt->bind_param("ssssss", 
                $event['title'],
                $event['description'],
                $event['event_date'],
                $event['location'],
                $event['category_id'],
                $event['status']
            );
            $stmt->execute();
        }
        $stmt->close();
    }

    // Add sample donations if table is empty
    $donation_count = $conn->query("SELECT COUNT(*) as count FROM donations")->fetch_assoc()['count'];
    if ($donation_count == 0) {
        $sample_donations = [
            [
                'donor_name' => 'John Doe',
                'amount' => 1000.00,
                'donation_date' => '2024-01-01',
                'purpose' => 'Education Support',
                'status' => 'completed',
                'payment_method' => 'UPI',
                'transaction_id' => 'TXN123456'
            ],
            [
                'donor_name' => 'Jane Smith',
                'amount' => 5000.00,
                'donation_date' => '2024-01-05',
                'purpose' => 'Medical Camp',
                'status' => 'completed',
                'payment_method' => 'Bank Transfer',
                'transaction_id' => 'TXN789012'
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO donations (donor_name, amount, donation_date, purpose, status, payment_method, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($sample_donations as $donation) {
            $stmt->bind_param("sdsssss", 
                $donation['donor_name'],
                $donation['amount'],
                $donation['donation_date'],
                $donation['purpose'],
                $donation['status'],
                $donation['payment_method'],
                $donation['transaction_id']
            );
            $stmt->execute();
        }
        $stmt->close();
    }

    // Add sample partners if table is empty
    $partner_count = $conn->query("SELECT COUNT(*) as count FROM partners")->fetch_assoc()['count'];
    if ($partner_count == 0) {
        $sample_partners = [
            [
                'name' => 'ABC Foundation',
                'partnership_date' => '2023-01-01',
                'description' => 'A leading foundation working in education sector.',
                'website_url' => 'https://www.abcfoundation.org',
                'status' => 'active',
                'display_order' => 1
            ],
            [
                'name' => 'XYZ Healthcare',
                'partnership_date' => '2023-02-01',
                'description' => 'Healthcare provider supporting our medical camps.',
                'website_url' => 'https://www.xyzhealthcare.org',
                'status' => 'active',
                'display_order' => 2
            ]
        ];

        $stmt = $conn->prepare("INSERT INTO partners (name, partnership_date, description, website_url, status, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sample_partners as $partner) {
            $stmt->bind_param("sssssi",
                $partner['name'],
                $partner['partnership_date'],
                $partner['description'],
                $partner['website_url'],
                $partner['status'],
                $partner['display_order']
            );
            $stmt->execute();
        }
        $stmt->close();
    }

    echo "Database tables created successfully with sample data.\n";

} catch (Exception $e) {
    error_log("Error setting up database tables: " . $e->getMessage());
    throw new Exception("Error setting up database tables: " . $e->getMessage());
}
