<?php
@session_start();
// Pega o nome do arquivo atual para marcar o menu como ativo
$pagina_atual = basename($_SERVER['PHP_SELF']);
$pag_url = isset($_GET['pag']) ? $_GET['pag'] : '';

// --- PROTEÇÃO DE VARIÁVEIS E COMPATIBILIDADE ---
$nome_display = 'Usuário';
if (isset($nome_usuario)) {
    $nome_display = $nome_usuario;
} elseif (isset($_SESSION['nome_usuario'])) {
    $nome_display = $_SESSION['nome_usuario'];
}

// O ID é fundamental para o link do perfil funcionar
$id_logado = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 0;

// Lógica de foto com verificação de existência física
$foto_display = 'usuario-icone-claro.png';
if (isset($foto_usuario) && !empty($foto_usuario)) {
    $foto_display = $foto_usuario;
} elseif (isset($_SESSION['foto_usuario']) && !empty($_SESSION['foto_usuario'])) {
    $foto_display = $_SESSION['foto_usuario'];
}
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link text-center border-secondary">
        <span class="brand-text font-weight-bold text-uppercase">Nova Arte BJJ</span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <img src="../img/perfis/<?php echo $foto_display; ?>" class="img-circle elevation-2"
                    style="width:35px; height:35px; object-fit:cover;"
                    onerror="this.src='../img/perfis/usuario-icone-claro.png';">
            </div>
            <div class="info d-flex align-items-center justify-content-between w-100">
                <a href="index.php?pag=ver-perfil-aluno&id=<?php echo $id_logado; ?>"
                    class="d-block text-white text-truncate" style="max-width: 120px; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($nome_display); ?>
                </a>
                <a href="index.php?pag=ver-perfil-aluno&id=<?php echo $id_logado; ?>" class="text-white-50 ml-2"
                    title="Ver Meu Perfil">
                    <i class="fas fa-user-edit fa-xs"></i>
                </a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu">

                <li class="nav-item">
                    <a href="index.php"
                        class="nav-link <?php echo ($pagina_atual == 'index.php' && $pag_url == '') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Início</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="index.php?pag=ver-perfil-aluno&id=<?php echo $id_logado; ?>"
                        class="nav-link <?php echo ($pag_url == 'ver-perfil-aluno') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-circle text-info"></i>
                        <p>Meu Perfil</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="index.php?pag=minhas-tecnicas"
                        class="nav-link <?php echo ($pag_url == 'minhas-tecnicas') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-book-open text-primary"></i>
                        <p>Minhas Técnicas</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="index.php?pag=horarios"
                        class="nav-link <?php echo ($pag_url == 'horarios') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Horários</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="index.php?pag=particulares"
                        class="nav-link <?php echo ($pag_url == 'particulares') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p>Aulas Particulares</p>
                    </a>
                </li>

                <li class="nav-item border-bottom border-secondary mb-2"></li>

                <li class="nav-item">
                    <a href="index.php?pag=mensalidades"
                        class="nav-link <?php echo ($pag_url == 'mensalidades') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-receipt text-success"></i>
                        <p>Financeiro</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="index.php?pag=evolucao"
                        class="nav-link <?php echo ($pag_url == 'evolucao') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Evolução Técnica</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="index.php?pag=graficos_evolucao"
                        class="nav-link <?php echo ($pag_url == 'graficos_evolucao') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-pie text-warning"></i>
                        <p>Meus Gráficos <span class="badge badge-info right">Novo</span></p>
                    </a>
                </li>

                <li class="nav-header mt-3 text-uppercase" style="font-size: 0.7rem;">Configurações</li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Sair do Sistema</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>