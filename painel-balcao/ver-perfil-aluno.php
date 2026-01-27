<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECUPERA O ID DO ALUNO
$id_aluno = @$_GET['id'];

if ($id_aluno == "") {
    echo "<div class='alert alert-danger'>Erro: ID do aluno não informado.</div>";
    exit();
}

// 3. BUSCA DADOS DO ALUNO
$query = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$query->bindValue(":id", $id_aluno);
$query->execute();
$dados = $query->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    echo "<div class='alert alert-danger'>Erro: Aluno não encontrado.</div>";
    exit();
}

// 4. BUSCA DADOS DE GRADUAÇÃO
$query_g = $pdo->prepare("SELECT * FROM graduacoes WHERE usuario_id = :id");
$query_g->bindValue(":id", $id_aluno);
$query_g->execute();
$res_g = $query_g->fetch(PDO::FETCH_ASSOC);

$faixa = $res_g['cor_faixa'] ?? 'Branca';
$graus = $res_g['graus'] ?? 0;
$aulas = $res_g['total_aulas'] ?? 0;

// 5. BUSCA SELOS (HABILIDADES)
$query_h = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC, tecnica ASC");
$query_h->bindValue(":id", $id_aluno);
$query_h->execute();
$habilidades = $query_h->fetchAll(PDO::FETCH_ASSOC);

// 6. DADOS PARA O GRÁFICO (Agrupado por posição)
$query_chart = $pdo->prepare("SELECT posicao, COUNT(*) as total FROM habilidades WHERE usuario_id = :id GROUP BY posicao");
$query_chart->bindValue(":id", $id_aluno);
$query_chart->execute();
$dados_grafico = $query_chart->fetchAll(PDO::FETCH_ASSOC);

$labels = [];
$valores = [];
foreach ($dados_grafico as $dg) {
    $labels[] = $dg['posicao'] ?: 'Geral';
    $valores[] = $dg['total'];
}
?>

<div class="container-fluid pt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0">
            <li class="breadcrumb-item"><a href="index.php?pag=usuarios">Alunos</a></li>
            <li class="breadcrumb-item active">Perfil Técnico: <?php echo htmlspecialchars($dados['nome']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4 border-0" style="border-radius: 15px;">
                <div class="card-body text-center">
                    <img src="../img/perfis/<?php echo $dados['foto'] ?: 'usuario-icone-claro.png'; ?>"
                        class="img-fluid rounded-circle img-thumbnail mb-3 shadow-sm"
                        style="width: 120px; height: 120px; object-fit: cover;">
                    <h5 class="font-weight-bold mb-0"><?php echo mb_strtoupper($dados['nome']); ?></h5>
                    <span class="badge badge-primary mb-3">MATRÍCULA #<?php echo $dados['id']; ?></span>

                    <div class="row text-center border-top pt-3">
                        <div class="col-6 border-right">
                            <small class="text-muted d-block text-uppercase">Faixa</small>
                            <span class="font-weight-bold"><?php echo strtoupper($faixa); ?></span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase">Aulas</small>
                            <span class="font-weight-bold"><?php echo $aulas; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white font-weight-bold"><i
                        class="fas fa-chart-pie mr-2 text-primary"></i>Domínio por Posição</div>
                <div class="card-body">
                    <canvas id="graficoTecnicas" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm mb-4 border-0" style="border-radius: 15px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-medal mr-2 text-warning"></i>Selos do
                        Aluno</h6>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" id="buscaSelo" class="form-control" placeholder="Filtrar técnica...">
                        <div class="input-group-append">
                            <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="tabelaSelos">
                            <thead class="bg-light sticky-top">
                                <tr class="small">
                                    <th>POSIÇÃO</th>
                                    <th>TÉCNICA</th>
                                    <th class="text-center">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($habilidades as $h):
                                    $status_check = mb_strtolower($h['status']);
                                    $dominado = ($status_check == 'dominado' || $status_check == 'concluído');
                                    $cor_status = $dominado ? 'badge-success' : 'badge-warning';
                                ?>
                                    <tr>
                                        <td class="small font-weight-bold text-muted">
                                            <?php echo strtoupper($h['posicao']); ?></td>
                                        <td><span class="nome-tecnica"><?php echo $h['tecnica']; ?></span></td>
                                        <td class="text-center">
                                            <span class="badge <?php echo $cor_status; ?> px-2 py-1">
                                                <?php echo $dominado ? '<i class="fas fa-check mr-1"></i>' : '<i class="fas fa-clock mr-1"></i>'; ?>
                                                <?php echo strtoupper($h['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-dark text-white font-weight-bold">
                    <i class="fas fa-history mr-2"></i>Histórico de Atividades / Disputas
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php
                        $query_l = $pdo->prepare("SELECT * FROM logs WHERE acao LIKE :nome ORDER BY id DESC LIMIT 6");
                        $query_l->bindValue(":nome", "%" . $dados['nome'] . "%");
                        $query_l->execute();
                        $logs = $query_l->fetchAll(PDO::FETCH_ASSOC);

                        if (count($logs) > 0) {
                            foreach ($logs as $l): ?>
                                <li class="list-group-item small d-flex justify-content-between align-items-center">
                                    <span><i
                                            class="fas fa-chevron-right text-primary mr-2"></i><?php echo htmlspecialchars($l['acao']); ?></span>
                                    <span
                                        class="badge badge-light text-muted font-weight-normal"><?php echo date('d/m | H:i', strtotime($l['data'])); ?></span>
                                </li>
                        <?php endforeach;
                        } else {
                            echo "<li class='list-group-item text-muted text-center py-4'>Sem registros de atividades.</li>";
                        } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="text-right py-4">
        <a href="index.php?pag=usuarios" class="btn btn-dark px-4 shadow-sm" style="border-radius: 20px;">
            <i class="fas fa-arrow-left mr-2"></i>Voltar para Alunos
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    // Lógica do Filtro de Selos
    $(document).ready(function() {
        $("#buscaSelo").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#tabelaSelos tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });

    // Lógica do Gráfico de Rosca
    const ctx = document.getElementById('graficoTecnicas').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                data: <?php echo json_encode($valores); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', '#5a5c69',
                    '#f8f9fc'
                ],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#dda20a', '#be2617', '#2c9faf', '#373840',
                    '#dde2e6'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10
                }
            },
            cutout: '70%',
        }
    });
</script>