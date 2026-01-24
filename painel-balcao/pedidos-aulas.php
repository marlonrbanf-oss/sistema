<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA - Ajustado para a coluna 'nivel' restaurada
if (
    !isset($_SESSION['id_usuario']) ||
    (@$_SESSION['nivel'] != 'Balconista' && @$_SESSION['nivel'] != 'Admin' && @$_SESSION['nivel'] != 'Professor')
) {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_professor = $_SESSION['id_usuario'];

// Lógica para Aprovar ou Recusar
if (isset($_GET['acao']) && isset($_GET['id_pedido'])) {
    $status = ($_GET['acao'] == 'aprovar') ? 'Aprovado' : 'Recusado';
    $id_p = $_GET['id_pedido'];

    $query_update = $pdo->prepare("UPDATE pedidos_aulas SET status = :status WHERE id = :id AND id_professor = :prof");
    $query_update->bindValue(":status", $status);
    $query_update->bindValue(":id", $id_p);
    $query_update->bindValue(":prof", $id_professor);
    $query_update->execute();

    echo "<script>window.location='pedidos-aulas.php'</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerenciar Aulas Particulares</title>
    <link rel="stylesheet" href="../painel-cliente/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../painel-cliente/dist/css/adminlte.min.css">
    <style>
        .btn-voltar:hover {
            transform: translateX(-3px);
        }

        .badge-experimental {
            background-color: #6f42c1;
            color: white;
        }

        .table td {
            vertical-align: middle !important;
        }

        .row-hover:hover {
            background-color: rgba(0, 0, 0, .02);
        }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container-fluid py-4">

        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <a href="index.php" class="btn btn-dark btn-sm shadow-sm btn-voltar">
                    <i class="fas fa-arrow-left mr-1"></i> VOLTAR
                </a>
                <h5 class="mb-0 font-weight-bold text-dark">MINHA AGENDA</h5>
            </div>
        </div>

        <div class="card card-dark shadow-lg border-0">
            <div class="card-header" style="background-color: #343a40;">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i> Solicitações Recebidas</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-secondary text-white">
                            <tr>
                                <th>Aluno</th>
                                <th>Data</th>
                                <th>Horário / Obs</th>
                                <th>Status / Tipo</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id, id_aluno, data_aula as data, horario_aula as info, status, 'NORMAL' as tipo 
                                    FROM pedidos_aulas 
                                    WHERE id_professor = :prof
                                    
                                    UNION 
                                    
                                    SELECT id, aluno_id as id_aluno, data as data, observacoes as info, 'Agendada' as status, 'EXPERIMENTAL' as tipo 
                                    FROM aulas_particulares 
                                    WHERE professor_id = :prof AND (observacoes LIKE '%EXPERIMENTAL%' OR observacoes LIKE '%experimental%')
                                    
                                    ORDER BY data ASC, info ASC";

                            $query = $pdo->prepare($sql);
                            $query->bindValue(":prof", $id_professor);
                            $query->execute();
                            $pedidos = $query->fetchAll(PDO::FETCH_ASSOC);

                            if (count($pedidos) > 0) {
                                foreach ($pedidos as $pedido) {
                                    // Busca o nome do aluno usando prepare para evitar falhas se o ID estiver vazio
                                    $q_aluno = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id_aluno");
                                    $q_aluno->bindValue(":id_aluno", $pedido['id_aluno']);
                                    $q_aluno->execute();
                                    $res_aluno = $q_aluno->fetch(PDO::FETCH_ASSOC);
                                    $nome_aluno = $res_aluno['nome'] ?? 'Aluno não encontrado';

                                    $status_atual = $pedido['status'];
                                    $classe_status = 'badge-warning'; // Pendente
                                    if ($status_atual == 'Aprovado' || $status_atual == 'Agendada') $classe_status = 'badge-success';
                                    if ($status_atual == 'Recusado') $classe_status = 'badge-danger';
                                    if ($pedido['tipo'] == 'EXPERIMENTAL') $classe_status = 'badge-experimental';

                                    $info_exibir = $pedido['info'];
                                    if ($pedido['tipo'] == 'NORMAL' && !empty($info_exibir)) {
                                        $info_exibir = date('H:i', strtotime($info_exibir));
                                    }
                            ?>
                                    <tr class="row-hover">
                                        <td><span class="font-weight-bold"><?php echo mb_strtoupper($nome_aluno); ?></span></td>
                                        <td><span class="badge badge-light border"><i
                                                    class="far fa-calendar-check mr-1 text-primary"></i>
                                                <?php echo date('d/m/Y', strtotime($pedido['data'])); ?></span></td>
                                        <td><small class="text-muted font-weight-bold"><?php echo $info_exibir; ?></small></td>
                                        <td>
                                            <span class="badge <?php echo $classe_status; ?> px-2 py-1 shadow-sm">
                                                <?php echo ($pedido['tipo'] == 'EXPERIMENTAL') ? 'EXPERIMENTAL' : strtoupper($status_atual); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($pedido['tipo'] == 'NORMAL' && ($status_atual == 'Pendente' || $status_atual == '')): ?>
                                                <a href="pedidos-aulas.php?acao=aprovar&id_pedido=<?php echo $pedido['id']; ?>"
                                                    class="btn btn-sm btn-success shadow-sm"
                                                    onclick="return confirm('Deseja confirmar este agendamento?')">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="pedidos-aulas.php?acao=recusar&id_pedido=<?php echo $pedido['id']; ?>"
                                                    class="btn btn-sm btn-danger shadow-sm ml-1"
                                                    onclick="return confirm('Deseja recusar este pedido?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php elseif ($pedido['tipo'] == 'EXPERIMENTAL'): ?>
                                                <i class="fas fa-info-circle text-muted"
                                                    title="Aula Experimental lançada pelo Admin"></i>
                                            <?php else: ?>
                                                <i class="fas fa-history text-muted" title="Solicitação já processada"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-5">
                                        <img src="../images/sem-dados.png" style="width:50px; opacity:0.3" class="mb-2 d-block mx-auto">
                                        <span class="text-muted small">Sua agenda está livre por enquanto.</span>
                                      </td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../painel-cliente/plugins/jquery/jquery.min.js"></script>
    <script src="../painel-cliente/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>