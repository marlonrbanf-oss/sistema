<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';

// Função para converter o nome da cor do banco em CSS válido
function tratarCorFaixa($cor)
{
    $c = mb_strtolower(trim($cor));
    $mapa = [
        'branca'   => 'white',
        'azul'     => '#0000FF',
        'roxa'     => '#8A2BE2',
        'marrom'   => '#5D2E11',
        'preta'    => 'black',
        'verde'    => '#008000',
        'amarela'  => '#FFFF00',
        'laranja'  => '#FFA500',
        'cinza'    => '#808080'
    ];
    return $mapa[$c] ?? $c;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Lista de Chamada e Selos - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        .table td {
            vertical-align: middle;
        }

        .btn-selo {
            background-color: #6f42c1;
            color: white;
            border: none;
        }

        .btn-selo:hover {
            background-color: #5a32a3;
            color: white;
        }

        .btn-analise {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .btn-analise:hover {
            background-color: #218838;
            color: white;
        }

        .status-atrasado {
            border-left: 6px solid #dc3545;
            background-color: #fff5f5;
        }

        .badge-faixa {
            padding: 6px 12px;
            border: 1px solid #333;
            min-width: 90px;
            display: inline-block;
            font-weight: bold;
            border-radius: 4px;
            text-shadow: 0.5px 0.5px 1px rgba(0, 0, 0, 0.1);
        }

        .search-container {
            border-radius: 50px;
            padding-left: 20px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-4">
        <div class="row align-items-center mb-4">
            <div class="col-md-7">
                <h3 class="font-weight-bold">
                    <i class="fas fa-users-cog mr-2 text-primary"></i>
                    <?php echo ($status_filtro == 'atrasado') ? 'Pendências Financeiras' : 'Alunos: Chamada e Selos'; ?>
                </h3>
            </div>
            <div class="col-md-5 text-md-right mt-2 mt-md-0">
                <a href="index.php" class="btn btn-outline-dark btn-sm shadow-sm mr-2">
                    <i class="fas fa-home"></i> Painel
                </a>
                <?php if ($status_filtro != 'atrasado'): ?>
                    <a href="usuarios.php?status=atrasado" class="btn btn-danger btn-sm shadow-sm">
                        <i class="fas fa-exclamation-triangle"></i> Ver Atrasados
                    </a>
                <?php else: ?>
                    <a href="usuarios.php" class="btn btn-primary btn-sm shadow-sm">
                        <i class="fas fa-list"></i> Ver Todos
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0" style="border-radius: 50px 0 0 50px;">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>
                    <input type="text" id="txt_busca"
                        class="form-control form-control-lg border-left-0 search-container"
                        placeholder="Pesquise por nome, e-mail ou CPF..." style="border-radius: 0 50px 50px 0;">
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
                                <th class="text-center">Vencimento</th>
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
                                $cor_banco = $aluno['cor_faixa'] ?? 'Branca';
                                $cor_css = tratarCorFaixa($cor_banco);

                                $texto_cor = (in_array($cor_css, ['white', '#FFFF00', 'yellow'])) ? '#000' : '#fff';

                                $data_venc = $aluno['data_venc'];
                                $vencido = (strtotime($data_venc) < strtotime($hoje)) ? true : false;
                            ?>
                                <tr class="<?php echo $vencido ? 'status-atrasado' : ''; ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                            </div>
                                            <div>
                                                <span
                                                    class="font-weight-bold"><?php echo mb_strtoupper($aluno['nome']) ?></span><br>
                                                <small class="text-muted"><?php echo $aluno['usuario'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $vencido ? 'badge-danger' : 'badge-light'; ?> p-2">
                                            <?php echo date('d/m/Y', strtotime($data_venc)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge-faixa"
                                            style="background-color: <?php echo $cor_css ?>; color: <?php echo $texto_cor ?>;">
                                            <?php echo strtoupper($cor_banco) ?>
                                            <span class="ml-1 opacity-75 small"><?php echo $aluno['graus'] ?>G</span>
                                        </span>
                                    </td>
                                    <td class="text-center font-weight-bold text-primary">
                                        <?php echo $aluno['total_aulas'] ?? 0 ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="index.php?pag=registrar-presenca&id=<?php echo $id ?>"
                                                class="btn btn-success btn-sm shadow-sm" title="Registrar Aula">
                                                <i class="fas fa-check"></i>
                                            </a>

                                            <a href="index.php?pag=lancar-tecnica&id=<?php echo $id ?>"
                                                class="btn btn-selo btn-sm shadow-sm" title="Lançar Selo Individual">
                                                <i class="fas fa-stamp"></i>
                                            </a>

                                            <a href="index.php?pag=relatorio-graduacao&id=<?php echo $id ?>"
                                                class="btn btn-warning btn-sm shadow-sm" title="Análise de Graduação">
                                                <i class="fas fa-chart-line"></i>
                                            </a>

                                            <a href="index.php?pag=editar-perfil-aluno&id=<?php echo $id ?>"
                                                class="btn btn-info btn-sm shadow-sm" title="Perfil do Aluno">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
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
                    var urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('status')) {
                        window.location.href = 'usuarios.php?status=atrasado';
                    } else {
                        window.location.reload();
                    }
                }
            });
        });
    </script>
</body>

</html>