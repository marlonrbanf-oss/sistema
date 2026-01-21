<?php
@session_start();
require_once("../conexao.php");

$id_usuario = $_SESSION['id_usuario'];

// --- DADOS PARA O GRÁFICO 1: RADAR (POR POSIÇÃO) ---
$query_radar = $pdo->prepare("SELECT posicao, AVG(nivel) as media FROM habilidades WHERE usuario_id = :id GROUP BY posicao");
$query_radar->bindValue(":id", $id_usuario);
$query_radar->execute();
$res_radar = $query_radar->fetchAll(PDO::FETCH_ASSOC);

$labels_radar = [];
$valores_radar = [];
foreach ($res_radar as $r) {
    $labels_radar[] = $r['posicao'] ? $r['posicao'] : 'Outros';
    $valores_radar[] = round($r['media']);
}

// --- DADOS PARA O GRÁFICO 2: PIZZA (STATUS TÉCNICO) ---
$query_pizza = $pdo->prepare("SELECT status, COUNT(*) as total FROM habilidades WHERE usuario_id = :id GROUP BY status");
$query_pizza->bindValue(":id", $id_usuario);
$query_pizza->execute();
$res_pizza = $query_pizza->fetchAll(PDO::FETCH_ASSOC);

$labels_pizza = [];
$valores_pizza = [];
$cores_pizza = [];
foreach ($res_pizza as $p) {
    $labels_pizza[] = strtoupper($p['status']);
    $valores_pizza[] = $p['total'];
    // Cores dinâmicas baseadas no status
    if (strpos(strtolower($p['status']), 'domina') !== false) $cores_pizza[] = '#28a745';
    elseif (strpos(strtolower($p['status']), 'revis') !== false) $cores_pizza[] = '#17a2b8';
    else $cores_pizza[] = '#ffc107';
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Gráficos - Nova Arte BJJ</title>
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include_once("sidebar.php"); // Sugiro mover sua sidebar para um arquivo separado 
        ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <h1><i class="fas fa-chart-pie mr-2"></i>Análise de Desempenho</h1>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="row">

                        <div class="col-md-6">
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Equilíbrio Técnico (Radar)</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartRadar" style="min-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Status das Técnicas</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartPizza" style="min-height: 300px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card card-dark">
                                <div class="card-header">
                                    <h3 class="card-title">Nível por Posição</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="chartBarras" style="min-height: 250px;"></canvas>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Configurações do Gráfico Radar

        const ctxRadar = document.getElementById('chartRadar').getContext('2d');
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($labels_radar); ?>,
                datasets: [{
                    label: 'Nível Médio %',
                    data: <?php echo json_encode($valores_radar); ?>,
                    backgroundColor: 'rgba(52, 58, 64, 0.2)',
                    borderColor: '#343a40',
                    pointBackgroundColor: '#dc3545'
                }]
            },
            options: {
                scales: {
                    r: {
                        suggestMin: 0,
                        suggestMax: 100
                    }
                }
            }
        });

        // Configurações do Gráfico Pizza
        const ctxPizza = document.getElementById('chartPizza').getContext('2d');
        new Chart(ctxPizza, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($labels_pizza); ?>,
                datasets: [{
                    data: <?php echo json_encode($valores_pizza); ?>,
                    backgroundColor: <?php echo json_encode($cores_pizza); ?>
                }]
            }
        });

        // Configurações do Gráfico de Barras
        const ctxBarras = document.getElementById('chartBarras').getContext('2d');
        new Chart(ctxBarras, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_radar); ?>,
                datasets: [{
                    label: 'Percentual de Domínio',
                    data: <?php echo json_encode($valores_radar); ?>,
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                indexAxis: 'y'
            } // Barras horizontais ficam ótimas em mobile
        });
    </script>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>

</html>