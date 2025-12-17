<?php
$page_title = 'Localidades';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cadastrar'])) {
        $codigo = $_POST['codigo'];
        $nome = $_POST['nome'];
        $responsavel = $_POST['responsavel'];
        $filial_nova = isset($_POST['filial_nova']) ? 1 : 0;
        
        $sql = "INSERT INTO localidades (codigo, nome, responsavel, filial_nova) VALUES (?, ?, ?, ?)";
        $id = db_insert($sql, [$codigo, $nome, $responsavel, $filial_nova]);
        
        if ($id && $filial_nova) {
            // Associar processos padrão
            $processos_padrao = db_fetch_all("SELECT id FROM modelos_processo WHERE padrao = TRUE");
            foreach ($processos_padrao as $processo) {
                db_execute("INSERT INTO localidade_processo (localidade_id, processo_id) VALUES (?, ?)", 
                         [$id, $processo['id']]);
                
                // Criar execução do processo
                db_execute("INSERT INTO execucao_processo (localidade_id, processo_id, usuario_id) VALUES (?, ?, ?)",
                         [$id, $processo['id'], $_SESSION['usuario_id']]);
            }
        }
        
        header('Location: ' . url('paginas/localidades.php?sucesso=1'));
        exit;
    }
    
    if (isset($_POST['excluir'])) {
        $id = $_POST['id'];
        db_execute("UPDATE localidades SET ativo = FALSE WHERE id = ?", [$id]);
        header('Location: ' . url('paginas/localidades.php?sucesso=2'));
        exit;
    }
}

// Buscar localidades
$localidades = db_fetch_all("SELECT * FROM localidades WHERE ativo = TRUE ORDER BY nome");
?>
<div class="main-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($_GET['sucesso'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 15px;">
                <?php 
                if ($_GET['sucesso'] == 1) echo '✅ Localidade cadastrada com sucesso!';
                if ($_GET['sucesso'] == 2) echo '✅ Localidade removida com sucesso!';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0; color: #007bff; font-size: 20px;">
                    <i class="fas fa-building"></i> Cadastrar Nova Localidade
                </h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin-bottom: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Código *</label>
                            <input type="text" name="codigo" class="form-control" required 
                                   placeholder="Ex: FL-001">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Nome da Localidade *</label>
                            <input type="text" name="nome" class="form-control" required 
                                   placeholder="Ex: Filial SP Centro">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Responsável</label>
                        <input type="text" name="responsavel" class="form-control" 
                               placeholder="Nome do responsável">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="filial_nova" value="1">
                            Esta é uma filial nova? (ativa processos padrão)
                        </label>
                    </div>
                    
                    <button type="submit" name="cadastrar" class="btn btn-primary" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
                        <i class="fas fa-save"></i> Cadastrar Localidade
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 style="margin: 0; color: #007bff; font-size: 20px;">
                    <i class="fas fa-list"></i> Localidades Cadastradas
                </h2>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Código</th>
                                <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Nome</th>
                                <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Responsável</th>
                                <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Tipo</th>
                                <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Data Cadastro</th>
                                <th style="padding: 12px 15px; text-align: left; background: #e6f2ff; color: #007bff; font-weight: 600; border-bottom: 1px solid #dee2e6;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($localidades as $local): ?>
                            <tr style="border-bottom: 1px solid #dee2e6;" onmouseover="this.style.backgroundColor='rgba(0,123,255,0.05)'" onmouseout="this.style.backgroundColor=''">
                                <td style="padding: 12px 15px;"><?php echo htmlspecialchars($local['codigo']); ?></td>
                                <td style="padding: 12px 15px;">
                                    <?php echo htmlspecialchars($local['nome']); ?>
                                    <?php if ($local['filial_nova']): ?>
                                        <span style="background: #ffc107; color: #856404; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 5px;">NOVA</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 15px;"><?php echo htmlspecialchars($local['responsavel']); ?></td>
                                <td style="padding: 12px 15px;">
                                    <?php echo $local['filial_nova'] ? 'Filial Nova' : 'Localidade'; ?>
                                </td>
                                <td style="padding: 12px 15px;"><?php echo date('d/m/Y', strtotime($local['data_cadastro'])); ?></td>
                                <td style="padding: 12px 15px;">
                                    <a href="<?php echo url('paginas/ver_localidade.php?id=' . $local['id']); ?>" 
                                       class="btn btn-primary" style="background: #007bff; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-block;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Tem certeza que deseja remover esta localidade?');">
                                        <input type="hidden" name="id" value="<?php echo $local['id']; ?>">
                                        <button type="submit" name="excluir" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>