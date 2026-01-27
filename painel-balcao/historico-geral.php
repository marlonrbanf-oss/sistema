<?php
@session_start();
require_once("../conexao.php");

// 1. SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script>window.location='../login.php';</script>";
    exit();
}

// 2. FILTROS DE DATA (Padrão: mês atual)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <style>
        .table-historico th {
            background-color: #f8f9fa;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .badge-nova {
            font-size: 0.9rem;
            min-width: 100px;
        }

        .inst-name {
            color: #6f42c1;
            font-weight: 600;
        }

        .card-historico {
            border-radius: 15px;
            overflow: hidden;
        }

        /* Ajuste para não sobrepor o menu lateral se houver */
        .container-historico {
            padding: 10px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid container-historico">
        <div class="card card-historico shadow-sm border-0">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="fas fa-history mr-2 text-warning"></i> Histórico Geral de Graduações</h5>
                <a href="index.php?pag=usuarios" class="btn btn-sm btn-outline-light rounded-pill">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar
                </a>
            </div>
            <div class="card-body">

                <form class="form-inline mb-4 bg-white p-3 border rounded shadow-sm">
                    <div class="form-group mr-3">
                        <label class="mr-2 font-weight-bold">De:</label>
                        <input type="date" name="data_inicio" class="form-control form-control-sm"
                            value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="form-group mr-3">
                        <label class="mr-2 font-weight-bold">Até:</label>
                        <input type="date" name="data_fim" class="form-control form-control-sm"
                            value="<?php echo $data_fim; ?>">
                        <input type="hidden" name="pag" value="historico-geral">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-historico border">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Graduação Anterior</th>
                                <th>Nova Graduação</th>
                                <th class="text-center">Data da Promoção</th>
                                <th>Instrutor Responsável</th>
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
                                    <td class="align-middle">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-circle mr-2 text-secondary"></i>
                                            <span
                                                class="font-weight-bold text-dark"><?php echo mb_strtoupper($log['nome']); ?></span>
                                        </div>
                                    </td>
                                    <td class="align-middle text-muted small">
                                        <i class="fas fa-arrow-left mr-1 opacity-50"></i>
                                        <?php echo $log['faixa_antiga']; ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-success p-2 badge-nova shadow-sm">
                                            <i class="fas fa-arrow-up mr-1"></i> <?php echo $log['faixa_nova']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle font-weight-bold">
                                        <span
                                            class="text-secondary"><?php echo date('d/m/Y', strtotime($log['data'])); ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <small class="inst-name">
                                            <i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo $log['instrutor']; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach;

                            if (count($logs) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center p-5 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-25"></i>
                                        Nenhuma promoção registrada entre
                                        <b><?php echo date('d/m/Y', strtotime($data_inicio)); ?></b> e
                                        <b><?php echo date('d/m/Y', strtotime($data_fim)); ?></b>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>

</html>