<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Filtros de Data (Padrão: Mês Atual)
$mes_atual = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano_atual = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

// CORREÇÃO APLICADA: Busca usando aluno_id, professor_id e observacoes
$query = $pdo->query("SELECT a.*, u.nome as nome_aluno, p.nome as nome_professor 
                      FROM aulas_particulares a 
                      INNER JOIN usuarios u ON a.aluno_id = u.id 
                      INNER JOIN usuarios p ON a.professor_id = p.id
                      WHERE a.observacoes LIKE '%EXPERIMENTAL%' 
                      AND a.professor_id != '0'
                      AND MONTH(a.data) = '$mes_atual' 
                      AND YEAR(a.data) = '$ano_atual'
                      ORDER BY a.data DESC");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_mes = count($res);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Relatório de Experimentais - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #f8f9fa;
            font-size: 0.85rem;
        }

        .card-stats {
            border-radius: 15px;
            background: #fff;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .table-resumo {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        @media print {
            .no-print {
                display: none;
            }

            .card-stats {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h4 class="font-weight-bold">Relatório de Conversão</h4>
            <div>
                <button onclick="window.print()" class="btn btn-sm btn-info mr-2"><i class="fas fa-print mr-1"></i>
                    Imprimir</button>
                <a href="index.php" class="btn btn-sm btn-dark">Voltar</a>
            </div>
        </div>

        <div class="card card-stats p-3 mb-4 no-print">
            <form method="get" class="row align-items-end">
                <div class="col-md-4">
                    <label class="small font-weight-bold">Mês</label>
                    <select name="mes" class="form-control form-control-sm">
                        <?php
                        $meses = ["01" => "Janeiro", "02" => "Fevereiro", "03" => "Março", "04" => "Abril", "05" => "Maio", "06" => "Junho", "07" => "Julho", "08" => "Agosto", "09" => "Setembro", "10" => "Outubro", "11" => "Novembro", "12" => "Dezembro"];
                        foreach ($meses as $num => $nome) {
                            $sel = ($num == $mes_atual) ? "selected" : "";
                            echo "<option value='$num' $sel>$nome</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small font-weight-bold">Ano</label>
                    <input type="number" name="ano" class="form-control form-control-sm"
                        value="<?php echo $ano_atual; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-stats p-4 text-center border-top border-primary"
                    style="border-width: 4px !important;">
                    <h6 class="text-muted mb-1 text-uppercase small">Aulas Experimentais Agendadas</h6>
                    <h1 class="font-weight-bold mb-0"><?php echo $total_mes; ?></h1>
                    <small class="text-info">Período: <?php echo $meses[$mes_atual] ?> /
                        <?php echo $ano_atual ?></small>
                </div>
            </div>
        </div>

        <div class="card table-resumo shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Data da Aula</th>
                        <th>Aluno</th>
                        <th>Professor Atribuído</th>
                        <th class="text-right">Observações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($res as $row) { ?>
                        <tr>
                            <td><b><?php echo date('d/m/Y', strtotime($row['data'])); ?></b>
                                <small><?php echo $row['horario']; ?></small>
                            </td>
                            <td><?php echo $row['nome_aluno']; ?></td>
                            <td><span class="badge badge-light p-2"><i class="fas fa-user-tie mr-1"></i>
                                    <?php echo $row['nome_professor']; ?></span></td>
                            <td class="text-right text-muted small"><?php echo $row['observacoes']; ?></td>
                        </tr>
                    <?php }
                    if ($total_mes == 0) echo "<tr><td colspan='4' class='text-center p-4'>Nenhum agendamento realizado neste período.</td></tr>"; ?>
                </tbody>
            </table>
        </div>

        <footer class="mt-4 text-center text-muted small no-print">
            Relatório gerado em <?php echo date('d/m/Y H:i'); ?> - Academia Nova Arte BJJ
        </footer>
    </div>
</body>

</html>