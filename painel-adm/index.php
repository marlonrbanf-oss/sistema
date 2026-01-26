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
$total_alunos = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim'")->rowCount();
$total_staff = $pdo->query("SELECT id FROM usuarios WHERE (nivel = 'Professor' OR nivel = 'Balconista') AND ativo = 'Sim'")->rowCount();
$total_adm = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Admin'")->rowCount();

// --- CORREÇÃO DE COLUNAS: Usando professor_id e observacoes conforme seu banco ---
$total_experimentais = $pdo->query("SELECT id FROM aulas_particulares WHERE professor_id = '0' AND observacoes LIKE '%EXPERIMENTAL%'")->rowCount();

// --- LÓGICA DO GRÁFICO DE FAIXAS (SINCRONIZADO COM TABELA USUARIOS) ---
$query_f = $pdo->query("SELECT faixa, COUNT(*) as qtd FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' GROUP BY faixa");
$dados_f = $query_f->fetchAll(PDO::FETCH_ASSOC);

$consolidado = [
    'Branca' => 0,
    'Azul'   => 0,
    'Roxa'   => 0,
    'Marrom' => 0,
    'Preta'  => 0
];

$traducao = [
    'branca' => 'Branca',
    'white' => 'Branca',
    'azul'   => 'Azul',
    'blue'  => 'Azul',
    'roxa'   => 'Roxa',
    'purple' => 'Roxa',
    'marrom' => 'Marrom',
    'brown' => 'Marrom',
    'preta'  => 'Preta',
    'black' => 'Preta'
];

foreach ($dados_f as $row) {
    $cor_banco = strtolower(trim($row['faixa'] ?? ''));
    if (isset($traducao[$cor_banco])) {
        $label = $traducao[$cor_banco];
        $consolidado[$label] += $row['qtd'];
    }
}

$consolidado = array_filter($consolidado);
$labels_faixas = array_keys($consolidado);
$valores_faixas = array_values($consolidado);

$mapa_cores = [
    'Branca' => '#f8f9fa',
    'Azul'   => '#007bff',
    'Roxa'   => '#6f42c1',
    'Marrom' => '#6d4c41',
    'Preta'  => '#212529'
];

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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
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
                    <a href="gestao_facial.php" class="bg-dark text-white border-left border-primary"
                        style="border-width: 4px !important;">
                        <i class="fas fa-user-shield mr-2 text-warning"></i> <b>GESTÃO FACIAL</b>
                    </a>
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

                    <a href="metodologia.php"><i class="fas fa-book-open mr-2 text-primary"></i> Metodologia</a>
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
                    <div class="d-flex align-items-center">
                        <span class="mr-3">Olá, <b class="text-dark"><?php echo $nome_usuario_logado; ?></b></span>
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 35px; height: 35px;"><i class="fas fa-user-check"></i></div>
                    </div>
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
                                    <p class="text-muted mb-0 small">ALUNOS ATIVOS</p>
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
                                    <p class="text-muted mb-0 small">STAFF ATIVO</p>
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
                                    <p class="text-muted mb-0 small">ADMINS</p>
                                    <h2 class="font-weight-bold mb-0"><?php echo $total_adm; ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white border-bottom p-3 small font-weight-bold text-uppercase">
                                Distribuição por Faixa</div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="chartFaixas"></canvas></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div
                                class="card-header bg-white border-bottom p-3 small font-weight-bold text-center text-uppercase">
                                Saúde Financeira</div>
                            <div class="card-body">
                                <div class="chart-container"><canvas id="chartMensalidade"></canvas></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100 border-top border-dark">
                            <div
                                class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                                <span class="small font-weight-bold text-uppercase">Atividade Recente</span>
                                <a href="logs.php" class="btn btn-xs btn-outline-primary py-0"
                                    style="font-size: 0.65rem;">HISTÓRICO</a>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0" style="font-size: 0.75rem;">
                                    <tbody>
                                        <?php
                                        $logs = $pdo->query("SELECT * FROM logs ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($logs as $l) {
                                            $data_log = date('d/m H:i', strtotime($l['data']));
                                            echo "<tr><td class='p-2 px-3 border-bottom'><small class='text-muted'>$data_log</small><br><b>{$l['usuario']}</b>: {$l['acao']}</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de Faixas
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
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });

        // Gráfico Financeiro
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
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>