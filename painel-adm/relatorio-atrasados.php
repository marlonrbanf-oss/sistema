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
            background-color: #f4f7f6;
            font-size: 0.85rem;
            /* Letras menores e mais elegantes */
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

        /* Cabeçalho Responsivo */
        .header-rep {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }

        h1 {
            font-size: 1.5rem;
        }

        /* Título menor */

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
        }

        .btn-whatsapp:hover {
            background-color: #128c7e;
            transform: scale(1.05);
        }

        /* Ajustes Mobile */
        @media (max-width: 576px) {
            .header-rep {
                text-align: center;
                flex-direction: column;
            }

            .btn-group-print {
                margin-top: 15px;
                width: 100%;
            }

            .btn-group-print .btn {
                width: 48%;
            }

            h1 {
                font-size: 1.2rem;
            }
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

            .btn-print,
            .btn-whatsapp,
            .btn-voltar,
            .no-print {
                display: none !important;
            }

            .table td,
            .table th {
                border: 1px solid #eee !important;
            }

            .text-atraso {
                color: #000 !important;
            }
        }
    </style>
</head>

<body>

    <div class="container relatorio-container">
        <div class="header-rep d-flex justify-content-between align-items-center">
            <div>
                <h1 class="font-weight-bold mb-0 text-dark">NOVA ARTE BJJ</h1>
                <p class="text-muted mb-0 small"><i class="fas fa-exclamation-circle mr-1"></i> Lista de Inadimplência</p>
            </div>
            <div class="text-md-right mt-2 mt-md-0">
                <p class="mb-2 small"><b>Data do Relatório:</b> <?php echo date('d/m/Y') ?></p>
                <div class="btn-group-print">
                    <button onclick="window.print()" class="btn btn-outline-danger btn-sm btn-print">
                        <i class="fas fa-print"></i> PDF
                    </button>
                    <a href="usuarios.php" class="btn btn-outline-dark btn-sm btn-voltar ml-1">
                        <i class="fas fa-arrow-left"></i> Sair
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
                        <th class="text-center">Atraso</th>
                        <th class="text-right no-print">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (count($res) > 0) {
                        foreach ($res as $row) {
                            $data_venc = new DateTime($row['data_venc']);
                            $agora = new DateTime($hoje);
                            $intervalo = $data_venc->diff($agora);
                            $dias_atraso = $intervalo->days;

                            $tel_limpo = preg_replace('/[^0-9]/', '', $row['telefone']);
                            $msg = "Olá *{$row['nome']}*, tudo bem? Notamos que sua mensalidade na *Nova Arte BJJ* está pendente desde o dia " . date('d/m/Y', strtotime($row['data_venc'])) . ". Como podemos te ajudar a regularizar? Oss!";
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
                                    <span class="badge badge-pill badge-light border text-danger" style="font-size: 0.7rem;">
                                        <?php echo $dias_atraso ?> dias
                                    </span>
                                </td>
                                <td class="align-middle text-right no-print">
                                    <a href="<?php echo $url_zap ?>" target="_blank" class="btn-whatsapp">
                                        <i class="fab fa-whatsapp mr-1"></i> COBRAR
                                    </a>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center py-5 text-muted'>Parabéns! Todos os alunos estão em dia.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
            <p class="text-muted small">Relatório gerado automaticamente pelo Sistema Nova Arte.</p>
            <p class="font-weight-bold small">Total: <?php echo count($res) ?> aluno(s)</p>
        </div>
    </div>

</body>

</html>