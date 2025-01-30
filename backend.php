<?php
// backend.php
require_once 'config.php';

class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die(json_encode(['error' => $e->getMessage()]));
        }
    }
    
    public function executeQuery($query) {
        try {
            // Basic SQL injection prevention
            if (preg_match('/\b(DROP|DELETE|UPDATE|INSERT|TRUNCATE|ALTER|GRANT)\b/i', $query)) {
                throw new Exception("Write operations are not permitted");
            }
            
            $result = $this->connection->query($query);
            
            if (!$result) {
                throw new Exception($this->connection->error);
            }
            
            $data = [];
            if ($result instanceof mysqli_result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free();
            }
            
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    header('Content-Type: application/json');
    
    $db = new Database();
    $result = $db->executeQuery($_POST['query']);
    
    echo json_encode($result);
    exit;
}