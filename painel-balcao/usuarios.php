<?php
@session_start();
require_once("../conexao.php");

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';

// Função para garantir que o CSS entenda a cor vinda do banco
function tratarCorFaixa($cor)
{
    $c = mb_strtolower(trim($cor));
    $mapa = [
        'branca' => 'white',
        'azul'   => 'blue',
        'roxa'   => 'purple',
        'marrom' => '#8B4513',
        'preta'  => 'black',
        'verde'  => 'green',
        'amarela' => 'yellow'
    ];
    return $mapa[$c] ?? $c;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Lista de Chamada - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        .table td {
            vertical-align: middle;
        }

        .btn-check-in {
            font-weight: bold;
        }

        .btn-selo {
            background-color: #6f42c1;
            color: white;
            border: none;
        }

        .status-atrasado {
            border-left: 5px solid #dc3545;
        }

        /* Garantir que a badge da faixa tenha tamanho visível */
        .badge-faixa {
            padding: 5px 10px;
            border: 1px solid #333;
            min-width: 80px;
            display: inline-block;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-4">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h3><i class="fas fa-list-alt mr-2 text-primary"></i>
                    <?php echo ($status_filtro == 'atrasado') ? 'Alunos com Pendência' : 'Chamada de Alunos'; ?>
                </h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="index.php" class="btn btn-dark btn-sm shadow-sm">Voltar ao Painel</a>
            </div>
        </div>

        <div class="card mb-4 shadow-sm border-primary">
            <div class="card-body">
                <div class="input-group input-group-lg">
                    <input type="text" id="txt_busca" class="form-control" placeholder="Pesquisar aluno...">
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabela_alunos">
                        <thead class="thead-dark">
                            <tr>
                                <th>Aluno</th>
                                <th>Graduação</th>
                                <th class="text-center">Aulas</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT u.id, u.nome, u.usuario, u.data_venc, g.cor_faixa, g.graus, g.total_aulas 
                                    FROM usuarios u 
                                    LEFT JOIN graduacoes g ON u.id = g.usuario_id 
                                    WHERE u.nivel = 'Cliente' AND u.ativo = 'Sim'";

                            if ($status_filtro == 'atrasado') {
                                $sql .= " AND u.data_venc < '$hoje'";
                            }
                            $sql .= " ORDER BY u.nome ASC";

                            $query = $pdo->query($sql);
                            $res = $query->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($res as $aluno) {
                                $id = $aluno['id'];
                                // TRATAMENTO DA COR
                                $cor_banco = $aluno['cor_faixa'] ?? 'white';
                                $cor_css = tratarCorFaixa($cor_banco);

                                // Definir cor do texto (Preto para faixas claras, Branco para escuras)
                                $texto_cor = ($cor_css == 'white' || $cor_css == 'yellow') ? '#000' : '#fff';

                                $vencido = (strtotime($aluno['data_venc'] ?? '') < strtotime($hoje)) ? true : false;
                            ?>
                                <tr class="<?php echo $vencido ? 'status-atrasado' : ''; ?>">
                                    <td>
                                        <strong><?php echo mb_strtoupper($aluno['nome']) ?></strong>
                                        <?php if ($vencido): ?> <span class="badge badge-danger">PENDENTE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-faixa"
                                            style="background-color: <?php echo $cor_css ?>; color: <?php echo $texto_cor ?>;">
                                            <?php echo strtoupper($cor_banco) ?> (<?php echo $aluno['graus'] ?>G)
                                        </span>
                                    </td>
                                    <td class="text-center font-weight-bold"><?php echo $aluno['total_aulas'] ?></td>
                                    <td class="text-center">
                                        <a href="registrar_presenca.php?id=<?php echo $id ?>"
                                            class="btn btn-success btn-sm px-3 shadow-sm">
                                            <i class="fas fa-check"></i> PRESENÇA
                                        </a>

                                        <a href="lancar-tecnica.php?id=<?php echo $id ?>"
                                            class="btn btn-selo btn-sm px-3 shadow-sm">
                                            <i class="fas fa-stamp"></i> SELO
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#txt_busca").keyup(function() {
                var texto = $(this).val();
                if (texto.length > 0) {
                    $.ajax({
                        url: "buscar_para_chamada.php",
                        method: "POST",
                        data: {
                            txt_busca: texto
                        },
                        success: function(data) {
                            $('#tabela_alunos tbody').html(data);
                        }
                    });
                } else {
                    location.reload();
                }
            });
        });
    </script>
</body>

</html>