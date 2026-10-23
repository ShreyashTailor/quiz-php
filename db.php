<?php
require_once __DIR__ . '/config.php';

/**
 * Secure Database Connection Class
 * Uses mysqli with prepared statements to prevent SQL injection
 */
class Database {
    private static $instance = null;
    private $connection;
    private $lastError = '';

    private function __construct() {
        $this->connect();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void {
        $this->connection = @mysqli_connect(
            DB_HOST,
            DB_USERNAME,
            DB_PASSWORD,
            DB_NAME,
            DB_PORT
        );

        if (!$this->connection) {
            $this->lastError = "Database connection failed: " . mysqli_connect_error();
            error_log($this->lastError);
            throw new Exception("Database connection failed. Please check configuration.");
        }

        // Set charset to utf8mb4 for full Unicode support
        if (!mysqli_set_charset($this->connection, 'utf8mb4')) {
            error_log("Failed to set charset: " . mysqli_error($this->connection));
        }

        // Set SQL mode for strict error handling
        mysqli_query($this->connection, "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    }

    public function getConnection(): mysqli {
        // Check if connection is still alive
        if (!$this->connection || !mysqli_ping($this->connection)) {
            $this->connect();
        }
        return $this->connection;
    }

    public function getLastError(): string {
        return $this->lastError ?: mysqli_error($this->connection);
    }

    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Array of parameters to bind
     * @param string $types Optional types string (s, i, d, b)
     * @return mysqli_stmt|false
     */
    public function execute(string $sql, array $params = [], string $types = ''): mysqli_stmt|false {
        $conn = $this->getConnection();
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $this->lastError = "Prepare failed: " . mysqli_error($conn);
            error_log($this->lastError . " | SQL: " . $sql);
            return false;
        }

        if (!empty($params)) {
            if (empty($types)) {
                // Auto-detect types
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) $types .= 'i';
                    elseif (is_double($param)) $types .= 'd';
                    elseif (is_string($param)) $types .= 's';
                    else $types .= 'b';
                }
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        if (!mysqli_stmt_execute($stmt)) {
            $this->lastError = "Execute failed: " . mysqli_stmt_error($stmt);
            error_log($this->lastError . " | SQL: " . $sql);
            return false;
        }

        return $stmt;
    }

    /**
     * Execute a query and return all results as associative array
     */
    public function fetchAll(string $sql, array $params = [], string $types = ''): array {
        $stmt = $this->execute($sql, $params, $types);
        if (!$stmt) return [];
        
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }

    /**
     * Execute a query and return single row as associative array
     */
    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array {
        $stmt = $this->execute($sql, $params, $types);
        if (!$stmt) return null;
        
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        if ($result) mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }

    /**
     * Execute a query and return single column value
     */
    public function fetchColumn(string $sql, array $params = [], string $types = ''): mixed {
        $stmt = $this->execute($sql, $params, $types);
        if (!$stmt) return null;
        
        $result = mysqli_stmt_get_result($stmt);
        $value = null;
        if ($result && ($row = mysqli_fetch_row($result))) {
            $value = $row[0];
        }
        if ($result) mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        return $value;
    }

    /**
     * Execute INSERT/UPDATE/DELETE and return affected rows
     */
    public function executeNonQuery(string $sql, array $params = [], string $types = ''): int {
        $stmt = $this->execute($sql, $params, $types);
        if (!$stmt) return -1;
        
        $affected = mysqli_stmt_affected_rows($stmt);
        $insertId = mysqli_insert_id($this->connection);
        mysqli_stmt_close($stmt);
        
        // Store insert ID for later retrieval
        $this->lastInsertId = $insertId;
        return $affected;
    }

    private $lastInsertId = 0;
    public function getLastInsertId(): int {
        return $this->lastInsertId ?: mysqli_insert_id($this->connection);
    }

    public function beginTransaction(): bool {
        return mysqli_begin_transaction($this->getConnection());
    }

    public function commit(): bool {
        return mysqli_commit($this->getConnection());
    }

    public function rollback(): bool {
        return mysqli_rollback($this->getConnection());
    }

    // Prevent cloning
    private function __clone() {}
    public function __wakeup() {}

    /**
     * Build an IN clause with placeholders for prepared statements
     * 
     * @param array $values Array of values for IN clause
     * @return array ['sql' => string, 'params' => array, 'types' => string]
     */
    public function buildInClause(array $values): array {
        if (empty($values)) {
            return ['sql' => 'FALSE', 'params' => [], 'types' => ''];
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_double($value)) $types .= 'd';
            elseif (is_string($value)) $types .= 's';
            else $types .= 'b';
        }
        return ['sql' => $placeholders, 'params' => $values, 'types' => $types];
    }
}

// Backward compatibility: provide $conn variable for existing code
$db = Database::getInstance();
$conn = $db->getConnection();

// Helper function for backward compatibility
function db(): Database {
    return Database::getInstance();
}