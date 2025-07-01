<?php
// src/utils/Database.php - WORKING VERSION

class Database
{
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct()
    {
        $this->loadConfig();
        $this->connect();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadConfig()
    {
        // Direct environment variable loading
        $this->config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'election_system',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ];
    }
    
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
            // Set timezone
            $this->connection->exec("SET time_zone = '-03:00'");
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function isConnected()
    {
        try {
            if (!$this->connection) {
                return false;
            }
            $stmt = $this->connection->query('SELECT 1');
            return $stmt !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function query($sql, $params = [])
    {
        try {
            if (empty($params)) {
                return $this->connection->query($sql);
            } else {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            }
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    public function fetch($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function execute($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }
    
    public function tableExists($tableName)
    {
        try {
            // Simple and direct method
            $result = $this->connection->query("SHOW TABLES LIKE '{$tableName}'")->fetch();
            return $result !== false;
        } catch (PDOException $e) {
            error_log("Database::tableExists error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getTables()
    {
        try {
            $stmt = $this->connection->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    public function commit()
    {
        return $this->connection->commit();
    }
    
    public function rollback()
    {
        return $this->connection->rollback();
    }
    
    public function __destruct()
    {
        $this->connection = null;
    }
}