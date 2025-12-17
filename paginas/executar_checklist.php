<?php
$page_title = 'Executar Checklist';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Verificar se é técnico ou superior
if ($_SESSION['usuario_perfil'] == 'tecnico' || $_SESSION['usuario_perfil'] == 'coordenador' || $_SESSION['usuario_perfil'] == 'admin') {
    // Permite acesso
} else {
    echo '<div class="alert alert-danger" style="margin: 20px;">';
    echo '<i class="fas fa-ban"></i> Acesso restrito. Apenas técnicos, coordenadores e administradores podem executar checklists.';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Variáveis de controle
$equipamento_id = $_GET['equipamento'] ?? 0;
$checklist_id = $_GET['checklist'] ?? 0;
$execucao_id = $_GET['execucao'] ?? 0;
$modo_edicao = false;

// Buscar equipamento
$equipamento = null;
if ($equipamento_id) {
    $equipamento = db_fetch_one("
        SELECT e.*, l.nome as localidade_nome, t.nome as tipo_nome 
        FROM equipamentos e
        LEFT JOIN localidades l ON e.localidade_id = l.id
        LEFT JOIN tipos_equipamento t ON e.tipo_id = t.id
        WHERE e.id = ?
    ", [$equipamento_id]);
}

// Buscar checklist
$checklist = null;
$itens_checklist = [];
if ($checklist_id) {
    $checklist = db_fetch_one("SELECT * FROM modelos_checklist WHERE id = ?", [$checklist_id]);
    $itens_checklist = db_fetch_all("SELECT * FROM checklist_itens WHERE checklist_id = ? ORDER BY ordem", [$checklist_id]);
}

// Se tiver execução_id, está editando uma execução existente
$execucao = null;
$respostas = [];
if ($execucao_id) {
    $execucao = db_fetch_one("SELECT * FROM execucao_checklist WHERE id = ?", [$execucao_id]);
    if ($execucao) {
        $modo_edicao = true;
        $equipamento_id = $execucao['equipamento_id'];
        $checklist_id = $execucao['checklist_id'];
        
        // Buscar equipamento e checklist novamente
        if (!$equipamento) $equipamento = db_fetch_one("SELECT * FROM equipamentos WHERE id = ?", [$equipamento_id]);
        if (!$checklist) $checklist = db_fetch_one("SELECT * FROM modelos_checklist WHERE id = ?", [$checklist_id]);
        if (empty($itens_checklist)) $itens_checklist = db_fetch_all("SELECT * FROM checklist_itens WHERE checklist_id = ? ORDER BY ordem", [$checklist_id]);
        
        // Buscar respostas salvas
        $respostas_raw = db_fetch_all("SELECT * FROM checklist_respostas WHERE execucao_id = ?", [$execucao_id]);
        foreach ($respostas_raw as $r) {
            $respostas[$r['item_id']] = $r['resposta'];
        }
    }
}

// Buscar checklists disponíveis para o equipamento
$checklists_disponiveis = [];
if ($equipamento_id) {
    $checklists_disponiveis = db_fetch_all("
        SELECT DISTINCT mc.* 
        FROM modelos_checklist mc
        LEFT JOIN tipo_checklist tc ON mc.id = tc.checklist_id
        LEFT JOIN equipamentos e ON e.tipo_id = tc.tipo_id
        WHERE e.id = ? OR mc.padrao = TRUE
        ORDER BY mc.nome
    ", [$equipamento_id]);
}

// Buscar histórico de execuções
$historico = [];
if ($equipamento_id && $checklist_id) {
    $historico = db_fetch_all("
        SELECT ec.*, u.nome as tecnico_nome
        FROM execucao_checklist ec
        LEFT JOIN usuarios u ON ec.usuario_id = u.id
        WHERE ec.equipamento_id = ? AND ec.checklist_id = ?
        ORDER BY ec.data_execucao DESC
        LIMIT 5
    ", [$equipamento_id, $checklist_id]);
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SALVAR RASCUNHO
    if (isset($_POST['salvar_rascunho'])) {
        $observacoes = $_POST['observacoes'] ?? '';
        
        if ($modo_edicao && $execucao) {
            // Atualizar execução existente
            $sql = "UPDATE execucao_checklist SET observacoes = ?, status_geral = 'pendente' WHERE id = ?";
            db_execute($sql, [$observacoes, $execucao_id]);
        } else {
            // Criar nova execução
            $sql = "INSERT INTO execucao_checklist (equipamento_id, checklist_id, usuario_id, observacoes, status_geral) 
                    VALUES (?, ?, ?, ?, 'pendente')";
            $execucao_id = db_insert($sql, [$equipamento_id, $checklist_id, $_SESSION['usuario_id'], $observacoes]);
        }
        
        // Salvar respostas
        if ($execucao_id && isset($_POST['respostas'])) {
            foreach ($_POST['respostas'] as $item_id => $resposta) {
                if (!empty($resposta)) {
                    // Verificar se já existe resposta
                    $existe = db_fetch_one("SELECT * FROM checklist_respostas WHERE execucao_id = ? AND item_id = ?", 
                                          [$execucao_id, $item_id]);
                    
                    if ($existe) {
                        // Atualizar
                        db_execute("UPDATE checklist_respostas SET resposta = ? WHERE execucao_id = ? AND item_id = ?",
                                  [$resposta, $execucao_id, $item_id]);
                    } else {
                        // Inserir nova
                        db_execute("INSERT INTO checklist_respostas (execucao_id, item_id, resposta) VALUES (?, ?, ?)",
                                  [$execucao_id, $item_id, $resposta]);
                    }
                }
            }
        }
        
        // Registrar log
        db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                  [$_SESSION['usuario_id'], "Salvou rascunho do checklist: {$checklist['nome']} para equipamento ID {$equipamento_id}", 'checklists']);
        
        header('Location: ' . url('paginas/executar_checklist.php?execucao=' . $execucao_id . '&sucesso=1'));
        exit;
    }
    
    // CONCLUIR CHECKLIST
    if (isset($_POST['concluir_checklist'])) {
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Calcular status geral baseado nas respostas
        $status_geral = 'aprovado'; // Inicia como aprovado
        $respostas_negativas = 0;
        $respostas_total = 0;
        
        if (isset($_POST['respostas'])) {
            foreach ($_POST['respostas'] as $item_id => $resposta) {
                $respostas_total++;
                // Verificar se é resposta negativa (Não, Não OK, etc.)
                if (in_array(strtolower($resposta), ['nao', 'não', 'nao_ok', 'não_ok', 'false', '0'])) {
                    $respostas_negativas++;
                }
            }
        }
        
        // Definir status baseado nas respostas negativas
        if ($respostas_negativas > 0) {
            $percentual_negativo = ($respostas_negativas / $respostas_total) * 100;
            if ($percentual_negativo > 50) {
                $status_geral = 'reprovado';
            } else {
                $status_geral = 'atencao';
            }
        }
        
        if ($modo_edicao && $execucao) {
            // Atualizar execução existente
            $sql = "UPDATE execucao_checklist SET observacoes = ?, status_geral = ? WHERE id = ?";
            db_execute($sql, [$observacoes, $status_geral, $execucao_id]);
        } else {
            // Criar nova execução
            $sql = "INSERT INTO execucao_checklist (equipamento_id, checklist_id, usuario_id, observacoes, status_geral) 
                    VALUES (?, ?, ?, ?, ?)";
            $execucao_id = db_insert($sql, [$equipamento_id, $checklist_id, $_SESSION['usuario_id'], $observacoes, $status_geral]);
        }
        
        // Salvar respostas
        if ($execucao_id && isset($_POST['respostas'])) {
            foreach ($_POST['respostas'] as $item_id => $resposta) {
                if (!empty($resposta)) {
                    // Verificar se já existe resposta
                    $existe = db_fetch_one("SELECT * FROM checklist_respostas WHERE execucao_id = ? AND item_id = ?", 
                                          [$execucao_id, $item_id]);
                    
                    if ($existe) {
                        // Atualizar
                        db_execute("UPDATE checklist_respostas SET resposta = ? WHERE execucao_id = ? AND item_id = ?",
                                  [$resposta, $execucao_id, $item_id]);
                    } else {
                        // Inserir nova
                        db_execute("INSERT INTO checklist_respostas (execucao_id, item_id, resposta) VALUES (?, ?, ?)",
                                  [$execucao_id, $item_id, $resposta]);
                    }
                }
            }
        }
        
        // Registrar log
        db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                  [$_SESSION['usuario_id'], "Concluiu checklist: {$checklist['nome']} para equipamento ID {$equipamento_id} - Status: {$status_geral}", 'checklists']);
        
        // Atualizar status do equipamento se necessário
        if ($status_geral == 'reprovado') {
            db_execute("UPDATE equipamentos SET status = 'manutencao' WHERE id = ?", [$equipamento_id]);
            // Registrar movimentação
            db_execute("INSERT INTO movimentacao (equipamento_id, status_anterior, status_novo, motivo, usuario_id) 
                       VALUES (?, ?, ?, ?, ?)",
                      [$equipamento_id, $equipamento['status'] ?? 'ativo', 'manutencao', 
                       'Checklist reprovado - necessário manutenção', $_SESSION['usuario_id']]);
        }
        
        header('Location: ' . url('paginas/executar_checklist.php?execucao=' . $execucao_id . '&sucesso=2'));
        exit;
    }
}

// Calcular progresso
$progresso = 0;
if (!empty($itens_checklist)) {
    $itens_respondidos = 0;
    foreach ($itens_checklist as $item) {
        if (isset($respostas[$item['id']]) && !empty($respostas[$item['id']])) {
            $itens_respondidos++;
        }
    }
    $progresso = round(($itens_respondidos / count($itens_checklist)) * 100);
}
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                <?php 
                if ($_GET['sucesso'] == 1) echo '✅ Rascunho salvo com sucesso!';
                if ($_GET['sucesso'] == 2) echo '✅ Checklist concluído com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="execucao-container">
            <!-- CABEÇALHO -->
            <div class="execucao-header">
                <h1 style="margin: 0 0 10px 0;">
                    <i class="fas fa-clipboard-check"></i> Executar Checklist
                </h1>
                <p style="margin: 0; opacity: 0.9;">
                    Preencha o checklist para o equipamento selecionado
                </p>
            </div>
            
            <!-- SELEÇÃO DE EQUIPAMENTO E CHECKLIST -->
            <?php if (!$equipamento_id || !$checklist_id): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-search"></i> Selecionar Equipamento e Checklist</h2>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label>Equipamento *</label>
                                <select name="equipamento" class="form-control" required 
                                        onchange="this.form.submit()">
                                    <option value="">Selecione um equipamento...</option>
                                    <?php 
                                    $equipamentos_lista = db_fetch_all("
                                        SELECT e.*, l.nome as localidade_nome 
                                        FROM equipamentos e
                                        LEFT JOIN localidades l ON e.localidade_id = l.id
                                        WHERE e.status != 'descartado'
                                        ORDER BY e.nome
                                    ");
                                    foreach ($equipamentos_lista as $eq): 
                                    ?>
                                    <option value="<?php echo $eq['id']; ?>" 
                                            <?php echo $equipamento_id == $eq['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($eq['nome'] . ' - ' . $eq['localidade_nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Checklist *</label>
                                <select name="checklist" class="form-control" required 
                                        <?php echo !$equipamento_id ? 'disabled' : ''; ?>
                                        onchange="this.form.submit()">
                                    <option value="">Selecione um checklist...</option>
                                    <?php if ($equipamento_id): ?>
                                        <?php foreach ($checklists_disponiveis as $chk): ?>
                                        <option value="<?php echo $chk['id']; ?>" 
                                                <?php echo $checklist_id == $chk['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($chk['nome']); ?>
                                            <?php if ($chk['padrao']): ?> (Padrão)<?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (!$equipamento_id): ?>
                                <small class="text-muted">Selecione um equipamento primeiro</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- INFORMAÇÕES DO CHECKLIST -->
            <?php if ($equipamento && $checklist): ?>
            <div class="execucao-info">
                <div class="info-item">
                    <span class="info-label">Equipamento</span>
                    <span class="info-value"><?php echo htmlspecialchars($equipamento['nome']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Localidade</span>
                    <span class="info-value"><?php echo htmlspecialchars($equipamento['localidade_nome']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Checklist</span>
                    <span class="info-value"><?php echo htmlspecialchars($checklist['nome']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Técnico Responsável</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                </div>
            </div>
            
            <!-- PROGRESSO -->
            <div class="progress-container">
                <div class="progress-circle-large">
                    <svg viewBox="0 0 36 36">
                        <path d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none" stroke="#e6f2ff" stroke-width="3"/>
                        <path d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none" stroke="#007bff" stroke-width="3" 
                            stroke-dasharray="<?php echo $progresso; ?>, 100"/>
                    </svg>
                    <div class="progress-text">
                        <div class="progress-percent"><?php echo $progresso; ?>%</div>
                        <div class="progress-label">Concluído</div>
                    </div>
                </div>
                
                <div class="progress-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo count($itens_checklist); ?></div>
                        <div class="stat-label">Total de Itens</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">
                            <?php 
                            $respondidos = 0;
                            foreach ($itens_checklist as $item) {
                                if (isset($respostas[$item['id']]) && !empty($respostas[$item['id']])) {
                                    $respondidos++;
                                }
                            }
                            echo $respondidos;
                            ?>
                        </div>
                        <div class="stat-label">Itens Respondidos</div>
                    </div>
                </div>
                
                <?php if ($modo_edicao && $execucao): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <span class="badge" style="background: #6c757d; color: white; padding: 5px 10px;">
                        <i class="fas fa-edit"></i> Editando execução #<?php echo $execucao_id; ?>
                    </span>
                    <?php if ($execucao['status_geral'] != 'pendente'): ?>
                    <span class="badge" style="background: #28a745; color: white; padding: 5px 10px; margin-left: 5px;">
                        <i class="fas fa-check"></i> <?php echo ucfirst($execucao['status_geral']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- FORMULÁRIO DO CHECKLIST -->
            <form method="POST" id="formChecklist">
                <!-- ITENS DO CHECKLIST -->
                <?php if (empty($itens_checklist)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Este checklist não possui itens definidos.
                    </div>
                <?php else: ?>
                    <?php foreach ($itens_checklist as $index => $item): 
                        $resposta_atual = $respostas[$item['id']] ?? '';
                        $item_class = '';
                        if (!empty($resposta_atual)) {
                            $item_class = 'completed';
                        } elseif ($index == 0) {
                            $item_class = 'pending';
                        }
                    ?>
                    <div class="checklist-item <?php echo $item_class; ?>" id="item-<?php echo $item['id']; ?>">
                        <div class="item-number"><?php echo $index + 1; ?></div>
                        <div class="item-content">
                            <div class="item-question">
                                <?php echo htmlspecialchars($item['descricao']); ?>
                                <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                                    Tipo: <?php 
                                    $tipos = [
                                        'sim_nao' => 'Sim/Não',
                                        'ok_nao_ok' => 'OK/Não OK',
                                        'texto' => 'Texto',
                                        'data' => 'Data'
                                    ];
                                    echo $tipos[$item['tipo_resposta']] ?? $item['tipo_resposta'];
                                    ?>
                                </small>
                            </div>
                            
                            <div class="item-resposta">
                                <?php if ($item['tipo_resposta'] == 'sim_nao'): ?>
                                    <div class="resposta-sim-nao">
                                        <label class="radio-option <?php echo $resposta_atual == 'sim' ? 'selected' : ''; ?>">
                                            <input type="radio" name="respostas[<?php echo $item['id']; ?>]" value="sim" 
                                                   <?php echo $resposta_atual == 'sim' ? 'checked' : ''; ?>
                                                   onchange="atualizarProgresso()">
                                            <span>Sim</span>
                                        </label>
                                        <label class="radio-option <?php echo $resposta_atual == 'nao' ? 'selected' : ''; ?>">
                                            <input type="radio" name="respostas[<?php echo $item['id']; ?>]" value="nao" 
                                                   <?php echo $resposta_atual == 'nao' ? 'checked' : ''; ?>
                                                   onchange="atualizarProgresso()">
                                            <span>Não</span>
                                        </label>
                                    </div>
                                
                                <?php elseif ($item['tipo_resposta'] == 'ok_nao_ok'): ?>
                                    <div class="resposta-sim-nao">
                                        <label class="radio-option <?php echo $resposta_atual == 'ok' ? 'selected' : ''; ?>">
                                            <input type="radio" name="respostas[<?php echo $item['id']; ?>]" value="ok" 
                                                   <?php echo $resposta_atual == 'ok' ? 'checked' : ''; ?>
                                                   onchange="atualizarProgresso()">
                                            <span>OK</span>
                                        </label>
                                        <label class="radio-option <?php echo $resposta_atual == 'nao_ok' ? 'selected' : ''; ?>">
                                            <input type="radio" name="respostas[<?php echo $item['id']; ?>]" value="nao_ok" 
                                                   <?php echo $resposta_atual == 'nao_ok' ? 'checked' : ''; ?>
                                                   onchange="atualizarProgresso()">
                                            <span>Não OK</span>
                                        </label>
                                    </div>
                                
                                <?php elseif ($item['tipo_resposta'] == 'texto'): ?>
                                    <div class="resposta-texto">
                                        <textarea name="respostas[<?php echo $item['id']; ?>]" 
                                                  class="form-control" 
                                                  rows="3"
                                                  oninput="atualizarProgresso()"
                                                  placeholder="Digite sua resposta aqui..."><?php echo htmlspecialchars($resposta_atual); ?></textarea>
                                    </div>
                                
                                <?php elseif ($item['tipo_resposta'] == 'data'): ?>
                                    <div class="resposta-data">
                                        <input type="date" 
                                               name="respostas[<?php echo $item['id']; ?>]" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars($resposta_atual); ?>"
                                               onchange="atualizarProgresso()"
                                               style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- OBSERVAÇÕES -->
                <div class="observacoes-container">
                    <h3><i class="fas fa-sticky-note"></i> Observações Adicionais</h3>
                    <div class="form-group">
                        <textarea name="observacoes" class="form-control" rows="4" 
                                  placeholder="Adicione observações relevantes sobre a execução deste checklist..."><?php 
                            echo $execucao['observacoes'] ?? ''; 
                        ?></textarea>
                    </div>
                </div>
                
                <!-- AÇÕES -->
                <div class="acoes-fixa">
                    <div>
                        <a href="<?php echo url('paginas/executar_checklist.php'); ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="salvar_rascunho" class="btn btn-salvar-rascunho">
                            <i class="fas fa-save"></i> Salvar Rascunho
                        </button>
                        
                        <button type="submit" name="concluir_checklist" class="btn btn-concluir"
                                onclick="return confirm('Tem certeza que deseja concluir este checklist?\n\nApós concluir, não será possível editar as respostas.')">
                            <i class="fas fa-check"></i> Concluir Checklist
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- HISTÓRICO -->
            <?php if (!empty($historico)): ?>
            <div style="margin-top: 50px;">
                <h2><i class="fas fa-history"></i> Histórico de Execuções</h2>
                <p>Últimas execuções deste checklist para este equipamento:</p>
                
                <?php foreach ($historico as $hist): 
                    if ($hist['id'] == $execucao_id) continue; // Não mostrar a execução atual
                ?>
                <div class="historico-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo htmlspecialchars($hist['tecnico_nome']); ?></strong>
                            <span class="badge" style="background: 
                                <?php 
                                $cores = [
                                    'aprovado' => '#28a745',
                                    'reprovado' => '#dc3545',
                                    'atencao' => '#ffc107',
                                    'pendente' => '#6c757d'
                                ];
                                echo $cores[$hist['status_geral']] ?? '#6c757d';
                                ?>; 
                                color: white; margin-left: 10px;">
                                <?php echo ucfirst($hist['status_geral']); ?>
                            </span>
                        </div>
                        <div>
                            <a href="<?php echo url('paginas/executar_checklist.php?execucao=' . $hist['id']); ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </div>
                    </div>
                    <div class="historico-data">
                        <?php echo date('d/m/Y H:i', strtotime($hist['data_execucao'])); ?>
                        <?php if ($hist['observacoes']): ?>
                            • <?php echo htmlspecialchars(substr($hist['observacoes'], 0, 100)); ?>...
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php endif; // Fim do if ($equipamento && $checklist) ?>
        </div>
    </div>
</div>

<script>
// Função para atualizar progresso
function atualizarProgresso() {
    // Esta função será chamada quando as respostas forem alteradas
    // O progresso real é calculado no servidor, mas podemos dar feedback visual
    const itens = document.querySelectorAll('.checklist-item');
    let respondidos = 0;
    
    itens.forEach(item => {
        const inputs = item.querySelectorAll('input, textarea');
        let preenchido = false;
        
        inputs.forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') {
                if (input.checked) preenchido = true;
            } else if (input.type === 'textarea' || input.type === 'text' || input.type === 'date') {
                if (input.value.trim() !== '') preenchido = true;
            }
        });
        
        if (preenchido) {
            item.classList.add('completed');
            item.classList.remove('pending');
            respondidos++;
        } else {
            item.classList.remove('completed');
        }
    });
    
    // Atualizar contador visual (opcional)
    const total = itens.length;
    const percentual = Math.round((respondidos / total) * 100);
    
    // Se quiser atualizar o círculo de progresso em tempo real, precisaria de mais código
    // Por enquanto, apenas marcamos os itens
}

// Rolar para o próximo item não respondido
function irParaProximoNaoRespondido() {
    const itens = document.querySelectorAll('.checklist-item:not(.completed)');
    if (itens.length > 0) {
        itens[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    // Marcar itens já respondidos
    atualizarProgresso();
    
    // Se houver itens não respondidos, rolar para o primeiro
    setTimeout(() => {
        irParaProximoNaoRespondido();
    }, 500);
    
    // Adicionar evento para mudança de respostas
    document.querySelectorAll('input, textarea').forEach(input => {
        input.addEventListener('change', atualizarProgresso);
        input.addEventListener('input', atualizarProgresso);
    });
});

// Prevenir saída sem salvar
window.addEventListener('beforeunload', function(e) {
    const form = document.getElementById('formChecklist');
    if (form) {
        const inputs = form.querySelectorAll('input, textarea');
        let modificado = false;
        
        inputs.forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') {
                if (input.defaultChecked !== input.checked) modificado = true;
            } else {
                if (input.defaultValue !== input.value) modificado = true;
            }
        });
        
        if (modificado) {
            e.preventDefault();
            e.returnValue = 'Você tem alterações não salvas. Tem certeza que deseja sair?';
            return e.returnValue;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>