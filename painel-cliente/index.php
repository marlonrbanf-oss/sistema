<?php
@session_start();
require_once("../conexao.php");
require_once("funcoes-graduacao.php");

// 1. Verificação de Segurança
if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// 2. Busca UNIFICADA de Graduação (Regras de aulas, faixa e grau)
$status = verificarRequisitos($pdo, $id_usuario);

// 3. Busca de Dados Pessoais
$query_u = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$query_u->bindValue(":id", $id_usuario);
$query_u->execute();
$dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

$nome_usuario = $dados_u['nome'] ?? 'Usuário';
$foto_usuario = (!empty($dados_u['foto'])) ? $dados_u['foto'] : 'usuario-icone-claro.png';

// --- AJUSTE: CÁLCULO DINÂMICO DA JORNADA DE MAESTRIA ---
// 1. Contamos o total de técnicas cadastradas para este aluno (o 100% técnico)
$query_total_hab = $pdo->prepare("SELECT COUNT(*) as total FROM habilidades WHERE usuario_id = :id");
$query_total_hab->bindValue(":id", $id_usuario);
$query_total_hab->execute();
$total_geral_tecnicas = $query_total_hab->fetch(PDO::FETCH_ASSOC)['total'] ?? 1;

$total_faixas_jornada = 7; // Branca até Vermelha
$meta_maestria = $total_geral_tecnicas + $total_faixas_jornada;

// 2. Contamos quantas técnicas o aluno já dominou (validadas pelo professor)
$query_maestria = $pdo->prepare("SELECT COUNT(*) as total FROM habilidades WHERE usuario_id = :id AND (status = 'Dominado' OR status = 'Concluído')");
$query_maestria->bindValue(":id", $id_usuario);
$query_maestria->execute();
$tecnicas_concluidas = $query_maestria->fetch(PDO::FETCH_ASSOC)['total'];

// 3. Definimos o peso da faixa atual na jornada
$niveis_faixa = ['branca' => 1, 'azul' => 2, 'roxa' => 3, 'marrom' => 4, 'preta' => 5, 'coral' => 6, 'vermelha' => 7];
$nivel_faixa_atual = $niveis_faixa[mb_strtolower($status['faixa'])] ?? 1;

// 4. Cálculo Final da Porcentagem de Maestria
$progresso_maestria = (($tecnicas_concluidas + $nivel_faixa_atual) / $meta_maestria) * 100;
if ($progresso_maestria > 100) $progresso_maestria = 100;
// ------------------------------------------------------

function tratarCorFaixa($cor)
{
    $c = mb_strtolower(trim($cor));
    $mapa = [
        'branca'            => 'white',
        'azul'              => '#0000FF',
        'roxa'              => '#8A2BE2',
        'marrom'            => '#8B4513',
        'preta'             => 'black',
        'coral'             => 'linear-gradient(to right, #ff0000 0%, #ff0000 20%, #000 20%, #000 40%, #ff0000 40%, #ff0000 60%, #000 60%, #000 80%, #ff0000 80%, #ff0000 100%)',
        'vermelha e branca' => 'linear-gradient(to right, #ff0000 0%, #ff0000 20%, #fff 20%, #fff 40%, #ff0000 40%, #ff0000 60%, #fff 60%, #fff 80%, #ff0000 80%, #ff0000 100%)',
        'vermelha'          => 'red'
    ];
    return $mapa[$c] ?? '#ddd';
}

$cor_css = tratarCorFaixa($status['faixa']);
$porcentagem_progresso = ($status['meta'] > 0) ? ($status['aulas'] / $status['meta']) * 100 : 0;
if ($porcentagem_progresso > 100) $porcentagem_progresso = 100;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal do Atleta - Nova Arte BJJ</title>

    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>

    <style>
        .card-body strong {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-3px);
            transition: all 0.3s ease;
        }

        body {
            background-color: #f4f6f9;
        }

        .main-sidebar {
            background-color: #1a1a1a !important;
        }

        .navbar-search-mobile {
            flex: 1;
            display: flex;
            justify-content: center;
        }

        @media (max-width: 576px) {
            .navbar-search-mobile {
                margin: 0 10px;
            }
        }

        .bjj-belt {
            width: 100%;
            max-width: 400px;
            height: 54px;
            background: <?php echo $cor_css;
                        ?>;
            border: 2px solid #333;
            position: relative;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin: 15px auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .belt-bar {
            width: 110px;
            height: 100%;
            background-color: <?php echo (mb_strtolower($status['faixa']) == 'preta') ? '#d31b1b' : '#000';
                                ?>;
            position: absolute;
            right: 30px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 10px;
            border-left: 1px solid rgba(0, 0, 0, 0.3);
            border-right: 1px solid rgba(0, 0, 0, 0.3);
        }

        .belt-stripe {
            width: 7px;
            height: 40px;
            background-color: #fff;
            border-radius: 1px;
        }

        .pulse {
            animation: pulse-animation 2s infinite;
        }

        @keyframes pulse-animation {
            0% {
                box-shadow: 0 0 0 0px rgba(40, 167, 69, 0.7);
            }

            100% {
                box-shadow: 0 0 0 15px rgba(40, 167, 69, 0);
            }
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed text-sm">
    <div class="wrapper">

        <nav class="main-header navbar navbar-expand navbar-light bg-white border-bottom shadow-sm">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars text-dark"></i></a></li>
            </ul>
            <div class="navbar-search-mobile">
                <div class="input-group input-group-sm w-100" style="max-width: 350px;">
                    <input class="form-control border-secondary" type="text" id="buscar_aluno"
                        placeholder="Pesquisar colega...">
                    <div class="input-group-append">
                        <button class="btn btn-dark" type="button" onclick="pesquisarEvolucao()"><i
                                class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item"><a href="../logout.php" class="nav-link text-danger font-weight-bold"><i
                            class="fas fa-sign-out-alt"></i> <span class="d-none d-md-inline">SAIR</span></a></li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <div class="brand-link text-center border-secondary"><span class="brand-text font-weight-bold">NOVA ARTE
                    BJJ</span></div>
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                    <div class="image">
                        <img src="../images/perfil/<?php echo $foto_usuario; ?>" class="img-circle elevation-2"
                            style="width:35px; height:35px; object-fit:cover;">
                    </div>
                    <div class="info d-flex align-items-center justify-content-between w-100">
                        <a href="#" class="d-block text-white text-truncate" style="max-width: 140px;">
                            <?php echo $nome_usuario; ?>
                        </a>
                        <a href="#" data-toggle="modal" data-target="#modalPerfil" class="text-white-50 ml-2">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column">
                        <li class="nav-item"><a href="index.php" class="nav-link active"><i
                                    class="nav-icon fas fa-home"></i>
                                <p>Início</p>
                            </a></li>
                        <li class="nav-item"><a href="horarios.php" class="nav-link"><i
                                    class="nav-icon fas fa-calendar-alt"></i>
                                <p>Horários</p>
                            </a></li>
                        <li class="nav-item"><a href="particulares.php" class="nav-link"><i
                                    class="nav-icon fas fa-user-graduate"></i>
                                <p>Aulas Particulares</p>
                            </a></li>
                        <li class="nav-item"><a href="mensalidades.php" class="nav-link"><i
                                    class="nav-icon fas fa-receipt"></i>
                                <p>Financeiro</p>
                            </a></li>
                        <li class="nav-item">
                            <a href="evolucao.php" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Evolução Técnica</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <section class="content pt-3">
                <div class="container-fluid">
                    <div id="resultado_pesquisa" style="display:none;" class="mb-4"></div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-dark shadow-sm mb-3">
                                <div class="card-header bg-dark">
                                    <h3 class="card-title font-weight-bold text-white">Minha Jornada</h3>
                                </div>
                                <div class="card-body text-center">
                                    <h5 class="text-muted">Faixa: <b
                                            class="text-dark"><?php echo strtoupper($status['faixa']); ?></b></h5>

                                    <div class="bjj-belt">
                                        <div class="belt-bar">
                                            <?php for ($i = 0; $i < $status['grau']; $i++): ?>
                                                <div class="belt-stripe"></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="mt-5 text-left px-md-4">
                                        <div class="d-flex justify-content-between mb-1 font-weight-bold">
                                            <span><i class="fas fa-calendar-check mr-1 text-success"></i> Frequência
                                                (Aulas)</span>
                                            <span><?php echo $status['aulas']; ?> / <?php echo $status['meta']; ?>
                                                Aulas</span>
                                        </div>
                                        <div class="progress mb-4"
                                            style="height: 18px; border-radius: 10px; background-color: #e9ecef;">
                                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                style="width: <?php echo $porcentagem_progresso ?>%">
                                                <?php echo round($porcentagem_progresso); ?>%
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mb-1 font-weight-bold">
                                            <span><i class="fas fa-brain mr-1 text-primary"></i> Maestria Técnica (Selos
                                                + Faixa)</span>
                                            <span><?php echo $tecnicas_concluidas; ?> /
                                                <?php echo $total_geral_tecnicas; ?> Selos</span>
                                        </div>
                                        <div class="progress"
                                            style="height: 18px; border-radius: 10px; background-color: #e9ecef;">
                                            <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                                style="width: <?php echo $progresso_maestria; ?>%">
                                                <?php echo round($progresso_maestria); ?>%
                                            </div>
                                        </div>

                                        <small class="text-muted d-block mt-2 text-center">
                                            <i class="fas fa-info-circle"></i> Complete todas as técnicas e chegue à
                                            faixa vermelha para atingir 100%.
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="card shadow-sm border-0 p-3 mb-4">
                                <?php if ($status['pode']): ?>
                                    <button onclick="abrirLeitorQR('graduacao', 'PROMOÇÃO')"
                                        class="btn btn-success btn-lg btn-block pulse shadow"><i
                                            class="fas fa-qrcode mr-2"></i> VALIDAR MEU GRAU / FAIXA</button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-block disabled" disabled><i
                                            class="fas fa-lock mr-2"></i> FALTA
                                        <?php echo ($status['meta'] - $status['aulas']); ?> AULAS</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-dark">
                                    <h3 class="card-title font-weight-bold">
                                        <i class="fas fa-medal mr-2 text-warning"></i>Meus Selos de Técnica
                                    </h3>
                                </div>
                                <div class="card-body bg-light p-3">
                                    <?php
                                    // 1. Busca unificada mantendo a ordem de posição
                                    $query_hab = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC, tecnica ASC");
                                    $query_hab->bindValue(":id", $id_usuario);
                                    $query_hab->execute();
                                    $res_hab = $query_hab->fetchAll(PDO::FETCH_ASSOC);

                                    if (count($res_hab) > 0) {
                                        // 2. Agrupamento em array para separar os blocos visualmente
                                        $grupos = [];
                                        foreach ($res_hab as $hab) {
                                            $posicao_label = (!empty($hab['posicao'])) ? $hab['posicao'] : 'OUTRAS TÉCNICAS';
                                            $grupos[$posicao_label][] = $hab;
                                        }

                                        // 3. Loop por cada Categoria/Posição
                                        foreach ($grupos as $posicao_titulo => $tecnicas) {
                                    ?>
                                            <div class="row mt-3 mb-2">
                                                <div class="col-12">
                                                    <h6 class="text-uppercase font-weight-bold text-dark border-bottom pb-1"
                                                        style="letter-spacing: 1px; font-size: 0.85rem;">
                                                        <i class="fas fa-tags mr-2 text-primary shadow-sm"></i>
                                                        <?php echo $posicao_titulo; ?>
                                                        <span class="badge badge-pill badge-secondary ml-2"
                                                            style="font-size: 0.65rem;"><?php echo count($tecnicas); ?></span>
                                                    </h6>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <?php
                                                foreach ($tecnicas as $hab) {
                                                    // Mantida a lógica original de cor do selo
                                                    $status_nome = mb_strtolower($hab['status']);
                                                    if ($status_nome == 'dominado' || $status_nome == 'concluído') {
                                                        $cor_selo = 'badge-success';
                                                        $borda_card = 'border-success';
                                                    } elseif ($status_nome == 'revisão' || $status_nome == 'aprendido') {
                                                        $cor_selo = 'badge-info';
                                                        $borda_card = 'border-info';
                                                    } else {
                                                        $cor_selo = 'badge-warning';
                                                        $borda_card = 'border-warning';
                                                    }
                                                ?>
                                                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                                                        <div class="card h-100 shadow-sm <?php echo $borda_card; ?>"
                                                            style="border-width: 2px; border-radius: 10px;">
                                                            <div
                                                                class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                <div>
                                                                    <strong class="d-block text-uppercase mb-2"
                                                                        style="font-size: 0.75rem; line-height: 1.1; min-height: 2.2em;">
                                                                        <?php echo $hab['tecnica']; ?>
                                                                    </strong>

                                                                    <div class="progress progress-xxs mb-2"
                                                                        style="background-color: #eee;">
                                                                        <div class="progress-bar bg-dark"
                                                                            style="width: <?php echo $hab['nivel']; ?>%"></div>
                                                                    </div>
                                                                </div>

                                                                <div>
                                                                    <span
                                                                        class="badge <?php echo $cor_selo; ?> shadow-sm w-100 py-1 mb-1"
                                                                        style="font-size: 0.65rem;">
                                                                        <?php echo strtoupper($hab['status']); ?>
                                                                    </span>

                                                                    <div class="mt-1">
                                                                        <?php if ($status_nome != 'dominado' && $status_nome != 'concluído'): ?>
                                                                            <button class="btn btn-xs btn-primary btn-block shadow-sm"
                                                                                style="font-size: 0.6rem;"
                                                                                onclick="abrirLeitorQR('tecnica', '<?php echo $hab['tecnica']; ?>', '<?php echo $hab['id']; ?>')">
                                                                                <i class="fas fa-qrcode mr-1"></i> VALIDAR
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <small class="text-success font-weight-bold"
                                                                                style="font-size: 0.65rem;">
                                                                                <i class="fas fa-check-double"></i> VALIDADO
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } // Fim do foreach técnicas 
                                                ?>
                                            </div>
                                    <?php
                                        } // Fim do foreach grupos
                                    } else {
                                        echo '<div class="col-12 text-center text-muted p-4"><p>Nenhum selo de técnica lançado até o momento.</p></div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-dark">
                                    <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2"></i>Aulas
                                        Particulares (Aprovadas)</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php
                                        // ALTERAÇÃO: Busca unindo a tabela pedidos_aulas com usuarios para pegar o nome do professor
                                        $query_ap = $pdo->prepare("SELECT p.*, u.nome as nome_professor FROM pedidos_aulas p INNER JOIN usuarios u ON p.id_professor = u.id WHERE p.id_aluno = :id AND p.status = 'Aprovado' ORDER BY p.data_aula DESC");
                                        $query_ap->bindValue(":id", $id_usuario);
                                        $query_ap->execute();
                                        $res_ap = $query_ap->fetchAll(PDO::FETCH_ASSOC);

                                        if (count($res_ap) > 0) {
                                            foreach ($res_ap as $aula) {
                                                $data_f = date('d/m/Y', strtotime($aula['data_aula']));
                                                $tema_exibir = (!empty($aula['tema'])) ? $aula['tema'] : 'Aula Particular';
                                        ?>
                                                <div class="col-6 col-md-3">
                                                    <div class="info-box shadow-none border">
                                                        <span class="info-box-icon bg-success elevation-1"><i
                                                                class="fas fa-user-check"></i></span>
                                                        <div class="info-box-content">
                                                            <span class="info-box-text text-uppercase font-weight-bold"
                                                                style="font-size:10px;"><?php echo $tema_exibir; ?></span>
                                                            <span
                                                                class="info-box-number small text-muted"><?php echo $data_f; ?></span>
                                                            <span class="small text-primary">Prof.
                                                                <?php echo explode(' ', $aula['nome_professor'])[0]; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                        } else {
                                            echo '<div class="col-12 text-center text-muted p-4"><p>Nenhuma aula particular aprovada até o momento.</p></div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="modalPerfil" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Editar Meus Dados</h5><button type="button" class="close text-white"
                        data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="form-perfil-completo" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-8 col-md-8">
                                <div class="form-group"><label>Nome Completo</label><input type="text"
                                        class="form-control" name="nome" value="<?php echo $nome_usuario ?>" required>
                                </div>
                                <div class="form-group"><label>Telefone / WhatsApp</label><input type="text"
                                        class="form-control" name="telefone" id="telefone"
                                        value="<?php echo $telefone_usuario ?>"></div>
                            </div>
                            <div class="col-4 col-md-4 text-center">
                                <label>Foto</label>
                                <img src="../images/perfil/<?php echo $foto_usuario; ?>" id="target-perfil"
                                    class="img-fluid rounded-circle border shadow-sm mb-2"
                                    style="width:100px; height:100px; object-fit:cover;">
                                <input type="file" name="foto" id="foto-perfil" onChange="carregarImgPerfil();"
                                    style="display:none;">
                                <button type="button" class="btn btn-xs btn-outline-dark"
                                    onclick="$('#foto-perfil').click();">Alterar</button>
                            </div>
                        </div>
                        <div class="form-group"><label>E-mail (Login)</label><input type="email" class="form-control"
                                name="email" value="<?php echo $email_usuario ?>" required></div>
                        <div id="mensagem-perfil"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-dark w-100 shadow">SALVAR
                            ALTERAÇÕES</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalQR" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Validar com o Professor</h5><button type="button" class="close text-white"
                        data-dismiss="modal" onclick="pararCamera()"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <div id="reader" style="width:100%" class="border"></div>
                    <input type="hidden" id="tipo_v"><input type="hidden" id="id_v">
                    <p class="mt-2 text-muted font-weight-bold text-uppercase" id="nome_v"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.js"></script>

    <script>
        let html5QrCode;

        function carregarImgPerfil() {
            var target = document.getElementById('target-perfil');
            var file = document.querySelector('#foto-perfil').files[0];
            var reader = new FileReader();
            reader.onloadend = function() {
                target.src = reader.result;
            };
            if (file) {
                reader.readAsDataURL(file);
            }
        }
        $("#form-perfil-completo").submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            $.ajax({
                url: "editar-perfil.php",
                type: 'POST',
                data: formData,
                success: function(mensagem) {
                    if (mensagem.trim() == "Salvo com Sucesso") {
                        location.reload();
                    } else {
                        $('#mensagem-perfil').removeClass().addClass(
                            'text-danger mt-2 font-weight-bold').text(mensagem);
                    }
                },
                cache: false,
                contentType: false,
                processData: false
            });
        });

        function pesquisarEvolucao() {
            var nome = $('#buscar_aluno').val();
            if (nome.length < 2) return;
            $.post("buscar_evolucao.php", {
                nome: nome
            }, function(data) {
                $('#resultado_pesquisa').fadeIn().html(data);
            });
        }

        function abrirLeitorQR(tipo, nome, id = 0) {
            $('#tipo_v').val(tipo);
            $('#id_v').val(id);
            $('#nome_v').text(nome);
            $('#modalQR').modal('show');
            html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start({
                facingMode: "environment"
            }, {
                fps: 10,
                qrbox: 250
            }, onScanSuccess);
        }

        function onScanSuccess(decodedText) {
            pararCamera();
            let tipo = $('#tipo_v').val();
            let id = $('#id_v').val();
            let url = (tipo == 'graduacao') ? "validar-promocao.php" : "validar-tecnica.php";
            $.post(url, {
                token_professor: decodedText,
                id: id
            }, function(res) {
                if (res.trim() == "Sucesso") {
                    confetti();
                    setTimeout(() => {
                        location.reload();
                    }, 1200);
                } else {
                    alert(res);
                    $('#modalQR').modal('hide');
                }
            });
        }

        function pararCamera() {
            if (html5QrCode && html5QrCode.isScanning) html5QrCode.stop();
        }
        $("#buscar_aluno").keypress(function(e) {
            if (e.which == 13) pesquisarEvolucao();
        });
    </script>
</body>

</html>