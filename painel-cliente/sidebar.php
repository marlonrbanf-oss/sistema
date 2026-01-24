<?php
// Pega o nome do arquivo atual para marcar o menu como ativo
$pagina_atual = basename($_SERVER['PHP_SELF']);

// --- PROTEÇÃO DE VARIÁVEIS DO USUÁRIO ---
// Garante que o menu não quebre caso a página pai não tenha carregado os dados
$nome_display = isset($nome_usuario) ? $nome_usuario : (isset($_SESSION['nome_usuario']) ? $_SESSION['nome_usuario'] : 'Usuário');
$foto_display = (isset($foto_usuario) && !empty($foto_usuario)) ? $foto_usuario : 'usuario-icone-claro.png';
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <div class="brand-link text-center border-secondary">
        <span class="brand-text font-weight-bold">NOVA ARTE BJJ</span>
    </div>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
            <div class="image">
                <img src="../images/perfil/<?php echo $foto_display; ?>" class="img-circle elevation-2"
                    style="width:35px; height:35px; object-fit:cover;"
                    onerror="this.src='../images/perfil/usuario-icone-claro.png';">
            </div>
            <div class="info d-flex align-items-center justify-content-between w-100">
                <a href="#" class="d-block text-white text-truncate" style="max-width: 140px;">
                    <?php echo htmlspecialchars($nome_display); ?>
                </a>
                <a href="#" data-toggle="modal" data-target="#modalPerfil" class="text-white-50 ml-2">
                    <i class="fas fa-edit"></i>
                </a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo ($pagina_atual == 'index.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Início</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="minhas-tecnicas.php"
                        class="nav-link <?php echo ($pagina_atual == 'minhas-tecnicas.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-book-open text-primary"></i>
                        <p>Minhas Técnicas</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="horarios.php"
                        class="nav-link <?php echo ($pagina_atual == 'horarios.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-calendar-alt"></i>
                        <p>Horários</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="particulares.php"
                        class="nav-link <?php echo ($pagina_atual == 'particulares.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p>Aulas Particulares</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="mensalidades.php"
                        class="nav-link <?php echo ($pagina_atual == 'mensalidades.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-receipt"></i>
                        <p>Financeiro</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="evolucao.php"
                        class="nav-link <?php echo ($pagina_atual == 'evolucao.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Evolução Técnica</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="graficos_evolucao.php"
                        class="nav-link <?php echo ($pagina_atual == 'graficos_evolucao.php') ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-chart-pie text-warning"></i>
                        <p>Meus Gráficos <span class="badge badge-info right">Novo</span></p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>