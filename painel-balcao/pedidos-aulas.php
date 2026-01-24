<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_professor = $_SESSION['id_usuario'];

// Lógica para Aprovar ou Recusar (Apenas para pedidos normais)
if (isset($_GET['acao']) && isset($_GET['id_pedido'])) {
    $status = ($_GET['acao'] == 'aprovar') ? 'Aprovado' : 'Recusado';
    $id_p = $_GET['id_pedido'];

    // Usando Prepare para maior segurança
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
        .btn-voltar {
            transition: all 0.3s;
        }

        .btn-voltar:hover {
            transform: translateX(-3px);
            background-color: #343a40 !important;
            color: #fff !important;
        }

        .badge-experimental {
            background-color: #6f42c1;
            color: white;
        }

        .table td {
            vertical-align: middle !important;
        }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container-fluid py-4">

        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <a href="index.php" class="btn btn-outline-dark btn-sm shadow-sm btn-voltar">
                    <i class="fas fa-arrow-left mr-1"></i> VOLTAR AO PAINEL
                </a>
                <span class="text-muted small text-uppercase font-weight-bold">Gestão de Agenda</span>
            </div>
        </div>

        <div class="card card-dark shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i> Solicitações e Experimentais</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Aluno</th>
                                <th>Data</th>
                                <th>Info / Horário</th>
                                <th>Status / Tipo</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // QUERY UNIFICADA: Busca Pedidos normais E Aulas Experimentais
                            // Melhoria: Ordenação por data ASC para ver os compromissos mais próximos primeiro
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
                                    // Buscar nome do aluno de forma eficiente
                                    $id_aluno = $pedido['id_aluno'];
                                    $q_aluno = $pdo->query("SELECT nome FROM usuarios WHERE id = '$id_aluno'");
                                    $res_aluno = $q_aluno->fetch(PDO::FETCH_ASSOC);
                                    $nome_aluno = $res_aluno['nome'] ?? 'Aluno Excluído';

                                    // Estilo do Status
                                    $status_atual = $pedido['status'];
                                    $classe_status = 'badge-warning'; // Pendente
                                    if ($status_atual == 'Aprovado' || $status_atual == 'Agendada') $classe_status = 'badge-success';
                                    if ($status_atual == 'Recusado') $classe_status = 'badge-danger';
                                    if ($pedido['tipo'] == 'EXPERIMENTAL') $classe_status = 'badge-experimental';

                                    // Formatar Informação (Horário ou Observação)
                                    $info_exibir = $pedido['info'];
                                    if ($pedido['tipo'] == 'NORMAL' && !empty($info_exibir)) {
                                        $info_exibir = date('H:i', strtotime($info_exibir));
                                    }
                            ?>
                                    <tr>
                                        <td><b><?php echo mb_strtoupper($nome_aluno); ?></b></td>
                                        <td><i class="far fa-calendar-alt mr-1 text-muted"></i>
                                            <?php echo date('d/m/Y', strtotime($pedido['data'])); ?></td>
                                        <td><i class="far fa-clock mr-1 text-muted"></i>
                                            <small><?php echo $info_exibir; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $classe_status; ?> px-2 py-1">
                                                <?php
                                                if ($pedido['tipo'] == 'EXPERIMENTAL') echo 'EXPERIMENTAL';
                                                else echo strtoupper($status_atual);
                                                ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($pedido['tipo'] == 'NORMAL' && ($status_atual == 'Pendente' || $status_atual == '')): ?>
                                                <a href="pedidos-aulas.php?acao=aprovar&id_pedido=<?php echo $pedido['id']; ?>"
                                                    class="btn btn-xs btn-success shadow-sm mr-1" title="Aprovar Aula">
                                                    <i class="fas fa-check"></i> Aprovar
                                                </a>
                                                <a href="pedidos-aulas.php?acao=recusar&id_pedido=<?php echo $pedido['id']; ?>"
                                                    class="btn btn-xs btn-danger shadow-sm" title="Recusar Aula">
                                                    <i class="fas fa-times"></i> Recusar
                                                </a>
                                            <?php elseif ($pedido['tipo'] == 'EXPERIMENTAL'): ?>
                                                <span class="text-muted small"><i class="fas fa-lock mr-1"></i> Admin</span>
                                            <?php else: ?>
                                                <span class="text-muted small">Processado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center p-5 text-muted">
                                        <i class="fas fa-calendar-times fa-2x d-block mb-2"></i>
                                        Nenhuma solicitação encontrada na sua agenda.
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