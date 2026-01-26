<?php
require_once("../conexao.php");
@session_start();

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');

// --- MANUTENÇÃO AUTOMÁTICA ANTES DE GERAR O RELATÓRIO ---
// Regra: Se venceu há mais de 30 dias e ainda está "Sim", muda para "Não" (Inativo)
$pdo->query("UPDATE usuarios SET ativo = 'Não' WHERE data_venc < DATE_SUB(NOW(), INTERVAL 30 DAY) AND nivel = 'Cliente' AND ativo = 'Sim'");

// Regra: Se está inativo e venceu há mais de 1 ano, exclui permanentemente
$pdo->query("DELETE FROM usuarios WHERE data_venc < DATE_SUB(NOW(), INTERVAL 365 DAY) AND ativo = 'Não' AND nivel = 'Cliente'");
// -------------------------------------------------------

// Busca alunos (Cliente) que estão com data_venc menor que hoje e ainda estão Ativos (dentro dos 30 dias de tolerância)
$query = $pdo->query("SELECT * FROM usuarios WHERE nivel = 'Cliente' AND data_venc < '$hoje' AND ativo = 'Sim' ORDER BY data_venc ASC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_atrasados = count($res);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Inadimplência - Nova Arte BJJ</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <style>
        body {
            background-color: #f4f7f6;
            font-size: 0.85rem;
            color: #333;
        }

        .relatorio-container {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .header-rep {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }

        h1 {
            font-size: 1.5rem;
        }

        .table thead th {
            background-color: #212529;
            color: white;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
        }

        .text-atraso {
            color: #d9534f;
            font-weight: 700;
        }

        .btn-whatsapp {
            background-color: #25d366;
            color: white !important;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
            font-weight: bold;
        }

        .btn-whatsapp:hover {
            background-color: #128c7e;
            transform: scale(1.05);
            text-decoration: none;
        }

        /* Estilos para Impressão */
        @media print {
            body {
                background-color: #fff;
            }

            .relatorio-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }

            .no-print,
            .btn-print,
            .btn-whatsapp,
            .btn-voltar {
                display: none !important;
            }

            .table td,
            .table th {
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>

<body>

    <div class="container relatorio-container">
        <div class="header-rep d-flex justify-content-between align-items-center">
            <div>
                <h1 class="font-weight-bold mb-0 text-dark">NOVA ARTE BJJ</h1>
                <p class="text-muted mb-0 small"><i class="fas fa-exclamation-triangle mr-1 text-danger"></i> Relatório de Cobrança (Até 30 dias de atraso)</p>
            </div>
            <div class="text-md-right no-print">
                <p class="mb-2 small"><b>Gerado em:</b> <?php echo date('d/m/Y H:i') ?></p>
                <div class="btn-group">
                    <button onclick="window.print()" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-print mr-1"></i> Imprimir / PDF
                    </button>
                    <a href="usuarios.php" class="btn btn-outline-dark btn-sm ml-1">
                        <i class="fas fa-arrow-left mr-1"></i> Sair
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Aluno / E-mail</th>
                        <th>Vencimento</th>
                        <th class="text-center">Dias de Atraso</th>
                        <th class="text-right no-print">Ação de Cobrança</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($total_atrasados > 0) {
                        foreach ($res as $row) {
                            $venc = new DateTime($row['data_venc']);
                            $hoje_dt = new DateTime($hoje);
                            $diff = $venc->diff($hoje_dt);
                            $dias = $diff->days;

                            $tel_limpo = preg_replace('/[^0-9]/', '', $row['telefone'] ?? '');
                            $primeiro_nome = explode(' ', trim($row['nome']))[0];

                            $msg = "Olá *{$primeiro_nome}*! 👋 Notamos que sua mensalidade na *Nova Arte BJJ* venceu no dia " . date('d/m/Y', strtotime($row['data_venc'])) . ". Como podemos te ajudar a regularizar para você não perder os treinos? Oss! 🥋";
                            $url_zap = "https://api.whatsapp.com/send?phone=55$tel_limpo&text=" . urlencode($msg);
                    ?>
                            <tr>
                                <td class="py-3">
                                    <span class="font-weight-bold text-uppercase" style="font-size: 0.8rem;"><?php echo $row['nome'] ?></span><br>
                                    <span class="text-muted small"><?php echo $row['usuario'] ?></span>
                                </td>
                                <td class="align-middle text-atraso">
                                    <?php echo date('d/m/Y', strtotime($row['data_venc'])) ?>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-pill badge-danger shadow-sm" style="padding: 5px 10px;">
                                        <?php echo $dias ?> dias
                                    </span>
                                </td>
                                <td class="align-middle text-right no-print">
                                    <?php if (!empty($tel_limpo)): ?>
                                        <a href="<?php echo $url_zap ?>" target="_blank" class="btn-whatsapp">
                                            <i class="fab fa-whatsapp mr-1"></i> ENVIAR COBRANÇA
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Sem Telefone</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>
                                <i class='fas fa-check-circle fa-2x text-success mb-2'></i><br>
                                Parabéns! Todos os alunos ativos estão em dia.
                              </td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
            <p class="text-muted small mb-0">Obs: Alunos com mais de 30 dias de atraso são movidos automaticamente para "Inativos".</p>
            <p class="font-weight-bold mb-0">Total Pendente: <?php echo $total_atrasados ?> aluno(s)</p>
        </div>
    </div>

</body>

</html>