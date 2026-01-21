<?php
@session_start();
require_once("../conexao.php");

if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

$posicoes_oficiais = [
    'Fundamentos',
    'Defesa Pessoal',
    'Quedas',
    'passagem de guarda',
    'guarda fechada por baixo',
    'guarda fechada por cima',
    'guarda aberta',
    'montada ataque (por cima)',
    'montada defesa (por baixo)',
    '100 kilos ataque (por cima)',
    '100 kilos defesa (por baixo)',
    'meia guarda por cima',
    'costas ataque (por cima)',
    'costas defesa (por baixo)',
    '4 apoios ataque (por cima)',
    '4 apoios por defesa (por baixo)',
    'defesa de finalizações'
];

$query = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY status DESC, tecnica ASC");
$query->bindValue(":id", $id_usuario);
$query->execute();
$habilidades = $query->fetchAll(PDO::FETCH_ASSOC);

$dados_evolucao = [];
foreach ($posicoes_oficiais as $pos) {
    $dados_evolucao[$pos] = ['total' => 0, 'dominados' => 0, 'tecnicas' => []];
}
$dados_evolucao['Outras Posições'] = ['total' => 0, 'dominados' => 0, 'tecnicas' => []];

foreach ($habilidades as $h) {
    $pos_aluno = $h['posicao'];
    $chave = in_array($pos_aluno, $posicoes_oficiais) ? $pos_aluno : 'Outras Posições';
    $dados_evolucao[$chave]['total']++;
    // Aceita 'dominado' ou 'concluído' como progresso total no gráfico
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
    <title>Evolução Técnica - Nova Arte BJJ</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        .card-evolucao {
            cursor: pointer;
            transition: 0.2s;
            border-left: 5px solid #17a2b8;
            border-radius: 10px;
        }

        .card-evolucao:hover {
            background-color: #fcfcfc;
            transform: translateY(-2px);
        }

        .tecnica-item {
            border-bottom: 1px solid #f4f4f4;
            padding: 10px 5px;
        }

        #reader {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }

        /* Estilo para os Gráficos */
        .chart-box {
            width: 60px;
            height: 60px;
            position: relative;
        }

        /* Botão Flutuante de Lote */
        .btn-lote {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            border-radius: 50px;
            padding: 15px 25px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: none;
            /* Aparece via JS ao selecionar */
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="font-weight-bold">Evolução Técnica</h1>
                        <a href="index.php" class="btn btn-dark shadow-sm text-white">
                            <i class="fas fa-arrow-left mr-1"></i> PAINEL
                        </a>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">

                    <button class="btn btn-success btn-lote animate__animated animate__fadeInUp" id="btnValidarLote"
                        onclick="abrirLeitorLote()">
                        <i class="fas fa-qrcode mr-2"></i> VALIDAR SELECIONADOS (<span id="countLote">0</span>)
                    </button>

                    <div class="row">
                        <?php
                        $count = 0;
                        foreach ($dados_evolucao as $posicao => $dados):
                            if ($dados['total'] == 0) continue;
                            $count++;
                            $id_collapse = "coll_" . $count;
                            $porcentagem = ($dados['dominados'] / $dados['total']) * 100;
                        ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-evolucao shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center"
                                            data-toggle="collapse" data-target="#<?php echo $id_collapse; ?>">
                                            <div style="flex: 1;">
                                                <h6 class="text-uppercase font-weight-bold m-0" style="font-size: 0.85rem;">
                                                    <?php echo $posicao; ?>
                                                </h6>
                                                <div class="small text-muted mt-1">
                                                    <?php echo $dados['dominados']; ?> de <?php echo $dados['total']; ?>
                                                    concluídos
                                                </div>
                                            </div>

                                            <div class="chart-box ml-2">
                                                <canvas id="chart-<?php echo $count; ?>" width="60" height="60"></canvas>
                                            </div>
                                            <i class="fas fa-chevron-down text-muted ml-2"></i>
                                        </div>

                                        <div class="collapse mt-3" id="<?php echo $id_collapse; ?>">
                                            <?php foreach ($dados['tecnicas'] as $tec):
                                                $status_tec = mb_strtolower($tec['status']);
                                                $dominado = ($status_tec == 'dominado' || $status_tec == 'concluído');
                                            ?>
                                                <div class="tecnica-item d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center" style="max-width: 70%;">
                                                        <?php if (!$dominado): ?>
                                                            <input type="checkbox" class="mr-2 check-tecnica"
                                                                value="<?php echo $tec['id']; ?>"
                                                                data-nome="<?php echo $tec['tecnica']; ?>"
                                                                onclick="event.stopPropagation(); atualizarContagemLote();">
                                                        <?php endif; ?>
                                                        <div class="small text-uppercase font-weight-bold">
                                                            <?php echo $tec['tecnica']; ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <?php if ($dominado): ?>
                                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check"></i>
                                                                OK</span>
                                                        <?php else: ?>
                                                            <button class="btn btn-xs btn-primary shadow-sm px-2"
                                                                onclick="event.stopPropagation(); abrirLeitorQR('<?php echo $tec['tecnica']; ?>', '<?php echo $tec['id']; ?>')">
                                                                <i class="fas fa-qrcode"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="modalQR" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="tituloModalQR">Validar Selo Técnica</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"
                        onclick="pararCamera()"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <p id="nome_tec_modal" class="font-weight-bold text-uppercase mb-3"></p>
                    <div id="reader"></div>
                    <input type="hidden" id="id_tec_modal">
                    <input type="hidden" id="modo_validacao" value="individual">
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

        // INICIALIZAÇÃO DOS GRÁFICOS DE PIZZA
        $(function() {
            <?php
            $count_js = 0;
            foreach ($dados_evolucao as $d):
                if ($d['total'] == 0) continue;
                $count_js++;
                $faltam = $d['total'] - $d['dominados'];
            ?>
                new Chart(document.getElementById('chart-<?php echo $count_js; ?>').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [<?php echo $d['dominados']; ?>, <?php echo $faltam; ?>],
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

        // LÓGICA DE LOTE
        function atualizarContagemLote() {
            let selecionados = $('.check-tecnica:checked').length;
            $('#countLote').text(selecionados);
            if (selecionados > 0) {
                $('#btnValidarLote').fadeIn();
            } else {
                $('#btnValidarLote').fadeOut();
            }
        }

        function abrirLeitorLote() {
            let ids = [];
            $('.check-tecnica:checked').each(function() {
                ids.push($(this).val());
            });

            $('#modo_validacao').val('lote');
            $('#id_tec_modal').val(ids.join(','));
            $('#nome_tec_modal').text("Validando " + ids.length + " técnicas selecionadas");
            $('#tituloModalQR').text("Validação em Lote");
            iniciarCamera();
        }

        // MANTIDA FUNCIONALIDADE ORIGINAL INDIVIDUAL
        function abrirLeitorQR(nome, id) {
            $('#modo_validacao').val('individual');
            $('#id_tec_modal').val(id);
            $('#nome_tec_modal').text(nome);
            $('#tituloModalQR').text("Validar Técnica");
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
            }, onScanSuccess).catch(err => {
                alert("Erro ao abrir a câmera: " + err);
            });
        }

        function onScanSuccess(decodedText) {
            pararCamera();
            let ids = $('#id_tec_modal').val();
            let modo = $('#modo_validacao').val();

            $.post("validar-tecnica.php", {
                token_professor: decodedText,
                id: ids,
                modo: modo
            }, function(res) {
                if (res.trim() == "Sucesso") {
                    alert("Validação concluída com sucesso! OSS!");
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