<?php
@session_start();
require_once("../conexao.php");

// --- REPARADOR DE EMERGÊNCIA (DIÁRIO DE GOLPES) ---
$tabela = 'tecnicas_estudo';
$colunas_necessarias = [
    'usuario_id' => "INT NOT NULL",
    'nome_tecnica' => "VARCHAR(255) NOT NULL",
    'status' => "VARCHAR(50) DEFAULT 'Em Estudo'",
    'repeticoes' => "INT DEFAULT 0",
    'ultimo_parceiro' => "VARCHAR(255) NULL",
    'ultimo_parceiro_id' => "INT NULL",
    'parceiro_confirmado' => "ENUM('Sim', 'Não') DEFAULT 'Não'",
    'data_inicio' => "DATE NULL",
    'data_ultima_pratica' => "DATE NULL",
    'tipo_registro' => "ENUM('Treino', 'Combate') DEFAULT 'Treino'",
    'acao_combate' => "VARCHAR(50) NULL"
];

foreach ($colunas_necessarias as $coluna => $definicao) {
    try {
        $pdo->query("SELECT $coluna FROM $tabela LIMIT 1");
    } catch (Exception $e) {
        $pdo->query("ALTER TABLE $tabela ADD COLUMN $coluna $definicao");
    }
}

if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = $_SESSION['nome_usuario'] ?? 'Eu';

// --- LOGICA DE EXCLUSÃO ---
if (isset($_GET['delete'])) {
    $res = $pdo->prepare("DELETE FROM $tabela WHERE id = :id AND usuario_id = :u_id");
    $res->execute([':id' => $_GET['delete'], ':u_id' => $id_usuario]);
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- LOGICA DE APROVAÇÃO (3 TAPINHAS) ---
if (isset($_GET['acao']) && $_GET['acao'] == 'aprovar' && isset($_GET['id_log'])) {
    $res = $pdo->prepare("UPDATE $tabela SET parceiro_confirmado = 'Sim' WHERE id = :id AND ultimo_parceiro_id = :meu_id");
    $res->execute([':id' => $_GET['id_log'], ':meu_id' => $id_usuario]);
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- LOGICA DE INSERÇÃO/REGISTRO ---
if (isset($_POST['nome_tecnica']) && !isset($_POST['id_log'])) {
    $nome = trim($_POST['nome_tecnica']);
    if (!empty($nome)) {
        $res = $pdo->prepare("INSERT INTO $tabela (usuario_id, nome_tecnica, status, data_inicio) VALUES (:id, :nome, 'Em Estudo', CURDATE())");
        $res->execute([':id' => $id_usuario, ':nome' => $nome]);
        echo "<script>window.location='minhas-tecnicas.php'</script>";
        exit();
    }
}
if (isset($_POST['id_log'])) {
    $tipo = $_POST['tipo_registro'];
    $reps = ($tipo == 'Treino') ? (int)$_POST['quantidade_reps'] : 1;
    $acao = ($tipo == 'Combate') ? $_POST['acao_combate'] : null;
    $res = $pdo->prepare("UPDATE $tabela SET repeticoes = repeticoes + :reps, ultimo_parceiro = :p, ultimo_parceiro_id = :pid, data_ultima_pratica = CURDATE(), parceiro_confirmado = 'Não', tipo_registro = :tipo, acao_combate = :acao WHERE id = :id AND usuario_id = :uid");
    $res->execute([':reps' => $reps, ':p' => $_POST['parceiro_nome_texto'], ':pid' => $_POST['parceiro_id'], ':tipo' => $tipo, ':acao' => $acao, ':id' => $_POST['id_log'], ':uid' => $id_usuario]);
    echo "<script>window.location='minhas-tecnicas.php'</script>";
    exit();
}

// --- DADOS PARA O RELATÓRIO DE EVOLUÇÃO ---
$posicoes_oficiais = ['Fundamentos', 'Defesa Pessoal', 'Quedas', 'passagem de guarda', 'guarda fechada por baixo', 'guarda fechada por cima', 'guarda aberta', 'montada ataque (por cima)', 'montada defesa (por baixo)', '100 kilos ataque (por cima)', '100 kilos defesa (por baixo)', 'meia guarda por cima', 'costas ataque (por cima)', 'costas defesa (por baixo)', '4 apoios ataque (por cima)', '4 apoios por defesa (por baixo)', 'defesa de finalizações'];

$query_hab = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY status DESC, tecnica ASC");
$query_hab->bindValue(":id", $id_usuario);
$query_hab->execute();
$habilidades = $query_hab->fetchAll(PDO::FETCH_ASSOC);

$dados_evolucao = [];
foreach ($posicoes_oficiais as $pos) {
    $dados_evolucao[$pos] = ['total' => 0, 'dominados' => 0, 'tecnicas' => []];
}
$dados_evolucao['Outras Posições'] = ['total' => 0, 'dominados' => 0, 'tecnicas' => []];

foreach ($habilidades as $h) {
    $chave = in_array($h['posicao'], $posicoes_oficiais) ? $h['posicao'] : 'Outras Posições';
    $dados_evolucao[$chave]['total']++;
    if (mb_strtolower($h['status']) == 'dominado' || mb_strtolower($h['status']) == 'concluído') {
        $dados_evolucao[$chave]['dominados']++;
    }
    $dados_evolucao[$chave]['tecnicas'][] = $h;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evolução e Diário - Arena BJJ</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .card-evolucao {
            cursor: pointer;
            border-left: 5px solid #17a2b8;
            border-radius: 10px;
        }

        .card-tecnica {
            border-left: 4px solid #007bff;
            position: relative;
        }

        .btn-delete-x {
            position: absolute;
            top: 8px;
            right: 8px;
            color: #dc3545;
            opacity: 0.6;
            z-index: 10;
        }

        .btn-delete-x:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .badge-reps {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
        }

        .vs-badge {
            width: 40px;
            height: 40px;
            background: #000;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto;
            border: 2px solid #fff;
        }

        .lista-busca {
            position: absolute;
            z-index: 1050;
            width: 100%;
            background: white;
            border: 1px solid #ddd;
            max-height: 150px;
            overflow-y: auto;
        }

        .item-aluno {
            padding: 8px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            display: block;
            color: #333;
        }

        .secao-combate {
            display: none;
        }

        .chart-box {
            width: 60px;
            height: 60px;
            position: relative;
        }

        .btn-lote {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            border-radius: 50px;
            padding: 15px 25px;
            font-weight: bold;
            display: none;
        }

        #reader {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container-fluid py-3">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="font-weight-bold m-0">Arena BJJ</h2>
            <a href="index.php" class="btn btn-dark btn-sm">PAINEL</a>
        </div>

        <div class="card shadow-sm mb-4 border-0 card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">PLACAR DE CONFRONTO</h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center text-center">
                    <div class="col-5">
                        <small class="text-muted d-block">VOCÊ</small>
                        <span class="font-weight-bold text-truncate d-block"><?= $nome_usuario ?></span>
                    </div>
                    <div class="col-2">
                        <div class="vs-badge">VS</div>
                    </div>
                    <div class="col-5">
                        <div class="position-relative">
                            <input type="text" id="input-comparativo"
                                class="form-control form-control-sm input-busca-aluno text-center"
                                placeholder="Oponente..." autocomplete="off">
                            <div class="lista-busca d-none text-left"></div>
                        </div>
                    </div>
                </div>
                <div id="resultado-disputa" class="mt-3 d-none">
                    <div class="row text-center bg-white p-2 rounded border">
                        <div class="col-5">
                            <div class="text-success small">FIN: <b id="meu-fin">0</b></div>
                            <div class="text-primary small">RAS: <b id="meu-ras">0</b></div>
                            <div class="text-info small">PAS: <b id="meu-pas">0</b></div>
                        </div>
                        <div class="col-2"><i class="fas fa-exchange-alt text-muted"></i></div>
                        <div class="col-5">
                            <div class="text-success small">FIN: <b id="dele-fin">0</b></div>
                            <div class="text-primary small">RAS: <b id="dele-ras">0</b></div>
                            <div class="text-info small">PAS: <b id="dele-pas">0</b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $query_solic = $pdo->prepare("SELECT t.*, u.nome FROM $tabela t INNER JOIN usuarios u ON t.usuario_id = u.id WHERE t.ultimo_parceiro_id = :id AND t.parceiro_confirmado = 'Não'");
        $query_solic->execute([':id' => $id_usuario]);
        while ($s = $query_solic->fetch(PDO::FETCH_ASSOC)) {
            $texto_n = ($s['tipo_registro'] == 'Combate') ? "fez uma <b>" . $s['acao_combate'] . "</b> de <b>" . $s['nome_tecnica'] . "</b> em você." : "treinou <b>" . $s['nome_tecnica'] . "</b> com você.";
        ?>
            <div class="alert alert-warning shadow-sm border-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-bell mr-2"></i> <b><?= $s['nome'] ?></b> <?= $texto_n ?></span>
                <a href="minhas-tecnicas.php?acao=aprovar&id_log=<?= $s['id'] ?>"
                    class="btn btn-sm btn-dark font-weight-bold">DAR 3 TAPINHAS <i
                        class="fas fa-sign-language ml-1"></i></a>
            </div>
        <?php } ?>

        <hr>

        <h4 class="font-weight-bold mb-3"><i class="fas fa-chart-line mr-2"></i>Evolução Técnica</h4>
        <div class="row">
            <?php $c_chart = 0;
            foreach ($dados_evolucao as $posicao => $dados): if ($dados['total'] == 0) continue;
                $c_chart++; ?>
                <div class="col-md-4 mb-3">
                    <div class="card card-evolucao shadow-sm">
                        <div class="card-body" data-toggle="collapse" data-target="#coll_<?= $c_chart ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div style="flex:1">
                                    <h6 class="text-uppercase font-weight-bold m-0" style="font-size:0.8rem"><?= $posicao ?>
                                    </h6>
                                    <small class="text-muted"><?= $dados['dominados'] ?>/<?= $dados['total'] ?>
                                        Concluídos</small>
                                </div>
                                <div class="chart-box"><canvas id="chart-<?= $c_chart ?>" width="60" height="60"></canvas>
                                </div>
                            </div>
                            <div class="collapse mt-3" id="coll_<?= $c_chart ?>">
                                <?php foreach ($dados['tecnicas'] as $tec):
                                    $dom = (mb_strtolower($tec['status']) == 'dominado' || mb_strtolower($tec['status']) == 'concluído'); ?>
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                        <div class="small text-uppercase font-weight-bold">
                                            <?php if (!$dom): ?><input type="checkbox" class="mr-2 check-tecnica"
                                                    value="<?= $tec['id'] ?>"
                                                    onclick="event.stopPropagation(); atualizarContagemLote();"><?php endif; ?>
                                            <?= $tec['tecnica'] ?>
                                        </div>
                                        <?= $dom ? '<span class="badge badge-success">OK</span>' : '<button class="btn btn-xs btn-primary" onclick="event.stopPropagation(); abrirLeitorQR(\'' . $tec['tecnica'] . '\', \'' . $tec['id'] . '\')"><i class="fas fa-qrcode"></i></button>' ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="btn btn-success btn-lote shadow" id="btnValidarLote" onclick="abrirLeitorLote()">
            <i class="fas fa-qrcode mr-2"></i> VALIDAR SELECIONADOS (<span id="countLote">0</span>)
        </button>

        <hr>

        <h4 class="font-weight-bold mb-3"><i class="fas fa-book mr-2"></i>Diário de Golpes</h4>
        <div class="card card-primary card-outline shadow-sm mb-4">
            <div class="card-body">
                <form method="POST">
                    <label class="small font-weight-bold text-muted text-uppercase">O que treinou hoje?</label>
                    <div class="input-group">
                        <input type="text" name="nome_tecnica" class="form-control"
                            placeholder="Ex: Armlock da Guarda..." required>
                        <div class="input-group-append"><button class="btn btn-primary" type="submit"><i
                                    class="fas fa-plus"></i></button></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php
            $query_t = $pdo->prepare("SELECT * FROM $tabela WHERE usuario_id = :id AND status = 'Em Estudo' ORDER BY id DESC");
            $query_t->execute([':id' => $id_usuario]);
            while ($t = $query_t->fetch(PDO::FETCH_ASSOC)) { ?>
                <div class="col-md-6 mb-3">
                    <div class="card card-tecnica shadow-sm h-100">
                        <a href="minhas-tecnicas.php?delete=<?= $t['id'] ?>" class="btn-delete-x"
                            onclick="return confirm('Excluir?')"><i class="fas fa-times-circle fa-lg"></i></a>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2 pr-4">
                                <h6 class="font-weight-bold text-uppercase mb-0"><?= htmlspecialchars($t['nome_tecnica']) ?>
                                </h6>
                                <span class="badge-reps"><?= $t['repeticoes'] ?></span>
                            </div>
                            <form method="POST" class="p-3 border rounded bg-white">
                                <input type="hidden" name="id_log" value="<?= $t['id'] ?>">
                                <div class="d-flex justify-content-around mb-2">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input radio-tipo" type="radio" name="tipo_registro"
                                            id="t<?= $t['id'] ?>" value="Treino" checked data-id="<?= $t['id'] ?>">
                                        <label for="t<?= $t['id'] ?>" class="custom-control-label small">Treino</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input radio-tipo" type="radio" name="tipo_registro"
                                            id="c<?= $t['id'] ?>" value="Combate" data-id="<?= $t['id'] ?>">
                                        <label for="c<?= $t['id'] ?>"
                                            class="custom-control-label text-danger font-weight-bold small">Combate</label>
                                    </div>
                                </div>
                                <div id="box-treino-<?= $t['id'] ?>"><input type="number" name="quantidade_reps"
                                        class="form-control form-control-sm mb-2" placeholder="Repetições"></div>
                                <div id="box-combate-<?= $t['id'] ?>" class="secao-combate mb-2">
                                    <select name="acao_combate" class="form-control form-control-sm">
                                        <option value="Finalização">Finalização ✅</option>
                                        <option value="Raspagem">Raspagem 🔄</option>
                                        <option value="Passagem">Passagem 🤺</option>
                                    </select>
                                </div>
                                <div class="position-relative mb-2">
                                    <input type="text" name="parceiro_nome_texto"
                                        class="form-control form-control-sm input-busca-aluno" placeholder="Colega..."
                                        autocomplete="off" required>
                                    <input type="hidden" name="parceiro_id" class="aluno-id-hidden">
                                    <div class="lista-busca d-none"></div>
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm btn-block font-weight-bold">ANOTAR</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="modal fade" id="modalQR" tabindex="-1" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5>Validar Técnica</h5><button type="button" class="close text-white" data-dismiss="modal"
                        onclick="pararCamera()"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <p id="nome_tec_modal" class="font-weight-bold text-uppercase"></p>
                    <div id="reader"></div><input type="hidden" id="id_tec_modal"><input type="hidden"
                        id="modo_validacao">
                </div>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/chart.js/Chart.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>

    <script>
        let html5QrCode;

        // GRÁFICOS
        $(function() {
            <?php $c_js = 0;
            foreach ($dados_evolucao as $d): if ($d['total'] == 0) continue;
                $c_js++; ?>
                new Chart(document.getElementById('chart-<?= $c_js ?>').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [<?= $d['dominados'] ?>, <?= $d['total'] - $d['dominados'] ?>],
                            backgroundColor: ['#28a745', '#e9ecef'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        cutoutPercentage: 70,
                        responsive: false,
                        maintainAspectRatio: false,
                        tooltips: {
                            enabled: false
                        }
                    }
                });
            <?php endforeach; ?>
        });

        // LÓGICA DE PLACAR E BUSCA
        $(document).on('change', '.radio-tipo', function() {
            let id = $(this).data('id');
            if ($(this).val() == 'Treino') {
                $(`#box-treino-${id}`).show();
                $(`#box-combate-${id}`).hide();
            } else {
                $(`#box-treino-${id}`).hide();
                $(`#box-combate-${id}`).show();
            }
        });

        $(document).on('keyup', '.input-busca-aluno', function() {
            let input = $(this);
            let lista = input.siblings('.lista-busca');
            if (input.val().length >= 2) {
                $.post('buscar_alunos.php', {
                    query: input.val()
                }, data => lista.removeClass('d-none').html(data));
            } else {
                lista.addClass('d-none');
            }
        });

        $(document).on('click', '.item-aluno', function() {
            let container = $(this).closest('.position-relative');
            container.find('.input-busca-aluno').val($(this).data('nome'));
            container.find('.aluno-id-hidden').val($(this).data('id'));
            $('.lista-busca').addClass('d-none');
            if (container.find('#input-comparativo').length > 0) {
                buscarPlacarDisputa($(this).data('id'));
            }
        });

        function buscarPlacarDisputa(parceiroId) {
            $.post('get_placar_disputa.php', {
                parceiro_id: parceiroId
            }, function(data) {
                let p = JSON.parse(data);
                $('#meu-fin').text(p.meu.fin);
                $('#meu-ras').text(p.meu.ras);
                $('#meu-pas').text(p.meu.pas);
                $('#dele-fin').text(p.dele.fin);
                $('#dele-ras').text(p.dele.ras);
                $('#dele-pas').text(p.dele.pas);
                $('#resultado-disputa').removeClass('d-none').hide().fadeIn();
            });
        }

        // LÓGICA QR CODE
        function abrirLeitorQR(nome, id) {
            $('#modo_validacao').val('individual');
            $('#id_tec_modal').val(id);
            $('#nome_tec_modal').text(nome);
            iniciarCamera();
        }

        function atualizarContagemLote() {
            let s = $('.check-tecnica:checked').length;
            $('#countLote').text(s);
            s > 0 ? $('#btnValidarLote').fadeIn() : $('#btnValidarLote').fadeOut();
        }

        function abrirLeitorLote() {
            let ids = [];
            $('.check-tecnica:checked').each(function() {
                ids.push($(this).val());
            });
            $('#modo_validacao').val('lote');
            $('#id_tec_modal').val(ids.join(','));
            $('#nome_tec_modal').text("Validando " + ids.length + " técnicas");
            iniciarCamera();
        }

        function iniciarCamera() {
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
            $.post("validar-tecnica.php", {
                token_professor: decodedText,
                id: $('#id_tec_modal').val(),
                modo: $('#modo_validacao').val()
            }, function(res) {
                if (res.trim() == "Sucesso") {
                    alert("OSS! Validado.");
                    location.reload();
                } else {
                    alert("Erro: " + res);
                    $('#modalQR').modal('hide');
                }
            });
        }

        function pararCamera() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
        }
    </script>
</body>

</html>