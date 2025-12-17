<?php
require_once __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Controle TI</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos inline de fallback */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .main-layout { display: flex; min-height: calc(100vh - 70px); }
        .sidebar { width: 250px; background: white; border-right: 1px solid #dee2e6; padding: 20px 0; }
        .main-content { flex: 1; padding: 20px; background: #f8f9fa; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: #e6f2ff; padding: 15px 20px; border-bottom: 1px solid #dee2e6; }
        .card-body { padding: 20px; }
        .btn { display: inline-block; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #007bff; color: white; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo url('index.php'); ?>" class="logo" style="display: flex; align-items: center; text-decoration: none; color: #333;">
                    <div style="background: #007bff; color: white; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px;">
                        <i class="fas fa-server"></i>
                    </div>
                    <div>
                        <h1 style="margin: 0; font-size: 24px;">Controle <span style="color: #007bff;">TI</span></h1>
                    </div>
                </a>
                
                <div class="user-info" style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-weight: 500;">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                    </span>
                    <span style="background: #6c757d; color: white; padding: 3px 8px; border-radius: 10px; font-size: 12px;">
                        <?php echo htmlspecialchars($_SESSION['usuario_perfil']); ?>
                    </span>
                    <a href="<?php echo url('logout.php'); ?>" style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px;">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="main-layout">