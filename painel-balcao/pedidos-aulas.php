<?php
@session_start();
require_once("../conexao.php");

$id_professor = $_SESSION['id_usuario']; // ID do professor logado

// Lógica para Aprovar ou Recusar via GET (simplificado)
if(isset($_GET['acao']) && isset($_GET['id_pedido'])){
    $status = ($_GET['acao'] == 'aprovar') ? 'Aprovado' : 'Recusado';
    $id_p = $_GET['id_pedido'];
    
    $pdo->query("UPDATE pedidos_aulas SET status = '$status' WHERE id = '$id_p'");
    echo "<script>window.location='pedidos-aulas.php'</script>";
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
                <h3 class="card-title"><i class="fas fa-calendar-check mr-2"></i> Solicitações de Aulas Particulares
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Aluno</th>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Busca os pedidos e o nome do aluno fazendo um JOIN
                            $query = $pdo->prepare("SELECT p.*, u.nome as nome_aluno 
                                                  FROM pedidos_aulas p 
                                                  INNER JOIN usuarios u ON p.id_aluno = u.id 
                                                  WHERE p.id_professor = :prof 
                                                  ORDER BY p.data_pedido DESC");
                            $query->bindValue(":prof", $id_professor);
                            $query->execute();
                            $pedidos = $query->fetchAll(PDO::FETCH_ASSOC);

                            if(count($pedidos) > 0){
                                foreach($pedidos as $pedido){
                                    $classe_status = 'badge-warning';
                                    if($pedido['status'] == 'Aprovado') $classe_status = 'badge-success';
                                    if($pedido['status'] == 'Recusado') $classe_status = 'badge-danger';
                            ?>
                            <tr>
                                <td><b><?php echo $pedido['nome_aluno']; ?></b></td>
                                <td><?php echo date('d/m/Y', strtotime($pedido['data_aula'])); ?></td>
                                <td><?php echo date('H:i', strtotime($pedido['horario_aula'])); ?></td>
                                <td><span
                                        class="badge <?php echo $classe_status; ?>"><?php echo $pedido['status']; ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($pedido['status'] == 'Pendente'): ?>
                                    <a href="pedidos-aulas.php?acao=aprovar&id_pedido=<?php echo $pedido['id']; ?>"
                                        class="btn btn-xs btn-success shadow-sm mr-1">Aprovar</a>
                                    <a href="pedidos-aulas.php?acao=recusar&id_pedido=<?php echo $pedido['id']; ?>"
                                        class="btn btn-xs btn-danger shadow-sm">Recusar</a>
                                    <?php else: ?>
                                    <span class="text-muted small">Finalizado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center p-4">Nenhuma solicitação encontrada.</td></tr>';
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