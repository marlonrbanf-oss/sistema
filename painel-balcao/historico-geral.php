<?php
@session_start();
require_once("../conexao.php");

// SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script>window.location='../login.php';</script>";
    exit();
}

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Início do mês atual
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');      // Fim do mês atual
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Histórico de Graduações - Nova Arte</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
</head>

<body class="bg-light p-4">

    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history mr-2"></i> Histórico Geral de Graduações</h5>
            <a href="index.php" class="btn btn-sm btn-outline-light">Voltar</a>
        </div>
        <div class="card-body">

            <form class="form-inline mb-4 bg-white p-3 border rounded shadow-sm">
                <label class="mr-2">De:</label>
                <input type="date" name="data_inicio" class="form-control mr-3" value="<?php echo $data_inicio; ?>">
                <label class="mr-2">Até:</label>
                <input type="date" name="data_fim" class="form-control mr-3" value="<?php echo $data_fim; ?>">
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </form>

            <div class="table-responsive">
                <table class="table table-hover border">
                    <thead class="bg-light">
                        <tr>
                            <th>Aluno</th>
                            <th>De (Antiga)</th>
                            <th>Para (Nova)</th>
                            <th class="text-center">Data</th>
                            <th>Instrutor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = $pdo->prepare("SELECT h.*, u.nome FROM historico_graduacoes h 
                                            JOIN usuarios u ON h.usuario_id = u.id 
                                            WHERE h.data BETWEEN :inicio AND :fim 
                                            ORDER BY h.data DESC");
                        $query->execute([':inicio' => $data_inicio, ':fim' => $data_fim]);
                        $logs = $query->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($logs as $log): ?>
                            <tr>
                                <td class="font-weight-bold"><?php echo mb_strtoupper($log['nome']); ?></td>
                                <td><span class="text-muted small"><?php echo $log['faixa_antiga']; ?></span></td>
                                <td><span class="badge badge-success p-2"><?php echo $log['faixa_nova']; ?></span></td>
                                <td class="text-center"><?php echo date('d/m/Y', strtotime($log['data'])); ?></td>
                                <td><small class="text-primary font-weight-bold"><?php echo $log['instrutor']; ?></small>
                                </td>
                            </tr>
                        <?php endforeach;

                        if (count($logs) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center p-4">Nenhuma promoção registrada neste período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>