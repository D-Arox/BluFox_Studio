<?php
class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;

    private function __construct() {
        $this->host = DB_HOST;
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASSWORD;
        $this->charset = 'utf8mb4';
        
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = DB_CONNECTION . ':host=' . $this->host . ';port=' . DB_PORT . ';dbname=' . $this->database . ';charset=' . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            if (DEBUG_MODE) {
                error_log("Database connection established successfully");
            }
            
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Database Connection Error: " . $e->getMessage());
            }
            
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            }
            throw new Exception("Database query failed");
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
?>