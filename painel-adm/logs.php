<?php
@session_start();
require_once("../conexao.php");

// Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Captura dados do Admin para o topo
$nome_usuario_logado = @$_SESSION['nome_usuario'];

// Lógica de Busca
$busca = @$_GET['busca'];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Logs do Sistema - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-size: 0.85rem;
        }

        /* BARRA SUPERIOR IGUAL AO INDEX */
        .header-top {
            background: #fff;
            padding: 12px 25px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            margin-bottom: 25px;
        }

        .card-logs {
            border-radius: 15px;
            border: none;
            overflow: hidden;
        }

        .badge-validacao {
            background-color: #e6fffa;
            color: #2c7a7b;
            border: 1px solid #b2f5ea;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-presenca {
            background-color: #fffaf0;
            color: #b7791f;
            border: 1px solid #fbd38d;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }

        .data-hora {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
    </style>
</head>

<body>

    <div class="header-top">
        <div>
            <h5 class="mb-0 font-weight-bold"><i class="fas fa-history mr-2 text-secondary"></i> Logs do Sistema</h5>
        </div>
        <div class="d-flex align-items-center">
            <span class="mr-3">Olá, <b class="text-dark"><?php echo $nome_usuario_logado; ?></b></span>
            <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                style="width: 35px; height: 35px;">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" class="d-flex">
                    <input type="text" name="busca" class="form-control shadow-sm border-0"
                        placeholder="Pesquisar por nome do aluno ou ação..." value="<?php echo $busca ?>"
                        style="border-radius: 20px 0 0 20px;">
                    <button class="btn btn-dark shadow-sm" type="submit"
                        style="border-radius: 0 20px 20px 0; padding-left: 20px; padding-right: 20px;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-6 text-right">
                <a href="index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="fas fa-chart-line mr-1"></i> Dashboard
                </a>
                <a href="usuarios.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 shadow-sm ml-2">
                    <i class="fas fa-users mr-1"></i> Usuários
                </a>
            </div>
        </div>

        <div class="card card-logs shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="pl-4">Responsável (Staff)</th>
                                <th>Ação Realizada</th>
                                <th class="text-center">Data / Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_busca = "%" . $busca . "%";
                            // A query busca na coluna 'acao' onde o nome do aluno foi gravado
                            $query = $pdo->prepare("SELECT * FROM logs WHERE acao LIKE :busca OR usuario LIKE :busca ORDER BY id DESC LIMIT 200");
                            $query->bindValue(":busca", $query_busca);
                            $query->execute();
                            $res = $query->fetchAll(PDO::FETCH_ASSOC);

                            if (count($res) > 0) {
                                foreach ($res as $log) {
                                    $dataF = date('d/m/Y', strtotime($log['data']));
                                    $horaF = date('H:i', strtotime($log['data']));

                                    $acao = $log['acao'];

                                    // Identifica o tipo de log para aplicar o estilo
                                    $is_selo = strpos($acao, 'Validou selo') !== false;
                                    $is_presenca = strpos($acao, 'presença') !== false;
                            ?>
                                    <tr>
                                        <td class="pl-4 align-middle" style="width: 25%">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-tie text-muted mr-2"></i>
                                                <span class="font-weight-bold text-dark"><?php echo $log['usuario'] ?></span>
                                            </div>
                                        </td>
                                        <td class="align-middle">
                                            <?php if ($is_selo): ?>
                                                <span class="badge-validacao text-uppercase" style="font-size: 0.7rem;">
                                                    <i class="fas fa-medal text-warning mr-1"></i> <?php echo $acao ?>
                                                </span>
                                            <?php elseif ($is_presenca): ?>
                                                <span class="badge-presenca text-uppercase" style="font-size: 0.7rem;">
                                                    <i class="fas fa-check-circle text-warning mr-1"></i> <?php echo $acao ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-secondary"><?php echo $acao ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle data-hora" style="width: 15%">
                                            <span class="text-dark font-weight-bold"><?php echo $dataF ?></span><br>
                                            <span class="badge badge-light border"><?php echo $horaF ?></span>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='3' class='text-center py-5 text-muted'>Nenhum registro encontrado para '$busca'.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="text-center mt-3 mb-5">
            <small class="text-muted">Exibindo os últimos 200 registros de atividade.</small>
        </div>
    </div>

</body>

</html>