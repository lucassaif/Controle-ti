<?php
$page_title = 'Processos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CADASTRAR NOVO PROCESSO
    if (isset($_POST['cadastrar_processo'])) {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $padrao = isset($_POST['padrao']) ? 1 : 0;
        
        $sql = "INSERT INTO modelos_processo (nome, descricao, padrao) VALUES (?, ?, ?)";
        $processo_id = db_insert($sql, [$nome, $descricao, $padrao]);
        
        if ($processo_id) {
            // Se for padrão, vincular a todas as localidades novas
            if ($padrao) {
                // Este vínculo será feito automaticamente quando marcar "filial_nova"
            }
            
            // Redirecionar para edição dos itens
            header('Location: ' . url('paginas/processos.php?editar=' . $processo_id . '&sucesso=1'));
            exit;
        }
    }
    
    // ADICIONAR ITEM AO PROCESSO
    if (isset($_POST['adicionar_item'])) {
        $processo_id = $_POST['processo_id'];
        $descricao = $_POST['descricao'];
        $tipo_resposta = $_POST['tipo_resposta'];
        $ordem = $_POST['ordem'];
        
        $sql = "INSERT INTO processo_itens (processo_id, descricao, tipo_resposta, ordem) VALUES (?, ?, ?, ?)";
        db_execute($sql, [$processo_id, $descricao, $tipo_resposta, $ordem]);
        
        header('Location: ' . url('paginas/processos.php?editar=' . $processo_id . '&sucesso=2'));
        exit;
    }
    
    // REMOVER ITEM
    if (isset($_POST['remover_item'])) {
        $item_id = $_POST['item_id'];
        $processo_id = $_POST['processo_id'];
        
        db_execute("DELETE FROM processo_itens WHERE id = ?", [$item_id]);
        
        header('Location: ' . url('paginas/processos.php?editar=' . $processo_id . '&sucesso=3'));
        exit;
    }
    
    // VINCULAR PROCESSO A LOCALIDADES
    if (isset($_POST['vincular_localidades'])) {
        $processo_id = $_POST['processo_id'];
        $localidades_selecionadas = $_POST['localidades'] ?? [];
        
        // Remover vínculos existentes
        db_execute("DELETE FROM localidade_processo WHERE processo_id = ?", [$processo_id]);
        
        // Adicionar novos vínculos
        foreach ($localidades_selecionadas as $localidade_id) {
            db_execute("INSERT INTO localidade_processo (localidade_id, processo_id) VALUES (?, ?)", 
                      [$localidade_id, $processo_id]);
            
            // Verificar se já existe execução para esta combinação
            $existe_execucao = db_fetch_one("SELECT id FROM execucao_processo WHERE localidade_id = ? AND processo_id = ?", 
                                          [$localidade_id, $processo_id]);
            if (!$existe_execucao) {
                // Criar execução pendente
                db_execute("INSERT INTO execucao_processo (localidade_id, processo_id, usuario_id, progresso) VALUES (?, ?, ?, ?)",
                          [$localidade_id, $processo_id, $_SESSION['usuario_id'], 0]);
            }
        }
        
        header('Location: ' . url('paginas/processos.php?editar=' . $processo_id . '&sucesso=4'));
        exit;
    }
    
    // EXCLUIR PROCESSO
    if (isset($_POST['excluir_processo'])) {
        $processo_id = $_POST['processo_id'];
        
        db_execute("DELETE FROM modelos_processo WHERE id = ?", [$processo_id]);
        
        header('Location: ' . url('paginas/processos.php?sucesso=5'));
        exit;
    }
}

// Buscar processos
$processos = db_fetch_all("SELECT * FROM modelos_processo ORDER BY nome");

// Buscar localidades para os selects
$localidades = db_fetch_all("SELECT * FROM localidades WHERE ativo = TRUE ORDER BY nome");

// Verificar se está editando algum processo
$editar_id = $_GET['editar'] ?? 0;
$processo_editando = null;
$itens_processo = [];
$localidades_vincular = [];

if ($editar_id) {
    $processo_editando = db_fetch_one("SELECT * FROM modelos_processo WHERE id = ?", [$editar_id]);
    if ($processo_editando) {
        $itens_processo = db_fetch_all("SELECT * FROM processo_itens WHERE processo_id = ? ORDER BY ordem", [$editar_id]);
        $localidades_vincular = db_fetch_all("SELECT l.*, lp.processo_id 
                                            FROM localidades l 
                                            LEFT JOIN localidade_processo lp ON l.id = lp.localidade_id AND lp.processo_id = ? 
                                            WHERE l.ativo = TRUE
                                            ORDER BY l.nome", [$editar_id]);
    }
}

// Buscar estatísticas de execução
$estatisticas = [];
foreach ($processos as $processo) {
    $estatisticas[$processo['id']] = db_fetch_one("
        SELECT 
            COUNT(DISTINCT ep.id) as total_execucoes,
            COUNT(DISTINCT CASE WHEN ep.progresso = 100 THEN ep.id END) as concluidas,
            COUNT(DISTINCT l.id) as localidades_vinculadas
        FROM modelos_processo mp
        LEFT JOIN localidade_processo lp ON mp.id = lp.processo_id
        LEFT JOIN localidades l ON lp.localidade_id = l.id
        LEFT JOIN execucao_processo ep ON mp.id = ep.processo_id
        WHERE mp.id = ?
        GROUP BY mp.id
    ", [$processo['id']]);
}
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                <?php 
                $mensagens = [
                    1 => 'Processo criado com sucesso!',
                    2 => 'Item adicionado ao processo!',
                    3 => 'Item removido do processo!',
                    4 => 'Localidades vinculadas com sucesso!',
                    5 => 'Processo excluído com sucesso!'
                ];
                echo $mensagens[$_GET['sucesso']] ?? 'Operação realizada com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- CARD: CADASTRAR NOVO PROCESSO -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Criar Novo Processo</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="nome">Nome do Processo *</label>
                            <input type="text" id="nome" name="nome" class="form-control" required 
                                   placeholder="Ex: Processo de Abertura de Filial">
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3" 
                                      placeholder="Descreva o propósito deste processo..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="padrao" value="1">
                                Processo Padrão (será associado automaticamente a novas filiais)
                            </label>
                        </div>
                        
                        <button type="submit" name="cadastrar_processo" class="btn btn-primary">
                            <i class="fas fa-save"></i> Criar Processo
                        </button>
                    </form>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #e6f2ff; border-radius: 4px;">
                        <h4 style="margin-top: 0; color: #007bff;"><i class="fas fa-lightbulb"></i> Dica</h4>
                        <p style="margin: 0; font-size: 14px;">
                            <strong>Processos</strong> são checklists aplicados a <strong>localidades/filiais</strong>.<br>
                            Exemplos: Abertura de filial, Manutenção mensal, Migração de sistema.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- CARD: LISTA DE PROCESSOS -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-tasks"></i> Processos Cadastrados</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($processos)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhum processo cadastrado ainda.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                        <th>Estatísticas</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($processos as $processo): 
                                        $contagem_itens = db_fetch_one("SELECT COUNT(*) as total FROM processo_itens WHERE processo_id = ?", 
                                                                      [$processo['id']])['total'];
                                        $stats = $estatisticas[$processo['id']] ?? ['total_execucoes' => 0, 'concluidas' => 0, 'localidades_vinculadas' => 0];
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($processo['nome']); ?>
                                            <?php if ($processo['padrao']): ?>
                                                <span class="badge badge-success">Padrão</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($processo['descricao'] ?? '', 0, 50)) . '...'; ?></td>
                                        <td>
                                            <?php echo $processo['padrao'] ? 'Padrão' : 'Personalizado'; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                                <span class="badge badge-info" title="Itens">
                                                    <i class="fas fa-list"></i> <?php echo $contagem_itens; ?>
                                                </span>
                                                <span class="badge badge-primary" title="Localidades vinculadas">
                                                    <i class="fas fa-building"></i> <?php echo $stats['localidades_vinculadas']; ?>
                                                </span>
                                                <span class="badge badge-success" title="Execuções concluídas">
                                                    <i class="fas fa-check"></i> <?php echo $stats['concluidas']; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('paginas/processos.php?editar=' . $processo['id']); ?>" 
                                               class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <a href="<?php echo url('paginas/executar_processo.php?processo=' . $processo['id']); ?>" 
                                               class="btn btn-sm btn-success" title="Executar">
                                                <i class="fas fa-play"></i>
                                            </a>
                                            
                                            <?php if (!$processo['padrao']): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmarExclusao(<?php echo $processo['id']; ?>, '<?php echo addslashes($processo['nome']); ?>')"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
        
        <!-- SEÇÃO DE EDIÇÃO (aparece apenas quando editar_id > 0) -->
        <?php if ($processo_editando): ?>
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-edit"></i> Editando: <?php echo htmlspecialchars($processo_editando['nome']); ?>
                    <?php if ($processo_editando['padrao']): ?>
                        <span class="badge badge-success">Padrão</span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="card-body">
                <!-- ABA DE ITENS -->
                <div style="margin-bottom: 30px;">
                    <h3><i class="fas fa-list-check"></i> Itens do Processo</h3>
                    
                    <?php if (empty($itens_processo)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Este processo ainda não tem itens. Adicione abaixo.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Ordem</th>
                                        <th>Descrição</th>
                                        <th>Tipo de Resposta</th>
                                        <th style="width: 100px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens_processo as $item): 
                                        $tipo_texto = [
                                            'sim_nao' => 'Sim/Não',
                                            'ok_nao_ok' => 'OK/Não OK',
                                            'texto' => 'Texto',
                                            'data' => 'Data'
                                        ][$item['tipo_resposta']] ?? $item['tipo_resposta'];
                                    ?>
                                    <tr>
                                        <td><?php echo $item['ordem']; ?></td>
                                        <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $tipo_texto; ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="processo_id" value="<?php echo $processo_editando['id']; ?>">
                                                <button type="submit" name="remover_item" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Remover este item?')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- FORMULÁRIO PARA ADICIONAR ITEM -->
                    <div class="card" style="background: #f8f9fa;">
                        <div class="card-body">
                            <h4><i class="fas fa-plus"></i> Adicionar Novo Item</h4>
                            <form method="POST">
                                <input type="hidden" name="processo_id" value="<?php echo $processo_editando['id']; ?>">
                                
                                <div class="row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 15px; align-items: end;">
                                    <div class="form-group">
                                        <label>Descrição do Item *</label>
                                        <input type="text" name="descricao" class="form-control" required 
                                               placeholder="Ex: Verificar se o backup está configurado">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Tipo de Resposta</label>
                                        <select name="tipo_resposta" class="form-control">
                                            <option value="sim_nao">Sim/Não</option>
                                            <option value="ok_nao_ok">OK/Não OK</option>
                                            <option value="texto">Texto</option>
                                            <option value="data">Data</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Ordem</label>
                                        <input type="number" name="ordem" class="form-control" 
                                               value="<?php echo count($itens_processo) + 1; ?>" min="1">
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" name="adicionar_item" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Adicionar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- ABA DE VINCULAÇÃO COM LOCALIDADES -->
                <div style="margin-top: 30px;">
                    <h3><i class="fas fa-link"></i> Vincular a Localidades</h3>
                    <p>Selecione as localidades onde este processo deve ser executado:</p>
                    
                    <form method="POST">
                        <input type="hidden" name="processo_id" value="<?php echo $processo_editando['id']; ?>">
                        
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selecionarTodas()">
                                    <i class="fas fa-check-square"></i> Selecionar Todas
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desmarcarTodas()">
                                    <i class="fas fa-square"></i> Desmarcar Todas
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="selecionarNovas()">
                                    <i class="fas fa-star"></i> Apenas Filiais Novas
                                </button>
                            </div>
                        </div>
                        
                        <div class="row" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; max-height: 300px; overflow-y: auto; padding: 10px;">
                            <?php foreach ($localidades_vincular as $localidade): 
                                $checked = $localidade['processo_id'] ? 'checked' : '';
                                $is_nova = db_fetch_one("SELECT filial_nova FROM localidades WHERE id = ?", [$localidade['id']])['filial_nova'] ?? 0;
                            ?>
                            <div class="form-check localidade-item" data-nova="<?php echo $is_nova; ?>">
                                <label class="form-check-label" style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid #dee2e6; border-radius: 4px; background: white;">
                                    <input type="checkbox" name="localidades[]" value="<?php echo $localidade['id']; ?>" 
                                           class="form-check-input localidade-checkbox" <?php echo $checked; ?>>
                                    
                                    <div style="flex: 1;">
                                        <strong><?php echo htmlspecialchars($localidade['nome']); ?></strong>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php echo htmlspecialchars($localidade['codigo']); ?>
                                            <?php if ($is_nova): ?>
                                                <span class="badge badge-warning" style="margin-left: 5px;">NOVA</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php 
                                    // Verificar progresso desta localidade
                                    $progresso = db_fetch_one("SELECT progresso FROM execucao_processo WHERE localidade_id = ? AND processo_id = ?", 
                                                             [$localidade['id'], $processo_editando['id']]);
                                    if ($progresso): 
                                    ?>
                                    <div style="margin-left: 10px;">
                                        <div style="width: 60px; height: 60px; position: relative;">
                                            <svg width="60" height="60" viewBox="0 0 36 36" style="transform: rotate(-90deg);">
                                                <path d="M18 2.0845
                                                    a 15.9155 15.9155 0 0 1 0 31.831
                                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                                    fill="none" stroke="#e6e6e6" stroke-width="3"/>
                                                <path d="M18 2.0845
                                                    a 15.9155 15.9155 0 0 1 0 31.831
                                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                                    fill="none" stroke="#28a745" stroke-width="3" 
                                                    stroke-dasharray="<?php echo $progresso['progresso']; ?>, 100"/>
                                            </svg>
                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 10px; font-weight: bold; color: #28a745;">
                                                <?php echo $progresso['progresso']; ?>%
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" name="vincular_localidades" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Vínculos
                        </button>
                        
                        <p class="text-muted" style="margin-top: 10px; font-size: 12px;">
                            <i class="fas fa-info-circle"></i> Este processo ficará disponível para execução nas localidades selecionadas.
                        </p>
                    </form>
                </div>
                
                <!-- PREVIEW DO PROCESSO -->
                <div style="margin-top: 40px; border-top: 2px solid #dee2e6; padding-top: 20px;">
                    <h3><i class="fas fa-eye"></i> Visualização do Processo</h3>
                    
                    <div class="card" style="border: 1px solid #dee2e6;">
                        <div class="card-header" style="background: #e6f2ff;">
                            <h4 style="margin: 0;"><?php echo htmlspecialchars($processo_editando['nome']); ?></h4>
                            <?php if ($processo_editando['descricao']): ?>
                                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">
                                    <?php echo htmlspecialchars($processo_editando['descricao']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($itens_processo)): ?>
                                <p class="text-muted">Nenhum item definido ainda.</p>
                            <?php else: ?>
                                <form style="max-width: 800px;">
                                    <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                                        <label style="font-weight: 500; margin-bottom: 8px;">
                                            <i class="fas fa-building"></i> Localidade:
                                        </label>
                                        <select class="form-control" style="max-width: 300px;">
                                            <option>Selecione uma localidade...</option>
                                            <?php foreach ($localidades as $loc): ?>
                                            <option><?php echo htmlspecialchars($loc['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <?php foreach ($itens_processo as $item): 
                                        $tipo = $item['tipo_resposta'];
                                    ?>
                                    <div class="form-group" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #eee;">
                                        <label style="font-weight: 500; margin-bottom: 8px;">
                                            <?php echo $item['ordem']; ?>. <?php echo htmlspecialchars($item['descricao']); ?>
                                        </label>
                                        
                                        <div>
                                            <?php if ($tipo === 'sim_nao'): ?>
                                                <div style="display: flex; gap: 20px;">
                                                    <label style="display: flex; align-items: center; gap: 5px;">
                                                        <input type="radio" name="item_<?php echo $item['id']; ?>" value="sim"> Sim
                                                    </label>
                                                    <label style="display: flex; align-items: center; gap: 5px;">
                                                        <input type="radio" name="item_<?php echo $item['id']; ?>" value="nao"> Não
                                                    </label>
                                                </div>
                                            
                                            <?php elseif ($tipo === 'ok_nao_ok'): ?>
                                                <div style="display: flex; gap: 20px;">
                                                    <label style="display: flex; align-items: center; gap: 5px;">
                                                        <input type="radio" name="item_<?php echo $item['id']; ?>" value="ok"> OK
                                                    </label>
                                                    <label style="display: flex; align-items: center; gap: 5px;">
                                                        <input type="radio" name="item_<?php echo $item['id']; ?>" value="nao_ok"> Não OK
                                                    </label>
                                                </div>
                                            
                                            <?php elseif ($tipo === 'texto'): ?>
                                                <textarea class="form-control" rows="2" placeholder="Digite aqui..."></textarea>
                                            
                                            <?php elseif ($tipo === 'data'): ?>
                                                <input type="date" class="form-control" style="max-width: 200px;">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="form-group" style="margin-top: 30px;">
                                        <div style="display: flex; gap: 15px; align-items: center;">
                                            <button type="button" class="btn btn-success" disabled>
                                                <i class="fas fa-check"></i> Finalizar Processo (Preview)
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" disabled>
                                                <i class="fas fa-save"></i> Salvar Rascunho
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação para exclusão -->
<div id="modalExcluir" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 8px; width: 500px;">
        <h3 style="color: #dc3545; margin-top: 0;">
            <i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão
        </h3>
        
        <p id="mensagemExclusao"></p>
        <p style="color: #666; font-size: 14px;">
            <i class="fas fa-info-circle"></i> Todos os itens e vínculos também serão removidos.
        </p>
        
        <form method="POST" id="formExcluir">
            <input type="hidden" name="processo_id" id="processoIdExcluir">
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalExcluir()" 
                        style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                    Cancelar
                </button>
                <button type="submit" name="excluir_processo" class="btn btn-danger" 
                        style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('processoIdExcluir').value = id;
    document.getElementById('mensagemExclusao').innerHTML = 
        'Tem certeza que deseja excluir o processo <strong>"' + nome + '"</strong>?';
    document.getElementById('modalExcluir').style.display = 'flex';
}

function fecharModalExcluir() {
    document.getElementById('modalExcluir').style.display = 'none';
}

// Fechar modal ao clicar fora
document.getElementById('modalExcluir').addEventListener('click', function(e) {
    if (e.target.id === 'modalExcluir') {
        fecharModalExcluir();
    }
});

// Funções para seleção de localidades
function selecionarTodas() {
    document.querySelectorAll('.localidade-checkbox').forEach(cb => {
        cb.checked = true;
    });
}

function desmarcarTodas() {
    document.querySelectorAll('.localidade-checkbox').forEach(cb => {
        cb.checked = false;
    });
}

function selecionarNovas() {
    document.querySelectorAll('.localidade-item').forEach(item => {
        const isNova = item.getAttribute('data-nova') === '1';
        const checkbox = item.querySelector('.localidade-checkbox');
        checkbox.checked = isNova;
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>