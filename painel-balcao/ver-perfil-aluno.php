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

// --- LÓGICA DE PROMOÇÃO DE GRADUAÇÃO (POST) ---
if (isset($_POST['nova_faixa'])) {
    $n_faixa = $_POST['nova_faixa'];
    $n_graus = $_POST['novo_grau'];

    $res = $pdo->prepare("UPDATE graduacoes SET cor_faixa = :f, graus = :g WHERE usuario_id = :id");
    $res->bindValue(":f", $n_faixa);
    $res->bindValue(":g", $n_graus);
    $res->bindValue(":id", $id_aluno);
    $res->execute();

    // Log da promoção
    $res_log = $pdo->prepare("INSERT INTO logs (acao, data, usuario) VALUES (:acao, NOW(), :user)");
    $res_log->bindValue(":acao", "O professor promoveu o aluno para " . strtoupper($n_faixa) . " " . $n_graus . "G");
    $res_log->bindValue(":user", $_SESSION['id_usuario']);
    $res_log->execute();

    echo "<script>alert('Graduação Atualizada!'); window.location='index.php?pag=ver-perfil-aluno&id=$id_aluno';</script>";
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

$dominados = 0;
foreach ($habilidades as $h) {
    if (mb_strtolower($h['status']) == 'dominado' || mb_strtolower($h['status']) == 'concluído') $dominados++;
}

// 6. DADOS PARA O GRÁFICO
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

// --- PREPARAÇÃO DA MENSAGEM WHATSAPP ---
$telefone_aluno = preg_replace('/[^0-9]/', '', $dados['telefone'] ?? '');
$texto_whatsapp = "*RELATÓRIO TÉCNICO - NOVA ARTE BJJ*%0A%0A";
$texto_whatsapp .= "Oss, *" . $dados['nome'] . "*!%0A";
$texto_whatsapp .= "Confira sua evolução atual:%0A";
$texto_whatsapp .= "🥋 *Graduação:* " . strtoupper($faixa) . " (" . $graus . " Graus)%0A";
$texto_whatsapp .= "📈 *Aulas Totais:* " . $aulas . "%0A";
$texto_whatsapp .= "🏅 *Técnicas Dominadas:* " . $dominados . " de " . count($habilidades) . "%0A%0A";
$texto_whatsapp .= "_Continue focado na jornada!_";
$link_whatsapp = "https://api.whatsapp.com/send?phone=55" . $telefone_aluno . "&text=" . $texto_whatsapp;
?>

<style>
    @media print {

        .no-print,
        .btn,
        .breadcrumb,
        #buscaSelo,
        .main-footer {
            display: none !important;
        }

        .content-wrapper {
            margin-left: 0 !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }

    .info-label {
        font-size: 0.75rem;
        text-uppercase;
        color: #777;
        font-weight: bold;
        display: block;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: #333;
    }
</style>

<div class="container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="m-0 font-weight-bold"><i class="fas fa-user-shield mr-2 text-primary"></i>Perfil de
            <?php echo $dados['nome']; ?></h5>
        <div>
            <button data-toggle="modal" data-target="#modalPromover"
                class="btn btn-warning shadow-sm rounded-pill mr-2 font-weight-bold">
                <i class="fas fa-medal mr-1"></i>GRADUAR
            </button>
            <a href="<?php echo $link_whatsapp; ?>" target="_blank" class="btn btn-success shadow-sm rounded-pill mr-2">
                <i class="fab fa-whatsapp"></i>
            </a>
            <button onclick="window.print();" class="btn btn-light shadow-sm border rounded-pill">
                <i class="fas fa-file-pdf text-danger mr-1"></i>PDF / Imprimir
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4 border-0" style="border-radius: 15px;">
                <div class="card-body text-center">
                    <img src="../img/perfis/<?php echo $dados['foto'] ?: 'usuario-icone-claro.png'; ?>"
                        class="img-fluid rounded-circle img-thumbnail mb-3 shadow-sm"
                        style="width: 130px; height: 130px; object-fit: cover;">
                    <h5 class="font-weight-bold mb-0"><?php echo mb_strtoupper($dados['nome']); ?></h5>
                    <span class="badge badge-dark mb-3">MATRÍCULA #<?php echo $dados['id']; ?></span>

                    <div class="text-left bg-light p-3 rounded" style="font-size: 0.85rem;">
                        <div class="mb-2">
                            <span class="info-label">CPF</span>
                            <span class="info-value"><?php echo $dados['cpf'] ?: 'Não informado'; ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="info-label">E-mail</span>
                            <span class="info-value"><?php echo $dados['usuario']; ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="info-label">Telefone</span>
                            <span class="info-value"><?php echo $dados['telefone'] ?: 'N/A'; ?></span>
                        </div>
                        <div class="mb-2">
                            <span class="info-label">Endereço</span>
                            <span class="info-value"><?php echo $dados['endereco'] ?: 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white font-weight-bold small"><i
                        class="fas fa-chart-pie mr-2 text-primary"></i>Domínio Técnico</div>
                <div class="card-body"><canvas id="graficoTecnicas" height="200"></canvas></div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="row no-print">
                <div class="col-md-6">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-primary"><i class="fas fa-ribbon"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">FAIXA ATUAL</span>
                            <span class="info-box-number"><?php echo strtoupper($faixa); ?> - <?php echo $graus; ?>º
                                GRAU</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-success"><i class="fas fa-calendar-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">AULAS COMPUTADAS</span>
                            <span class="info-box-number"><?php echo $aulas; ?> AULAS</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4 border-0" style="border-radius: 15px;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-medal mr-2 text-warning"></i>Selos e Habilidades
                    </h6>
                    <input type="text" id="buscaSelo" class="form-control form-control-sm no-print"
                        style="width: 180px;" placeholder="Filtrar técnica...">
                </div>
                <div class="card-body p-0" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover mb-0" id="tabelaSelos">
                        <thead class="bg-light sticky-top">
                            <tr class="small text-uppercase">
                                <th>Posição</th>
                                <th>Técnica</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($habilidades as $h): $dominado = (mb_strtolower($h['status']) == 'dominado' || mb_strtolower($h['status']) == 'concluído'); ?>
                                <tr>
                                    <td><b><?php echo $h['posicao']; ?></b></td>
                                    <td><?php echo $h['tecnica']; ?></td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $dominado ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo strtoupper($h['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-dark text-white font-weight-bold small"><i
                        class="fas fa-history mr-2"></i>Logs de Evolução</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <?php
                        $query_l = $pdo->prepare("SELECT * FROM logs WHERE acao LIKE :nome ORDER BY id DESC LIMIT 4");
                        $query_l->bindValue(":nome", "%" . $dados['nome'] . "%");
                        $query_l->execute();
                        $logs = $query_l->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($logs as $l): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($l['acao']); ?></span>
                                <span class="text-muted"><?php echo date('d/m/Y', strtotime($l['data'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPromover" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content border-0 shadow" style="border-radius: 15px;">
            <div class="modal-header bg-warning">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-medal mr-2"></i>Promover Atleta</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="small">Nova Faixa</label>
                        <select class="form-control" name="nova_faixa" required>
                            <?php
                            $cores = ['Branca', 'Cinza', 'Amarela', 'Laranja', 'Verde', 'Azul', 'Roxa', 'Marrom', 'Preta'];
                            foreach ($cores as $c) {
                                $sel = ($faixa == $c) ? 'selected' : '';
                                echo "<option value='$c' $sel>$c</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small">Graus (0 a 4)</label>
                        <input type="number" class="form-control" name="novo_grau" value="<?php echo $graus; ?>" min="0"
                            max="4" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-warning btn-block font-weight-bold">CONFIRMAR PROMOÇÃO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        $("#buscaSelo").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#tabelaSelos tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });

    new Chart(document.getElementById('graficoTecnicas').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                data: <?php echo json_encode($valores); ?>,
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc', '#5a5c69']
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 8,
                        font: {
                            size: 9
                        }
                    }
                }
            },
            cutout: '75%'
        }
    });
</script>