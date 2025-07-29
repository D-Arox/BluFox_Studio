<?php

function db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // Fix: Use DB_PASSWORD instead of DB_PASS to match your .env file
            $host = DB_HOST;
            $dbname = DB_NAME;
            $username = DB_USER;
            $password = defined('DB_PASS') ? DB_PASS : (defined('DB_PASSWORD') ? DB_PASSWORD : '');
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Database connection attempt:");
                error_log("Host: " . $host);
                error_log("Database: " . $dbname);
                error_log("Username: " . ($username ?: 'EMPTY'));
                error_log("Password: " . ($password ? 'SET' : 'EMPTY'));
            }
            
            // Validate required parameters
            if (empty($host)) {
                throw new Exception("Database host is empty");
            }
            if (empty($dbname)) {
                throw new Exception("Database name is empty");
            }
            if (empty($username)) {
                throw new Exception("Database username is empty");
            }
            if (empty($password)) {
                throw new Exception("Database password is empty");
            }
            
            // Add port support to match your .env
            $port = defined('DB_PORT') ? DB_PORT : 3306;
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $dbname . ";charset=utf8mb4";
            
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Database connection successful!");
            }
            
        } catch (PDOException $e) {
            error_log("Database PDO error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Database general error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    return new class($pdo) {
        private $pdo;
        
        public function __construct($pdo) {
            $this->pdo = $pdo;
        }
        
        public function fetch($sql, $params = []) {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetch();
            } catch (PDOException $e) {
                error_log("Database fetch error: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
                throw $e;
            }
        }
        
        public function fetchAll($sql, $params = []) {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                error_log("Database fetchAll error: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
                throw $e;
            }
        }
        
        public function insert($table, $data) {
            try {
                $columns = implode(', ', array_keys($data));
                $placeholders = ':' . implode(', :', array_keys($data));
                $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($data);
                return $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                error_log("Database insert error: " . $e->getMessage() . " SQL: " . $sql . " Data: " . json_encode($data));
                throw $e;
            }
        }
        
        public function update($table, $data, $where, $params = []) {
            try {
                $set = [];
                foreach ($data as $key => $value) {
                    $set[] = "`{$key}` = :{$key}";
                }
                $setClause = implode(', ', $set);
                
                $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(array_merge($data, $params));
                return $stmt->rowCount();
            } catch (PDOException $e) {
                error_log("Database update error: " . $e->getMessage() . " SQL: " . $sql . " Data: " . json_encode($data) . " Params: " . json_encode($params));
                throw $e;
            }
        }
        
        public function delete($table, $where, $params = []) {
            try {
                $sql = "DELETE FROM `{$table}` WHERE {$where}";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->rowCount();
            } catch (PDOException $e) {
                error_log("Database delete error: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
                throw $e;
            }
        }
        
        public function execute($sql, $params = []) {
            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                error_log("Database execute error: " . $e->getMessage() . " SQL: " . $sql . " Params: " . json_encode($params));
                throw $e;
            }
        }
        
        // Add transaction support
        public function beginTransaction() {
            return $this->pdo->beginTransaction();
        }
        
        public function commit() {
            return $this->pdo->commit();
        }
        
        public function rollback() {
            return $this->pdo->rollback();
        }
        
        public function inTransaction() {
            return $this->pdo->inTransaction();
        }
    };
}

function testDatabaseConnection() {
    try {
        $db = db();
        $result = $db->fetch("SELECT 1 as test");
        return $result && isset($result['test']) && $result['test'] == 1;
    } catch (Exception $e) {
        error_log("Database test failed: " . $e->getMessage());
        return false;
    }
}

?>