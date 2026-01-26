<?php
@session_start();
require_once("../conexao.php");

// 1. SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script>window.location='../login.php';</script>";
    exit();
}

$id_aluno = $_GET['id'];

// 2. BUSCA DADOS DO ALUNO E GRADUAÇÃO
$query_a = $pdo->prepare("SELECT u.nome, u.data_venc, g.* FROM usuarios u LEFT JOIN graduacoes g ON u.id = g.usuario_id WHERE u.id = :id");
$query_a->execute([':id' => $id_aluno]);
$aluno = $query_a->fetch(PDO::FETCH_ASSOC);

// 3. CÁLCULO TÉCNICO
$query_h = $pdo->prepare("SELECT status, COUNT(*) as total FROM habilidades WHERE usuario_id = :id GROUP BY status");
$query_h->execute([':id' => $id_aluno]);
$habilidades = $query_h->fetchAll(PDO::FETCH_ASSOC);

$stats = ['Concluído' => 0, 'Revisão' => 0, 'Em Estudo' => 0];
foreach ($habilidades as $h) {
    $stats[$h['status']] = $h['total'];
}

$total_tecnicas_curriculo = 150;
$pontuacao_atual = ($stats['Concluído'] * 100) + ($stats['Revisão'] * 60) + ($stats['Em Estudo'] * 30);
$porcentagem_tecnica = ($total_tecnicas_curriculo > 0) ? round(($pontuacao_atual / ($total_tecnicas_curriculo * 100)) * 100) : 0;
if ($porcentagem_tecnica > 100) $porcentagem_tecnica = 100;

// Função auxiliar para cor do texto na badge
$cor_fundo = $aluno['cor_faixa'] ?? 'white';
$texto_cor = (in_array(strtolower($cor_fundo), ['white', 'branca', 'amarela', 'yellow'])) ? '#000' : '#fff';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Análise de Graduação - <?php echo $aluno['nome']; ?></title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        .card-analise {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }

        .circular-progress {
            width: 120px;
            height: 120px;
            background: radial-gradient(closest-side, white 79%, transparent 80% 100%),
                conic-gradient(#28a745 <?php echo $porcentagem_tecnica; ?>%, #e9ecef 0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .meta-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            height: 100%;
            border: 1px solid #eee;
        }

        .badge-faixa-analise {
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
            border: 1px solid #333;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="card card-analise shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i> Análise de Prontidão:
                    <?php echo $aluno['nome']; ?></h5>
                <div>
                    <button onclick="window.print()" class="btn btn-sm btn-light mr-2"><i
                            class="fas fa-print"></i></button>
                    <a href="usuarios.php" class="btn btn-sm btn-outline-light">Voltar</a>
                </div>
            </div>
            <div class="card-body">

                <div class="row align-items-center mb-4">
                    <div class="col-md-4 text-center">
                        <div class="circular-progress shadow-sm">
                            <span class="h4 mb-0 font-weight-bold"><?php echo $porcentagem_tecnica; ?>%</span>
                        </div>
                        <p class="mt-2 font-weight-bold text-muted small">CONHECIMENTO TÉCNICO</p>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="meta-box shadow-sm">
                                    <small class="text-muted d-block">FAIXA ATUAL</small>
                                    <span class="badge-faixa-analise"
                                        style="background: <?php echo $cor_fundo; ?>; color: <?php echo $texto_cor; ?>;">
                                        <?php echo strtoupper($aluno['cor_faixa'] ?? 'Branca'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="meta-box shadow-sm">
                                    <small class="text-muted d-block">GRAUS ATUAIS</small>
                                    <span class="h5 font-weight-bold"><?php echo $aluno['graus'] ?? 0; ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="meta-box shadow-sm">
                                    <small class="text-muted d-block">TOTAL DE AULAS</small>
                                    <span
                                        class="h5 font-weight-bold text-primary"><?php echo $aluno['total_aulas'] ?? 0; ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="meta-box shadow-sm">
                                    <small class="text-muted d-block">MENSALIDADE</small>
                                    <span
                                        class="badge <?php echo (strtotime($aluno['data_venc']) < time()) ? 'badge-danger' : 'badge-success'; ?>">
                                        <?php echo (strtotime($aluno['data_venc']) < time()) ? 'EM ATRASO' : 'OK'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="font-weight-bold border-bottom pb-2 mb-3 text-secondary">Checklist de Avaliação</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><i
                                    class="fas <?php echo ($porcentagem_tecnica >= 70) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> mr-2"></i>
                                Domínio Técnico (>70%)</td>
                            <td class="text-right font-weight-bold"><?php echo $porcentagem_tecnica; ?>%</td>
                        </tr>
                        <tr>
                            <td><i
                                    class="fas <?php echo (($aluno['total_aulas'] ?? 0) >= 40) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> mr-2"></i>
                                Mínimo de Aulas (Frequência)</td>
                            <td class="text-right font-weight-bold"><?php echo $aluno['total_aulas'] ?? 0; ?>/40</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-info-circle text-primary mr-2"></i> Disciplina e Atitude</td>
                            <td class="text-right text-muted">Avaliável pelo Professor</td>
                        </tr>
                    </table>
                </div>

                <hr class="my-4">

                <div class="row text-center">
                    <div class="col-12">
                        <button class="btn btn-primary btn-lg px-5 shadow-sm" data-toggle="modal"
                            data-target="#modalPromover">
                            <i class="fas fa-award mr-2"></i> GERENCIAR GRADUAÇÃO (PROMOVER)
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPromover" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Promover Aluno: <?php echo $aluno['nome']; ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="form-promover">
                    <div class="modal-body">
                        <input type="hidden" name="id_aluno" value="<?php echo $id_aluno; ?>">

                        <div class="form-group">
                            <label class="font-weight-bold">Nova Faixa:</label>
                            <select name="nova_faixa" class="form-control form-control-lg">
                                <?php
                                $faixas = ['Branca', 'Cinza', 'Amarela', 'Laranja', 'Verde', 'Azul', 'Roxa', 'Marrom', 'Preta'];
                                foreach ($faixas as $f) {
                                    $sel = ($aluno['cor_faixa'] == $f) ? 'selected' : '';
                                    echo "<option value='$f' $sel>$f</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Quantidade de Graus:</label>
                            <input type="number" name="novo_grau" class="form-control form-control-lg"
                                value="<?php echo ($aluno['graus'] ?? 0); ?>" min="0" max="4">
                        </div>

                        <div class="alert alert-info py-2">
                            <small><i class="fas fa-info-circle mr-1"></i> Ao confirmar, o contador de <b>Aulas
                                    Atuais</b> será resetado para 0.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success px-4" id="btn_confirmar">CONFIRMAR
                            PROMOÇÃO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        $('#form-promover').submit(function(e) {
            e.preventDefault();
            $('#btn_confirmar').html('<i class="fas fa-spinner fa-spin"></i> Processando...').prop('disabled',
                true);

            $.ajax({
                url: "promover-aluno.php",
                method: "POST",
                data: $(this).serialize(),
                success: function(mensagem) {
                    if (mensagem.trim() === "Sucesso") {
                        alert("Graduação atualizada com sucesso! OSS.");
                        window.location.reload();
                    } else {
                        alert(mensagem);
                        $('#btn_confirmar').html('CONFIRMAR PROMOÇÃO').prop('disabled', false);
                    }
                }
            });
        });
    </script>

</body>

</html>