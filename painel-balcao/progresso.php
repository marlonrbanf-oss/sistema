<?php
@session_start();
require_once("../conexao.php");

$id_aluno = $_SESSION['id_usuario'];

// 1. BUSCA O RESUMO DE PROGRESSO
$query_cont = $pdo->prepare("SELECT status, COUNT(*) as total FROM habilidades WHERE usuario_id = :id GROUP BY status");
$query_cont->execute([':id' => $id_aluno]);
$resumo = $query_cont->fetchAll(PDO::FETCH_ASSOC);

$stats = ['Concluído' => 0, 'Revisão' => 0, 'Em Estudo' => 0];
foreach ($resumo as $r) {
    $stats[$r['status']] = $r['total'];
}
$total_geral = array_sum($stats);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Meu Progresso - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card-stats {
            border: none;
            border-radius: 15px;
            transition: transform 0.2s;
        }

        .card-stats:hover {
            transform: translateY(-5px);
        }

        .selo-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 6px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Cores por Status */
        .status-concluido {
            border-left-color: #28a745;
            background: #f0fff4;
        }

        .status-revisao {
            border-left-color: #17a2b8;
            background: #f0faff;
        }

        .status-estudo {
            border-left-color: #ffc107;
            background: #fffdf5;
        }

        .badge-status {
            font-size: 0.7rem;
            text-transform: uppercase;
            padding: 5px 10px;
            border-radius: 50px;
        }

        .icon-check {
            color: #28a745;
            font-size: 1.2rem;
        }

        .progresso-barra {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            overflow: hidden;
        }

        .fill-barra {
            height: 100%;
            transition: width 1s ease-in-out;
        }
    </style>
</head>

<body>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="font-weight-bold">🥋 Meu Progresso Técnico</h4>
            <a href="index.php" class="btn btn-outline-dark btn-sm rounded-pill">Voltar</a>
        </div>

        <div class="row mb-4">
            <div class="col-4">
                <div class="card card-stats bg-success text-white shadow-sm">
                    <div class="card-body text-center p-2">
                        <h3 class="mb-0"><?php echo $stats['Concluído']; ?></h3>
                        <small>Concluídos</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card card-stats bg-info text-white shadow-sm">
                    <div class="card-body text-center p-2">
                        <h3 class="mb-0"><?php echo $stats['Revisão']; ?></h3>
                        <small>Revisão</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card card-stats bg-warning text-dark shadow-sm">
                    <div class="card-body text-center p-2">
                        <h3 class="mb-0"><?php echo $stats['Em Estudo']; ?></h3>
                        <small>Estudando</small>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Agrupa as habilidades por posição (categoria)
        $query_h = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC, status DESC");
        $query_h->execute([':id' => $id_aluno]);
        $habilidades = $query_h->fetchAll(PDO::FETCH_ASSOC);

        $categoria_atual = "";
        foreach ($habilidades as $h):
            if ($categoria_atual != $h['posicao']):
                $categoria_atual = $h['posicao'];
                echo "<h6 class='mt-4 mb-3 font-weight-bold text-muted text-uppercase'><i class='fas fa-tag mr-2'></i> " . ($categoria_atual ?: 'Geral') . "</h6>";
            endif;

            $css_status = "";
            $label_status = $h['status'];
            if ($h['status'] == 'Concluído') $css_status = "status-concluido";
            if ($h['status'] == 'Revisão') $css_status = "status-revisao";
            if ($h['status'] == 'Em Estudo') $css_status = "status-estudo";
        ?>
            <div class="selo-card <?php echo $css_status; ?> d-flex justify-content-between align-items-center">
                <div>
                    <div class="font-weight-bold text-dark"><?php echo $h['tecnica']; ?></div>
                    <small class="text-muted">Conquistado em:
                        <?php echo date('d/m/Y', strtotime($h['data_conquista'])); ?></small>
                </div>
                <div class="text-right">
                    <?php if ($h['status'] == 'Concluído'): ?>
                        <i class="fas fa-certificate text-success mr-2" title="Técnica Masterizada"></i>
                    <?php endif; ?>
                    <span class="badge badge-light border badge-status"><?php echo $label_status; ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($habilidades) == 0): ?>
            <div class="text-center mt-5">
                <i class="fas fa-medal fa-3x text-light mb-3"></i>
                <p class="text-muted">Você ainda não possui selos registrados.<br>Continue treinando, OSS!</p>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>