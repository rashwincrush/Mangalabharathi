<?php
class EventManager {
    private $conn;
    private $upload_dir;
    private $items_per_page;
    private $is_shared_connection = false;

    public function __construct($connection = null, $items_per_page = 12) {
        // Ensure connection is open and valid
        $db_instance = Database::getInstance();
        $this->conn = $db_instance->getConnection();
        
        // Verify connection is MySQLi
        if (!($this->conn instanceof mysqli)) {
            // Attempt to get MySQLi connection
            $this->conn = new mysqli(
                $_ENV['DB_HOST'] ?? 'localhost', 
                $_ENV['DB_USERNAME'] ?? 'root', 
                $_ENV['DB_PASSWORD'] ?? '', 
                $_ENV['DB_NAME'] ?? 'managalabhrathi_trust'
            );

            if ($this->conn->connect_error) {
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }
        }
        
        // Check if this is a shared connection
        $this->is_shared_connection = true;
        
        $this->upload_dir = dirname(__DIR__) . '/uploads/events/';
        $this->items_per_page = $items_per_page;
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    // Destructor to help manage connection
    public function __destruct() {
        // No connection closure - using persistent connection
        $this->conn = null;
    }
    
    public function createEvent($data) {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['event_date'])) {
                throw new Exception("Title and Event Date are required.");
            }

            // Begin transaction
            $this->conn->begin_transaction();
            
            // Get or create category
            $category_id = $this->getOrCreateCategory($data['category'] ?? null);
            
            // Prepare event data
            $title = $this->conn->real_escape_string($data['title']);
            $description = $this->conn->real_escape_string($data['description'] ?? '');
            $event_date = $this->conn->real_escape_string($data['event_date']);
            $location = $this->conn->real_escape_string($data['location'] ?? '');
            $status = $this->conn->real_escape_string($data['status'] ?? 'upcoming');
            
            // Insert event
            $event_query = "INSERT INTO events (title, description, event_date, location, category_id, status) 
                            VALUES ('$title', '$description', '$event_date', '$location', " . 
                            ($category_id ? "'$category_id'" : "NULL") . ", '$status')";
            
            if (!$this->conn->query($event_query)) {
                throw new Exception("Error creating event: " . $this->conn->error);
            }
            
            $event_id = $this->conn->insert_id;
            
            // Handle impact metrics
            if (!empty($data['people_helped']) || !empty($data['volunteers']) || !empty($data['impact_description'])) {
                $people_helped = intval($data['people_helped'] ?? 0);
                $volunteers = intval($data['volunteers'] ?? 0);
                $impact_description = $this->conn->real_escape_string($data['impact_description'] ?? '');
                
                $metrics_query = "INSERT INTO event_impact_metrics (event_id, people_helped, volunteers, impact_description) 
                                  VALUES ($event_id, $people_helped, $volunteers, '$impact_description')";
                
                if (!$this->conn->query($metrics_query)) {
                    throw new Exception("Error saving impact metrics: " . $this->conn->error);
                }
            }
            
            // Handle media uploads
            $this->uploadEventMedia($event_id, $_FILES['media'] ?? []);
            
            // Commit transaction
            $this->conn->commit();
            
            return $event_id;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            error_log("Event creation error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getOrCreateCategory($category) {
        if (empty($category)) {
            return null;
        }
        
        // Check if category exists
        $category_escaped = $this->conn->real_escape_string($category);
        $check_query = "SELECT id FROM categories WHERE category = '$category_escaped'";
        $result = $this->conn->query($check_query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // Create new category
        $insert_query = "INSERT INTO categories (category) VALUES ('$category_escaped')";
        if ($this->conn->query($insert_query)) {
            return $this->conn->insert_id;
        }
        
        return null;
    }
    
    private function uploadEventMedia($event_id, $media) {
        if (empty($media['name'][0])) {
            return;
        }
        
        foreach ($media['name'] as $key => $name) {
            if ($media['error'][$key] == 0) {
                $tmp_name = $media['tmp_name'][$key];
                $type = $media['type'][$key];
                
                // Determine media type
                $media_type = (strpos($type, 'image') !== false) ? 'image' : 
                              ((strpos($type, 'video') !== false) ? 'video' : 'document');
                
                // Generate unique filename
                $filename = uniqid() . '_' . basename($name);
                $upload_path = $this->upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    // Insert media record
                    $media_query = "INSERT INTO event_media (event_id, media_type, media_url) 
                                    VALUES (?, ?, ?)";
                    $stmt = $this->conn->prepare($media_query);
                    $stmt->bind_param('iss', $event_id, $media_type, $filename);
                    $stmt->execute();
                }
            }
        }
    }
    
    public function updateEvent($event_id, $data) {
        try {
            $this->conn->begin_transaction();

            // Basic validation
            if (empty($data['title'])) {
                throw new Exception("Event title is required");
            }

            // Update event details
            $stmt = $this->conn->prepare("
                UPDATE events 
                SET title = ?,
                    description = ?,
                    event_date = ?,
                    location = ?,
                    status = ?
                WHERE id = ?
            ");

            // Determine status based on date
            $event_date = !empty($data['event_date']) ? new DateTime($data['event_date']) : new DateTime();
            $current_date = new DateTime();
            $status = ($event_date > $current_date) ? 'upcoming' : 'past';
            
            $stmt->bind_param("sssssi", 
                $data['title'],
                $data['description'],
                $data['event_date'],
                $data['location'],
                $status,
                $event_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to update event: " . $stmt->error);
            }

            // Handle category
            if (!empty($data['category'])) {
                $category_id = $this->getOrCreateCategory($data['category']);
                if ($category_id) {
                    $cat_stmt = $this->conn->prepare("UPDATE events SET category_id = ? WHERE id = ?");
                    $cat_stmt->bind_param("ii", $category_id, $event_id);
                    $cat_stmt->execute();
                }
            }

            // Update impact metrics - first check if metrics exist
            $metrics_check = $this->conn->prepare("SELECT id FROM event_impact_metrics WHERE event_id = ?");
            $metrics_check->bind_param("i", $event_id);
            $metrics_check->execute();
            $metrics_result = $metrics_check->get_result();

            $people_helped = isset($data['people_helped']) ? (int)$data['people_helped'] : 0;
            $volunteers = isset($data['volunteers']) ? (int)$data['volunteers'] : 0;

            if ($metrics_result->num_rows > 0) {
                // Update existing metrics
                $metrics_stmt = $this->conn->prepare("
                    UPDATE event_impact_metrics 
                    SET people_helped = ?,
                        volunteers = ?
                    WHERE event_id = ?
                ");
                $metrics_stmt->bind_param("iii", 
                    $people_helped,
                    $volunteers,
                    $event_id
                );
            } else {
                // Insert new metrics
                $metrics_stmt = $this->conn->prepare("
                    INSERT INTO event_impact_metrics 
                    (event_id, people_helped, volunteers)
                    VALUES (?, ?, ?)
                ");
                $metrics_stmt->bind_param("iii", 
                    $event_id,
                    $people_helped,
                    $volunteers
                );
            }

            if (!$metrics_stmt->execute()) {
                throw new Exception("Failed to update impact metrics: " . $metrics_stmt->error);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
    
    public function getEvent($event_id) {
        // Start comprehensive logging
        error_log("========== EventManager::getEvent Debug Start ==========");
        error_log("Attempting to retrieve event with ID: " . $event_id);
        error_log("Current connection status: " . ($this->conn ? "Connected" : "Not Connected"));
        
        // Validate input
        if (!is_numeric($event_id) || $event_id <= 0) {
            error_log("VALIDATION ERROR: Invalid event ID - " . $event_id);
            return null;
        }
        
        // Check database connection
        if (!$this->conn) {
            error_log("CONNECTION ERROR: Database connection is null");
            return null;
        }
        
        try {
            // Detailed error handling for database operations
            $this->conn->set_charset("utf8mb4");
            
            // First, verify basic event existence
            $check_query = "SELECT id, title FROM events WHERE id = ?";
            $check_stmt = $this->conn->prepare($check_query);
            
            if (!$check_stmt) {
                error_log("PREPARE ERROR (check query): " . $this->conn->error);
                error_log("Query: " . $check_query);
                return null;
            }
            
            $check_stmt->bind_param("i", $event_id);
            
            if (!$check_stmt->execute()) {
                error_log("EXECUTE ERROR (check query): " . $check_stmt->error);
                return null;
            }
            
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                error_log("EVENT NOT FOUND: No event exists with ID " . $event_id);
                return null;
            }
            
            $basic_event_data = $check_result->fetch_assoc();
            error_log("Basic Event Data: " . print_r($basic_event_data, true));
            
            // Full query with comprehensive joins
            $query = "
                SELECT 
                    e.*,
                    c.category,
                    c.id as category_id,
                    m.people_helped,
                    m.volunteers,
                    m.impact_description
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.id
                LEFT JOIN event_impact_metrics m ON e.id = m.event_id
                WHERE e.id = ?
            ";
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("PREPARE ERROR (full query): " . $this->conn->error);
                error_log("Query: " . $query);
                return null;
            }
            
            $stmt->bind_param("i", $event_id);
            
            if (!$stmt->execute()) {
                error_log("EXECUTE ERROR (full query): " . $stmt->error);
                return null;
            }
            
            $result = $stmt->get_result();
            $event = $result->fetch_assoc();
            
            if (!$event) {
                error_log("FETCH ERROR: No event details found for ID " . $event_id);
                return null;
            }
            
            // Fetch media with comprehensive error handling
            $media_query = "
                SELECT 
                    id, 
                    media_type, 
                    media_url
                FROM event_media 
                WHERE event_id = ?
            ";

            $media_stmt = $this->conn->prepare($media_query);

            if (!$media_stmt) {
                error_log("PREPARE ERROR (media query): " . $this->conn->error);
                error_log("Media Query: " . $media_query);
            } else {
                $media_stmt->bind_param("i", $event_id);
                
                if (!$media_stmt->execute()) {
                    error_log("EXECUTE ERROR (media query): " . $media_stmt->error);
                } else {
                    $media_result = $media_stmt->get_result();
                    $event['media'] = [];
                    
                    while ($media = $media_result->fetch_assoc()) {
                        $event['media'][] = $media;
                    }
                    
                    error_log("Media Count: " . count($event['media']));
                }
            }
            
            // Final logging
            error_log("EVENT DATA: " . print_r($event, true));
            error_log("========== EventManager::getEvent Debug End ==========");
            
            return $event;
            
        } catch (Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            error_log("Stack Trace: " . $e->getTraceAsString());
            return null;
        } finally {
            // Ensure statements are closed
            if (isset($check_stmt)) $check_stmt->close();
            if (isset($stmt)) $stmt->close();
            if (isset($media_stmt)) $media_stmt->close();
        }
    }
    
    public function getEvents($filter = [], $page = 1) {
        try {
            // Validate connection
            if (!($this->conn instanceof mysqli || $this->conn instanceof PDO)) {
                throw new Exception("Invalid database connection");
            }

            // Log input parameters for debugging
            error_log("EventManager::getEvents - Input Filter: " . json_encode($filter));
            error_log("EventManager::getEvents - Page: " . $page);

            // Validate and sanitize filter parameters
            $page = max(1, intval($page));
            $offset = ($page - 1) * $this->items_per_page;

            // Prepare base query with comprehensive filtering
            $base_query = "SELECT e.*, c.category as category_name 
                           FROM events e 
                           LEFT JOIN categories c ON e.category_id = c.id 
                           WHERE 1=1";
            
            // Prepare conditions and parameters
            $conditions = [];
            $param_types = '';
            $param_values = [];

            // Year filter
            if (!empty($filter['year']) && $filter['year'] !== 'all') {
                $conditions[] = "YEAR(e.event_date) = ?";
                $param_types .= 'i';
                $param_values[] = intval($filter['year']);
            }

            // Category filter
            if (!empty($filter['category']) && $filter['category'] !== 'all') {
                $conditions[] = "e.category_id = ?";
                $param_types .= 'i';
                $param_values[] = intval($filter['category']);
            }

            // Status filter
            if (!empty($filter['status']) && $filter['status'] !== 'all') {
                $conditions[] = "e.status = ?";
                $param_types .= 's';
                $param_values[] = $filter['status'];
            }

            // Search filter
            if (!empty($filter['search'])) {
                $conditions[] = "(e.title LIKE ? OR e.description LIKE ?)";
                $param_types .= 'ss';
                $search_term = '%' . $filter['search'] . '%';
                $param_values[] = $search_term;
                $param_values[] = $search_term;
            }

            // Combine conditions
            if (!empty($conditions)) {
                $base_query .= " AND " . implode(' AND ', $conditions);
            }

            // Order and pagination
            $base_query .= " ORDER BY e.event_date DESC LIMIT ? OFFSET ?";
            $param_types .= 'ii';
            $param_values[] = $this->items_per_page;
            $param_values[] = $offset;

            // Prepare and execute query based on connection type
            if ($this->conn instanceof PDO) {
                // Prepare statement
                $stmt = $this->conn->prepare($base_query);
                
                // Bind parameters
                foreach ($param_values as $key => $value) {
                    $stmt->bindValue($key + 1, $value, 
                        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
                    );
                }
                
                $stmt->execute();
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Count total records
                $count_query = preg_replace('/SELECT e\.\*.*?FROM/', 'SELECT COUNT(*) as total FROM', 
                    substr($base_query, 0, strpos($base_query, " ORDER BY")));
                
                // Remove LIMIT and OFFSET parameters
                $count_param_values = array_slice($param_values, 0, count($param_values) - 2);
                
                // Log count query for debugging
                error_log("EventManager::getEvents - Count Query: " . $count_query);
                error_log("EventManager::getEvents - Count Query Parameters: " . json_encode($count_param_values));
                
                $count_stmt = $this->conn->prepare($count_query);
                
                // Bind parameters for count query only if there are parameters
                if (!empty($count_param_values)) {
                    // Remove LIMIT and OFFSET parameters
                    $count_param_types = substr($param_types, 0, -2);
                    
                    // Dynamically create bind_param arguments for count query
                    $count_bind_params = [&$count_param_types];
                    foreach ($count_param_values as $key => &$value) {
                        $count_bind_params[] = &$count_param_values[$key];
                    }
                    
                    // Use call_user_func_array to handle dynamic parameter binding
                    call_user_func_array([$count_stmt, 'bind_param'], $count_bind_params);
                }
                
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                
                // Enhanced error logging for count query
                if ($count_result === false) {
                    error_log("EventManager::getEvents - Count Query Execution Failed: " . $count_stmt->error);
                    $total_records = 0;
                } else {
                    $count_row = $count_result->fetch_assoc();
                    error_log("EventManager::getEvents - Count Query Result: " . json_encode($count_row));
                    $total_records = $count_row['total'] ?? 0;
                }
                $count_stmt->close();

            } elseif ($this->conn instanceof mysqli) {
                // Prepare statement
                $stmt = $this->conn->prepare($base_query);
                
                // Bind parameters dynamically only if there are parameters
                if (!empty($param_values)) {
                    // Dynamically create bind_param arguments
                    $bind_params = [&$param_types];
                    foreach ($param_values as $key => &$value) {
                        $bind_params[] = &$param_values[$key];
                    }
                    
                    // Use call_user_func_array to handle dynamic parameter binding
                    call_user_func_array([$stmt, 'bind_param'], $bind_params);
                }
                
                // Execute and fetch results
                $stmt->execute();
                $result = $stmt->get_result();
                $events = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
                $stmt->close();

                // Count total records
                $count_query = preg_replace('/SELECT e\.\*.*?FROM/', 'SELECT COUNT(*) as total FROM', 
                    substr($base_query, 0, strpos($base_query, " ORDER BY")));
                
                // Remove LIMIT and OFFSET parameters
                $count_param_values = array_slice($param_values, 0, count($param_values) - 2);
                
                // Log count query for debugging
                error_log("EventManager::getEvents - Count Query: " . $count_query);
                error_log("EventManager::getEvents - Count Query Parameters: " . json_encode($count_param_values));
                
                $count_stmt = $this->conn->prepare($count_query);
                
                // Bind parameters for count query only if there are parameters
                if (!empty($count_param_values)) {
                    // Remove LIMIT and OFFSET parameters
                    $count_param_types = substr($param_types, 0, -2);
                    
                    // Dynamically create bind_param arguments for count query
                    $count_bind_params = [&$count_param_types];
                    foreach ($count_param_values as $key => &$value) {
                        $count_bind_params[] = &$count_param_values[$key];
                    }
                    
                    // Use call_user_func_array to handle dynamic parameter binding
                    call_user_func_array([$count_stmt, 'bind_param'], $count_bind_params);
                }
                
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                
                // Enhanced error logging for count query
                if ($count_result === false) {
                    error_log("EventManager::getEvents - Count Query Execution Failed: " . $count_stmt->error);
                    $total_records = 0;
                } else {
                    $count_row = $count_result->fetch_assoc();
                    error_log("EventManager::getEvents - Count Query Result: " . json_encode($count_row));
                    $total_records = $count_row['total'] ?? 0;
                }
                $count_stmt->close();

            } else {
                throw new Exception("Unsupported database connection type");
            }

            // Calculate pagination details
            $total_pages = ceil($total_records / $this->items_per_page);
            $prev_page = $page > 1 ? $page - 1 : null;
            $next_page = $page < $total_pages ? $page + 1 : null;

            // Return comprehensive result
            return [
                'events' => $events,
                'pagination' => [
                    'total_records' => $total_records,
                    'total_pages' => $total_pages,
                    'current_page' => $page,
                    'items_per_page' => $this->items_per_page,
                    'prev_page' => $prev_page,
                    'next_page' => $next_page
                ]
            ];

        } catch (Exception $e) {
            // Comprehensive error logging
            error_log("EVENT RETRIEVAL ERROR: " . $e->getMessage());
            error_log("Error Trace: " . $e->getTraceAsString());
            error_log("Filter Details: " . json_encode($filter));

            // Rethrow or return empty result
            throw $e;
        }
    }
    
    public function getAllEvents($filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($filters['category'])) {
            $where[] = "c.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $where[] = "(e.title LIKE ? OR e.description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "
            SELECT e.*, c.category
            FROM events e
            LEFT JOIN categories c ON e.category_id = c.id
            $where_clause
            ORDER BY e.event_date DESC
        ";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            if (!empty($params)) {
                $stmt->bind_param(str_repeat("s", count($params)), ...$params);
            }
            
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Exception in getAllEvents: " . $e->getMessage());
            return null;
        }
    }
    
    public function deleteEvent($event_id) {
        try {
            // Begin transaction
            $this->conn->begin_transaction();

            // Check if event exists
            $check_query = "SELECT id FROM events WHERE id = ?";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bind_param('i', $event_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows === 0) {
                throw new Exception("Event not found");
            }

            // Delete associated media files
            $media_query = "SELECT media_url FROM event_media WHERE event_id = ?";
            $media_stmt = $this->conn->prepare($media_query);
            $media_stmt->bind_param('i', $event_id);
            $media_stmt->execute();
            $media_result = $media_stmt->get_result();

            while ($row = $media_result->fetch_assoc()) {
                $file_path = $this->upload_dir . $row['media_url'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Delete associated media records
            $delete_media_query = "DELETE FROM event_media WHERE event_id = ?";
            $delete_media_stmt = $this->conn->prepare($delete_media_query);
            $delete_media_stmt->bind_param('i', $event_id);
            $delete_media_stmt->execute();

            // Attempt to delete impact metrics if table exists
            $check_metrics_table = "SHOW TABLES LIKE 'event_impact_metrics'";
            $table_result = $this->conn->query($check_metrics_table);
            
            if ($table_result->num_rows > 0) {
                $delete_metrics_query = "DELETE FROM event_impact_metrics WHERE event_id = ?";
                $delete_metrics_stmt = $this->conn->prepare($delete_metrics_query);
                $delete_metrics_stmt->bind_param('i', $event_id);
                $delete_metrics_stmt->execute();
            }

            // Delete the event
            $delete_event_query = "DELETE FROM events WHERE id = ?";
            $delete_event_stmt = $this->conn->prepare($delete_event_query);
            $delete_event_stmt->bind_param('i', $event_id);
            $delete_event_stmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollback();
            
            // Log error
            error_log("Event Deletion Error: " . $e->getMessage());
            
            return false;
        }
    }
    
    public function addEventMedia($mediaData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO event_media (event_id, media_type, media_url) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param("iss", 
                $mediaData['event_id'], 
                $mediaData['media_type'], 
                $mediaData['media_url']
            );
            
            $stmt->execute();
            return $this->conn->insert_id;
        } catch (Exception $e) {
            error_log("Error adding event media: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Diagnostic method to retrieve detailed information about events and related data
     * 
     * @return array Comprehensive diagnostic information about events
     */
    public function diagnosticEventInfo() {
        $diagnostic_info = [
            'events' => [],
            'categories' => [],
            'event_media' => []
        ];

        try {
            // Retrieve events with category name
            $events_query = "
                SELECT 
                    e.id, 
                    e.title, 
                    e.description, 
                    e.event_date, 
                    e.category_id, 
                    e.status,
                    c.category AS category_name
                FROM events e
                LEFT JOIN categories c ON e.category_id = c.id
                ORDER BY e.event_date DESC
            ";
            
            // Handle different connection types
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare($events_query);
                $stmt->execute();
                $diagnostic_info['events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($this->conn instanceof mysqli) {
                $events_result = $this->conn->query($events_query);
                if ($events_result) {
                    $diagnostic_info['events'] = $events_result->fetch_all(MYSQLI_ASSOC);
                }
            }

            // Retrieve categories
            $categories_query = "SELECT id, category, 'active' as status FROM categories";
            
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare($categories_query);
                $stmt->execute();
                $diagnostic_info['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } elseif ($this->conn instanceof mysqli) {
                $categories_result = $this->conn->query($categories_query);
                if ($categories_result) {
                    $diagnostic_info['categories'] = $categories_result->fetch_all(MYSQLI_ASSOC);
                }
            }

            // Retrieve event media with flexible column handling
            try {
                $media_query = "
                    SELECT 
                        event_id, 
                        media_url AS url, 
                        media_type, 
                        'active' as status, 
                        0 as display_order
                    FROM event_media
                ";

                if ($this->conn instanceof PDO) {
                    $stmt = $this->conn->prepare($media_query);
                    $stmt->execute();
                    $diagnostic_info['event_media'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($this->conn instanceof mysqli) {
                    $media_result = $this->conn->query($media_query);
                    if ($media_result) {
                        $diagnostic_info['event_media'] = $media_result->fetch_all(MYSQLI_ASSOC);
                    }
                }
            } catch (Exception $e) {
                error_log("Event Media Query Error: " . $e->getMessage());
                $diagnostic_info['event_media'] = [];
            }

            // Additional diagnostic information
            $diagnostic_info['total_events'] = count($diagnostic_info['events']);
            $diagnostic_info['total_categories'] = count($diagnostic_info['categories']);
            $diagnostic_info['total_media'] = count($diagnostic_info['event_media']);

            return $diagnostic_info;

        } catch (Exception $e) {
            error_log("Diagnostic Event Info Error: " . $e->getMessage());
            return [
                'events' => [],
                'categories' => [],
                'event_media' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    public function getYears(): array {
        try {
            $query = "SELECT DISTINCT YEAR(event_date) as year FROM events ORDER BY year DESC";
            
            // Handle different connection types
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
                return $result;
            } elseif ($this->conn instanceof mysqli) {
                $result = $this->conn->query($query);
                $years = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $years[] = $row['year'];
                    }
                }
                return $years;
            }
            
            return [];
        } catch (Exception $e) {
            error_log("Error retrieving years: " . $e->getMessage());
            return [];
        }
    }

    public function getCategories(): array {
        try {
            $query = "SELECT id, category FROM categories ORDER BY category";
            
            // Handle different connection types
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                return $result ?: $this->getFallbackCategories();
            } elseif ($this->conn instanceof mysqli) {
                $result = $this->conn->query($query);
                $categories = [];
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $categories[$row['id']] = $row['category'];
                    }
                }
                
                return !empty($categories) ? $categories : $this->getFallbackCategories();
            }
            
            return $this->getFallbackCategories();
        } catch (Exception $e) {
            error_log("Error retrieving categories: " . $e->getMessage());
            return $this->getFallbackCategories();
        }
    }

    /**
     * Fallback method to return default categories
     * 
     * @return array
     */
    private function getFallbackCategories(): array {
        return [
            'education' => 'Education', 
            'health' => 'Health', 
            'community' => 'Community', 
            'environment' => 'Environment'
        ];
    }

    public function getEventDetails(int $event_id): ?array {
        try {
            $query = "
                SELECT 
                    e.*,
                    DATE_FORMAT(e.event_date, '%Y') as year,
                    DATE_FORMAT(e.event_date, '%b') as month,
                    DATE_FORMAT(e.event_date, '%d') as day,
                    DATE_FORMAT(e.event_date, '%h:%i %p') as time,
                    (SELECT GROUP_CONCAT(image_url) FROM event_images WHERE event_id = e.id) as images,
                    (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendee_count,
                    (SELECT GROUP_CONCAT(CONCAT(name, ':', role) SEPARATOR '|') 
                     FROM event_organizers WHERE event_id = e.id) as organizers,
                    (SELECT COUNT(*) FROM event_comments WHERE event_id = e.id) as comment_count
                FROM events e
                WHERE e.id = ?";
                
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $event_id);
            $stmt->execute();
            
            $event = $stmt->get_result()->fetch_assoc();
            
            if ($event) {
                $event['images'] = $event['images'] ? explode(',', $event['images']) : [];
                $event['is_upcoming'] = strtotime($event['event_date']) > time();
                
                if ($event['organizers']) {
                    $organizers = [];
                    foreach (explode('|', $event['organizers']) as $organizer) {
                        list($name, $role) = explode(':', $organizer);
                        $organizers[] = ['name' => $name, 'role' => $role];
                    }
                    $event['organizers'] = $organizers;
                } else {
                    $event['organizers'] = [];
                }
            }
            
            return $event;
            
        } catch (Exception $e) {
            error_log("Error fetching event details: " . $e->getMessage());
            return null;
        }
    }
}
