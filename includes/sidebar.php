<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <ul class="sidebar-nav" style="list-style: none; padding: 0;">
        <li style="margin-bottom: 5px;">
            <a href="<?php echo url('index.php'); ?>" 
               style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
               class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"
               onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
               onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="<?php echo url('paginas/localidades.php'); ?>" 
               style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
               class="<?php echo $current_page == 'localidades.php' ? 'active' : ''; ?>"
               onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
               onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
                <i class="fas fa-building"></i> Localidades
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="<?php echo url('paginas/equipamentos.php'); ?>" 
               style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
               class="<?php echo $current_page == 'equipamentos.php' ? 'active' : ''; ?>"
               onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
               onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
                <i class="fas fa-desktop"></i> Equipamentos
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="<?php echo url('paginas/checklists.php'); ?>" 
               style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
               class="<?php echo $current_page == 'checklists.php' ? 'active' : ''; ?>"
               onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
               onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
                <i class="fas fa-clipboard-check"></i> Checklists
            </a>
        </li>
        <li style="margin-bottom: 5px;">
            <a href="<?php echo url('paginas/processos.php'); ?>" 
               style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
               class="<?php echo $current_page == 'processos.php' ? 'active' : ''; ?>"
               onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
               onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
                <i class="fas fa-tasks"></i> Processos
            </a>
        </li>
        <?php if (is_admin()): ?>
        <li style="margin-bottom: 5px;">
            <a href="<?php echo url('paginas/usuarios.php'); ?>" 
               style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
               class="<?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>"
               onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
               onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
                <i class="fas fa-users-cog"></i> Usuários
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <li style="margin-bottom: 5px;">
    <a href="<?php echo url('paginas/executar_checklist.php'); ?>" 
       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
       class="<?php echo $current_page == 'executar_checklist.php' ? 'active' : ''; ?>"
       onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
       onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
        <i class="fas fa-play-circle"></i> Executar Checklist
    </a>
</li>
<li style="margin-bottom: 5px;">
    <a href="<?php echo url('paginas/executar_processo.php'); ?>" 
       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; text-decoration: none; color: #333; border-left: 3px solid transparent;"
       class="<?php echo $current_page == 'executar_processo.php' ? 'active' : ''; ?>"
       onmouseover="this.style.backgroundColor='#e6f2ff'; this.style.borderLeftColor='#007bff';"
       onmouseout="if(!this.classList.contains('active')) {this.style.backgroundColor=''; this.style.borderLeftColor='transparent';}">
        <i class="fas fa-play-circle"></i> Executar Processo
    </a>
</li>
</nav>