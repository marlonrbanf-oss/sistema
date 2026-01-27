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
    $valores[] = (int)$dg['total'];
}
if (empty($valores)) {
    $labels = ['Sem dados'];
    $valores = [1];
}

// 7. PREPARAÇÃO WHATSAPP
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

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #007bff;
        --dark-text: #1e272e;
        --gray-text: #485460;
    }

    body {
        font-family: 'Inter', sans-serif;
        color: var(--dark-text);
        background-color: #f8f9fa;
    }

    /* Cores de texto explícitas para garantir legibilidade no fundo branco */
    .text-dark-custom {
        color: var(--dark-text) !important;
        font-weight: 600;
    }

    .text-gray-custom {
        color: var(--gray-text) !important;
    }

    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        background: #fff;
    }

    /* Design de Botões Modernos */
    .btn-graduar {
        background: #f1c40f;
        color: #000 !important;
        border: none;
        border-radius: 50px;
        padding: 10px 25px;
        font-weight: 700;
        transition: 0.3s;
    }

    .btn-graduar:hover {
        background: #d4ac0d;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(241, 196, 15, 0.4);
    }

    .btn-wpp {
        background: #25d366;
        color: #fff !important;
        border-radius: 50px;
        padding: 10px 20px;
    }

    .btn-pdf {
        background: #f1f2f6;
        color: #d63031 !important;
        border: 1px solid #dfe4ea;
        border-radius: 50px;
        padding: 10px 20px;
        font-weight: 600;
    }

    .info-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #808e9b;
        font-weight: 800;
        letter-spacing: 1px;
        display: block;
        margin-bottom: 2px;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--dark-text);
        display: block;
        margin-bottom: 15px;
    }

    .img-perfil {
        width: 130px;
        height: 130px;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .info-box-status {
        background: #f1f2f6;
        border-radius: 12px;
        padding: 20px;
        border-left: 6px solid var(--primary);
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #eee !important;
        }

        .content-wrapper {
            margin-left: 0 !important;
        }
    }
</style>

<div class="container-fluid pt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 no-print">
        <div>
            <h4 class="font-weight-bold text-dark-custom mb-1"><i
                    class="fas fa-user-circle mr-2 text-primary"></i>Perfil do Aluno</h4>
            <p class="text-gray-custom mb-0">Visualizando dados técnicos de evolução</p>
        </div>
        <div class="mt-3 mt-md-0 d-flex align-items-center">
            <button type="button" onclick="abrirModal()" class="btn btn-graduar mr-2">
                <i class="fas fa-medal mr-2"></i>GRADUAR
            </button>
            <a href="<?php echo $link_whatsapp; ?>" target="_blank" class="btn btn-wpp mr-2">
                <i class="fab fa-whatsapp"></i>
            </a>
            <button onclick="window.print();" class="btn btn-pdf">
                <i class="fas fa-file-pdf mr-1"></i>PDF / Imprimir
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4 text-center p-4">
                <div class="card-body p-0">
                    <img src="../img/perfis/<?php echo $dados['foto'] ?: 'usuario-icone-claro.png'; ?>"
                        class="rounded-circle img-perfil mb-3">
                    <h5 class="font-weight-bold text-dark-custom mb-1 text-uppercase"><?php echo $dados['nome']; ?></h5>
                    <span class="badge badge-light border text-dark px-3 py-2 rounded-pill mb-4">Matrícula:
                        #<?php echo $id_aluno; ?></span>

                    <hr>

                    <div class="text-left mt-3">
                        <span class="info-label">CPF</span>
                        <span class="info-value"><?php echo $dados['cpf'] ?: 'Não informado'; ?></span>

                        <span class="info-label">E-mail</span>
                        <span class="info-value text-lowercase"><?php echo $dados['usuario']; ?></span>

                        <span class="info-label">Telefone</span>
                        <span class="info-value"><?php echo $dados['telefone'] ?: '---'; ?></span>

                        <span class="info-label">Endereço</span>
                        <span class="info-value small"><?php echo $dados['endereco'] ?: 'Não cadastrado'; ?></span>
                    </div>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="font-weight-bold text-dark-custom mb-3"><i
                        class="fas fa-chart-pie mr-2 text-primary"></i>Habilidades por Posição</h6>
                <div style="height: 250px;">
                    <canvas id="graficoTecnicas"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="info-box-status">
                        <span class="info-label text-primary">Graduação Atual</span>
                        <h3 class="font-weight-bold text-dark-custom mb-0 mt-1"><?php echo strtoupper($faixa); ?></h3>
                        <p class="mb-0 text-gray-custom font-weight-bold"><?php echo $graus; ?>º GRAU</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="info-box-status" style="border-left-color: #27ae60;">
                        <span class="info-label text-success">Presenças</span>
                        <h3 class="font-weight-bold text-dark-custom mb-0 mt-1"><?php echo $aulas; ?> Aulas</h3>
                        <p class="mb-0 text-gray-custom font-weight-bold">TOTAL ACUMULADO</p>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark-custom">Checklist de Habilidades</h6>
                    <input type="text" id="buscaSelo" class="form-control form-control-sm no-print"
                        style="width: 200px;" placeholder="Filtrar técnica...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="tabelaSelos">
                            <thead class="bg-light sticky-top">
                                <tr class="small text-dark-custom font-weight-bold">
                                    <th>POSIÇÃO</th>
                                    <th>TÉCNICA</th>
                                    <th class="text-center">STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="text-dark-custom">
                                <?php foreach ($habilidades as $h):
                                    $isDominado = (mb_strtolower($h['status']) == 'dominado' || mb_strtolower($h['status']) == 'concluído');
                                ?>
                                    <tr>
                                        <td class="small font-weight-bold py-3"><?php echo $h['posicao']; ?></td>
                                        <td class="py-3"><?php echo $h['tecnica']; ?></td>
                                        <td class="text-center py-3">
                                            <span
                                                class="badge px-3 py-2 <?php echo $isDominado ? 'badge-success' : 'badge-warning'; ?> rounded-pill">
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

            <div class="card">
                <div class="card-header bg-dark text-white rounded-top">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-history mr-2"></i>Histórico de Atividades</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php
                        $query_l = $pdo->prepare("SELECT * FROM logs WHERE acao LIKE :nome ORDER BY id DESC LIMIT 5");
                        $query_l->bindValue(":nome", "%" . $dados['nome'] . "%");
                        $query_l->execute();
                        $logs = $query_l->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($logs as $l): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0"
                                style="border-bottom: 1px solid #eee !important;">
                                <span
                                    class="text-dark-custom small font-weight-bold"><?php echo htmlspecialchars($l['acao']); ?></span>
                                <span
                                    class="badge badge-light border text-gray-custom"><?php echo date('d/m/Y', strtotime($l['data'])); ?></span>
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
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-warning text-dark border-0">
                <h6 class="modal-title font-weight-bold">Promover Atleta</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            </div>
            <form id="form-promover">
                <div class="modal-body p-4">
                    <input type="hidden" name="id_aluno" value="<?php echo $id_aluno; ?>">
                    <div class="form-group mb-3">
                        <label class="info-label">Nova Faixa</label>
                        <select class="form-control custom-select" name="nova_faixa" required>
                            <?php
                            $cores = ['Branca', 'Cinza', 'Amarela', 'Laranja', 'Verde', 'Azul', 'Roxa', 'Marrom', 'Preta'];
                            foreach ($cores as $c) {
                                $sel = (mb_strtolower($faixa) == mb_strtolower($c)) ? 'selected' : '';
                                echo "<option value='$c' $sel>$c</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="info-label">Graus (0 a 4)</label>
                        <input type="number" class="form-control" name="novo_grau" value="<?php echo $graus; ?>" min="0"
                            max="4" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" id="btn_promover"
                        class="btn btn-warning btn-block font-weight-bold py-2 shadow-sm">CONFIRMAR PROMOÇÃO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Função para abrir o modal via JS para garantir funcionamento
    function abrirModal() {
        $('#modalPromover').modal('show');
    }

    // Ajax para envio ao promover-aluno.php
    $('#form-promover').submit(function(e) {
        e.preventDefault();
        $('#btn_promover').text('Processando...').prop('disabled', true);

        $.ajax({
            url: "promover-aluno.php",
            method: "POST",
            data: $(this).serialize(),
            success: function(mensagem) {
                if (mensagem.trim() == "Sucesso") {
                    alert("Promoção realizada com sucesso!");
                    window.location.reload();
                } else {
                    alert(mensagem);
                    $('#btn_promover').text('CONFIRMAR PROMOÇÃO').prop('disabled', false);
                }
            },
            error: function() {
                alert("Erro ao conectar com o servidor.");
                $('#btn_promover').text('CONFIRMAR PROMOÇÃO').prop('disabled', false);
            }
        });
    });

    // Gráfico de Técnicas
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('graficoTecnicas');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($valores); ?>,
                        backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc',
                            '#5a5c69'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#485460',
                                font: {
                                    size: 10
                                }
                            }
                        }
                    },
                    cutout: '75%'
                }
            });
        }

        // Filtro de Busca
        $("#buscaSelo").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#tabelaSelos tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>