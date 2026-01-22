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

// 2. Busca de Dados Pessoais (CORRIGIDO: Definindo variáveis para o modal)
$query_u = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$query_u->bindValue(":id", $id_usuario);
$query_u->execute();
$dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

$nome_usuario = $dados_u['nome'] ?? 'Usuário';
$foto_usuario = (!empty($dados_u['foto'])) ? $dados_u['foto'] : 'usuario-icone-claro.png';
$email_usuario = $dados_u['usuario'] ?? '';
$telefone_usuario = $dados_u['telefone'] ?? '';

// 3. Busca UNIFICADA de Graduação (Regras de aulas, faixa e grau)
$status = verificarRequisitos($pdo, $id_usuario);

// --- LÓGICA: ADICIONAR TÉCNICA DE ESTUDO (ALUNO) ---
if (isset($_POST['nova_tecnica_estudo'])) {
    $tec_nome = $_POST['nova_tecnica_estudo'];
    $res_estudo = $pdo->prepare("INSERT INTO tecnicas_estudo SET usuario_id = :id, nome_tecnica = :nome, status = 'Em Estudo'");
    $res_estudo->bindValue(":id", $id_usuario);
    $res_estudo->bindValue(":nome", $tec_nome);
    $res_estudo->execute();
    echo "<script>alert('Técnica enviada para sua lista de estudos!'); window.location='index.php'</script>";
}

// --- CÁLCULO DINÂMICO DA JORNADA DE MAESTRIA ---
$query_total_hab = $pdo->prepare("SELECT COUNT(*) as total FROM habilidades WHERE usuario_id = :id");
$query_total_hab->bindValue(":id", $id_usuario);
$query_total_hab->execute();
$total_geral_tecnicas = $query_total_hab->fetch(PDO::FETCH_ASSOC)['total'] ?? 1;

$total_faixas_jornada = 7;
$meta_maestria = $total_geral_tecnicas + $total_faixas_jornada;

$query_maestria = $pdo->prepare("SELECT COUNT(*) as total FROM habilidades WHERE usuario_id = :id AND (status = 'Dominado' OR status = 'Concluído')");
$query_maestria->bindValue(":id", $id_usuario);
$query_maestria->execute();
$tecnicas_concluidas = $query_maestria->fetch(PDO::FETCH_ASSOC)['total'];

$niveis_faixa = ['branca' => 1, 'azul' => 2, 'roxa' => 3, 'marrom' => 4, 'preta' => 5, 'coral' => 6, 'vermelha' => 7];
$nivel_faixa_atual = $niveis_faixa[mb_strtolower($status['faixa'])] ?? 1;

$progresso_maestria = (($tecnicas_concluidas + $nivel_faixa_atual) / $meta_maestria) * 100;
if ($progresso_maestria > 100) $progresso_maestria = 100;

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
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i
                            class="fas fa-bars text-dark"></i></a></li>
            </ul>
            <div class="navbar-search-mobile flex-grow-1 px-2">
                <div class="input-group input-group-sm mx-auto" style="max-width: 350px;">
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
                            class="fas fa-sign-out-alt"></i></a></li>
            </ul>
        </nav>

        <?php include_once("sidebar.php"); ?>

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
                                            <span><i class="fas fa-calendar-check mr-1 text-success"></i>
                                                Frequência</span>
                                            <span><?php echo $status['aulas']; ?> / <?php echo $status['meta']; ?>
                                                Aulas</span>
                                        </div>
                                        <div class="progress mb-4" style="height: 18px; border-radius: 10px;">
                                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                                style="width: <?php echo $porcentagem_progresso ?>%">
                                                <?php echo round($porcentagem_progresso); ?>%
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mb-1 font-weight-bold">
                                            <span><i class="fas fa-brain mr-1 text-primary"></i> Maestria Técnica</span>
                                            <span><?php echo $tecnicas_concluidas; ?> Selos</span>
                                        </div>
                                        <div class="progress" style="height: 18px; border-radius: 10px;">
                                            <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                                                style="width: <?php echo $progresso_maestria; ?>%">
                                                <?php echo round($progresso_maestria); ?>%
                                            </div>
                                        </div>
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
                                <div class="card-header bg-primary">
                                    <h3 class="card-title font-weight-bold text-white"><i
                                            class="fas fa-book-reader mr-2"></i>Minhas Técnicas de Estudo</h3>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="mb-4">
                                        <div class="input-group">
                                            <input type="text" name="nova_tecnica_estudo" class="form-control"
                                                placeholder="O que você deseja aprender hoje?" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit"><i
                                                        class="fas fa-plus mr-1"></i> Adicionar</button>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="row">
                                        <?php
                                        $query_estudo = $pdo->prepare("SELECT * FROM tecnicas_estudo WHERE usuario_id = :id ORDER BY status ASC, id DESC");
                                        $query_estudo->bindValue(":id", $id_usuario);
                                        $query_estudo->execute();
                                        $res_estudo = $query_estudo->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($res_estudo as $item_e) {
                                            $cor_bg = ($item_e['status'] == 'Dominado') ? 'bg-success' : 'bg-warning';
                                        ?>
                                            <div class="col-6 col-md-3">
                                                <div class="info-box shadow-none border">
                                                    <span class="info-box-icon <?php echo $cor_bg; ?> elevation-1"><i
                                                            class="fas fa-graduation-cap"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text text-uppercase font-weight-bold"
                                                            style="font-size:10px;"><?php echo $item_e['nome_tecnica']; ?></span>
                                                        <span
                                                            class="badge <?php echo ($item_e['status'] == 'Dominado') ? 'badge-success' : 'badge-warning'; ?>"><?php echo strtoupper($item_e['status']); ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-dark">
                                    <h3 class="card-title font-weight-bold"><i
                                            class="fas fa-medal mr-2 text-warning"></i>Meus Selos de Técnica</h3>
                                </div>
                                <div class="card-body bg-light p-3">
                                    <?php
                                    $query_hab = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC, tecnica ASC");
                                    $query_hab->bindValue(":id", $id_usuario);
                                    $query_hab->execute();
                                    $res_hab = $query_hab->fetchAll(PDO::FETCH_ASSOC);

                                    if (count($res_hab) > 0) {
                                        $grupos = [];
                                        foreach ($res_hab as $hab) {
                                            $posicao_label = (!empty($hab['posicao'])) ? $hab['posicao'] : 'OUTRAS TÉCNICAS';
                                            $grupos[$posicao_label][] = $hab;
                                        }

                                        foreach ($grupos as $posicao_titulo => $tecnicas) {
                                    ?>
                                            <div class="row mt-3 mb-2">
                                                <div class="col-12">
                                                    <h6 class="text-uppercase font-weight-bold text-dark border-bottom pb-1">
                                                        <?php echo $posicao_titulo; ?></h6>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <?php foreach ($tecnicas as $hab) {
                                                    $status_nome = mb_strtolower($hab['status']);
                                                    $cor_selo = ($status_nome == 'dominado' || $status_nome == 'concluído') ? 'badge-success' : (($status_nome == 'revisão') ? 'badge-info' : 'badge-warning');
                                                ?>
                                                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                                                        <div class="card h-100 shadow-sm border-light" style="border-radius: 10px;">
                                                            <div
                                                                class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                <strong class="d-block text-uppercase mb-2"
                                                                    style="font-size: 0.75rem;"><?php echo $hab['tecnica']; ?></strong>
                                                                <div>
                                                                    <span
                                                                        class="badge <?php echo $cor_selo; ?> w-100 mb-1"><?php echo strtoupper($hab['status']); ?></span>
                                                                    <?php if ($status_nome != 'dominado' && $status_nome != 'concluído'): ?>
                                                                        <button class="btn btn-xs btn-primary btn-block"
                                                                            onclick="abrirLeitorQR('tecnica', '<?php echo $hab['tecnica']; ?>', '<?php echo $hab['id']; ?>')">VALIDAR</button>
                                                                    <?php else: ?>
                                                                        <small class="text-success font-weight-bold"><i
                                                                                class="fas fa-check-double"></i> VALIDADO</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                    <?php }
                                    } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="modal fade" id="modalPerfil" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title">Editar Meus Dados</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form id="form-perfil-completo" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-8">
                                    <div class="form-group"><label>Nome Completo</label>
                                        <input type="text" class="form-control" name="nome"
                                            value="<?php echo $nome_usuario ?>" required>
                                    </div>
                                    <div class="form-group"><label>Telefone</label>
                                        <input type="text" class="form-control" name="telefone" id="telefone"
                                            value="<?php echo $telefone_usuario ?>">
                                    </div>
                                </div>
                                <div class="col-4 text-center">
                                    <img src="../images/perfil/<?php echo $foto_usuario; ?>" id="target-perfil"
                                        class="img-fluid rounded-circle border shadow-sm"
                                        style="width:100px; height:100px; object-fit:cover;">
                                    <input type="file" name="foto" id="foto-perfil" onChange="carregarImgPerfil();"
                                        style="display:none;">
                                    <button type="button" class="btn btn-xs btn-outline-dark mt-2"
                                        onclick="$('#foto-perfil').click();">Alterar</button>
                                </div>
                            </div>
                            <div class="form-group"><label>E-mail (Login)</label>
                                <input type="email" class="form-control" name="email"
                                    value="<?php echo $email_usuario ?>" required>
                            </div>
                            <div id="mensagem-perfil"></div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-dark w-100">SALVAR
                                ALTERAÇÕES</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalQR" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Validar com o Professor</h5>
                        <button type="button" class="close text-white" data-dismiss="modal"
                            onclick="pararCamera()"><span>&times;</span></button>
                    </div>
                    <div class="modal-body text-center">
                        <div id="reader" style="width:100%" class="border"></div>
                        <input type="hidden" id="tipo_v"><input type="hidden" id="id_v">
                        <p class="mt-2 text-muted font-weight-bold" id="nome_v"></p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.js"></script>

    <script>
        let html5QrCode;

        // SCRIPT PERFIL
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
                        $('#mensagem-perfil').addClass('text-danger').text(mensagem);
                    }
                },
                cache: false,
                contentType: false,
                processData: false
            });
        });

        // SCRIPT QR CODE
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

        // SCRIPT BUSCA
        function pesquisarEvolucao() {
            var nome = $('#buscar_aluno').val();
            if (nome.length < 2) return;
            $.post("buscar_evolucao.php", {
                nome: nome
            }, function(data) {
                $('#resultado_pesquisa').fadeIn().html(data);
            });
        }
        $("#buscar_aluno").keypress(function(e) {
            if (e.which == 13) pesquisarEvolucao();
        });
    </script>
</body>

</html>