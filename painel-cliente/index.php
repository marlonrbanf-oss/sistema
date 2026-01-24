<?php
@session_start();
require_once("../conexao.php");
require_once("funcoes-graduacao.php");

// --- REPARADOR DE EMERGÊNCIA ---
$tabela_estudo = 'tecnicas_estudo';
$colunas_necessarias = [
    'data_inicio' => "DATE NULL",
    'repeticoes' => "INT DEFAULT 0",
    'status' => "VARCHAR(50) DEFAULT 'Em Estudo'"
];

foreach ($colunas_necessarias as $coluna => $definicao) {
    try {
        $pdo->query("SELECT $coluna FROM $tabela_estudo LIMIT 1");
    } catch (Exception $e) {
        $pdo->query("ALTER TABLE $tabela_estudo ADD COLUMN $coluna $definicao");
    }
}

// --- VERIFICAÇÃO DE AUTENTICAÇÃO ---
$nivel_logado = $_SESSION['nivel'] ?? ($_SESSION['nivel_usuario'] ?? '');
if ($nivel_logado != 'Cliente' && $nivel_logado != 'Atleta') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Busca status de graduação
$status = verificarRequisitos($pdo, $id_usuario);

// Busca dados do usuário
$query_u = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$query_u->bindValue(":id", $id_usuario);
$query_u->execute();
$dados_u = $query_u->fetch(PDO::FETCH_ASSOC);

$nome_usuario = $dados_u['nome'] ?? 'Usuário';
$foto_usuario = (!empty($dados_u['foto'])) ? $dados_u['foto'] : 'usuario-icone-claro.png';
$email_usuario = $dados_u['email'] ?? ($dados_u['usuario'] ?? '');

// Lógica de nova técnica
if (isset($_POST['nova_tecnica_estudo'])) {
    $tec_nome = trim($_POST['nova_tecnica_estudo']);
    if (!empty($tec_nome)) {
        $res_estudo = $pdo->prepare("INSERT INTO $tabela_estudo (usuario_id, nome_tecnica, status, data_inicio) VALUES (:id, :nome, 'Em Estudo', CURDATE())");
        $res_estudo->bindValue(":id", $id_usuario);
        $res_estudo->bindValue(":nome", $tec_nome);
        $res_estudo->execute();
        echo "<script>window.location='index.php'</script>";
        exit();
    }
}

// --- CÁLCULO DE MÉTRICAS ---
$query_total_hab = $pdo->prepare("SELECT COUNT(*) as total FROM habilidades WHERE usuario_id = :id");
$query_total_hab->bindValue(":id", $id_usuario);
$query_total_hab->execute();
$total_geral_tecnicas = $query_total_hab->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$query_maestria = $pdo->prepare("SELECT COUNT(*) as total FROM habilidades WHERE usuario_id = :id AND (status = 'Dominado' OR status = 'Concluído')");
$query_maestria->bindValue(":id", $id_usuario);
$query_maestria->execute();
$tecnicas_concluidas = $query_maestria->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$total_calculo = ($total_geral_tecnicas > 0) ? $total_geral_tecnicas : 1;
$progresso_maestria = ($tecnicas_concluidas / $total_calculo) * 100;

function tratarCorFaixa($cor)
{
    $c = mb_strtolower(trim($cor ?? 'branca'));
    $mapa = [
        'branca' => 'white',
        'azul' => '#0000FF',
        'roxa' => '#8A2BE2',
        'marrom' => '#8B4513',
        'preta' => 'black',
        'coral' => 'linear-gradient(to right, #ff0000 0%, #ff0000 20%, #000 20%, #000 40%, #ff0000 40%, #ff0000 60%, #000 60%, #000 80%, #ff0000 80%, #ff0000 100%)',
        'vermelha' => 'red'
    ];
    return $mapa[$c] ?? '#ddd';
}

$cor_css = tratarCorFaixa($status['faixa'] ?? 'branca');
$meta_aulas = (int)($status['meta'] ?? 0);
$aulas_atuais = (int)($status['aulas'] ?? 0);
$porcentagem_progresso = ($meta_aulas > 0) ? ($aulas_atuais / $meta_aulas) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Portal do Atleta - Nova Arte BJJ</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <style>
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
            background-color: <?php echo (mb_strtolower($status['faixa'] ?? '') == 'preta') ? '#d31b1b' : '#000';
                                ?>;
            position: absolute;
            right: 30px;
            display: flex;
            flex-direction: row-reverse;
            /* INVERTE A ORDEM: Direita para Esquerda */
            justify-content: flex-start;
            align-items: center;
            padding: 0 5px;
        }

        .belt-stripe {
            width: 8px;
            height: 42px;
            background-color: #fff;
            margin: 0 3px;
            border-radius: 1px;
            box-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        #reader {
            width: 100% !important;
            border: none !important;
            background-color: #000;
            border-radius: 12px;
            overflow: hidden;
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
                <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <div class="navbar-search-mobile mx-auto">
                <div class="input-group input-group-sm" style="max-width: 250px;">
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

                    <?php if ($status['pode'] ?? false): ?>
                        <div class="alert alert-success shadow border-0 mt-3 pulse">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-medal fa-2x mr-3 text-success"></i>
                                <div>
                                    <h6 class="mb-0"><b>PRONTO PARA A GRADUAÇÃO!</b></h6>
                                    <small><?php echo htmlspecialchars($status['mensagem'] ?? ''); ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="resultado_pesquisa" style="display:none;" class="mb-4"></div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-dark shadow-sm">
                                <div class="card-header bg-dark">
                                    <h3 class="card-title font-weight-bold">Minha Jornada</h3>
                                </div>
                                <div class="card-body text-center">
                                    <h5 class="text-muted text-uppercase">Faixa:
                                        <b><?php echo ($status['faixa'] ?? 'BRANCA'); ?></b>
                                    </h5>

                                    <div class="bjj-belt">
                                        <div class="belt-bar">
                                            <?php for ($i = 0; $i < ($status['grau'] ?? 0); $i++): ?>
                                                <div class="belt-stripe"></div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="mt-4 text-left">
                                        <div class="d-flex justify-content-between mb-1 font-weight-bold">
                                            <span>Frequência</span>
                                            <span><?php echo $aulas_atuais; ?> / <?php echo $meta_aulas; ?> Aulas</span>
                                        </div>
                                        <div class="progress mb-3" style="height: 15px; border-radius: 10px;">
                                            <div class="progress-bar bg-success progress-bar-animated"
                                                style="width: <?php echo $porcentagem_progresso ?>%">
                                                <?php echo round($porcentagem_progresso); ?>%</div>
                                        </div>

                                        <div class="d-flex justify-content-between mb-1 font-weight-bold">
                                            <span>Maestria Técnica</span>
                                            <span><?php echo $tecnicas_concluidas; ?> /
                                                <?php echo $total_geral_tecnicas; ?></span>
                                        </div>
                                        <div class="progress" style="height: 15px; border-radius: 10px;">
                                            <div class="progress-bar bg-primary progress-bar-animated"
                                                style="width: <?php echo $progresso_maestria; ?>%">
                                                <?php echo round($progresso_maestria); ?>%</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <?php if ($status['pode'] ?? false): ?>
                                        <button onclick="abrirLeitorQR('graduacao', 'PROMOÇÃO')"
                                            class="btn btn-success btn-block font-weight-bold"><i
                                                class="fas fa-qrcode mr-2"></i> VALIDAR GRADUAÇÃO</button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-block disabled" disabled><i
                                                class="fas fa-lock mr-2"></i> FALTAM
                                            <?php echo ($meta_aulas - $aulas_atuais); ?> AULAS</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-dark">
                                    <h3 class="card-title font-weight-bold"><i
                                            class="fas fa-medal mr-2 text-warning"></i>Meus Selos</h3>
                                </div>
                                <div class="card-body bg-light p-3">
                                    <?php
                                    $query_hab = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC, tecnica ASC");
                                    $query_hab->bindValue(":id", $id_usuario);
                                    $query_hab->execute();
                                    $res_hab = $query_hab->fetchAll(PDO::FETCH_ASSOC);

                                    $grupos = [];
                                    foreach ($res_hab as $hab) {
                                        $posicao = $hab['posicao'] ?: 'OUTRAS';
                                        $grupos[$posicao][] = $hab;
                                    }

                                    foreach ($grupos as $titulo => $tecs): ?>
                                        <h6 class="text-uppercase font-weight-bold border-bottom pb-1 mt-3">
                                            <?php echo $titulo; ?></h6>
                                        <div class="row">
                                            <?php foreach ($tecs as $h):
                                                $check = (mb_strtolower($h['status']) == 'dominado' || mb_strtolower($h['status']) == 'concluído');
                                            ?>
                                                <div class="col-6 col-md-3 mb-2">
                                                    <div class="card h-100 p-2 text-center border shadow-none">
                                                        <small
                                                            class="font-weight-bold text-truncate d-block"><?php echo $h['tecnica']; ?></small>
                                                        <span
                                                            class="badge <?php echo $check ? 'badge-success' : 'badge-warning'; ?> my-1"><?php echo strtoupper($h['status']); ?></span>
                                                        <?php if (!$check): ?>
                                                            <button class="btn btn-xs btn-primary"
                                                                onclick="abrirLeitorQR('tecnica', '<?php echo $h['tecnica']; ?>', '<?php echo $h['id']; ?>')">VALIDAR</button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="modalQR" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i>Validar <span id="nome_v"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal"
                        onclick="pararCamera()">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="reader"></div>
                    <input type="hidden" id="tipo_v"><input type="hidden" id="id_v">
                </div>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.js"></script>
    <script>
        let html5QrCode;

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
            }, (txt) => {
                pararCamera();
                let url = (tipo == 'graduacao') ? "validar-promocao.php" : "validar-tecnica.php";
                $.post(url, {
                    token_professor: txt,
                    id: id
                }, function(res) {
                    if (res.trim() == "Sucesso") {
                        confetti();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        alert(res);
                        $('#modalQR').modal('hide');
                    }
                });
            }).catch(err => console.error(err));
        }

        function pararCamera() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => html5QrCode.clear());
            }
        }

        function pesquisarEvolucao() {
            var nome = $('#buscar_aluno').val();
            if (nome.length < 2) return;
            $.post("buscar_evolucao.php", {
                nome: nome
            }, function(data) {
                $('#resultado_pesquisa').fadeIn().html(data);
            });
        }
    </script>
</body>

</html>