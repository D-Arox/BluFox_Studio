<?php
// Ensure config is always loaded
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection;
    private $statement;

    private function __construct() {
        try {
            // Debug output (remove after fixing)
            if (DEBUG_MODE) {
                error_log("DB Connection Debug:");
                error_log("DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED'));
                error_log("DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED'));
                error_log("DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED'));
                error_log("DB_PASS: " . (defined('DB_PASS') ? 'DEFINED' : 'NOT DEFINED'));
            }

            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (DEBUG_MODE) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
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

    public function prepare($sql) {
        try {
            $this->statement = $this->connection->prepare($sql);
            return $this;
        } catch (PDOException $e) {
            error_log("Query preparation failed: " . $e->getMessage());
            throw new Exception("Query preparation failed");
        }
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }

    public function execute($params = []) {
        try {
            if (!empty($params)) {
                return $this->statement->execute($params);
            }
            return $this->statement->execute();
        } catch (PDOException $e) {
            error_log("Query execution failed: " . $e->getMessage());
            throw new Exception("Query execution failed");
        }
    }

    public function fetch() {
        $this->execute();
        return $this->statement->fetch();
    }

    public function fetchAll() {
        $this->execute();
        return $this->statement->fetchAll();
    }

    public function rowCount() {
        return $this->statement->rowCount();
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

    public function rollback() {
        return $this->connection->rollBack();
    }

    public function select($table, $conditions = [], $columns = '*', $orderBy = '', $limit = '') {
        $sql = "SELECT $columns FROM $table";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                $whereClause[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        
        $this->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $this->bind(":$key", $value);
        }
        
        return $this->fetchAll();
    }

    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->prepare($sql);
        
        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }
        
        $this->execute();
        return $this->lastInsertId();
    }

    public function update($table, $data, $conditions) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :set_$key";
        }
        
        $whereClause = [];
        foreach ($conditions as $key => $value) {
            $whereClause[] = "$key = :where_$key";
        }
        
        $sql = "UPDATE $table SET " . implode(', ', $setClause) . " WHERE " . implode(' AND ', $whereClause);
        $this->prepare($sql);
        
        foreach ($data as $key => $value) {
            $this->bind(":set_$key", $value);
        }
        
        foreach ($conditions as $key => $value) {
            $this->bind(":where_$key", $value);
        }
        
        $this->execute();
        return $this->rowCount();
    }

    public function delete($table, $conditions) {
        $whereClause = [];
        foreach ($conditions as $key => $value) {
            $whereClause[] = "$key = :$key";
        }
        
        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClause);
        $this->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $this->bind(":$key", $value);
        }
        
        $this->execute();
        return $this->rowCount();
    }

    public function tableExists($table)  {
        $sql = "SHOW TABLES LIKE :table";
        $this->prepare($sql);
        $this->bind(':table', $table);
        $result = $this->fetch();
        return !empty($result);
    }

    public function runMigrations() {
        $migrationPath = ROOT_PATH . '/database/migrations/';
        
        if (!is_dir($migrationPath)) {
            return false;
        }
        
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        )";
        $this->connection->exec($sql);
        
        $executedMigrations = $this->select('migrations', [], 'migration');
        $executed = array_column($executedMigrations, 'migration');
        $migrationFiles = glob($migrationPath . '*.sql');

        sort($migrationFiles);
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.sql');
            
            if (!in_array($migrationName, $executed)) {
                try {
                    $sql = file_get_contents($file);
                    $this->connection->exec($sql);
                    
                    $this->insert('migrations', ['migration' => $migrationName]);
                    
                    error_log("Migration executed: $migrationName");
                } catch (Exception $e) {
                    error_log("Migration failed: $migrationName - " . $e->getMessage());
                    throw $e;
                }
            }
        }
        
        return true;
    }

    public function count($table, $conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $key => $value) {
                if (strpos($key, '!=') !== false) {
                    $field = trim(str_replace('!=', '', $key));
                    $whereClause[] = "$field != :$field";
                } else {
                    $whereClause[] = "$key = :$key";
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $this->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            if (strpos($key, '!=') !== false) {
                $field = trim(str_replace('!=', '', $key));
                $this->bind(":$field", $value);
            } else {
                $this->bind(":$key", $value);
            }
        }
        
        $result = $this->fetch();
        return (int) $result['count'];
    }

    public function raw($sql, $params = []) {
        $this->prepare($sql);
        
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        
        return $this->fetchAll();
    }

    public function rawSingle($sql, $params = []) {
        $this->prepare($sql);
        
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        
        return $this->fetch();
    }

    public function getStats() {
        $stats = [];
        
        $sql = "SELECT 
                    table_name,
                    table_rows,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = :database
                ORDER BY size_mb DESC";
        
        $this->prepare($sql);
        $this->bind(':database', DB_NAME);
        $stats['tables'] = $this->fetchAll();
        
        $sql = "SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS total_size_mb
                FROM information_schema.tables 
                WHERE table_schema = :database";
        
        $this->prepare($sql);
        $this->bind(':database', DB_NAME);
        $result = $this->fetch();
        $stats['total_size_mb'] = $result['total_size_mb'] ?? 0;
        
        return $stats;
    }

    public function cleanup() {
        try {
            $this->delete('user_sessions', ['last_activity < NOW() - INTERVAL 30 DAY']);
            $this->delete('page_views', ['created_at < NOW() - INTERVAL 90 DAY']);
            $this->delete('contact_inquiries', ['created_at < NOW() - INTERVAL 1 YEAR', 'status' => 'resolved']);
            
            return true;
        } catch (Exception $e) {
            error_log("Database cleanup failed: " . $e->getMessage());
            return false;
        }
    }
}

try {
    $db = Database::getInstance();

    // if (!isset($_SESSION['migrations_run'])) {
    //     $db->runMigrations();
    //     $_SESSION['migrations_run'] = true;
    // }
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    if (DEBUG_MODE) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

function db() {
    return Database::getInstance();
}