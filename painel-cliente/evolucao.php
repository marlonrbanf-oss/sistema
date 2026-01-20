<?php
@session_start();
require_once("../conexao.php");

if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Lista de posições oficiais para o agrupamento
$posicoes_oficiais = [
    'Fundamentos',
    'Defesa Pessoal',
    'Quedas',
    'passagem de guarda',
    'guarda fechada por baixo',
    'guarda fechada por cima',
    'guarda aberta',
    'montada ataque (por cima)',
    'montada defesa (por baixo)',
    '100 kilos ataque (por cima)',
    '100 kilos defesa (por baixo)',
    'meia guarda por cima',
    'costas ataque (por cima)',
    'costas defesa (por baixo)',
    '4 apoios ataque (por cima)',
    '4 apoios por defesa (por baixo)',
    'defesa de finalizações'
];

// Busca todas as habilidades do aluno
$query = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id");
$query->bindValue(":id", $id_usuario);
$query->execute();
$habilidades = $query->fetchAll(PDO::FETCH_ASSOC);

$dados_evolucao = [];
foreach ($posicoes_oficiais as $pos) {
    $dados_evolucao[$pos] = ['total' => 0, 'dominados' => 0];
}
$dados_evolucao['Outras Posições'] = ['total' => 0, 'dominados' => 0];

// Processamento dos dados
foreach ($habilidades as $h) {
    $pos_aluno = $h['posicao'];
    // Se a posição não estiver na lista oficial, vai para "Outras Posições"
    $chave = in_array($pos_aluno, $posicoes_oficiais) ? $pos_aluno : 'Outras Posições';

    $dados_evolucao[$chave]['total']++;
    if (mb_strtolower($h['status']) == 'dominado') {
        $dados_evolucao[$chave]['dominados']++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evolução Técnica - Nova Arte BJJ</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Minha Evolução Técnica</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <?php foreach ($dados_evolucao as $posicao => $dados):
                            if ($dados['total'] == 0) continue; // Não mostra se não tiver nenhum selo na categoria

                            $porcentagem = ($dados['dominados'] / $dados['total']) * 100;
                            $cor_barra = 'bg-info';
                            if ($porcentagem >= 100) $cor_barra = 'bg-success';
                            elseif ($porcentagem < 50) $cor_barra = 'bg-warning';
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-body">
                                        <h6 class="text-uppercase font-weight-bold text-dark">
                                            <?php echo $posicao; ?>
                                        </h6>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Domínio da Categoria</small>
                                            <small class="font-weight-bold"><?php echo round($porcentagem); ?>%</small>
                                        </div>
                                        <div class="progress mb-3" style="height: 12px; border-radius: 6px;">
                                            <div class="progress-bar <?php echo $cor_barra; ?> progress-bar-striped"
                                                role="progressbar" style="width: <?php echo $porcentagem; ?>%">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="badge badge-light border">
                                                <i class="fas fa-medal text-primary mr-1"></i> Selos:
                                                <?php echo $dados['total']; ?>
                                            </span>
                                            <span class="badge badge-light border">
                                                <i class="fas fa-check-circle text-success mr-1"></i> Dominados:
                                                <?php echo $dados['dominados']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>

</html>