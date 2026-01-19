<?php
// index.php - Ponto de entrada principal
session_start();

// Incluir configurações
require_once 'config.php';
require_once 'conexao.php';

// Roteamento básico
$pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'home';
$pagina = str_replace(['../', './', '/'], '', $pagina); // Segurança

// Incluir cabeçalho
include_once 'cabecalho.php';

// Carregar conteúdo
if (file_exists($pagina . '.php')) {
  include_once $pagina . '.php';
} elseif (file_exists('paginas/' . $pagina . '.php')) {
  include_once 'paginas/' . $pagina . '.php';
} else {
  // Página padrão (seu código atual)
?>
  <!DOCTYPE html>
  <html lang="pt-BR">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academia Nova Arte</title>
    <link rel="stylesheet" href="/css/style.css">
  </head>

  <body>
    <h1>Academia Nova Arte Online! 🥋</h1>
    <p>Site está funcionando corretamente no Railway.</p>
    <p><a href="/">Home</a> | <a href="/login.php">Login</a></p>
  </body>

  </html>
<?php
}

// Incluir rodapé
include_once 'rodape.php';
?>