<?php
// index.php - Dashboard do Controle TI
require_once __DIR__ . '/config.php';
require_login();

$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db_connect.php';
?>

<div class="main-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Bem-vindo ao Controle TI, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
        
        <div class="dashboard-stats">
            <?php
            // Buscar estatísticas usando funções globais
            $total_equipamentos = db_fetch_one("SELECT COUNT(*) as total FROM equipamentos")['total'] ?? 0;
            $equipamentos_ativos = db_fetch_one("SELECT COUNT(*) as total FROM equipamentos WHERE status = 'ativo'")['total'] ?? 0;
            $equipamentos_manutencao = db_fetch_one("SELECT COUNT(*) as total FROM equipamentos WHERE status = 'manutencao'")['total'] ?? 0;
            $total_localidades = db_fetch_one("SELECT COUNT(*) as total FROM localidades WHERE ativo = TRUE")['total'] ?? 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #007bff;">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_equipamentos; ?></h3>
                    <p>Equipamentos Total</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #28a745;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $equipamentos_ativos; ?></h3>
                    <p>Equipamentos Ativos</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #ffc107;">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $equipamentos_manutencao; ?></h3>
                    <p>Em Manutenção</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #17a2b8;">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_localidades; ?></h3>
                    <p>Localidades</p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-home"></i> Dashboard do Sistema</h2>
            </div>
            <div class="card-body">
                <p>Selecione uma opção no menu lateral para começar.</p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <div style="background: #e6f2ff; padding: 15px; border-radius: 5px;">
                        <h3><i class="fas fa-building"></i> Localidades</h3>
                        <p>Gerencie filiais, setores e laboratórios</p>
                        <a href="<?php echo url('paginas/localidades.php'); ?>" class="btn btn-primary">
                            Acessar
                        </a>
                    </div>
                    
                    <div style="background: #e6f2ff; padding: 15px; border-radius: 5px;">
                        <h3><i class="fas fa-desktop"></i> Equipamentos</h3>
                        <p>Controle de inventário de TI</p>
                        <a href="<?php echo url('paginas/equipamentos.php'); ?>" class="btn btn-primary">
                            Acessar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>