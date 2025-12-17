<?php
// ============================================
// config.php - VERSÃO CORRIGIDA (URLs CORRETAS)
// ============================================

// Verificar se já foi incluído
if (defined('CONFIG_LOADED')) {
    return;
}

define('CONFIG_LOADED', true);

// HABILITAR TODOS OS ERROS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações do sistema
define('SITE_NAME', 'Controle TI');
define('SITE_COLOR', '#007bff');
define('SITE_COLOR_LIGHT', '#e6f2ff');
date_default_timezone_set('America/Sao_Paulo');

// Configurações MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'controle_ti');

// ============================================
// URL BASE DO SISTEMA - CORRIGIDA
// ============================================

if (!defined('BASE_URL')) {
    // URL base FIXA - mais simples e confiável
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Para XAMPP no Windows, geralmente é assim:
    define('BASE_URL', $protocol . '://' . $host . '/Controle-ti');
    
    // DEBUG: Descomente para ver a URL base
    // echo "BASE_URL: " . BASE_URL . "<br>";
}

// ============================================
// FUNÇÕES DE URL - SIMPLIFICADAS E CORRETAS
// ============================================

if (!function_exists('redirect')) {
    function redirect($url) {
        // Se não começar com http, adiciona BASE_URL
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
            $url = BASE_URL . '/' . ltrim($url, '/');
        }
        header('Location: ' . $url);
        exit();
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!isset($_SESSION['usuario_id'])) {
            redirect('login.php');
        }
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] === 'admin';
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        // Se já for URL completa, retorna como está
        if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
            return $path;
        }
        
        // Remove barras duplicadas e retorna URL completa
        $path = ltrim($path, '/');
        return BASE_URL . '/' . $path;
    }
}

// A função url() deve ser UM ALIAS de asset() - não duplicar!
if (!function_exists('url')) {
    function url($path = '') {
        return asset($path);
    }
}
?>