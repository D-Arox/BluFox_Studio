<?php
class Database {
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;
    
    private function __construct() {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;
        
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
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            logMessage('info', 'Database connection established');
            
        } catch (PDOException $e) {
            logMessage('error', 'Database connection failed: ' . $e->getMessage());
            
            if (DEBUG_MODE) {
                throw new Exception('Database connection failed: ' . $e->getMessage());
            } else {
                throw new Exception('Database connection failed');
            }
        }
    }
    
    public function getConnection() {
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            $this->connect();
        }
        
        return $this->connection;
    }
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            
            logMessage('debug', 'SQL Query executed', ['sql' => $sql, 'params' => $params]);
            
            return $stmt;
            
        } catch (PDOException $e) {
            logMessage('error', 'SQL Query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            if (DEBUG_MODE) {
                throw new Exception('Database query failed: ' . $e->getMessage());
            } else {
                throw new Exception('Database query failed');
            }
        }
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function insert($sql, $params = []) {
        $this->execute($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    public function update($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function getTableStructure($tableName) {
        $sql = "DESCRIBE `{$tableName}`";
        return $this->fetchAll($sql);
    }
    
    public function tableExists($tableName) {
        $sql = "SHOW TABLES LIKE ?";
        $result = $this->fetchOne($sql, [$tableName]);
        return $result !== false;
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize a singleton.");
    }
}

class DatabaseMigration {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->execute($sql);
    }
    
    public function runMigration($migrationName, $sql) {
        try {
            $this->db->beginTransaction();
            
            $existing = $this->db->fetchOne(
                "SELECT id FROM migrations WHERE migration = ?", 
                [$migrationName]
            );
            
            if ($existing) {
                $this->db->rollback();
                logMessage('info', "Migration {$migrationName} already executed");
                return false;
            }
            
            $this->db->execute($sql);
            $this->db->insert(
                "INSERT INTO migrations (migration) VALUES (?)",
                [$migrationName]
            );
            
            $this->db->commit();
            logMessage('info', "Migration {$migrationName} executed successfully");
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            logMessage('error', "Migration {$migrationName} failed: " . $e->getMessage());
            throw $e;
        }
    }
}
?>