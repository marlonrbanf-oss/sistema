<?php
require_once("../conexao.php");
@session_start();

// Validação de Segurança básica
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');

// Busca apenas alunos (Cliente) que estão com data_venc menor que hoje e estão ativos
$query = $pdo->query("SELECT * FROM usuarios WHERE nivel = 'Cliente' AND data_venc < '$hoje' AND ativo != 'Não' ORDER BY data_venc ASC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
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
        background-color: #f8f9fa;
        font-size: 0.9rem;
    }

    .relatorio-container {
        background: #fff;
        padding: 40px;
        min-height: 100vh;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .header-rep {
        border-bottom: 3px solid #333;
        margin-bottom: 30px;
    }

    .table thead th {
        background-color: #343a40;
        color: white;
        border: none;
    }

    .text-atraso {
        color: #dc3545;
        font-weight: bold;
    }

    /* Estilos para Impressão */
    @media print {
        body {
            background-color: #fff;
            padding: 0;
        }

        .relatorio-container {
            box-shadow: none;
            padding: 0;
        }

        .btn-print,
        .btn-whatsapp,
        .btn-voltar {
            display: none !important;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #dee2e6 !important;
        }
    }

    .btn-whatsapp {
        color: #25d366;
        text-decoration: none;
        font-weight: bold;
        transition: 0.3s;
    }

    .btn-whatsapp:hover {
        color: #128c7e;
        text-decoration: none;
    }
    </style>
</head>

<body>

    <div class="container relatorio-container">
        <div class="header-rep d-flex justify-content-between align-items-center pb-3">
            <div>
                <h1 class="font-weight-bold mb-0">NOVA ARTE BJJ</h1>
                <p class="text-muted mb-0">Gestão de Inadimplência e Cobrança</p>
            </div>
            <div class="text-right">
                <p class="mb-1"><b>Gerado em:</b> <?php echo date('d/m/Y H:i') ?></p>
                <div class="btn-group-print">
                    <button onclick="window.print()" class="btn btn-danger btn-sm btn-print">
                        <i class="fas fa-print mr-1"></i> Imprimir / PDF
                    </button>
                    <a href="usuarios.php" class="btn btn-dark btn-sm btn-voltar ml-2">
                        <i class="fas fa-arrow-left mr-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Vencimento</th>
                        <th class="text-center">Dias de Atraso</th>
                        <th class="text-center btn-print">Ação Rápida</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                if(count($res) > 0){
                    foreach($res as $row) { 
                        $data_venc = new DateTime($row['data_venc']);
                        $agora = new DateTime($hoje);
                        $intervalo = $data_venc->diff($agora);
                        $dias_atraso = $intervalo->days;

                        // Configuração da mensagem de WhatsApp
                        $tel_limpo = preg_replace('/[^0-9]/', '', $row['telefone']);
                        $msg = "Olá *{$row['nome']}*, tudo bem? Notamos que sua mensalidade na *Nova Arte BJJ* está pendente desde o dia " . date('d/m/Y', strtotime($row['data_venc'])) . ". Como podemos te ajudar a regularizar? Oss!";
                        $url_zap = "https://api.whatsapp.com/send?phone=55$tel_limpo&text=" . urlencode($msg);
                ?>
                    <tr>
                        <td class="align-middle">
                            <span class="font-weight-bold"><?php echo mb_strtoupper($row['nome']) ?></span><br>
                            <small class="text-muted"><?php echo $row['usuario'] ?></small>
                        </td>
                        <td class="align-middle text-atraso">
                            <?php echo date('d/m/Y', strtotime($row['data_venc'])) ?>
                        </td>
                        <td class="align-middle text-center">
                            <span class="badge badge-danger px-3 py-2"><?php echo $dias_atraso ?> dias</span>
                        </td>
                        <td class="align-middle text-center btn-print">
                            <a href="<?php echo $url_zap ?>" target="_blank" class="btn-whatsapp">
                                <i class="fab fa-whatsapp fa-lg mr-1"></i> Cobrar via Zap
                            </a>
                        </td>
                    </tr>
                    <?php 
                    } 
                } else {
                    echo "<tr><td colspan='4' class='text-center py-4'>Parabéns! Não há alunos em atraso no momento.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>

        <div class="mt-5 pt-3 border-top text-center">
            <p class="text-muted small">
                Total de registros: <b><?php echo count($res) ?></b> inadimplente(s) ativo(s).
            </p>
        </div>
    </div>

</body>

</html>