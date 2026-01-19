<?php @session_start(); ?>

<!DOCTYPE html>
<html class="wide wow-animation" lang="pt-br">

<head>
  <title>Nova Arte Jiu-Jitsu</title>
  <meta name="format-detection" content="telephone=no">
  <meta name="viewport"
    content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <meta name="description" content="Jiu-Jitsu Palhoça, Nova Arte, Fazer Uma Aula experimental ">
  <meta name="author" content="Nova Arte">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta charset="utf-8">

  <link rel="icon" href="images/favicon.ico" type="image/x-icon">
  <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Poppins:300,400,500">
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="stylesheet" href="css/fonts.css">
  <link rel="stylesheet" href="css/style.css">

  <style>
    /* Ajuste para exibir o nome do usuário logado de forma elegante */
    .dados-usuarios p {
      color: #fff;
      margin-bottom: 0;
      font-size: 13px;
    }

    .dados-usuarios a {
      color: #fff;
      text-decoration: none;
      margin-left: 10px;
    }

    .dados-usuarios a:hover {
      color: #ccc;
    }
  </style>
</head>

<body>
  <div class="page">
    <header class="section page-header">
      <div class="rd-navbar-wrap rd-navbar-modern-wrap">
        <nav class="rd-navbar rd-navbar-modern" data-layout="rd-navbar-fixed" data-sm-layout="rd-navbar-fixed"
          data-md-layout="rd-navbar-fixed" data-md-device-layout="rd-navbar-fixed"
          data-lg-layout="rd-navbar-static" data-lg-device-layout="rd-navbar-fixed"
          data-xl-layout="rd-navbar-static" data-xl-device-layout="rd-navbar-static"
          data-xxl-layout="rd-navbar-static" data-xxl-device-layout="rd-navbar-static"
          data-lg-stick-up-offset="46px" data-xl-stick-up-offset="46px" data-xxl-stick-up-offset="70px"
          data-lg-stick-up="true" data-xl-stick-up="true" data-xxl-stick-up="true">
          <div class="rd-navbar-main-outer">
            <div class="rd-navbar-main">
              <div class="rd-navbar-panel">
                <button class="rd-navbar-toggle"
                  data-rd-navbar-toggle=".rd-navbar-nav-wrap"><span></span></button>
                <div class="rd-navbar-brand">
                  <a title="Ir para Página Inicial" class="brand" href="index.php">
                    <img src="images/logo-default-196x47.png" alt="" width="196" height="47" />
                  </a>
                </div>
              </div>

              <div class="rd-navbar-main-element">
                <div class="rd-navbar-nav-wrap">

                  <div class="rd-navbar-basket-wrap">
                    <button class="rd-navbar-basket fl-bigmug-line-shopping198"
                      data-rd-navbar-toggle=".cart-inline"><span>2</span></button>
                    <div class="cart-inline">
                      <div class="cart-inline-header">
                        <?php if (isset($_SESSION['nome_usuario'])): ?>
                          <span class="dados-usuarios">
                            <p>
                              Olá,
                              <strong><?php echo explode(' ', $_SESSION['nome_usuario'])[0]; ?></strong>
                              <a href="logout.php" title="Sair"><img src="images/logout.png"
                                  width="18px"></a>
                            </p>
                          </span>
                        <?php endif; ?>
                        <h5 class="cart-inline-title">Carrinho:<span> 2</span> Produtos</h5>
                      </div>
                    </div>
                  </div>

                  <ul class="rd-navbar-nav">
                    <li class="rd-nav-item"><a class="rd-nav-link" href="index.php">Inicio</a></li>
                    <li class="rd-nav-item"><a class="rd-nav-link" href="sobre.php">Sobre</a></li>
                    <li class="rd-nav-item"><a class="rd-nav-link" href="contatos.php">Contatos</a>
                    </li>

                    <?php
                    // Lógica de exibição Login / Painel
                    if (!isset($_SESSION['nome_usuario'])) {
                      echo '<li class="rd-nav-item"><a class="rd-nav-link" href="login.php">Login</a></li>';
                    } else {
                      // Define a pasta de destino baseada no nível salvo no autenticar.php
                      $pasta_painel = 'painel-cliente';
                      if ($_SESSION['nivel_usuario'] == 'Admin') $pasta_painel = 'painel-adm';
                      if ($_SESSION['nivel_usuario'] == 'Balconista') $pasta_painel = 'painel-balcao';

                      echo '<li class="rd-nav-item"><a class="rd-nav-link" href="' . $pasta_painel . '/index.php">Painel</a></li>';
                    }
                    ?>
                  </ul>
                </div>
              </div>

              <div class="rd-navbar-project-hamburger" data-multitoggle=".rd-navbar-main"
                data-multitoggle-blur=".rd-navbar-wrap" data-multitoggle-isolate>
                <div class="project-hamburger">
                  <span class="project-hamburger-arrow-top"></span>
                  <span class="project-hamburger-arrow-center"></span>
                  <span class="project-hamburger-arrow-bottom"></span>
                </div>
                <div class="project-close"><span></span><span></span></div>
              </div>
            </div>
          </div>
        </nav>
      </div>
    </header>