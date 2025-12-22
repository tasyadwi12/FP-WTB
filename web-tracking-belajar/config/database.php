<?php
/**
 * Database Configuration & Connection - FIXED
 * File: config/database.php
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika tidak ada password
define('DB_NAME', 'tracking_belajar');
define('DB_CHARSET', 'utf8mb4');

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;
    
    public $conn;
    private static $instance = null;
    
    // Singleton Pattern - Hanya 1 koneksi
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Constructor - Connect to Database
    private function __construct() {
        $this->connect();
    }
    
    // Connect Method
    private function connect() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
            
        } catch(PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }
    }
    
    // Get Connection
    public function getConnection() {
        return $this->conn;
    }
    
    // Close Connection
    public function closeConnection() {
        $this->conn = null;
    }
    
    // Test Connection
    public function testConnection() {
        if ($this->conn) {
            return true;
        }
        return false;
    }
}

// =====================================================
// DATABASE HELPER FUNCTIONS
// =====================================================

/**
 * Get Database Connection
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}

/**
 * Execute Query (SELECT) - Returns PDOStatement
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return PDOStatement|false
 */
function query($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute Query and Return Single Row (Associative Array)
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false
 */
function queryOne($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("QueryOne Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute Query and Return All Rows (Array of Associative Arrays)
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false
 */
function queryAll($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("QueryAll Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute Query (INSERT/UPDATE/DELETE)
 * @param string $sql SQL query
 * @param array $params Parameters for prepared statement
 * @return bool
 */
function execute($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch(PDOException $e) {
        error_log("Execute Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Last Insert ID
 * @return string
 */
function getLastInsertId() {
    return getDB()->lastInsertId();
}

/**
 * Begin Transaction
 * @return bool
 */
function beginTransaction() {
    return getDB()->beginTransaction();
}

/**
 * Commit Transaction
 * @return bool
 */
function commit() {
    return getDB()->commit();
}

/**
 * Rollback Transaction
 * @return bool
 */
function rollback() {
    return getDB()->rollBack();
}

/**
 * Count Rows from Query Result
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return int
 */
function countRows($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(PDOException $e) {
        error_log("CountRows Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if Record Exists
 * @param string $table Table name
 * @param string $column Column name
 * @param mixed $value Value to check
 * @return bool
 */
function recordExists($table, $column, $value) {
    try {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $result = queryOne($sql, [$value]);
        return $result && $result['count'] > 0;
    } catch(Exception $e) {
        error_log("RecordExists Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get Single Value from Query
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return mixed|null
 */
function getValue($sql, $params = []) {
    try {
        $db = getDB();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch(PDOException $e) {
        error_log("GetValue Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Insert Record (Simple)
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @return bool|string Returns last insert ID on success, false on failure
 */
function insert($table, $data) {
    try {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        if (execute($sql, array_values($data))) {
            return getLastInsertId();
        }
        return false;
    } catch(Exception $e) {
        error_log("Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update Record (Simple)
 * @param string $table Table name
 * @param array $data Associative array of column => value
 * @param string $where WHERE clause (e.g., "id = ?")
 * @param array $whereParams Parameters for WHERE clause
 * @return bool
 */
function update($table, $data, $where, $whereParams = []) {
    try {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        
        return execute($sql, $params);
    } catch(Exception $e) {
        error_log("Update Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete Record (Simple)
 * @param string $table Table name
 * @param string $where WHERE clause (e.g., "id = ?")
 * @param array $params Parameters for WHERE clause
 * @return bool
 */
function delete($table, $where, $params = []) {
    try {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return execute($sql, $params);
    } catch(Exception $e) {
        error_log("Delete Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Debug Query - For Development Only
 * @param string $sql SQL query
 * @param array $params Parameters
 * @return void
 */
function debugQuery($sql, $params = []) {
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo "<strong>SQL:</strong>\n{$sql}\n\n";
    if (!empty($params)) {
        echo "<strong>Parameters:</strong>\n";
        print_r($params);
    }
    echo "</pre>";
}

/**
 * Test Database Connection
 * @return bool
 */
function testDatabaseConnection() {
    try {
        $db = getDB();
        return $db !== null;
    } catch(Exception $e) {
        error_log("Database Connection Test Failed: " . $e->getMessage());
        return false;
    }
}
?>