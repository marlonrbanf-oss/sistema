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

if ($status_filtro == 'atrasado') {
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

        .btn-novo {
            background-color: #28a745;
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn-novo:hover {
            background-color: #218838;
            color: white;
            transform: scale(1.05);
        }

        .btn-importar {
            background-color: #17a2b8;
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            font-weight: bold;
            transition: 0.3s;
        }

        .radio-group label {
            border: 1px solid #ddd;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: 0.2s;
            margin-right: 5px;
            display: inline-block;
        }

        .radio-group input[type="radio"] {
            display: none;
        }

        .radio-group input[type="radio"]:checked+label {
            background-color: #343a40;
            color: white;
            border-color: #343a40;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .faixa-radio label {
            border-width: 2px;
            font-weight: bold;
            text-shadow: 0px 0px 2px rgba(255, 255, 255, 0.5);
        }

        .faixa-radio input[type="radio"]:checked+label {
            transform: scale(1.1);
            border-color: #ffc107;
            color: inherit;
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
                <button class="btn btn-sm btn-novo shadow-sm mr-2" data-toggle="modal" data-target="#modalNovo">
                    <i class="fas fa-user-plus mr-1"></i> NOVO USUÁRIO
                </button>
                <button class="btn btn-sm btn-importar shadow-sm mr-2" data-toggle="modal" data-target="#modalImportar">
                    <i class="fas fa-file-excel mr-1"></i> IMPORTAR EXCEL
                </button>
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
                            <?php foreach ($res as $usu) {
                                $id = $usu['id'];
                                $data_venc = $usu['data_venc'];
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
                                        <span
                                            class="badge <?php echo $cor_selo ?> badge-nivel"><?php echo ($usu['nivel'] == 'Balconista') ? 'PROFESSOR' : $usu['nivel']; ?></span>
                                        <small class="text-muted ml-1"><?php echo $usu['usuario'] ?></small>
                                    </td>
                                    <td><?php if ($usu['nivel'] == 'Cliente'): ?><span
                                                class="badge-aulas"><?php echo $total_aulas ?></span><?php else: ?><span
                                                class="text-muted">---</span><?php endif; ?></td>
                                    <td>
                                        <?php if ($usu['nivel'] == 'Cliente'): ?>
                                            <div class="faixa-amostra" style="background-color: <?php echo $cor_exibicao ?>;">
                                            </div>
                                            <span class="ml-1 small font-weight-bold"><?php echo $usu['graus'] ?? '0' ?>º
                                                G</span>
                                        <?php else: ?><span class="text-muted small">---</span><?php endif; ?>
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
                                                        href="lancar-tecnica.php?id=<?php echo $id ?>"><i
                                                            class="fas fa-stamp mr-2"></i> Lançar Selo</a>
                                                    <a class="dropdown-item text-primary"
                                                        href="mudar-status.php?id=<?php echo $id ?>&acao=renovar"><i
                                                            class="fas fa-calendar-check mr-2"></i> Renovar</a>
                                                    <a class="dropdown-item text-danger"
                                                        href="mudar-status.php?id=<?php echo $id ?>&acao=atrasar"><i
                                                            class="fas fa-hand-holding-usd mr-2"></i> Definir Atrasado</a>
                                                    <a class="dropdown-item" href="#"
                                                        onclick="darPresenca(<?php echo $id ?>, '<?php echo $usu['nome'] ?>')"><i
                                                            class="fas fa-check mr-2 text-success"></i> Dar Presença</a>
                                                <?php endif; ?>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item" href="editar-usuario.php?id=<?php echo $id ?>"><i
                                                        class="fas fa-edit mr-2 text-info"></i> Editar</a>
                                                <a class="dropdown-item text-danger"
                                                    href="excluir-usuario.php?id=<?php echo $id ?>"
                                                    onclick="return confirm('Excluir?')"><i
                                                        class="fas fa-trash-alt mr-2"></i> Excluir</a>
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

    <div class="modal fade" id="modalNovo" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content shadow border-0" style="border-radius: 15px;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-2"></i> Cadastrar Usuário
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="form-usuarios" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Nome Completo</label><input type="text" name="nome"
                                    class="form-control" required></div>
                            <div class="col-md-6 form-group"><label>E-mail / Usuário</label><input type="email"
                                    name="email" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>CPF</label><input type="text" name="cpf"
                                    class="form-control cpf"></div>
                            <div class="col-md-4 form-group"><label>Telefone</label><input type="text" name="telefone"
                                    class="form-control telefone"></div>
                            <div class="col-md-4 form-group"><label>Senha</label><input type="password" name="senha"
                                    class="form-control" required></div>
                        </div>
                        <hr>
                        <h6 class="font-weight-bold text-secondary mb-3"><i class="fas fa-medal mr-2"></i> Nível de
                            Acesso</h6>
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <div class="radio-group">
                                    <input type="radio" name="nivel" value="Cliente" id="nivel_cliente" checked
                                        onclick="toggleGraduacao('Cliente')">
                                    <label for="nivel_cliente"><i class="fas fa-user-graduate mr-1"></i> ALUNO
                                        (CLIENTE)</label>
                                    <input type="radio" name="nivel" value="Professor" id="nivel_prof"
                                        onclick="toggleGraduacao('Professor')">
                                    <label for="nivel_prof"><i class="fas fa-user-tie mr-1"></i> PROFESSOR</label>
                                    <input type="radio" name="nivel" value="Admin" id="nivel_admin"
                                        onclick="toggleGraduacao('Admin')">
                                    <label for="nivel_admin"><i class="fas fa-user-shield mr-1"></i> ADMIN</label>
                                </div>
                            </div>
                        </div>
                        <div class="div-grad">
                            <hr>
                            <h6 class="font-weight-bold text-secondary mb-3"><i class="fas fa-layer-group mr-2"></i>
                                Dados de Graduação</h6>
                            <div class="row">
                                <div class="col-md-12 form-group">
                                    <label>Cor da Faixa</label><br>
                                    <div class="radio-group faixa-radio">
                                        <input type="radio" name="cor_faixa" value="Branca" id="f_branca" checked><label
                                            for="f_branca" style="background: white; color: black;">BRANCA</label>
                                        <input type="radio" name="cor_faixa" value="Azul" id="f_azul"><label
                                            for="f_azul" style="background: blue; color: white;">AZUL</label>
                                        <input type="radio" name="cor_faixa" value="Roxa" id="f_roxa"><label
                                            for="f_roxa" style="background: purple; color: white;">ROXA</label>
                                        <input type="radio" name="cor_faixa" value="Marrom" id="f_marrom"><label
                                            for="f_marrom" style="background: #8B4513; color: white;">MARROM</label>
                                        <input type="radio" name="cor_faixa" value="Preta" id="f_preta"><label
                                            for="f_preta" style="background: black; color: white;">PRETA</label>
                                    </div>
                                </div>
                                <div class="col-md-3 form-group"><label>Graus</label><input type="number" name="graus"
                                        class="form-control" value="0" min="0" max="4"></div>
                                <div class="col-md-3 form-group"><label>Aulas Realizadas</label><input type="number"
                                        name="total_aulas" class="form-control" value="0" min="0"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" id="btn-salvar"
                            class="btn btn-success btn-block">SALVAR NOVO USUÁRIO</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImportar" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content shadow border-0" style="border-radius: 15px;">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i> Importar via Excel</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted small">Use nossa planilha padrão para evitar erros no banco de dados.</p>
                    <a href="modelo-importacao.xlsx" class="btn btn-sm btn-outline-secondary mb-3"><i
                            class="fas fa-download mr-1"></i> Baixar Tabela Padrão (Modelo)</a>
                    <hr>
                    <form action="importar-excel.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group text-left">
                            <label class="font-weight-bold">Selecione o arquivo preenchido:</label>
                            <input type="file" name="arquivo_excel" class="form-control-file border p-2 w-100"
                                accept=".xlsx, .xls, .csv" required>
                        </div>
                        <button type="submit" class="btn btn-info btn-block mt-3 font-weight-bold">PROCESSAR
                            IMPORTAÇÃO</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <script>
        $(document).ready(function() {
            // Máscaras
            $('.cpf').mask('000.000.000-00', {
                reverse: true
            });
            var SPMaskBehavior = function(val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            };
            var spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };
            $('.telefone').mask(SPMaskBehavior, spOptions);

            // Busca na tabela
            $("#inputBusca").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#tabelaUsuarios tbody tr").each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            // AJAX para salvar
            $("#form-usuarios").submit(function(event) {
                event.preventDefault();
                $('#btn-salvar').text('Salvando...');
                $('#btn-salvar').attr('disabled', true);

                $.ajax({
                    url: "salvar-usuario.php",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(mensagem) {
                        if (mensagem.trim() === "Salvo com Sucesso") {
                            let nomeAluno = $("input[name='nome']").val();
                            let emailAluno = $("input[name='email']").val();
                            let senhaAluno = $("input[name='senha']").val();
                            let telefoneAluno = $("input[name='telefone']").val().replace(/\D/g,
                                '');
                            let nivelAcesso = $("input[name='nivel']:checked").val();

                            if (nivelAcesso === 'Cliente' && telefoneAluno !== "") {
                                let texto = "Olá *" + nomeAluno +
                                    "*! 👋\n\nSeja muito bem-vindo(a) à *Nova Arte BJJ*! 🥋\nSeu cadastro foi realizado com sucesso.\n\n📍 *DADOS DE ACESSO AO PAINEL:*\n🔗 Link de Acesso: https://sistema-production-e48b.up.railway.app/login.php\n📧 Usuário: `" +
                                    emailAluno + "`\n🔑 Senha: `" + senhaAluno +
                                    "`\n\nNo painel você poderá acompanhar suas aulas e progresso! \n\n*OSS!*";
                                let linkZap = "https://api.whatsapp.com/send?phone=55" +
                                    telefoneAluno + "&text=" + encodeURIComponent(texto);
                                alert(
                                    "Usuário salvo! Clique em OK para enviar os dados de acesso via WhatsApp."
                                );
                                window.open(linkZap, '_blank');
                            } else {
                                alert(mensagem);
                            }
                            window.location = "usuarios.php";
                        } else {
                            alert(mensagem);
                            $('#btn-salvar').text('SALVAR NOVO USUÁRIO').attr('disabled',
                                false);
                        }
                    }, // VÍRGULA ADICIONADA AQUI (CORREÇÃO)
                    error: function() {
                        alert("Erro ao processar a requisição no servidor.");
                        $('#btn-salvar').text('SALVAR NOVO USUÁRIO').attr('disabled', false);
                    }
                });
            });
        });

        function toggleGraduacao(nivel) {
            if (nivel === 'Cliente') {
                $('.div-grad').slideDown();
            } else {
                $('.div-grad').slideUp();
            }
        }

        function darPresenca(id, nome) {
            let quantidade = prompt("Quantas presenças deseja atribuir para " + nome + "?", "1");
            if (quantidade !== null && quantidade !== "" && !isNaN(quantidade) && quantidade > 0) {
                window.location.href = "dar-presenca.php?id=" + id + "&quantidade=" + quantidade;
            } else if (quantidade !== null) {
                alert("Por favor, insira um número válido.");
            }
        }
    </script>
</body>

</html>