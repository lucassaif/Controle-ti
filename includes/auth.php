<?php
require_once __DIR__ . '/db_connect.php';

class Auth {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function login($usuario, $senha) {
        $sql = "SELECT id, nome, usuario, senha, perfil, primeiro_login 
                FROM usuarios 
                WHERE usuario = ? AND ativo = TRUE";
        
        $user = $this->db->fetchOne($sql, [$usuario]);
        
        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_perfil'] = $user['perfil'];
            $_SESSION['primeiro_login'] = $user['primeiro_login'];
            
            // Registrar log - método simplificado
            $this->registrarLog($user['id'], 'Login no sistema', 'auth');
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['usuario_id'])) {
            $this->registrarLog($_SESSION['usuario_id'], 'Logout do sistema', 'auth');
        }
        
        session_destroy();
    }
    
    public function alterarSenha($usuario_id, $nova_senha) {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET senha = ?, primeiro_login = FALSE WHERE id = ?";
        
        if ($this->db->execute($sql, [$hash, $usuario_id])) {
            $this->registrarLog($usuario_id, 'Alteração de senha', 'usuarios');
            return true;
        }
        
        return false;
    }
    
    public function resetarSenha($usuario_id) {
        $senha_temp = '102030';
        $hash = password_hash($senha_temp, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET senha = ?, primeiro_login = TRUE WHERE id = ?";
        
        if ($this->db->execute($sql, [$hash, $usuario_id])) {
            $this->registrarLog($_SESSION['usuario_id'], "Resetou senha do usuário ID $usuario_id", 'usuarios');
            return true;
        }
        
        return false;
    }
    
    public function criarUsuario($nome, $usuario, $perfil) {
        $senha_temp = '102030';
        $hash = password_hash($senha_temp, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nome, usuario, senha, perfil) VALUES (?, ?, ?, ?)";
        $id = $this->db->insert($sql, [$nome, $usuario, $hash, $perfil]);
        
        if ($id) {
            $this->registrarLog($_SESSION['usuario_id'], "Criou usuário: $nome ($usuario)", 'usuarios');
            return $id;
        }
        
        return false;
    }
    
    // === MÉTODO ADICIONADO AQUI ===
    private function registrarLog($usuario_id, $acao, $modulo) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $sql = "INSERT INTO logs_sistema (usuario_id, acao, modulo, ip) VALUES (?, ?, ?, ?)";
        $this->db->execute($sql, [$usuario_id, $acao, $modulo, $ip]);
    }
    // === FIM DO MÉTODO ADICIONADO ===
}

$auth = new Auth();
?>
