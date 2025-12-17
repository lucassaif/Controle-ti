<?php
// ============================================
// db_connect.php - VERSÃO CORRIGIDA
// ============================================

require_once __DIR__ . '/../config.php';

class Database {
    private $conn;
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Erro de conexão MySQL: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        if (!empty($params)) {
            $types = '';
            $values = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            
            $stmt->bind_param($types, ...$values);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return [];
        
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return null;
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return false;
        
        return $stmt->insert_id;
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if (!$stmt) return false;
        
        return $stmt->affected_rows;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Criar instância global
$db = new Database();

// ============================================
// FUNÇÕES GLOBAIS (para compatibilidade)
// ============================================

function db_query($sql, $params = []) {
    global $db;
    return $db->query($sql, $params);
}

function db_fetch_all($sql, $params = []) {
    global $db;
    return $db->fetchAll($sql, $params);
}

function db_fetch_one($sql, $params = []) {
    global $db;
    return $db->fetchOne($sql, $params);
}

function db_insert($sql, $params = []) {
    global $db;
    return $db->insert($sql, $params);
}

function db_execute($sql, $params = []) {
    global $db;
    return $db->execute($sql, $params);
}
?>