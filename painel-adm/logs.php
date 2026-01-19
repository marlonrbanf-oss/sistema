<?php
@session_start();
require_once("../conexao.php");

// Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

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
            background-color: #f8f9fa;
            font-size: 0.9rem;
        }

        .card-logs {
            border-radius: 15px;
            border: none;
        }

        .badge-validacao {
            background-color: #e6fffa;
            color: #2c7a7b;
            border: 1px solid #b2f5ea;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        .data-hora {
            color: #6c757d;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-4">
        <div class="row mb-3 align-items-center">
            <div class="col-md-4">
                <h3><i class="fas fa-history mr-2 text-secondary"></i> Histórico Geral</h3>
            </div>
            <div class="col-md-5">
                <form method="get">
                    <div class="input-group">
                        <input type="text" name="busca" class="form-control form-control-sm"
                            placeholder="Buscar aluno, técnica ou professor..." value="<?php echo $busca ?>">
                        <div class="input-group-append">
                            <button class="btn btn-dark btn-sm" type="submit">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-3 text-right">
                <a href="index.php" class="btn btn-sm btn-outline-dark"><i class="fas fa-arrow-left"></i> Painel</a>
            </div>
        </div>

        <div class="card card-logs shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Responsável</th>
                                <th>Ação</th>
                                <th class="text-center">Data / Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_busca = "%" . $busca . "%";
                            $query = $pdo->prepare("SELECT * FROM logs WHERE acao LIKE :busca OR usuario LIKE :busca ORDER BY id DESC LIMIT 200");
                            $query->bindValue(":busca", $query_busca);
                            $query->execute();
                            $res = $query->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($res as $log) {
                                $dataF = date('d/m/Y', strtotime($log['data']));
                                $horaF = date('H:i', strtotime($log['data']));

                                // Destaque visual para validação de selos
                                $is_selo = strpos($log['acao'], 'Validou selo') !== false;
                            ?>
                                <tr>
                                    <td style="width: 20%">
                                        <i class="fas fa-user-circle text-muted"></i>
                                        <strong><?php echo $log['usuario'] ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($is_selo): ?>
                                            <span class="badge-validacao"><i class="fas fa-medal text-warning"></i>
                                                <?php echo $log['acao'] ?></span>
                                        <?php else: ?>
                                            <?php echo $log['acao'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center data-hora" style="width: 15%">
                                        <strong><?php echo $dataF ?></strong><br><?php echo $horaF ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>

</html>