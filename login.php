<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db_connect.php';

// Se já estiver logado, redireciona para dashboard
if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Buscar usuário usando a função global
    $sql = "SELECT id, nome, usuario, senha, perfil, primeiro_login 
            FROM usuarios 
            WHERE usuario = ? AND ativo = TRUE";
    
    $user = db_fetch_one($sql, [$usuario]);
    
    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_perfil'] = $user['perfil'];
        $_SESSION['primeiro_login'] = $user['primeiro_login'];
        
        // DEBUG: Verificar se redireciona corretamente
        // echo "Login OK! Redirecionando...";
        // exit();
        
        redirect('index.php');
        
    } else {
        $erro = 'Usuário ou senha inválidos';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle TI</title>
    <link rel="stylesheet" href="<?php echo url('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-server"></i> Controle TI</h1>
                <p>Sistema de Gestão de Ativos e Processos</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="usuario">Usuário</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" 
                           value="admin" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" 
                           value="D@13m@04a@2010" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                <p><strong>Credenciais padrão:</strong></p>
                <p>Usuário: <code>admin</code></p>
                <p>Senha: <code>D@13m@04a@2010</code></p>
            </div>
        </div>
    </div>
</body>
</html>