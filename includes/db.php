<?php
/**
 * Database Connection Management Class
 * Provides secure and flexible database connectivity
 */
class Database {
    private static ?Database $instance = null;
    private $connection = null;
    private string $host;
    private string $username;
    private string $password;
    private string $database;
    private int $port;
    private int $max_reconnect_attempts = 3;
    private int $reconnect_delay = 1;
    private float $connection_start_time = 0;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct(
        ?string $host = null, 
        ?string $username = null, 
        ?string $password = null, 
        ?string $database = null, 
        int $port = 3306
    ) {
        $this->host = $host ?? $_ENV['DB_HOST'] ?? 'localhost';
        $this->username = $username ?? $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $password ?? $_ENV['DB_PASSWORD'] ?? '';
        $this->database = $database ?? $_ENV['DB_NAME'] ?? 'managalabhrathi';
        $this->port = $port;

        $this->connect();
    }

    /**
     * Get database instance (singleton pattern)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization (must be public)
     */
    public function __wakeup() {
        // Prevent unserialization of the singleton instance
        throw new Exception("Cannot unserialize singleton Database instance");
    }

    /**
     * Check if connection is active without using query
     */
    private function isConnected(): bool {
        if ($this->connection === null) {
            return false;
        }

        try {
            if ($this->connection instanceof mysqli) {
                // Check connection status without query
                return $this->connection->connect_errno === 0 
                    && $this->connection->server_info !== null;
            } elseif ($this->connection instanceof PDO) {
                // For PDO, check if we can get an attribute
                return $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS) !== null;
            }
        } catch (Exception $e) {
            error_log("Connection check failed: " . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Configure reconnection parameters
     * @param int $max_attempts Maximum reconnection attempts
     * @param int $delay Delay between reconnection attempts
     * @return self
     */
    public function configureReconnection(int $max_attempts = 3, int $delay = 1): self {
        $this->max_reconnect_attempts = $max_attempts;
        $this->reconnect_delay = $delay;
        return $this;
    }

    /**
     * Get connection diagnostic information
     * @return array Connection details for debugging
     */
    public function getConnectionInfo(): array {
        $info = [
            'host' => $this->host,
            'database' => $this->database,
            'port' => $this->port,
            'connection_time' => $this->connection_start_time > 0 
                ? round(microtime(true) - $this->connection_start_time, 4) 
                : 0,
            'connection_type' => match(true) {
                $this->connection instanceof mysqli => 'MySQLi',
                $this->connection instanceof PDO => 'PDO',
                default => 'Not Connected'
            }
        ];

        // Add additional connection details if available
        if ($this->connection instanceof mysqli) {
            $info['thread_id'] = $this->connection->thread_id;
            $info['server_info'] = $this->connection->server_info;
        } elseif ($this->connection instanceof PDO) {
            $info['driver_name'] = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        }

        return $info;
    }

    /**
     * Establish database connection
     */
    private function connect(): void {
        $connection_start = microtime(true);
        
        try {
            error_log(sprintf(
                "Attempting database connection to %s:%d/%s at %s", 
                $this->host, 
                $this->port, 
                $this->database, 
                date('Y-m-d H:i:s')
            ));

            $this->connection = new mysqli(
                $this->host, 
                $this->username, 
                $this->password, 
                $this->database, 
                $this->port
            );

            if ($this->connection->connect_error) {
                throw new Exception("MySQLi Connection Error: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");
            
            // Record connection start time
            $this->connection_start_time = $connection_start;
            
            error_log(sprintf(
                "MySQLi connection established in %.4f seconds", 
                microtime(true) - $connection_start
            ));

        } catch (Exception $mysqli_error) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                ];

                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                
                // Record connection start time
                $this->connection_start_time = $connection_start;
                
                error_log(sprintf(
                    "PDO connection established in %.4f seconds", 
                    microtime(true) - $connection_start
                ));

            } catch (PDOException $pdo_error) {
                error_log(sprintf(
                    "Database connection failed - MySQLi: %s, PDO: %s", 
                    $mysqli_error->getMessage(), 
                    $pdo_error->getMessage()
                ));
                throw new Exception("Database connection failed", 500);
            }
        }
    }

    /**
     * Get active database connection with automatic reconnection
     * @return mysqli|PDO
     * @throws Exception
     */
    public function getConnection() {
        // Check if connection is lost
        if (!$this->isConnected()) {
            error_log("Connection lost, attempting to reconnect...");
            $this->reconnect();
        }
        return $this->connection;
    }

    /**
     * Attempt to reconnect to database
     * @throws Exception
     */
    private function reconnect(): void {
        for ($attempt = 1; $attempt <= $this->max_reconnect_attempts; $attempt++) {
            try {
                error_log("Reconnection attempt {$attempt}");
                
                // Close existing connection
                $this->closeConnection();
                
                // Attempt to reconnect
                $this->connect();
                
                // Verify connection
                if ($this->isConnected()) {
                    error_log("Reconnection successful");
                    return;
                }
            } catch (Exception $e) {
                error_log("Reconnection attempt {$attempt} failed: " . $e->getMessage());
                
                // Exponential backoff
                if ($attempt < $this->max_reconnect_attempts) {
                    sleep($this->reconnect_delay * $attempt);
                }
            }
        }
        
        // If all attempts fail
        throw new Exception("Failed to reconnect after {$this->max_reconnect_attempts} attempts");
    }

    /**
     * Close database connection safely
     */
    public function closeConnection(): void {
        try {
            if ($this->connection instanceof mysqli) {
                // Safely check and close mysqli connection
                if ($this->connection) {
                    // Use a method that doesn't rely on query
                    $connection_status = $this->connection->connect_errno === 0 
                        && $this->connection->server_info !== null;

                    if ($connection_status) {
                        // Suppress any warnings during close
                        @$this->connection->close();
                        error_log("MySQLi connection closed successfully");
                    } else {
                        error_log("MySQLi connection already closed or invalid");
                    }
                }
            } elseif ($this->connection instanceof PDO) {
                // For PDO, simply nullify the connection
                error_log("PDO connection closed");
            }
        } catch (Exception $e) {
            error_log("Error during connection closure: " . $e->getMessage());
        } finally {
            // Always set connection to null
            $this->connection = null;
        }
    }

    /**
     * Destructor ensures connection closure
     */
    public function __destruct() {
        // Only attempt to close if connection exists
        if ($this->connection !== null) {
            $this->closeConnection();
        }
    }

    /**
     * Static method for shutdown handling
     */
    public static function shutdown(): void {
        try {
            if (self::$instance !== null) {
                // Safely close connection
                if (self::$instance->connection !== null) {
                    // Use a method that avoids direct query
                    self::$instance->closeConnection();
                }
                
                // Nullify the instance
                self::$instance = null;
            }
        } catch (Throwable $e) {
            // Catch all possible errors
            error_log("Shutdown error: " . $e->getMessage());
        }
    }
}

// Backward compatibility function
function get_db_connection() {
    return Database::getInstance()->getConnection();
}

// Register shutdown handler
register_shutdown_function([Database::class, 'shutdown']);