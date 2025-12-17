<?php
$page_title = 'Executar Processo';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Verificar se tem permissão (técnico, coordenador ou admin)
if (!in_array($_SESSION['usuario_perfil'], ['tecnico', 'coordenador', 'admin'])) {
    echo '<div class="alert alert-danger" style="margin: 20px;">';
    echo '<i class="fas fa-ban"></i> Acesso restrito. Apenas técnicos, coordenadores e administradores podem executar processos.';
    echo '</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Variáveis de controle
$localidade_id = $_GET['localidade'] ?? 0;
$processo_id = $_GET['processo'] ?? 0;
$execucao_id = $_GET['execucao'] ?? 0;
$modo_edicao = false;

// Buscar localidade
$localidade = null;
if ($localidade_id) {
    $localidade = db_fetch_one("SELECT * FROM localidades WHERE id = ? AND ativo = TRUE", [$localidade_id]);
}

// Buscar processo
$processo = null;
$itens_processo = [];
if ($processo_id) {
    $processo = db_fetch_one("SELECT * FROM modelos_processo WHERE id = ?", [$processo_id]);
    $itens_processo = db_fetch_all("SELECT * FROM processo_itens WHERE processo_id = ? ORDER BY ordem", [$processo_id]);
}

// Se tiver execução_id, está editando uma execução existente
$execucao = null;
$respostas = [];
if ($execucao_id) {
    $execucao = db_fetch_one("SELECT * FROM execucao_processo WHERE id = ?", [$execucao_id]);
    if ($execucao) {
        $modo_edicao = true;
        $localidade_id = $execucao['localidade_id'];
        $processo_id = $execucao['processo_id'];
        
        // Buscar localidade e processo novamente
        if (!$localidade) $localidade = db_fetch_one("SELECT * FROM localidades WHERE id = ?", [$localidade_id]);
        if (!$processo) $processo = db_fetch_one("SELECT * FROM modelos_processo WHERE id = ?", [$processo_id]);
        if (empty($itens_processo)) $itens_processo = db_fetch_all("SELECT * FROM processo_itens WHERE processo_id = ? ORDER BY ordem", [$processo_id]);
        
        // Buscar respostas salvas
        $respostas_raw = db_fetch_all("SELECT * FROM processo_respostas WHERE execucao_id = ?", [$execucao_id]);
        foreach ($respostas_raw as $r) {
            $respostas[$r['item_id']] = $r['resposta'];
        }
    }
}

// Buscar processos disponíveis para a localidade
$processos_disponiveis = [];
if ($localidade_id) {
    $processos_disponiveis = db_fetch_all("
        SELECT DISTINCT mp.* 
        FROM modelos_processo mp
        LEFT JOIN localidade_processo lp ON mp.id = lp.processo_id
        WHERE lp.localidade_id = ? OR mp.padrao = TRUE
        ORDER BY mp.nome
    ", [$localidade_id]);
}

// Buscar histórico de execuções
$historico = [];
if ($localidade_id && $processo_id) {
    $historico = db_fetch_all("
        SELECT ep.*, u.nome as responsavel_nome
        FROM execucao_processo ep
        LEFT JOIN usuarios u ON ep.usuario_id = u.id
        WHERE ep.localidade_id = ? AND ep.processo_id = ?
        ORDER BY ep.data_inicio DESC
        LIMIT 5
    ", [$localidade_id, $processo_id]);
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // SALVAR RASCUNHO
    if (isset($_POST['salvar_rascunho'])) {
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Calcular progresso baseado nas respostas
        $progresso = 0;
        if (!empty($itens_processo) && isset($_POST['respostas'])) {
            $itens_respondidos = 0;
            foreach ($itens_processo as $item) {
                if (isset($_POST['respostas'][$item['id']]) && !empty($_POST['respostas'][$item['id']])) {
                    $itens_respondidos++;
                }
            }
            $progresso = round(($itens_respondidos / count($itens_processo)) * 100);
        }
        
        if ($modo_edicao && $execucao) {
            // Atualizar execução existente
            $sql = "UPDATE execucao_processo SET observacoes = ?, progresso = ? WHERE id = ?";
            db_execute($sql, [$observacoes, $progresso, $execucao_id]);
        } else {
            // Criar nova execução
            $sql = "INSERT INTO execucao_processo (localidade_id, processo_id, usuario_id, observacoes, progresso) 
                    VALUES (?, ?, ?, ?, ?)";
            $execucao_id = db_insert($sql, [$localidade_id, $processo_id, $_SESSION['usuario_id'], $observacoes, $progresso]);
        }
        
        // Salvar respostas
        if ($execucao_id && isset($_POST['respostas'])) {
            foreach ($_POST['respostas'] as $item_id => $resposta) {
                if (!empty($resposta)) {
                    // Verificar se já existe resposta
                    $existe = db_fetch_one("SELECT * FROM processo_respostas WHERE execucao_id = ? AND item_id = ?", 
                                          [$execucao_id, $item_id]);
                    
                    if ($existe) {
                        // Atualizar
                        db_execute("UPDATE processo_respostas SET resposta = ? WHERE execucao_id = ? AND item_id = ?",
                                  [$resposta, $execucao_id, $item_id]);
                    } else {
                        // Inserir nova
                        db_execute("INSERT INTO processo_respostas (execucao_id, item_id, resposta) VALUES (?, ?, ?)",
                                  [$execucao_id, $item_id, $resposta]);
                    }
                }
            }
        }
        
        // Registrar log
        db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                  [$_SESSION['usuario_id'], "Salvou rascunho do processo: {$processo['nome']} para localidade ID {$localidade_id}", 'processos']);
        
        header('Location: ' . url('paginas/executar_processo.php?execucao=' . $execucao_id . '&sucesso=1'));
        exit;
    }
    
    // CONCLUIR PROCESSO
    if (isset($_POST['concluir_processo'])) {
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Calcular progresso final (100%)
        $progresso = 100;
        
        if ($modo_edicao && $execucao) {
            // Atualizar execução existente
            $sql = "UPDATE execucao_processo SET observacoes = ?, progresso = ?, data_conclusao = NOW() WHERE id = ?";
            db_execute($sql, [$observacoes, $progresso, $execucao_id]);
        } else {
            // Criar nova execução
            $sql = "INSERT INTO execucao_processo (localidade_id, processo_id, usuario_id, observacoes, progresso, data_conclusao) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $execucao_id = db_insert($sql, [$localidade_id, $processo_id, $_SESSION['usuario_id'], $observacoes, $progresso]);
        }
        
        // Salvar respostas
        if ($execucao_id && isset($_POST['respostas'])) {
            foreach ($_POST['respostas'] as $item_id => $resposta) {
                if (!empty($resposta)) {
                    // Verificar se já existe resposta
                    $existe = db_fetch_one("SELECT * FROM processo_respostas WHERE execucao_id = ? AND item_id = ?", 
                                          [$execucao_id, $item_id]);
                    
                    if ($existe) {
                        // Atualizar
                        db_execute("UPDATE processo_respostas SET resposta = ? WHERE execucao_id = ? AND item_id = ?",
                                  [$resposta, $execucao_id, $item_id]);
                    } else {
                        // Inserir nova
                        db_execute("INSERT INTO processo_respostas (execucao_id, item_id, resposta) VALUES (?, ?, ?)",
                                  [$execucao_id, $item_id, $resposta]);
                    }
                }
            }
        }
        
        // Registrar log
        db_execute("INSERT INTO logs_sistema (usuario_id, acao, modulo) VALUES (?, ?, ?)",
                  [$_SESSION['usuario_id'], "Concluiu processo: {$processo['nome']} para localidade ID {$localidade_id}", 'processos']);
        
        // Se for processo de abertura de filial e estiver concluído, marcar filial como não mais "nova"
        if ($processo['nome'] == 'Processo de Abertura de Filial' || strpos($processo['nome'], 'Abertura') !== false) {
            db_execute("UPDATE localidades SET filial_nova = FALSE WHERE id = ?", [$localidade_id]);
        }
        
        header('Location: ' . url('paginas/executar_processo.php?execucao=' . $execucao_id . '&sucesso=2'));
        exit;
    }
}

// Calcular progresso atual
$progresso = 0;
if (!empty($itens_processo)) {
    $itens_respondidos = 0;
    foreach ($itens_processo as $item) {
        if (isset($respostas[$item['id']]) && !empty($respostas[$item['id']])) {
            $itens_respondidos++;
        }
    }
    $progresso = round(($itens_respondidos / count($itens_processo)) * 100);
}
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                <?php 
                if ($_GET['sucesso'] == 1) echo '✅ Rascunho salvo com sucesso!';
                if ($_GET['sucesso'] == 2) echo '✅ Processo concluído com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="execucao-container">
            <!-- CABEÇALHO -->
            <div class="execucao-header">
                <h1 style="margin: 0 0 10px 0;">
                    <i class="fas fa-tasks"></i> Executar Processo
                </h1>
                <p style="margin: 0; opacity: 0.9;">
                    Execute processos para a localidade selecionada
                </p>
            </div>
            
            <!-- SELEÇÃO DE LOCALIDADE E PROCESSO -->
            <?php if (!$localidade_id || !$processo_id): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-search"></i> Selecionar Localidade e Processo</h2>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label>Localidade *</label>
                                <select name="localidade" class="form-control" required 
                                        onchange="this.form.submit()">
                                    <option value="">Selecione uma localidade...</option>
                                    <?php 
                                    $localidades_lista = db_fetch_all("
                                        SELECT * FROM localidades 
                                        WHERE ativo = TRUE
                                        ORDER BY nome
                                    ");
                                    foreach ($localidades_lista as $loc): 
                                        $is_nova = $loc['filial_nova'] ? ' ⭐ NOVA' : '';
                                    ?>
                                    <option value="<?php echo $loc['id']; ?>" 
                                            <?php echo $localidade_id == $loc['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['nome'] . ' (' . $loc['codigo'] . ')' . $is_nova); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Processo *</label>
                                <select name="processo" class="form-control" required 
                                        <?php echo !$localidade_id ? 'disabled' : ''; ?>
                                        onchange="this.form.submit()">
                                    <option value="">Selecione um processo...</option>
                                    <?php if ($localidade_id): ?>
                                        <?php foreach ($processos_disponiveis as $proc): ?>
                                        <option value="<?php echo $proc['id']; ?>" 
                                                <?php echo $processo_id == $proc['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($proc['nome']); ?>
                                            <?php if ($proc['padrao']): ?> (Padrão)<?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (!$localidade_id): ?>
                                <small class="text-muted">Selecione uma localidade primeiro</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- INFORMAÇÕES DO PROCESSO -->
            <?php if ($localidade && $processo): ?>
            <div class="execucao-info">
                <div class="info-item">
                    <span class="info-label">Localidade</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($localidade['nome']); ?>
                        <?php if ($localidade['filial_nova']): ?>
                            <span class="badge badge-warning" style="margin-left: 5px;">NOVA</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Código</span>
                    <span class="info-value"><?php echo htmlspecialchars($localidade['codigo']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Processo</span>
                    <span class="info-value"><?php echo htmlspecialchars($processo['nome']); ?></span>
                </div>
                
                <div class="info-item">
                    <span class="info-label">Responsável</span>
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
                        <div class="stat-number"><?php echo count($itens_processo); ?></div>
                        <div class="stat-label">Total de Etapas</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">
                            <?php 
                            $respondidos = 0;
                            foreach ($itens_processo as $item) {
                                if (isset($respostas[$item['id']]) && !empty($respostas[$item['id']])) {
                                    $respondidos++;
                                }
                            }
                            echo $respondidos;
                            ?>
                        </div>
                        <div class="stat-label">Etapas Concluídas</div>
                    </div>
                </div>
                
                <?php if ($modo_edicao && $execucao): ?>
                <div style="text-align: center; margin-top: 15px;">
                    <span class="badge" style="background: #6c757d; color: white; padding: 5px 10px;">
                        <i class="fas fa-edit"></i> Editando execução #<?php echo $execucao_id; ?>
                    </span>
                    <?php if ($execucao['progresso'] == 100): ?>
                    <span class="badge" style="background: #28a745; color: white; padding: 5px 10px; margin-left: 5px;">
                        <i class="fas fa-check"></i> Concluído
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- FORMULÁRIO DO PROCESSO -->
            <form method="POST" id="formProcesso">
                <!-- ETAPAS DO PROCESSO -->
                <?php if (empty($itens_processo)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Este processo não possui etapas definidas.
                    </div>
                <?php else: ?>
                    <?php foreach ($itens_processo as $index => $item): 
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
                                                  placeholder="Descreva o resultado desta etapa..."><?php echo htmlspecialchars($resposta_atual); ?></textarea>
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
                    <h3><i class="fas fa-sticky-note"></i> Observações Finais</h3>
                    <div class="form-group">
                        <textarea name="observacoes" class="form-control" rows="4" 
                                  placeholder="Adicione observações relevantes sobre a execução deste processo..."><?php 
                            echo $execucao['observacoes'] ?? ''; 
                        ?></textarea>
                    </div>
                </div>
                
                <!-- AÇÕES -->
                <div class="acoes-fixa">
                    <div>
                        <a href="<?php echo url('paginas/executar_processo.php'); ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="salvar_rascunho" class="btn btn-salvar-rascunho">
                            <i class="fas fa-save"></i> Salvar Progresso
                        </button>
                        
                        <button type="submit" name="concluir_processo" class="btn btn-concluir"
                                onclick="return confirm('Tem certeza que deseja concluir este processo?\n\nApós concluir, não será possível editar as respostas.')">
                            <i class="fas fa-check"></i> Concluir Processo
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- HISTÓRICO -->
            <?php if (!empty($historico)): ?>
            <div style="margin-top: 50px;">
                <h2><i class="fas fa-history"></i> Histórico de Execuções</h2>
                <p>Últimas execuções deste processo para esta localidade:</p>
                
                <?php foreach ($historico as $hist): 
                    if ($hist['id'] == $execucao_id) continue; // Não mostrar a execução atual
                ?>
                <div class="historico-item">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong><?php echo htmlspecialchars($hist['responsavel_nome']); ?></strong>
                            <span class="badge" style="background: 
                                <?php 
                                echo $hist['progresso'] == 100 ? '#28a745' : '#ffc107';
                                ?>; 
                                color: white; margin-left: 10px;">
                                <?php echo $hist['progresso']; ?>% Concluído
                            </span>
                        </div>
                        <div>
                            <a href="<?php echo url('paginas/executar_processo.php?execucao=' . $hist['id']); ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </div>
                    </div>
                    <div class="historico-data">
                        <?php echo date('d/m/Y H:i', strtotime($hist['data_inicio'])); ?>
                        <?php if ($hist['data_conclusao']): ?>
                            • Concluído em: <?php echo date('d/m/Y H:i', strtotime($hist['data_conclusao'])); ?>
                        <?php endif; ?>
                        <?php if ($hist['observacoes']): ?>
                            • <?php echo htmlspecialchars(substr($hist['observacoes'], 0, 100)); ?>...
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php endif; // Fim do if ($localidade && $processo) ?>
        </div>
    </div>
</div>

<script>
// Função para atualizar progresso
function atualizarProgresso() {
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
    const form = document.getElementById('formProcesso');
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

// Validação antes de concluir
document.querySelector('button[name="concluir_processo"]')?.addEventListener('click', function(e) {
    const itens = document.querySelectorAll('.checklist-item');
    let todosRespondidos = true;
    
    itens.forEach(item => {
        const inputs = item.querySelectorAll('input[type="radio"], input[type="text"], input[type="date"], textarea');
        let itemPreenchido = false;
        
        inputs.forEach(input => {
            if (input.type === 'radio' || input.type === 'checkbox') {
                if (input.checked) itemPreenchido = true;
            } else {
                if (input.value.trim() !== '') itemPreenchido = true;
            }
        });
        
        if (!itemPreenchido) {
            todosRespondidos = false;
            item.classList.add('error');
        } else {
            item.classList.remove('error');
        }
    });
    
    if (!todosRespondidos) {
        e.preventDefault();
        alert('Por favor, responda todas as etapas antes de concluir o processo.');
        irParaProximoNaoRespondido();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>