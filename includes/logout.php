<?php
require_once __DIR__ . '/config.php';

// Destruir sessão
session_destroy();

// Redirecionar para login
redirect('login.php');
?>