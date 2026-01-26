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

// 3. CÁLCULO TÉCNICO (Mesma lógica do painel do aluno)
$query_h = $pdo->prepare("SELECT status, COUNT(*) as total FROM habilidades WHERE usuario_id = :id GROUP BY status");
$query_h->execute([':id' => $id_aluno]);
$habilidades = $query_h->fetchAll(PDO::FETCH_ASSOC);

$stats = ['Concluído' => 0, 'Revisão' => 0, 'Em Estudo' => 0];
foreach ($habilidades as $h) {
    $stats[$h['status']] = $h['total'];
}

$total_tecnicas_curriculo = 150; // Total de técnicas da sua metodologia
$pontuacao_atual = ($stats['Concluído'] * 100) + ($stats['Revisão'] * 60) + ($stats['Em Estudo'] * 30);
$porcentagem_tecnica = ($total_tecnicas_curriculo > 0) ? round(($pontuacao_atual / ($total_tecnicas_curriculo * 100)) * 100) : 0;
if ($porcentagem_tecnica > 100) $porcentagem_tecnica = 100;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Análise de Graduação</title>
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
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4">
        <div class="card card-analise shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <h5 class="mb-0">Análise de Prontidão: <?php echo $aluno['nome']; ?></h5>
                <button onclick="window.print()" class="btn btn-sm btn-light"><i class="fas fa-print"></i></button>
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
                                    <span class="badge p-2"
                                        style="background: <?php echo $aluno['cor_faixa']; ?>; border: 1px solid #ddd;">
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

                <h6 class="font-weight-bold border-bottom pb-2 mb-3">Checklist de Avaliação</h6>
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
                                    class="fas <?php echo ($aluno['total_aulas'] >= 40) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> mr-2"></i>
                                Mínimo de Aulas (Frequência)</td>
                            <td class="text-right font-weight-bold"><?php echo $aluno['total_aulas']; ?>/40</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-info-circle text-primary mr-2"></i> Disciplina e Atitude</td>
                            <td class="text-right">Avaliável pelo Professor</td>
                        </tr>
                    </table>
                </div>

                <hr>

                <div class="row text-center">
                    <div class="col-6">
                        <button class="btn btn-outline-primary btn-block"
                            onclick="alert('Funcionalidade: Lançar Grau')">
                            <i class="fas fa-plus-circle mr-2"></i> PROMOVER GRAU
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-dark btn-block" onclick="alert('Funcionalidade: Trocar Faixa')">
                            <i class="fas fa-sync-alt mr-2"></i> TROCAR FAIXA
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>