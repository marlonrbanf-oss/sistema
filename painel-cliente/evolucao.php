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
$query = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY status DESC, tecnica ASC");
$query->bindValue(":id", $id_usuario);
$query->execute();
$habilidades = $query->fetchAll(PDO::FETCH_ASSOC);

$dados_evolucao = [];
foreach ($posicoes_oficiais as $pos) {
    $dados_evolucao[$pos] = ['total' => 0, 'dominados' => 0, 'tecnicas' => []];
}
$dados_evolucao['Outras Posições'] = ['total' => 0, 'dominados' => 0, 'tecnicas' => []];

// Processamento e Agrupamento
foreach ($habilidades as $h) {
    $pos_aluno = $h['posicao'];
    $chave = in_array($pos_aluno, $posicoes_oficiais) ? $pos_aluno : 'Outras Posições';

    $dados_evolucao[$chave]['total']++;
    if (mb_strtolower($h['status']) == 'dominado') {
        $dados_evolucao[$chave]['dominados']++;
    }
    // Armazena a técnica para listar no clique
    $dados_evolucao[$chave]['tecnicas'][] = $h;
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
    <style>
        .card-evolucao {
            cursor: pointer;
            transition: 0.3s;
        }

        .card-evolucao:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .tecnica-item {
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }

        .tecnica-item:last-child {
            border-bottom: none;
        }

        .status-estudo {
            color: #17a2b8;
        }

        .status-dominado {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <h1 class="font-weight-bold text-dark">Evolução por Posição</h1>
                    <p class="text-muted">Clique nos cards para ver os detalhes das técnicas.</p>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <?php
                        $count = 0;
                        foreach ($dados_evolucao as $posicao => $dados):
                            if ($dados['total'] == 0) continue;
                            $count++;
                            $id_collapse = "collapse_" . $count;
                            $porcentagem = ($dados['dominados'] / $dados['total']) * 100;
                            $cor_barra = ($porcentagem >= 100) ? 'bg-success' : 'bg-info';
                        ?>

                            <div class="col-md-6 col-lg-4">
                                <div class="card card-evolucao shadow-sm mb-3" data-toggle="collapse"
                                    data-target="#<?php echo $id_collapse; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-uppercase font-weight-bold m-0"><?php echo $posicao; ?></h6>
                                            <i class="fas fa-chevron-down text-muted small"></i>
                                        </div>

                                        <div class="progress mb-2" style="height: 10px; border-radius: 5px;">
                                            <div class="progress-bar <?php echo $cor_barra; ?>"
                                                style="width: <?php echo $porcentagem; ?>%"></div>
                                        </div>

                                        <div class="d-flex justify-content-between small">
                                            <span><?php echo $dados['dominados']; ?>/<?php echo $dados['total']; ?>
                                                Dominados</span>
                                            <span class="font-weight-bold"><?php echo round($porcentagem); ?>%</span>
                                        </div>

                                        <div class="collapse mt-3 border-top pt-2" id="<?php echo $id_collapse; ?>">
                                            <?php foreach ($dados['tecnicas'] as $tec):
                                                $is_dominado = (mb_strtolower($tec['status']) == 'dominado');
                                            ?>
                                                <div class="tecnica-item d-flex justify-content-between align-items-center">
                                                    <span
                                                        class="small text-uppercase <?php echo $is_dominado ? 'text-dark' : 'text-muted'; ?>">
                                                        <?php echo $tec['tecnica']; ?>
                                                    </span>
                                                    <?php if ($is_dominado): ?>
                                                        <i class="fas fa-check-circle status-dominado" title="Dominado"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock status-estudo" title="Em Estudo"></i>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
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
    <script>
        // Gira o ícone de seta quando abre/fecha
        $('.collapse').on('show.bs.collapse', function() {
            $(this).parent().find(".fa-chevron-down").css("transform", "rotate(180deg)");
        }).on('hide.bs.collapse', function() {
            $(this).parent().find(".fa-chevron-down").css("transform", "rotate(0deg)");
        });
    </script>
</body>

</html>