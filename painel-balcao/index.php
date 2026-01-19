<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_professor = $_SESSION['id_usuario'];
$hoje = date('Y-m-d');

// LÓGICA PARA APROVAR/RECUSAR AULA
if (isset($_GET['acao_aula']) && isset($_GET['id_p'])) {
    $status_novo = ($_GET['acao_aula'] == 'aprovar') ? 'Aprovado' : 'Recusado';
    $id_p = $_GET['id_p'];
    $pdo->query("UPDATE pedidos_aulas SET status = '$status_novo' WHERE id = '$id_p'");
    echo "<script>window.location='index.php'</script>";
}

$total_alunos = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim'")->rowCount();
$total_atrasados = $pdo->query("SELECT id FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' AND data_venc < '$hoje'")->rowCount();

// Contador de Aulas Particulares Pendentes
$total_aulas_pendentes = $pdo->query("SELECT id FROM pedidos_aulas WHERE id_professor = '$id_professor' AND status = 'Pendente'")->rowCount();
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Painel do Professor - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #f8f9fa;
        }

        .card-menu {
            transition: 0.3s;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            height: 100%;
        }

        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .bg-qr {
            background: linear-gradient(45deg, #28a745, #1e7e34);
            color: white;
        }

        .bg-gradient-dark {
            background: linear-gradient(45deg, #1a1a1a, #4d4d4d);
            color: white;
        }

        .item-log {
            border-left: 5px solid #dee2e6;
            transition: 0.2s;
        }

        .item-log-sucesso {
            border-left: 5px solid #28a745;
            background-color: #f0fff4;
        }

        .badge-notif {
            position: absolute;
            top: 10px;
            right: 10px;
            border-radius: 50%;
            padding: 5px 10px;
        }

        .text-purple {
            color: #6f42c1;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark bg-dark shadow-sm">
        <a class="navbar-brand font-weight-bold" href="#">NOVA ARTE - PROFESSOR</a>
        <div class="text-white small">
            <i class="fas fa-user-circle mr-1"></i> <?php echo $_SESSION['nome_usuario']; ?> |
            <a href="../logout.php" class="text-danger ml-2 font-weight-bold">SAIR</a>
        </div>
    </nav>

    <div class="container-fluid mt-4 px-4">
        <div class="row">
            <div class="col-md mb-4">
                <a href="#" onclick="window.open('gerar-qr.php', 'Gerar QR', 'width=500,height=600');"
                    class="text-decoration-none">
                    <div class="card card-menu shadow-sm p-3 text-center bg-qr">
                        <i class="fas fa-qrcode fa-2x mb-2"></i>
                        <h6 class="font-weight-bold">QR Validador</h6>
                        <p class="small mb-0">Técnica e Faixa</p>
                    </div>
                </a>
            </div>

            <div class="col-md mb-4">
                <a href="usuarios.php" class="text-decoration-none text-dark">
                    <div class="card card-menu shadow-sm p-3 text-center border-top"
                        style="border-top: 5px solid #007bff !important;">
                        <i class="fas fa-user-check fa-2x mb-2 text-primary"></i>
                        <h6 class="font-weight-bold">Presenças/Selos</h6>
                        <p class="text-muted small mb-0">Aplicar Conteúdo</p>
                    </div>
                </a>
            </div>

            <div class="col-md mb-4">
                <a href="pedidos-aulas.php" class="text-decoration-none text-dark">
                    <div class="card card-menu shadow-sm p-3 text-center border-top"
                        style="border-top: 5px solid #6f42c1 !important;">
                        <?php if ($total_aulas_pendentes > 0): ?>
                            <span class="badge badge-danger badge-notif"><?php echo $total_aulas_pendentes ?></span>
                        <?php endif; ?>
                        <i class="fas fa-user-graduate fa-2x mb-2 text-purple"></i>
                        <h6 class="font-weight-bold">Aulas Particulares</h6>
                        <p class="text-muted small mb-0">Ver Pedidos</p>
                    </div>
                </a>
            </div>

            <div class="col-md mb-4">
                <a href="usuarios.php?status=atrasado" class="text-decoration-none text-dark">
                    <div class="card card-menu shadow-sm p-3 text-center border-top"
                        style="border-top: 5px solid #dc3545 !important;">
                        <?php if ($total_atrasados > 0): ?>
                            <span class="badge badge-danger badge-notif"><?php echo $total_atrasados ?></span>
                        <?php endif; ?>
                        <i class="fas fa-exclamation-triangle fa-2x mb-2 text-danger"></i>
                        <h6 class="font-weight-bold">Atrasados</h6>
                        <p class="text-muted small mb-0">Financeiro</p>
                    </div>
                </a>
            </div>

            <div class="col-md mb-4">
                <div class="card card-menu shadow-sm p-3 bg-gradient-dark text-center">
                    <i class="fas fa-users fa-2x mb-2 text-white-50"></i>
                    <h3 class="font-weight-bold mb-0"><?php echo $total_alunos ?></h3>
                    <p class="small mb-0">Alunos Ativos</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">

                <div class="card shadow-sm border-0 mb-4" id="sessao-aulas" style="border-radius: 15px;">
                    <div class="card-header bg-white font-weight-bold py-3 text-purple">
                        <i class="fas fa-calendar-check mr-2"></i> Pedidos de Aulas Particulares
                    </div>
                    <div class="card-body p-0 text-center">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light small">
                                    <tr>
                                        <th>ALUNO</th>
                                        <th>DATA/HORA</th>
                                        <th>AÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $pedidos = $pdo->query("SELECT p.*, u.nome FROM pedidos_aulas p INNER JOIN usuarios u ON p.id_aluno = u.id WHERE p.id_professor = '$id_professor' AND p.status = 'Pendente' ORDER BY p.data_aula ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    if (count($pedidos) > 0) {
                                        foreach ($pedidos as $p) {
                                    ?>
                                            <tr>
                                                <td><b><?php echo $p['nome'] ?></b></td>
                                                <td><?php echo date('d/m/Y', strtotime($p['data_aula'])) ?> às
                                                    <?php echo date('H:i', strtotime($p['horario_aula'])) ?></td>
                                                <td>
                                                    <a href="index.php?acao_aula=aprovar&id_p=<?php echo $p['id'] ?>"
                                                        class="btn btn-success btn-sm p-1 px-2"><i class="fas fa-check"></i></a>
                                                    <a href="index.php?acao_aula=recusar&id_p=<?php echo $p['id'] ?>"
                                                        class="btn btn-danger btn-sm p-1 px-2"><i class="fas fa-times"></i></a>
                                                </td>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">Nenhum pedido pendente</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header bg-white font-weight-bold py-3 text-muted">
                        <i class="fas fa-history mr-2"></i> Minhas Últimas Validações
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 text-sm">
                                <tbody>
                                    <?php
                                    $nome_prof = $_SESSION['nome_usuario'];
                                    $logs = $pdo->query("SELECT * FROM logs WHERE usuario = '$nome_prof' ORDER BY id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($logs as $l) {
                                        $data_f = date('H:i', strtotime($l['data']));
                                        $is_validacao = strpos($l['acao'], 'Validou selo') !== false;
                                        $classe = $is_validacao ? "item-log-sucesso" : "item-log";
                                    ?>
                                        <tr class="<?php echo $classe ?>">
                                            <td class='px-4 py-2'>
                                                <?php echo $is_validacao ? "<i class='fas fa-medal text-warning mr-2'></i>" : "<i class='fas fa-check-circle text-success mr-2'></i>"; ?>
                                                <?php echo $l['acao'] ?>
                                                <span class='float-right text-muted small mt-1'><?php echo $data_f ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <?php if ($_SESSION['nivel_usuario'] == 'Admin'): ?>
                    <div class="card bg-info text-white shadow-sm p-4 text-center mb-4"
                        style="border-radius: 15px; border:none;">
                        <i class="fas fa-cog fa-2x mb-2"></i>
                        <h5>Gestão Completa</h5>
                        <p class="small">Acesso ao painel administrativo e logs globais.</p>
                        <a href="../painel-adm/index.php" class="btn btn-light btn-sm font-weight-bold">IR PARA ADM</a>
                    </div>
                <?php endif; ?>

                <div class="card bg-white shadow-sm p-3 text-center" style="border-radius: 15px;">
                    <h6 class="text-muted font-weight-bold">Status do Sistema</h6>
                    <hr class="mt-1">
                    <p class="small text-muted">Data: <?php echo date('d/m/Y'); ?></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>