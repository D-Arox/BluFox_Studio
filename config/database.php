<?php
class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [];
    
    private function __construct() {
        $this->config = [
            'host' => DB_HOST,
            'port' => DB_PORT,
            'dbname' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ];
        
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset']
            );
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            if (DEBUG_MODE) {
                error_log("Database connected successfully to " . $this->config['host']);
            }
            
        } catch (PDOException $e) {
            $error_msg = "Database connection failed: " . $e->getMessage();
            
            if (DEBUG_MODE) {
                error_log($error_msg);
                throw new Exception($error_msg);
            } else {
                error_log("Database connection failed - check configuration");
                throw new Exception("Database service temporarily unavailable");
            }
        }
    }
    
    public function getConnection() {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            
            // Log queries in debug mode
            if (DEBUG_MODE) {
                error_log("SQL Query: " . $sql . " | Params: " . json_encode($params));
            }
            
            $stmt->execute($params);
            return $stmt;
            
        } catch (PDOException $e) {
            $error_msg = "Query failed: " . $e->getMessage();
            
            if (DEBUG_MODE) {
                error_log($error_msg . " | SQL: " . $sql . " | Params: " . json_encode($params));
            } else {
                error_log("Database query error occurred");
            }
            
            throw new Exception("Database operation failed");
        }
    }

    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    public function isConnected() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getStats() {
        return [
            'host' => $this->config['host'],
            'database' => $this->config['dbname'],
            'connected' => $this->isConnected(),
            'server_info' => $this->connection->getAttribute(PDO::ATTR_SERVER_INFO),
            'client_version' => $this->connection->getAttribute(PDO::ATTR_CLIENT_VERSION)
        ];
    }
    
    // Prevent cloning and unserialization
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

function db() {
    return Database::getInstance();
}

function db_query($sql, $params = []) {
    return db()->query($sql, $params);
}

function db_fetch($sql, $params = []) {
    return db()->fetch($sql, $params);
}

function db_fetch_all($sql, $params = []) {
    return db()->fetchAll($sql, $params);
}

function db_fetch_column($sql, $params = []) {
    return db()->fetchColumn($sql, $params);
}

function db_insert($table, $data) {
    $fields = array_keys($data);
    $placeholders = ':' . implode(', :', $fields);
    $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
    
    db()->query($sql, $data);
    return db()->lastInsertId();
}

function db_update($table, $data, $where, $where_params = []) {
    $set = [];
    foreach (array_keys($data) as $field) {
        $set[] = "{$field} = :{$field}";
    }
    
    $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
    $params = array_merge($data, $where_params);
    
    return db()->query($sql, $params)->rowCount();
}

function db_delete($table, $where, $where_params = []) {
    $sql = "DELETE FROM {$table} WHERE {$where}";
    return db()->query($sql, $where_params)->rowCount();
}
?>