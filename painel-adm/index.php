<?php
@session_start();
require_once("../conexao.php");

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$nome_usuario_logado = @$_SESSION['nome_usuario'];
$nivel_logado = @$_SESSION['nivel_usuario'];
$hoje = date('Y-m-d');

// --- BUSCA DE ESTATÍSTICAS PARA OS CARDS ---
$total_alunos = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente'")->rowCount();
$total_staff = $pdo->query("SELECT id FROM usuarios WHERE (nivel = 'Professor' OR nivel = 'Balconista')")->rowCount();
$total_adm = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Admin'")->rowCount();

// --- BUSCA EXPERIMENTAIS PENDENTES ---
$total_experimentais = $pdo->query("SELECT id FROM aulas_particulares WHERE professor = '0' AND descricao LIKE '%EXPERIMENTAL%'")->rowCount();

// --- LÓGICA DO GRÁFICO DE FAIXAS ---
$query_f = $pdo->query("SELECT cor_faixa, COUNT(*) as qtd FROM graduacoes GROUP BY cor_faixa");
$dados_f = $query_f->fetchAll(PDO::FETCH_ASSOC);
$consolidado = [];
$traducao = ['branca' => 'Branca', 'white' => 'Branca', 'azul' => 'Azul', 'blue' => 'Azul', 'roxa' => 'Roxa', 'purple' => 'Roxa', 'marrom' => 'Marrom', 'brown' => 'Marrom', 'preta' => 'Preta', 'black' => 'Preta'];
$mapa_cores = ['Branca' => '#FFFFFF', 'Azul' => '#007bff', 'Roxa' => '#6f42c1', 'Marrom' => '#6d4c41', 'Preta' => '#212529'];

foreach ($dados_f as $row) {
    $cor_banco = strtolower(trim($row['cor_faixa'] ?? ''));
    if ($cor_banco == '') continue;
    $label_final = isset($traducao[$cor_banco]) ? $traducao[$cor_banco] : ucfirst($cor_banco);
    if (!isset($consolidado[$label_final])) {
        $consolidado[$label_final] = 0;
    }
    $consolidado[$label_final] += $row['qtd'];
}
$labels_faixas = array_keys($consolidado);
$valores_faixas = array_values($consolidado);
$cores_js = [];
foreach ($labels_faixas as $l) {
    $cores_js[] = isset($mapa_cores[$l]) ? $mapa_cores[$l] : '#cccccc';
}

// --- LÓGICA DE MENSALIDADES ---
$pagos = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' AND data_venc >= '$hoje'")->rowCount();
$atrasados = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' AND data_venc < '$hoje'")->rowCount();
$inativos = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Não'")->rowCount();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Dashboard Gestão - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f4f6f9;
            font-size: 0.85rem;
        }

        .sidebar {
            min-height: 100vh;
            background: #1a1a1a;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar a {
            color: #ccc;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            border-bottom: 1px solid #2d2d2d;
            transition: 0.3s;
        }

        .sidebar a:hover {
            background: #333;
            color: white;
            border-left: 4px solid #007bff;
        }

        .sidebar-footer {
            margin-top: auto;
            background: #111;
            padding-bottom: 20px;
        }

        .card {
            border-radius: 10px;
            border: none;
            overflow: hidden;
        }

        .chart-container {
            position: relative;
            margin: auto;
            height: 230px;
            width: 100%;
        }

        .divider {
            height: 1px;
            background: #333;
            margin: 10px 20px;
        }

        .header-top {
            background: #fff;
            margin: -24px -24px 25px -24px;
            padding: 12px 25px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0 sidebar shadow">
                <div class="text-center py-4 bg-dark">
                    <h5 class="font-weight-bold text-white mb-0">NOVA ARTE</h5>
                    <small class="text-primary">ADMINISTRAÇÃO</small>
                </div>

                <div class="nav-links">
                    <a href="index.php"><i class="fas fa-chart-line mr-2 text-primary"></i> Dashboard</a>
                    <a href="usuarios.php"><i class="fas fa-users-cog mr-2 text-info"></i> Todos os Usuários</a>
                    <a href="usuarios.php?nivel=Cliente"><i class="fas fa-user-graduate mr-2 text-success"></i>
                        Alunos</a>
                    <a href="usuarios.php?nivel=Professor"><i class="fas fa-chalkboard-teacher mr-2 text-warning"></i>
                        Staff</a>

                    <a href="aulas_experimentais.php" class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-flask mr-2 text-info"></i> Experimentais</span>
                        <?php if ($total_experimentais > 0): ?>
                            <span class="badge badge-danger"><?php echo $total_experimentais; ?></span>
                        <?php endif; ?>
                    </a>

                    <a href="logs.php"><i class="fas fa-history mr-2"></i> Logs do Sistema</a>

                    <a href="relatorio_experimentais.php"><i class="fas fa-file-invoice mr-2 text-light"></i> Relatório
                        de Aulas</a>
                </div>

                <div class="sidebar-footer">
                    <div class="divider"></div>
                    <small class="px-3 text-muted d-block mb-2">COBRANÇA & RETENÇÃO</small>
                    <a href="usuarios.php?status=atrasado" class="text-danger font-weight-bold"><i
                            class="fas fa-exclamation-triangle mr-2"></i> Alunos Atrasados</a>
                    <a href="alunos-inativos.php" class="text-secondary"><i class="fas fa-user-slash mr-2"></i> Alunos
                        Inativos</a>
                    <a href="../logout.php" class="text-white bg-danger mt-2 mx-2 rounded text-center py-2"><i
                            class="fas fa-sign-out-alt mr-2"></i> Sair</a>
                </div>
            </div>

            <div class="col-md-10 p-4">
                <div class="header-top">
                    <div><i class="far fa-calendar-alt text-muted mr-1"></i><span
                            class="text-muted"><?php echo date('d/m/Y'); ?></span></div>
                    <div>Olá, <b class="text-dark"><?php echo $nome_usuario_logado; ?></b></div>
                </div>

                <h4 class="font-weight-bold mb-4">Dashboard de Gestão</h4>

                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-white shadow-sm p-3 border-left border-primary"
                            style="border-width: 5px !important;">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded mr-3 text-primary"><i
                                        class="fas fa-user-graduate fa-2x"></i></div>
                                <div>
                                    <p class="text-muted mb-0">ALUNOS</p>
                                    <h2 class="font-weight-bold mb-0"><?php echo $total_alunos; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-white shadow-sm p-3 border-left border-warning"
                            style="border-width: 5px !important;">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded mr-3 text-warning"><i class="fas fa-users fa-2x"></i>
                                </div>
                                <div>
                                    <p class="text-muted mb-0">STAFF</p>
                                    <h2 class="font-weight-bold mb-0"><?php echo $total_staff; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-white shadow-sm p-3 border-left border-dark"
                            style="border-width: 5px !important;">
                            <div class="d-flex align-items-center">
                                <div class="bg-light p-3 rounded mr-3 text-dark"><i
                                        class="fas fa-user-shield fa-2x"></i></div>
                                <div>
                                    <p class="text-muted mb-0">ADMINS</p>
                                    <h2 class="font-weight-bold mb-0"><?php echo $total_adm; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom p-3">Distribuição por Faixa</div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="chartFaixas"></canvas></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom p-3 text-center">Saúde Financeira</div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="chartMensalidade"></canvas></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        new Chart(document.getElementById('chartFaixas'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($labels_faixas); ?>,
                datasets: [{
                    data: <?php echo json_encode($valores_faixas); ?>,
                    backgroundColor: <?php echo json_encode($cores_js); ?>,
                    borderColor: '#dee2e6',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        new Chart(document.getElementById('chartMensalidade'), {
            type: 'doughnut',
            data: {
                labels: ['Em Dia', 'Atrasado', 'Inativo'],
                datasets: [{
                    data: [<?php echo $pagos ?>, <?php echo $atrasados ?>, <?php echo $inativos ?>],
                    backgroundColor: ['#28a745', '#dc3545', '#adb5bd']
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>