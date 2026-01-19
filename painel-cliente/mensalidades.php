<?php
@session_start();
require_once("../conexao.php");

// 1. Verificação de Segurança
if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$hoje = date('Y-m-d');

// 2. Busca dados do Aluno (Tabela 'usuarios' possui 'data_venc')
$query_u = $pdo->prepare("SELECT nome, data_venc FROM usuarios WHERE id = :id");
$query_u->bindValue(":id", $id_usuario);
$query_u->execute();
$dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

$vencimento_cadastro = $dados_u['data_venc'] ?? null;

// Lógica de Status para o Card Principal
if ($vencimento_cadastro && strtotime($vencimento_cadastro) < strtotime($hoje)) {
    $status_card = "ATRASADO";
    $classe_card = "bg-danger";
} else {
    $status_card = "EM DIA";
    $classe_card = "bg-success";
}

// 3. Busca Histórico (CORREÇÃO: Usando 'data_vencimento' que é o nome real na sua tabela)
$query_m = $pdo->prepare("SELECT * FROM mensalidades WHERE usuario_id = :id ORDER BY data_vencimento DESC");
$query_m->bindValue(":id", $id_usuario);
$query_m->execute();
$res_m = $query_m->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minhas Mensalidades - Nova Arte</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
    .card-mensalidade {
        border-radius: 15px;
        border: none;
    }

    .status-badge {
        border-radius: 50px;
        padding: 5px 15px;
        font-size: 0.75rem;
    }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="wrapper">
        <div class="content-wrapper ml-0 bg-light">
            <section class="content pt-4">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="font-weight-bold"><i class="fas fa-receipt mr-2 text-dark"></i> Financeiro</h3>
                        <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left mr-1"></i>
                            Voltar</a>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-mensalidade shadow <?php echo $classe_card; ?> text-white p-3 mb-4">
                                <div class="card-body">
                                    <h6 class="text-uppercase small">Vencimento Atual</h6>
                                    <h1 class="font-weight-bold">
                                        <?php echo ($vencimento_cadastro) ? date('d/m/Y', strtotime($vencimento_cadastro)) : '--/--/----'; ?>
                                    </h1>
                                    <hr style="border-top: 1px solid rgba(255,255,255,0.2)">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>STATUS: <b><?php echo $status_card; ?></b></span>
                                        <i class="fas fa-shield-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card card-mensalidade shadow">
                                <div class="card-header bg-white border-0 py-3">
                                    <h5 class="card-title font-weight-bold">Histórico de Mensalidades</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="bg-light text-muted small text-uppercase">
                                                <tr>
                                                    <th>Vencimento</th>
                                                    <th>Valor</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($res_m) > 0): ?>
                                                <?php foreach ($res_m as $m):
                                                        $st = $m['status'];
                                                        $classe_st = ($st == 'Pago') ? 'badge-success' : 'badge-warning';
                                                    ?>
                                                <tr>
                                                    <td class="font-weight-bold">
                                                        <?php echo date('d/m/Y', strtotime($m['data_vencimento'])); ?>
                                                    </td>
                                                    <td>R$ <?php echo number_format($m['valor'], 2, ',', '.'); ?></td>
                                                    <td class="text-center">
                                                        <span class="badge status-badge <?php echo $classe_st; ?>">
                                                            <?php echo strtoupper($st); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-5 text-muted">
                                                        Nenhum registro de mensalidade encontrado.
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>

</html>