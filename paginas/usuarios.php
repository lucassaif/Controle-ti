<?php
$page_title = 'Usuários';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Verificar se é administrador
if (!is_admin()) {
    echo '<div class="alert alert-danger" style="margin: 20px;">';
    echo '<i class="fas fa-ban"></i> Acesso restrito. Apenas administradores podem gerenciar usuários.';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CRIAR NOVO USUÁRIO
    if (isset($_POST['criar_usuario'])) {
        $nome = trim($_POST['nome']);
        $usuario = trim($_POST['usuario']);
        $perfil = $_POST['perfil'];
        $senha_temporaria = '102030'; // Senha padrão
        
        // Verificar se usuário já existe
        $existe = db_fetch_one("SELECT id FROM usuarios WHERE usuario = ?", [$usuario]);
        
        if ($existe) {
            $erro = "Usuário '{$usuario}' já existe!";
        } else {
            $hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, usuario, senha, perfil, primeiro_login) VALUES (?, ?, ?, ?, TRUE)";
            $usuario_id = db_insert($sql, [$nome, $usuario, $hash, $perfil]);
            
            if ($usuario_id) {
                // Registrar log
                db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                          [$_SESSION['usuario_id'], "Criou usuário: {$nome} ({$usuario})", 'usuarios']);
                
                header('Location: ' . url('paginas/usuarios.php?sucesso=1&id=' . $usuario_id));
                exit;
            } else {
                $erro = "Erro ao criar usuário. Tente novamente.";
            }
        }
    }
    
    // RESETAR SENHA
    if (isset($_POST['resetar_senha'])) {
        $usuario_id = $_POST['usuario_id'];
        $senha_temporaria = '102030';
        $hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
        
        $sql = "UPDATE usuarios SET senha = ?, primeiro_login = TRUE WHERE id = ?";
        if (db_execute($sql, [$hash, $usuario_id])) {
            // Registrar log
            db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                      [$_SESSION['usuario_id'], "Resetou senha do usuário ID {$usuario_id}", 'usuarios']);
            
            header('Location: ' . url('paginas/usuarios.php?sucesso=2&id=' . $usuario_id));
            exit;
        }
    }
    
    // ALTERAR STATUS (ativo/inativo)
    if (isset($_POST['alterar_status'])) {
        $usuario_id = $_POST['usuario_id'];
        $novo_status = $_POST['novo_status'] == 'ativo' ? 1 : 0;
        
        $sql = "UPDATE usuarios SET ativo = ? WHERE id = ?";
        if (db_execute($sql, [$novo_status, $usuario_id])) {
            $status_text = $novo_status ? 'ativado' : 'desativado';
            // Registrar log
            db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                      [$_SESSION['usuario_id'], "{$status_text} usuário ID {$usuario_id}", 'usuarios']);
            
            header('Location: ' . url('paginas/usuarios.php?sucesso=3&id=' . $usuario_id));
            exit;
        }
    }
    
    // ALTERAR PERFIL
    if (isset($_POST['alterar_perfil'])) {
        $usuario_id = $_POST['usuario_id'];
        $novo_perfil = $_POST['novo_perfil'];
        
        $sql = "UPDATE usuarios SET perfil = ? WHERE id = ?";
        if (db_execute($sql, [$novo_perfil, $usuario_id])) {
            // Registrar log
            db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                      [$_SESSION['usuario_id'], "Alterou perfil do usuário ID {$usuario_id} para {$novo_perfil}", 'usuarios']);
            
            header('Location: ' . url('paginas/usuarios.php?sucesso=4&id=' . $usuario_id));
            exit;
        }
    }
    
    // EXCLUIR USUÁRIO (apenas se não for o próprio admin)
    if (isset($_POST['excluir_usuario'])) {
        $usuario_id = $_POST['usuario_id'];
        
        // Impedir exclusão do próprio usuário
        if ($usuario_id == $_SESSION['usuario_id']) {
            $erro = "Você não pode excluir sua própria conta!";
        } else {
            $sql = "DELETE FROM usuarios WHERE id = ?";
            if (db_execute($sql, [$usuario_id])) {
                // Registrar log
                db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                          [$_SESSION['usuario_id'], "Excluiu usuário ID {$usuario_id}", 'usuarios']);
                
                header('Location: ' . url('paginas/usuarios.php?sucesso=5'));
                exit;
            }
        }
    }
}

// Buscar usuários
$usuarios = db_fetch_all("SELECT * FROM usuarios ORDER BY nome");

// Buscar estatísticas
$estatisticas = db_fetch_one("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN ativo = TRUE THEN 1 END) as ativos,
        COUNT(CASE WHEN perfil = 'admin' THEN 1 END) as admins,
        COUNT(CASE WHEN perfil = 'tecnico' THEN 1 END) as tecnicos,
        COUNT(CASE WHEN perfil = 'coordenador' THEN 1 END) as coordenadores,
        COUNT(CASE WHEN primeiro_login = TRUE THEN 1 END) as primeiro_login
    FROM usuarios
");

// Buscar logs recentes de usuários
$logs_recentes = db_fetch_all("
    SELECT l.*, u.nome as usuario_nome 
    FROM logs_sistema l
    JOIN usuarios u ON l.usuario_id = u.id
    WHERE l.modulo = 'usuarios'
    ORDER BY l.data_registro DESC
    LIMIT 10
");

// Verificar se está visualizando um usuário específico
$visualizar_id = $_GET['id'] ?? 0;
$usuario_detalhes = null;
$atividades_usuario = [];

if ($visualizar_id) {
    $usuario_detalhes = db_fetch_one("SELECT * FROM usuarios WHERE id = ?", [$visualizar_id]);
    if ($usuario_detalhes) {
        $atividades_usuario = db_fetch_all("
            SELECT * FROM logs_sistema 
            WHERE usuario_id = ? 
            ORDER BY data_registro DESC 
            LIMIT 20
        ", [$visualizar_id]);
    }
}
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                <?php 
                $mensagens = [
                    1 => '✅ Usuário criado com sucesso! Senha temporária: <strong>102030</strong>',
                    2 => '✅ Senha resetada com sucesso! Nova senha temporária: <strong>102030</strong>',
                    3 => '✅ Status do usuário alterado com sucesso!',
                    4 => '✅ Perfil do usuário alterado com sucesso!',
                    5 => '✅ Usuário excluído com sucesso!'
                ];
                echo $mensagens[$_GET['sucesso']] ?? 'Operação realizada com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <!-- CARD: ESTATÍSTICAS -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chart-bar"></i> Estatísticas do Sistema</h2>
            </div>
            <div class="card-body">
                <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #007bff;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $estatisticas['total']; ?></h3>
                            <p>Total de Usuários</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #28a745;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $estatisticas['ativos']; ?></h3>
                            <p>Usuários Ativos</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ffc107;">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $estatisticas['primeiro_login']; ?></h3>
                            <p>Primeiro Login Pendente</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #17a2b8;">
                            <i class="fas fa-user-tag"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $estatisticas['admins']; ?></h3>
                            <p>Administradores</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- CARD: CRIAR NOVO USUÁRIO -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-user-plus"></i> Criar Novo Usuário</h2>
                </div>
                <div class="card-body">
                    <form method="POST" id="formCriarUsuario">
                        <div class="form-group">
                            <label for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" class="form-control" required 
                                   placeholder="Ex: João da Silva">
                        </div>
                        
                        <div class="form-group">
                            <label for="usuario">Nome de Usuário *</label>
                            <input type="text" id="usuario" name="usuario" class="form-control" required 
                                   placeholder="Ex: joao.silva (sem espaços ou acentos)">
                            <small class="text-muted">Será usado para login no sistema</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="perfil">Perfil de Acesso *</label>
                            <select id="perfil" name="perfil" class="form-control" required>
                                <option value="">Selecione um perfil...</option>
                                <option value="tecnico">Técnico</option>
                                <option value="coordenador">Coordenador</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <small class="text-muted">
                                <strong>Técnico:</strong> Executa checklists e processos<br>
                                <strong>Coordenador:</strong> Gerencia localidades e equipamentos<br>
                                <strong>Administrador:</strong> Acesso total ao sistema
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Senha inicial:</strong> 102030<br>
                            O usuário será obrigado a alterar a senha no primeiro login.
                        </div>
                        
                        <button type="submit" name="criar_usuario" class="btn btn-primary">
                            <i class="fas fa-save"></i> Criar Usuário
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- CARD: LISTA DE USUÁRIOS -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users-cog"></i> Gerenciar Usuários</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($usuarios)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhum usuário cadastrado.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Usuário</th>
                                        <th>Perfil</th>
                                        <th>Status</th>
                                        <th>Último Acesso</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): 
                                        $perfil_class = [
                                            'admin' => 'badge-admin',
                                            'tecnico' => 'badge-tecnico',
                                            'coordenador' => 'badge-coordenador'
                                        ][$user['perfil']] ?? '';
                                        
                                        $status_class = $user['ativo'] ? 'status-ativo' : 'status-inativo';
                                        $status_text = $user['ativo'] ? 'Ativo' : 'Inativo';
                                        
                                        // Buscar último log
                                        $ultimo_log = db_fetch_one("
                                            SELECT data_registro 
                                            FROM logs_sistema 
                                            WHERE usuario_id = ? AND acao LIKE '%Login%'
                                            ORDER BY data_registro DESC 
                                            LIMIT 1
                                        ", [$user['id']]);
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="profile-avatar" style="width: 30px; height: 30px; font-size: 14px;">
                                                    <?php echo strtoupper(substr($user['nome'], 0, 1)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($user['nome']); ?></span>
                                                <?php if ($user['primeiro_login']): ?>
                                                    <span class="badge badge-warning" title="Primeiro login pendente">
                                                        <i class="fas fa-key"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($user['usuario']); ?></code></td>
                                        <td>
                                            <span class="badge-perfil <?php echo $perfil_class; ?>">
                                                <?php echo ucfirst($user['perfil']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="<?php echo $status_class; ?>"></span>
                                            <?php echo $status_text; ?>
                                        </td>
                                        <td>
                                            <?php if ($ultimo_log && $ultimo_log['data_registro']): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($ultimo_log['data_registro'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca acessou</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <button type="button" class="btn btn-sm btn-info" 
                                                        onclick="visualizarUsuario(<?php echo $user['id']; ?>)"
                                                        title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="resetarSenha(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nome']); ?>')"
                                                        title="Resetar Senha">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                
                                                <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                                <button type="button" class="btn btn-sm btn-secondary" 
                                                        onclick="alterarStatus(<?php echo $user['id']; ?>, '<?php echo $user['ativo'] ? 'ativo' : 'inativo'; ?>', '<?php echo addslashes($user['nome']); ?>')"
                                                        title="Alterar Status">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        onclick="alterarPerfil(<?php echo $user['id']; ?>, '<?php echo $user['perfil']; ?>', '<?php echo addslashes($user['nome']); ?>')"
                                                        title="Alterar Perfil">
                                                    <i class="fas fa-user-tag"></i>
                                                </button>
                                                
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="excluirUsuario(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nome']); ?>')"
                                                        title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- LOGS RECENTES -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Logs Recentes de Usuários</h2>
            </div>
            <div class="card-body">
                <?php if (empty($logs_recentes)): ?>
                    <p class="text-muted">Nenhuma atividade registrada ainda.</p>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($logs_recentes as $log): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <strong><?php echo htmlspecialchars($log['usuario_nome']); ?></strong>
                                <?php echo htmlspecialchars($log['acao']); ?>
                                <div class="timeline-time">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['data_registro'])); ?>
                                    • IP: <?php echo htmlspecialchars($log['ip'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- DETALHES DO USUÁRIO (Modal) -->
        <?php if ($usuario_detalhes): ?>
        <div class="modal" id="modalDetalhes" style="display: flex;">
            <div class="modal-content modal-user">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">
                        <i class="fas fa-user"></i> Detalhes do Usuário
                    </h3>
                    <button type="button" onclick="fecharModal('modalDetalhes')" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="form-avatar-preview">
                    <?php echo strtoupper(substr($usuario_detalhes['nome'], 0, 1)); ?>
                </div>
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3><?php echo htmlspecialchars($usuario_detalhes['nome']); ?></h3>
                    <p style="color: #666; margin-bottom: 10px;">
                        <code><?php echo htmlspecialchars($usuario_detalhes['usuario']); ?></code>
                    </p>
                    
                    <?php 
                    $perfil_class = [
                        'admin' => 'badge-admin',
                        'tecnico' => 'badge-tecnico',
                        'coordenador' => 'badge-coordenador'
                    ][$usuario_detalhes['perfil']] ?? '';
                    ?>
                    <span class="badge-perfil <?php echo $perfil_class; ?>" style="font-size: 14px; padding: 6px 15px;">
                        <?php echo ucfirst($usuario_detalhes['perfil']); ?>
                    </span>
                    
                    <div style="margin-top: 10px;">
                        <span class="<?php echo $usuario_detalhes['ativo'] ? 'status-ativo' : 'status-inativo'; ?>"></span>
                        <span><?php echo $usuario_detalhes['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                        
                        <?php if ($usuario_detalhes['primeiro_login']): ?>
                            <span class="badge badge-warning" style="margin-left: 10px;">
                                <i class="fas fa-key"></i> Primeiro login pendente
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <button type="button" class="btn-action" onclick="resetarSenha(<?php echo $usuario_detalhes['id']; ?>, '<?php echo addslashes($usuario_detalhes['nome']); ?>')">
                        <i class="fas fa-key"></i>
                        <span>Resetar Senha</span>
                    </button>
                    
                    <?php if ($usuario_detalhes['id'] != $_SESSION['usuario_id']): ?>
                    <button type="button" class="btn-action" onclick="alterarStatus(<?php echo $usuario_detalhes['id']; ?>, '<?php echo $usuario_detalhes['ativo'] ? 'ativo' : 'inativo'; ?>', '<?php echo addslashes($usuario_detalhes['nome']); ?>')">
                        <i class="fas fa-power-off"></i>
                        <span>Alterar Status</span>
                    </button>
                    
                    <button type="button" class="btn-action" onclick="alterarPerfil(<?php echo $usuario_detalhes['id']; ?>, '<?php echo $usuario_detalhes['perfil']; ?>', '<?php echo addslashes($usuario_detalhes['nome']); ?>')">
                        <i class="fas fa-user-tag"></i>
                        <span>Alterar Perfil</span>
                    </button>
                    
                    <button type="button" class="btn-action" onclick="excluirUsuario(<?php echo $usuario_detalhes['id']; ?>, '<?php echo addslashes($usuario_detalhes['nome']); ?>')">
                        <i class="fas fa-trash"></i>
                        <span>Excluir</span>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 30px;">
                    <h4><i class="fas fa-info-circle"></i> Informações</h4>
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 8px 0; color: #666;">Data de Criação:</td>
                            <td style="padding: 8px 0; text-align: right;">
                                <?php echo date('d/m/Y H:i', strtotime($usuario_detalhes['data_criacao'])); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #666;">Último Login:</td>
                            <td style="padding: 8px 0; text-align: right;">
                                <?php 
                                $ultimo_login = db_fetch_one("
                                    SELECT data_registro 
                                    FROM logs_sistema 
                                    WHERE usuario_id = ? AND acao LIKE '%Login%'
                                    ORDER BY data_registro DESC 
                                    LIMIT 1
                                ", [$usuario_detalhes['id']]);
                                
                                if ($ultimo_login && $ultimo_login['data_registro']) {
                                    echo date('d/m/Y H:i', strtotime($ultimo_login['data_registro']));
                                } else {
                                    echo '<span class="text-muted">Nunca acessou</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; color: #666;">Total de Logins:</td>
                            <td style="padding: 8px 0; text-align: right;">
                                <?php 
                                $total_logins = db_fetch_one("
                                    SELECT COUNT(*) as total 
                                    FROM logs_sistema 
                                    WHERE usuario_id = ? AND acao LIKE '%Login%'
                                ", [$usuario_detalhes['id']])['total'];
                                echo $total_logins;
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php if (!empty($atividades_usuario)): ?>
                <div style="margin-top: 30px;">
                    <h4><i class="fas fa-history"></i> Atividades Recentes</h4>
                    <div style="max-height: 200px; overflow-y: auto; margin-top: 10px;">
                        <?php foreach ($atividades_usuario as $atividade): ?>
                        <div style="padding: 10px; border-bottom: 1px solid #eee; font-size: 13px;">
                            <div><?php echo htmlspecialchars($atividade['acao']); ?></div>
                            <div style="color: #666; font-size: 11px;">
                                <?php echo date('d/m/Y H:i', strtotime($atividade['data_registro'])); ?>
                                • Módulo: <?php echo htmlspecialchars($atividade['modulo']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- MODAL: RESETAR SENHA -->
        <div class="modal" id="modalResetSenha">
            <div class="modal-content">
                <h3 style="color: #ffc107; margin-top: 0;">
                    <i class="fas fa-key"></i> Resetar Senha
                </h3>
                
                <p id="mensagemResetSenha"></p>
                <p style="color: #666; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> A nova senha temporária será: <strong>102030</strong><br>
                    O usuário será obrigado a alterar a senha no próximo login.
                </p>
                
                <form method="POST" id="formResetSenha">
                    <input type="hidden" name="usuario_id" id="usuarioIdReset">
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="fecharModal('modalResetSenha')" 
                                style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                            Cancelar
                        </button>
                        <button type="submit" name="resetar_senha" class="btn btn-warning" 
                                style="background: #ffc107; color: #212529; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-key"></i> Resetar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- MODAL: ALTERAR STATUS -->
        <div class="modal" id="modalAlterarStatus">
            <div class="modal-content">
                <h3 style="color: #17a2b8; margin-top: 0;">
                    <i class="fas fa-power-off"></i> Alterar Status
                </h3>
                
                <p id="mensagemAlterarStatus"></p>
                
                <form method="POST" id="formAlterarStatus">
                    <input type="hidden" name="usuario_id" id="usuarioIdStatus">
                    <input type="hidden" name="novo_status" id="novoStatus">
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="fecharModal('modalAlterarStatus')" 
                                style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                            Cancelar
                        </button>
                        <button type="submit" name="alterar_status" class="btn btn-info" 
                                style="background: #17a2b8; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-save"></i> Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- MODAL: ALTERAR PERFIL -->
        <div class="modal" id="modalAlterarPerfil">
            <div class="modal-content">
                <h3 style="color: #007bff; margin-top: 0;">
                    <i class="fas fa-user-tag"></i> Alterar Perfil
                </h3>
                
                <p id="mensagemAlterarPerfil"></p>
                
                <div class="form-group">
                    <label>Novo Perfil:</label>
                    <select name="novo_perfil" id="novoPerfil" class="form-control">
                        <option value="tecnico">Técnico</option>
                        <option value="coordenador">Coordenador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <form method="POST" id="formAlterarPerfil">
                    <input type="hidden" name="usuario_id" id="usuarioIdPerfil">
                    <input type="hidden" name="novo_perfil" id="novoPerfilHidden">
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="fecharModal('modalAlterarPerfil')" 
                                style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                            Cancelar
                        </button>
                        <button type="submit" name="alterar_perfil" class="btn btn-primary" 
                                style="background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-save"></i> Alterar Perfil
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- MODAL: EXCLUIR USUÁRIO -->
        <div class="modal" id="modalExcluirUsuario">
            <div class="modal-content">
                <h3 style="color: #dc3545; margin-top: 0;">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão
                </h3>
                
                <p id="mensagemExcluirUsuario"></p>
                <p style="color: #666; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Esta ação não pode ser desfeita. Todos os logs e registros do usuário serão removidos.
                </p>
                
                <form method="POST" id="formExcluirUsuario">
                    <input type="hidden" name="usuario_id" id="usuarioIdExcluir">
                    
                    <div style="text-align: right; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="fecharModal('modalExcluirUsuario')" 
                                style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                            Cancelar
                        </button>
                        <button type="submit" name="excluir_usuario" class="btn btn-danger" 
                                style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-trash"></i> Excluir Usuário
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Funções para modais
function visualizarUsuario(id) {
    window.location.href = '<?php echo url("paginas/usuarios.php"); ?>?id=' + id;
}

function resetarSenha(id, nome) {
    document.getElementById('usuarioIdReset').value = id;
    document.getElementById('mensagemResetSenha').innerHTML = 
        'Resetar senha do usuário <strong>"' + nome + '"</strong>?';
    abrirModal('modalResetSenha');
}

function alterarStatus(id, statusAtual, nome) {
    const novoStatus = statusAtual === 'ativo' ? 'inativo' : 'ativo';
    const statusTexto = novoStatus === 'ativo' ? 'ativar' : 'desativar';
    
    document.getElementById('usuarioIdStatus').value = id;
    document.getElementById('novoStatus').value = novoStatus;
    document.getElementById('mensagemAlterarStatus').innerHTML = 
        statusTexto.charAt(0).toUpperCase() + statusTexto.slice(1) + 
        ' o usuário <strong>"' + nome + '"</strong>?<br>' +
        '<small>Status atual: <span class="' + (statusAtual === 'ativo' ? 'status-ativo' : 'status-inativo') + '"></span> ' + 
        statusAtual.charAt(0).toUpperCase() + statusAtual.slice(1) + '</small>';
    
    abrirModal('modalAlterarStatus');
}

function alterarPerfil(id, perfilAtual, nome) {
    document.getElementById('usuarioIdPerfil').value = id;
    document.getElementById('novoPerfil').value = perfilAtual;
    document.getElementById('mensagemAlterarPerfil').innerHTML = 
        'Alterar perfil do usuário <strong>"' + nome + '"</strong>?<br>' +
        '<small>Perfil atual: <span class="badge-perfil badge-' + perfilAtual + '">' + 
        perfilAtual.charAt(0).toUpperCase() + perfilAtual.slice(1) + '</span></small>';
    
    abrirModal('modalAlterarPerfil');
}

function excluirUsuario(id, nome) {
    document.getElementById('usuarioIdExcluir').value = id;
    document.getElementById('mensagemExcluirUsuario').innerHTML = 
        'Tem certeza que deseja excluir o usuário <strong>"' + nome + '"</strong>?';
    abrirModal('modalExcluirUsuario');
}

// Funções gerais de modal
function abrirModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function fecharModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fechar modais ao clicar fora
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
});

// Atualizar perfil selecionado
document.getElementById('novoPerfil').addEventListener('change', function() {
    document.getElementById('novoPerfilHidden').value = this.value;
});

// Se veio com parâmetro ID, abrir modal de detalhes automaticamente
<?php if ($visualizar_id): ?>
window.onload = function() {
    document.getElementById('modalDetalhes').style.display = 'flex';
};
<?php endif; ?>

// Validação do formulário de criação
document.getElementById('formCriarUsuario').addEventListener('submit', function(e) {
    const usuario = document.getElementById('usuario').value;
    const nome = document.getElementById('nome').value;
    
    // Validar nome de usuário (sem espaços, acentos)
    const usuarioRegex = /^[a-zA-Z0-9._-]+$/;
    if (!usuarioRegex.test(usuario)) {
        e.preventDefault();
        alert('Nome de usuário inválido! Use apenas letras, números, ponto, hífen ou underline.');
        return;
    }
    
    // Validar nome (mínimo 3 caracteres)
    if (nome.trim().length < 3) {
        e.preventDefault();
        alert('Nome deve ter pelo menos 3 caracteres.');
        return;
    }
    
    // Confirmar criação
    if (!confirm('Criar novo usuário?\n\nNome: ' + nome + '\nUsuário: ' + usuario + '\n\nA senha inicial será: 102030')) {
        e.preventDefault();
    }
});
</script>

<style>
/* Estilos específicos para esta página */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.badge-admin { background: #007bff; color: white; }
.badge-tecnico { background: #28a745; color: white; }
.badge-coordenador { background: #ffc107; color: #212529; }

.status-ativo, .status-inativo {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-ativo { background: #28a745; }
.status-inativo { background: #dc3545; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>