<?php
$page_title = 'Equipamentos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Buscar tipos e localidades para os selects
$tipos = db_fetch_all("SELECT * FROM tipos_equipamento ORDER BY nome");
$localidades = db_fetch_all("SELECT * FROM localidades WHERE ativo = TRUE ORDER BY nome");

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cadastrar'])) {
        $dados = [
            $_POST['nome'],
            $_POST['numero_serie'],
            $_POST['patrimonio'],
            $_POST['localidade_id'],
            $_POST['tipo_id'],
            $_POST['status'],
            $_POST['fornecedor'],
            $_POST['data_entrada'],
            $_POST['observacoes']
        ];
        
        $sql = "INSERT INTO equipamentos (nome, numero_serie, patrimonio, localidade_id, tipo_id, 
                                          status, fornecedor, data_entrada, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $equipamento_id = db_insert($sql, $dados);
        
        if ($equipamento_id) {
            // Associar checklists padrão do tipo
            $checklists_padrao = db_fetch_all("
                SELECT checklist_id 
                FROM tipo_checklist 
                WHERE tipo_id = ?
                UNION
                SELECT id 
                FROM modelos_checklist 
                WHERE padrao = TRUE
            ", [$_POST['tipo_id']]);
            
            foreach ($checklists_padrao as $checklist) {
                db_execute("INSERT INTO equipamento_checklist (equipamento_id, checklist_id) VALUES (?, ?)",
                         [$equipamento_id, $checklist['checklist_id'] ?? $checklist['id']]);
            }
            
            // Registrar movimentação de entrada
            db_execute("
                INSERT INTO movimentacao (equipamento_id, status_anterior, status_novo, usuario_id)
                VALUES (?, 'novo', ?, ?)
            ", [$equipamento_id, $_POST['status'], $_SESSION['usuario_id']]);
            
            header('Location: ' . url('paginas/equipamentos.php?sucesso=1'));
            exit;
        }
    }
    
    if (isset($_POST['alterar_status'])) {
        $equipamento_id = $_POST['equipamento_id'];
        $novo_status = $_POST['novo_status'];
        $motivo = $_POST['motivo'];
        $fornecedor = $_POST['fornecedor_mov'];
        
        // Buscar status atual
        $equipamento = db_fetch_one("SELECT status FROM equipamentos WHERE id = ?", [$equipamento_id]);
        
        // Atualizar status
        db_execute("UPDATE equipamentos SET status = ? WHERE id = ?", [$novo_status, $equipamento_id]);
        
        // Registrar movimentação
        db_execute("
            INSERT INTO movimentacao (equipamento_id, status_anterior, status_novo, motivo, fornecedor, usuario_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$equipamento_id, $equipamento['status'], $novo_status, $motivo, $fornecedor, $_SESSION['usuario_id']]);
        
        header('Location: ' . url('paginas/equipamentos.php?sucesso=2'));
        exit;
    }
}

// Buscar equipamentos com filtros
$filtro_local = $_GET['localidade'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';

$where = [];
$params = [];

if ($filtro_local) {
    $where[] = "e.localidade_id = ?";
    $params[] = $filtro_local;
}

if ($filtro_status && $filtro_status !== 'todos') {
    $where[] = "e.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_tipo) {
    $where[] = "e.tipo_id = ?";
    $params[] = $filtro_tipo;
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Verificar se a tabela equipamentos existe
$tabela_existe = db_fetch_one("SHOW TABLES LIKE 'equipamentos'");
if (!$tabela_existe) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 20px;'>";
    echo "<h3>⚠️ Tabela 'equipamentos' não encontrada!</h3>";
    echo "<p>Execute este SQL no phpMyAdmin:</p>";
    echo "<pre style='background: white; padding: 10px; border: 1px solid #ddd;'>";
    echo "CREATE TABLE equipamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    numero_serie VARCHAR(50),
    patrimonio VARCHAR(50),
    localidade_id INT,
    tipo_id INT,
    status ENUM('ativo', 'saida', 'manutencao', 'descartado') DEFAULT 'ativo',
    fornecedor VARCHAR(100),
    data_entrada DATE,
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";
    echo "</pre>";
    echo "</div>";
    $equipamentos = [];
} else {
    $sql = "
        SELECT e.*, l.nome as localidade_nome, t.nome as tipo_nome
        FROM equipamentos e
        LEFT JOIN localidades l ON e.localidade_id = l.id
        LEFT JOIN tipos_equipamento t ON e.tipo_id = t.id
        $where_sql
        ORDER BY e.nome
    ";

    $equipamentos = db_fetch_all($sql, $params);
}
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_GET['sucesso'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;">
                <?php 
                if ($_GET['sucesso'] == 1) echo '✅ Equipamento cadastrado com sucesso!';
                if ($_GET['sucesso'] == 2) echo '✅ Status do equipamento alterado com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0; color: #007bff; font-size: 20px;">
                    <i class="fas fa-filter"></i> Filtros
                </h2>
            </div>
            <div class="card-body">
                <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px;">
                    <select name="localidade" class="form-control">
                        <option value="">Todas Localidades</option>
                        <?php foreach ($localidades as $local): ?>
                        <option value="<?php echo $local['id']; ?>" 
                                <?php echo $filtro_local == $local['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($local['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="status" class="form-control">
                        <option value="todos">Todos Status</option>
                        <option value="ativo" <?php echo $filtro_status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="saida" <?php echo $filtro_status == 'saida' ? 'selected' : ''; ?>>Saída</option>
                        <option value="manutencao" <?php echo $filtro_status == 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
                        <option value="descartado" <?php echo $filtro_status == 'descartado' ? 'selected' : ''; ?>>Descartado</option>
                    </select>
                    
                    <select name="tipo" class="form-control">
                        <option value="">Todos Tipos</option>
                        <?php foreach ($tipos as $tipo): ?>
                        <option value="<?php echo $tipo['id']; ?>" 
                                <?php echo $filtro_tipo == $tipo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary" style="background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0; color: #007bff; font-size: 20px;">
                    <i class="fas fa-desktop"></i> Cadastrar Novo Equipamento
                </h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Nome/Identificação *</label>
                            <input type="text" name="nome" class="form-control" required 
                                   placeholder="Ex: PC-SALA01">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Número de Série</label>
                            <input type="text" name="numero_serie" class="form-control">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Patrimônio</label>
                            <input type="text" name="patrimonio" class="form-control">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Localidade *</label>
                            <select name="localidade_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($localidades as $local): ?>
                                <option value="<?php echo $local['id']; ?>">
                                    <?php echo htmlspecialchars($local['nome'] . ' (' . $local['codigo'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Tipo de Equipamento *</label>
                            <select name="tipo_id" class="form-control" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($tipos as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>">
                                    <?php echo htmlspecialchars($tipo['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="ativo">Ativo</option>
                                <option value="saida">Saída</option>
                                <option value="manutencao">Manutenção</option>
                                <option value="descartado">Descartado</option>
                            </select>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Fornecedor</label>
                            <input type="text" name="fornecedor" class="form-control">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Data de Entrada</label>
                            <input type="date" name="data_entrada" class="form-control"
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" name="cadastrar" class="btn btn-primary" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-save"></i> Cadastrar Equipamento
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0; color: #007bff; font-size: 20px;">
                    <i class="fas fa-list"></i> Equipamentos Cadastrados
                </h2>
            </div>
            <div class="card-body">
                <?php if (empty($equipamentos)): ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <i class="fas fa-desktop" style="font-size: 48px; color: #dee2e6; margin-bottom: 15px;"></i>
                        <h3>Nenhum equipamento cadastrado</h3>
                        <p>Cadastre seu primeiro equipamento usando o formulário acima.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Nome</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Localidade</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Tipo</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Nº Série</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Patrimônio</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Status</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Data Entrada</th>
                                    <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipamentos as $equip): 
                                    $status_class = [
                                        'ativo' => 'background: #d4edda; color: #155724;',
                                        'saida' => 'background: #fff3cd; color: #856404;',
                                        'manutencao' => 'background: #f8d7da; color: #721c24;',
                                        'descartado' => 'background: #e2e3e5; color: #383d41;'
                                    ][$equip['status']] ?? 'background: #e2e3e5; color: #383d41;';
                                ?>
                                <tr style="border-bottom: 1px solid #dee2e6;" onmouseover="this.style.backgroundColor='rgba(0,123,255,0.05)'" onmouseout="this.style.backgroundColor=''">
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($equip['nome']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($equip['localidade_nome']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($equip['tipo_nome']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($equip['numero_serie']); ?></td>
                                    <td style="padding: 12px 15px;"><?php echo htmlspecialchars($equip['patrimonio']); ?></td>
                                    <td style="padding: 12px 15px;">
                                        <span style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; <?php echo $status_class; ?>">
                                            <?php echo ucfirst($equip['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 15px;"><?php echo $equip['data_entrada'] ? date('d/m/Y', strtotime($equip['data_entrada'])) : '-'; ?></td>
                                    <td style="padding: 12px 15px;">
                                        <button type="button" class="btn btn-primary" style="background: #007bff; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 5px;"
                                                onclick="alterarStatus(<?php echo $equip['id']; ?>, '<?php echo addslashes($equip['nome']); ?>', '<?php echo $equip['status']; ?>')">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <a href="<?php echo url('paginas/ver_equipamento.php?id=' . $equip['id']); ?>" 
                                           class="btn btn-primary" style="background: #17a2b8; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-block;">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
</div>

<!-- Modal para alterar status -->
<div id="modalStatus" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; width: 500px;">
        <h3 style="margin-top: 0;">Alterar Status do Equipamento</h3>
        <p id="equipamentoNome"></p>
        
        <form method="POST" id="formStatus">
            <input type="hidden" name="equipamento_id" id="equipamentoId">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Novo Status *</label>
                <select id="novo_status" name="novo_status" class="form-control" required>
                    <option value="ativo">Ativo</option>
                    <option value="saida">Saída</option>
                    <option value="manutencao">Manutenção</option>
                    <option value="descartado">Descartado</option>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Motivo da Alteração</label>
                <textarea id="motivo" name="motivo" class="form-control" rows="3" 
                          placeholder="Descreva o motivo da alteração de status..."></textarea>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Fornecedor (se aplicável)</label>
                <input type="text" id="fornecedor_mov" name="fornecedor_mov" class="form-control" 
                       placeholder="Nome do fornecedor para manutenção...">
            </div>
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-primary" onclick="fecharModal()" style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancelar</button>
                <button type="submit" name="alterar_status" class="btn btn-primary" style="background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Alterar Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function alterarStatus(id, nome, statusAtual) {
    document.getElementById('equipamentoId').value = id;
    document.getElementById('equipamentoNome').innerHTML = '<strong>' + nome + '</strong><br>Status atual: ' + statusAtual;
    document.getElementById('novo_status').value = statusAtual;
    document.getElementById('modalStatus').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modalStatus').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('modalStatus').addEventListener('click', function(e) {
    if (e.target.id === 'modalStatus') {
        fecharModal();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>