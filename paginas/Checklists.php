<?php
$page_title = 'Checklists';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CADASTRAR NOVO CHECKLIST
    if (isset($_POST['cadastrar_checklist'])) {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $padrao = isset($_POST['padrao']) ? 1 : 0;
        
        $sql = "INSERT INTO modelos_checklist (nome, descricao, padrao) VALUES (?, ?, ?)";
        $checklist_id = db_insert($sql, [$nome, $descricao, $padrao]);
        
        if ($checklist_id) {
            // Se for padrão, vincular a todos os tipos de equipamento
            if ($padrao) {
                $tipos = db_fetch_all("SELECT id FROM tipos_equipamento");
                foreach ($tipos as $tipo) {
                    db_execute("INSERT INTO tipo_checklist (tipo_id, checklist_id) VALUES (?, ?)", 
                              [$tipo['id'], $checklist_id]);
                }
            }
            
            // Redirecionar para edição dos itens
            header('Location: ' . url('paginas/checklists.php?editar=' . $checklist_id . '&sucesso=1'));
            exit;
        }
    }
    
    // ADICIONAR ITEM AO CHECKLIST
    if (isset($_POST['adicionar_item'])) {
        $checklist_id = $_POST['checklist_id'];
        $descricao = $_POST['descricao'];
        $tipo_resposta = $_POST['tipo_resposta'];
        $ordem = $_POST['ordem'];
        
        $sql = "INSERT INTO checklist_itens (checklist_id, descricao, tipo_resposta, ordem) VALUES (?, ?, ?, ?)";
        db_execute($sql, [$checklist_id, $descricao, $tipo_resposta, $ordem]);
        
        header('Location: ' . url('paginas/checklists.php?editar=' . $checklist_id . '&sucesso=2'));
        exit;
    }
    
    // REMOVER ITEM
    if (isset($_POST['remover_item'])) {
        $item_id = $_POST['item_id'];
        $checklist_id = $_POST['checklist_id'];
        
        db_execute("DELETE FROM checklist_itens WHERE id = ?", [$item_id]);
        
        header('Location: ' . url('paginas/checklists.php?editar=' . $checklist_id . '&sucesso=3'));
        exit;
    }
    
    // VINCULAR CHECKLIST A TIPOS
    if (isset($_POST['vincular_tipos'])) {
        $checklist_id = $_POST['checklist_id'];
        $tipos_selecionados = $_POST['tipos'] ?? [];
        
        // Remover vínculos existentes
        db_execute("DELETE FROM tipo_checklist WHERE checklist_id = ?", [$checklist_id]);
        
        // Adicionar novos vínculos
        foreach ($tipos_selecionados as $tipo_id) {
            db_execute("INSERT INTO tipo_checklist (tipo_id, checklist_id) VALUES (?, ?)", 
                      [$tipo_id, $checklist_id]);
        }
        
        header('Location: ' . url('paginas/checklists.php?editar=' . $checklist_id . '&sucesso=4'));
        exit;
    }
    
    // EXCLUIR CHECKLIST
    if (isset($_POST['excluir_checklist'])) {
        $checklist_id = $_POST['checklist_id'];
        
        db_execute("DELETE FROM modelos_checklist WHERE id = ?", [$checklist_id]);
        
        header('Location: ' . url('paginas/checklists.php?sucesso=5'));
        exit;
    }
}

// Buscar checklists
$checklists = db_fetch_all("SELECT * FROM modelos_checklist ORDER BY nome");

// Buscar tipos de equipamento para os selects
$tipos_equipamento = db_fetch_all("SELECT * FROM tipos_equipamento ORDER BY nome");

// Verificar se está editando algum checklist
$editar_id = $_GET['editar'] ?? 0;
$checklist_editando = null;
$itens_checklist = [];
$tipos_vincular = [];

if ($editar_id) {
    $checklist_editando = db_fetch_one("SELECT * FROM modelos_checklist WHERE id = ?", [$editar_id]);
    if ($checklist_editando) {
        $itens_checklist = db_fetch_all("SELECT * FROM checklist_itens WHERE checklist_id = ? ORDER BY ordem", [$editar_id]);
        $tipos_vincular = db_fetch_all("SELECT t.*, tc.checklist_id 
                                      FROM tipos_equipamento t 
                                      LEFT JOIN tipo_checklist tc ON t.id = tc.tipo_id AND tc.checklist_id = ? 
                                      ORDER BY t.nome", [$editar_id]);
    }
}
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_GET['sucesso'])): ?>
            <div class="alert alert-success">
                <?php 
                $mensagens = [
                    1 => 'Checklist criado com sucesso!',
                    2 => 'Item adicionado ao checklist!',
                    3 => 'Item removido do checklist!',
                    4 => 'Tipos de equipamento vinculados com sucesso!',
                    5 => 'Checklist excluído com sucesso!'
                ];
                echo $mensagens[$_GET['sucesso']] ?? 'Operação realizada com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- CARD: CADASTRAR NOVO CHECKLIST -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-plus-circle"></i> Criar Novo Checklist</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="nome">Nome do Checklist *</label>
                            <input type="text" id="nome" name="nome" class="form-control" required 
                                   placeholder="Ex: Checklist Instalação Windows">
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="form-control" rows="3" 
                                      placeholder="Descreva o propósito deste checklist..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="padrao" value="1">
                                Checklist Padrão (será associado automaticamente a novos tipos de equipamento)
                            </label>
                        </div>
                        
                        <button type="submit" name="cadastrar_checklist" class="btn btn-primary">
                            <i class="fas fa-save"></i> Criar Checklist
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- CARD: LISTA DE CHECKLISTS -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-clipboard-list"></i> Checklists Cadastrados</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($checklists)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Nenhum checklist cadastrado ainda.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                        <th>Itens</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($checklists as $checklist): 
                                        $contagem_itens = db_fetch_one("SELECT COUNT(*) as total FROM checklist_itens WHERE checklist_id = ?", 
                                                                      [$checklist['id']])['total'];
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($checklist['nome']); ?>
                                            <?php if ($checklist['padrao']): ?>
                                                <span class="badge badge-success">Padrão</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($checklist['descricao'] ?? '', 0, 50)) . '...'; ?></td>
                                        <td>
                                            <?php echo $checklist['padrao'] ? 'Padrão' : 'Personalizado'; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $contagem_itens; ?> itens</span>
                                        </td>
                                        <td>
                                            <a href="<?php echo url('paginas/checklists.php?editar=' . $checklist['id']); ?>" 
                                               class="btn btn-sm btn-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if (!$checklist['padrao']): ?>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmarExclusao(<?php echo $checklist['id']; ?>, '<?php echo addslashes($checklist['nome']); ?>')"
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
        <?php if ($checklist_editando): ?>
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-edit"></i> Editando: <?php echo htmlspecialchars($checklist_editando['nome']); ?>
                    <?php if ($checklist_editando['padrao']): ?>
                        <span class="badge badge-success">Padrão</span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <div class="card-body">
                <!-- ABA DE ITENS -->
                <div style="margin-bottom: 30px;">
                    <h3><i class="fas fa-list-check"></i> Itens do Checklist</h3>
                    
                    <?php if (empty($itens_checklist)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Este checklist ainda não tem itens. Adicione abaixo.
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
                                    <?php foreach ($itens_checklist as $item): 
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
                                                <input type="hidden" name="checklist_id" value="<?php echo $checklist_editando['id']; ?>">
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
                                <input type="hidden" name="checklist_id" value="<?php echo $checklist_editando['id']; ?>">
                                
                                <div class="row" style="display: grid; grid-template-columns: 3fr 1fr 1fr auto; gap: 15px; align-items: end;">
                                    <div class="form-group">
                                        <label>Descrição do Item *</label>
                                        <input type="text" name="descricao" class="form-control" required 
                                               placeholder="Ex: Verificar se o antivírus está atualizado">
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
                                               value="<?php echo count($itens_checklist) + 1; ?>" min="1">
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
                
                <!-- ABA DE VINCULAÇÃO COM TIPOS DE EQUIPAMENTO -->
                <div style="margin-top: 30px;">
                    <h3><i class="fas fa-link"></i> Vincular a Tipos de Equipamento</h3>
                    <p>Selecione os tipos de equipamento que devem usar este checklist:</p>
                    
                    <form method="POST">
                        <input type="hidden" name="checklist_id" value="<?php echo $checklist_editando['id']; ?>">
                        
                        <div class="row" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 20px;">
                            <?php foreach ($tipos_vincular as $tipo): 
                                $checked = $tipo['checklist_id'] ? 'checked' : '';
                            ?>
                            <div class="form-check">
                                <label class="form-check-label" style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="tipos[]" value="<?php echo $tipo['id']; ?>" 
                                           class="form-check-input" <?php echo $checked; ?>>
                                    <?php echo htmlspecialchars($tipo['nome']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" name="vincular_tipos" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Vínculos
                        </button>
                        
                        <p class="text-muted" style="margin-top: 10px; font-size: 12px;">
                            <i class="fas fa-info-circle"></i> Este checklist será disponibilizado para execução nos equipamentos dos tipos selecionados.
                        </p>
                    </form>
                </div>
                
                <!-- PREVIEW DO CHECKLIST -->
                <div style="margin-top: 40px; border-top: 2px solid #dee2e6; padding-top: 20px;">
                    <h3><i class="fas fa-eye"></i> Visualização do Checklist</h3>
                    
                    <div class="card" style="border: 1px solid #dee2e6;">
                        <div class="card-header" style="background: #e6f2ff;">
                            <h4 style="margin: 0;"><?php echo htmlspecialchars($checklist_editando['nome']); ?></h4>
                            <?php if ($checklist_editando['descricao']): ?>
                                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">
                                    <?php echo htmlspecialchars($checklist_editando['descricao']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($itens_checklist)): ?>
                                <p class="text-muted">Nenhum item definido ainda.</p>
                            <?php else: ?>
                                <form style="max-width: 800px;">
                                    <?php foreach ($itens_checklist as $item): 
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
                                    
                                    <div class="form-group">
                                        <button type="button" class="btn btn-success" disabled>
                                            <i class="fas fa-check"></i> Finalizar Checklist (Preview)
                                        </button>
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
            <i class="fas fa-info-circle"></i> Todos os itens vinculados também serão removidos.
        </p>
        
        <form method="POST" id="formExcluir">
            <input type="hidden" name="checklist_id" id="checklistIdExcluir">
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalExcluir()" 
                        style="background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                    Cancelar
                </button>
                <button type="submit" name="excluir_checklist" class="btn btn-danger" 
                        style="background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmarExclusao(id, nome) {
    document.getElementById('checklistIdExcluir').value = id;
    document.getElementById('mensagemExclusao').innerHTML = 
        'Tem certeza que deseja excluir o checklist <strong>"' + nome + '"</strong>?';
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

// Ordenação manual dos itens (opcional)
function moverItem(itemId, direcao) {
    // Implementar se quiser ordenação arrastável
    alert('Funcionalidade de ordenação em desenvolvimento.');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
