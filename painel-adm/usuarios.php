<?php
@session_start();
require_once("../conexao.php");

// Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$hoje = date('Y-m-d');

/**
 * Função para converter a cor da faixa para CSS
 */
function converterCorFaixa($cor_banco)
{
    $cor = mb_strtolower(trim($cor_banco));
    switch ($cor) {
        case 'white':
        case 'branca':
            return 'white';
        case 'blue':
        case 'azul':
            return 'blue';
        case 'purple':
        case 'roxa':
            return 'purple';
        case 'brown':
        case 'marrom':
            return '#8B4513';
        case 'black':
        case 'preta':
            return 'black';
        default:
            return $cor_banco;
    }
}

// 1. Captura os parâmetros da URL
$nivel_filtro = isset($_GET['nivel']) ? $_GET['nivel'] : '';
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';

// 2. Monta a Query SQL baseada nos filtros recebidos
$sql = "SELECT u.*, g.cor_faixa, g.graus, g.total_aulas 
        FROM usuarios as u 
        LEFT JOIN graduacoes as g ON u.id = g.usuario_id";

// Lógica de Filtros Combinada
if ($status_filtro == 'atrasado') {
    // Filtro vindo do Dashboard: Alunos ativos com vencimento menor que hoje
    $sql .= " WHERE u.nivel = 'Cliente' AND u.ativo = 'Sim' AND u.data_venc < '$hoje'";
} elseif ($nivel_filtro == 'Cliente') {
    $sql .= " WHERE u.nivel = 'Cliente'";
} elseif ($nivel_filtro == 'Professor') {
    $sql .= " WHERE (u.nivel = 'Professor' OR u.nivel = 'Balconista')";
}

$sql .= " ORDER BY u.nome ASC";

$query = $pdo->query($sql);
$res = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Gestão de Pessoas - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            font-size: 0.85rem;
            background: #f4f6f9;
        }

        .faixa-amostra {
            width: 35px;
            height: 14px;
            border: 1px solid #000;
            display: inline-block;
            border-radius: 2px;
            vertical-align: middle;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .table td {
            vertical-align: middle !important;
        }

        .badge-nivel {
            font-size: 0.65rem;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
        }

        .badge-aulas {
            background: #6f42c1;
            color: #fff;
            padding: 4px 10px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 0.75rem;
            box-shadow: 0 2px 4px rgba(111, 66, 193, 0.3);
        }

        .status-pill {
            width: 100px;
            display: inline-block;
            text-align: center;
            font-weight: 800;
            padding: 6px 4px;
            border-radius: 4px;
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #ffffff !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .search-box {
            border-radius: 20px;
            padding-left: 40px;
            border: 1px solid #ddd;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #aaa;
            z-index: 10;
        }

        @media (max-width: 768px) {
            .btn-group-mobile {
                display: flex;
                flex-direction: column;
                width: 100%;
                gap: 5px;
            }
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-3 mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <h3 class="font-weight-bold mb-0 text-dark"><i class="fas fa-users-cog mr-2 text-primary"></i> Gestão de
                Pessoas</h3>
            <div class="btn-group-mobile">
                <a href="relatorio-atrasados.php" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm mr-2">
                    <i class="fas fa-file-pdf mr-1"></i> Lista de Atrasados
                </a>
                <a href="alunos-inativos.php" class="btn btn-sm btn-outline-secondary shadow-sm mr-2">
                    <i class="fas fa-user-slash mr-1"></i> Ver Inativos
                </a>
                <div class="btn-group shadow-sm mr-md-2">
                    <a href="usuarios.php"
                        class="btn btn-sm btn-outline-dark <?php echo ($nivel_filtro == '' && $status_filtro == '') ? 'active' : '' ?>">Todos</a>
                    <a href="usuarios.php?nivel=Cliente"
                        class="btn btn-sm btn-outline-primary <?php echo $nivel_filtro == 'Cliente' ? 'active' : '' ?>">Alunos</a>
                    <a href="usuarios.php?nivel=Professor"
                        class="btn btn-sm btn-outline-info <?php echo $nivel_filtro == 'Professor' ? 'active' : '' ?>">Professores</a>
                </div>
                <a href="index.php" class="btn btn-sm btn-dark"><i class="fas fa-home"></i></a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 col-lg-4 position-relative">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="inputBusca" class="form-control shadow-sm search-box"
                    placeholder="Localizar usuário..." autocomplete="off">
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tabelaUsuarios">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Treinos</th>
                                <th>Graduação</th>
                                <th>Status Fin.</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($res as $usu) {
                                $id = $usu['id'];
                                $data_venc = $usu['data_venc'];
                                $tel = preg_replace('/[^0-9]/', '', $usu['telefone'] ?? '');
                                $total_aulas = $usu['total_aulas'] ?? 0;
                                $cor_exibicao = converterCorFaixa($usu['cor_faixa'] ?? 'white');

                                if ($usu['ativo'] == 'Não') {
                                    $st_txt = "Inativo";
                                    $st_class = "bg-secondary";
                                } elseif ($data_venc == null || $data_venc == '0000-00-00') {
                                    $st_txt = "Sem Data";
                                    $st_class = "bg-warning";
                                } elseif (strtotime($data_venc) < strtotime($hoje)) {
                                    $st_txt = "Atrasado";
                                    $st_class = "bg-danger";
                                } else {
                                    $st_txt = "Em Dia";
                                    $st_class = "bg-success";
                                }

                                $cor_selo = 'badge-secondary';
                                if ($usu['nivel'] == 'Admin') $cor_selo = 'badge-dark';
                                if ($usu['nivel'] == 'Professor' || $usu['nivel'] == 'Balconista') $cor_selo = 'badge-primary';
                                if ($usu['nivel'] == 'Cliente') $cor_selo = 'badge-info';
                            ?>
                                <tr>
                                    <td>
                                        <span
                                            class="font-weight-bold d-block"><?php echo mb_strtoupper($usu['nome']) ?></span>
                                        <span class="badge <?php echo $cor_selo ?> badge-nivel">
                                            <?php echo ($usu['nivel'] == 'Balconista') ? 'PROFESSOR' : $usu['nivel']; ?>
                                        </span>
                                        <small class="text-muted ml-1"><?php echo $usu['usuario'] ?></small>
                                    </td>
                                    <td>
                                        <?php if ($usu['nivel'] == 'Cliente'): ?>
                                            <span class="badge-aulas"><?php echo $total_aulas ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($usu['nivel'] == 'Cliente'): ?>
                                            <div class="faixa-amostra" style="background-color: <?php echo $cor_exibicao ?>;">
                                            </div>
                                            <span class="ml-1 small font-weight-bold"><?php echo $usu['graus'] ?? '0' ?>º
                                                G</span>
                                        <?php else: ?>
                                            <span class="text-muted small">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-pill <?php echo $st_class ?>"><?php echo $st_txt ?></span>
                                        <?php if ($usu['nivel'] == 'Cliente' && $data_venc != null && $data_venc != '0000-00-00'): ?>
                                            <br><small class="text-muted">Venc:
                                                <?php echo date('d/m/Y', strtotime($data_venc)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm border dropdown-toggle" type="button"
                                                data-toggle="dropdown">Gerenciar</button>
                                            <div class="dropdown-menu dropdown-menu-right shadow border-0">

                                                <?php if ($usu['nivel'] == 'Cliente'): ?>
                                                    <a class="dropdown-item text-warning font-weight-bold"
                                                        href="lancar-tecnica.php?id=<?php echo $id ?>">
                                                        <i class="fas fa-stamp mr-2"></i> Lançar Selo/Técnica
                                                    </a>

                                                    <a class="dropdown-item text-primary"
                                                        href="mudar-status.php?id=<?php echo $id ?>&acao=renovar">
                                                        <i class="fas fa-calendar-check mr-2"></i> Renovar (Ativar/Em Dia)
                                                    </a>

                                                    <a class="dropdown-item text-danger"
                                                        href="mudar-status.php?id=<?php echo $id ?>&acao=atrasar">
                                                        <i class="fas fa-hand-holding-usd mr-2"></i> Definir como Atrasado
                                                    </a>

                                                    <a class="dropdown-item" href="dar-presenca.php?id=<?php echo $id ?>">
                                                        <i class="fas fa-check mr-2 text-success"></i> Dar Presença
                                                    </a>

                                                    <?php if ($st_txt == "Atrasado" && $tel != ''):
                                                        $msg = "Olá {$usu['nome']}, notamos que sua mensalidade venceu em " . date('d/m/Y', strtotime($data_venc)) . ". Como podemos ajudar?";
                                                    ?>
                                                        <a class="dropdown-item text-success"
                                                            href="https://api.whatsapp.com/send?phone=55<?php echo $tel ?>&text=<?php echo urlencode($msg) ?>"
                                                            target="_blank">
                                                            <i class="fab fa-whatsapp mr-2"></i> Cobrar WhatsApp
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <div class="dropdown-divider"></div>

                                                <?php if ($usu['ativo'] == 'Sim'): ?>
                                                    <a class="dropdown-item text-secondary"
                                                        href="mudar-status.php?id=<?php echo $id ?>&acao=inativar">
                                                        <i class="fas fa-user-slash mr-2"></i> Inativar Conta
                                                    </a>
                                                <?php else: ?>
                                                    <a class="dropdown-item text-success font-weight-bold"
                                                        href="mudar-status.php?id=<?php echo $id ?>&acao=ativar">
                                                        <i class="fas fa-user-check mr-2"></i> Ativar Conta
                                                    </a>
                                                <?php endif; ?>

                                                <a class="dropdown-item" href="editar-usuario.php?id=<?php echo $id ?>">
                                                    <i class="fas fa-edit mr-2 text-info"></i> Editar / Senha
                                                </a>
                                                <a class="dropdown-item text-danger"
                                                    href="excluir-usuario.php?id=<?php echo $id ?>"
                                                    onclick="return confirm('Excluir?')">
                                                    <i class="fas fa-trash-alt mr-2"></i> Excluir
                                                </a>
                                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#inputBusca").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#tabelaUsuarios tbody tr").each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>
</body>

</html>