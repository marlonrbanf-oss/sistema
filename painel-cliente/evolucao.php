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
    if (mb_strtolower($h['status']) == 'dominado') {
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
        }

        .card-evolucao:hover {
            background-color: #fcfcfc;
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
                                    <div class="card-body" data-toggle="collapse"
                                        data-target="#<?php echo $id_collapse; ?>">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-uppercase font-weight-bold m-0" style="font-size: 0.9rem;">
                                                <?php echo $posicao; ?></h6>
                                            <i class="fas fa-chevron-down text-muted"></i>
                                        </div>

                                        <div class="progress mb-2" style="height: 8px; border-radius: 4px;">
                                            <div class="progress-bar bg-info" style="width: <?php echo $porcentagem; ?>%">
                                            </div>
                                        </div>
                                        <div class="small text-muted"><?php echo $dados['dominados']; ?> de
                                            <?php echo $dados['total']; ?> dominados</div>

                                        <div class="collapse mt-3" id="<?php echo $id_collapse; ?>">
                                            <?php foreach ($dados['tecnicas'] as $tec):
                                                $dominado = (mb_strtolower($tec['status']) == 'dominado');
                                            ?>
                                                <div class="tecnica-item d-flex justify-content-between align-items-center">
                                                    <div class="small text-uppercase font-weight-bold">
                                                        <?php echo $tec['tecnica']; ?>
                                                    </div>
                                                    <div>
                                                        <?php if ($dominado): ?>
                                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check"></i>
                                                                OK</span>
                                                        <?php else: ?>
                                                            <button class="btn btn-xs btn-primary shadow-sm px-2"
                                                                onclick="event.stopPropagation(); abrirLeitorQR('<?php echo $tec['tecnica']; ?>', '<?php echo $tec['id']; ?>')">
                                                                <i class="fas fa-qrcode"></i> VALIDAR
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
                    <h5 class="modal-title">Validar Selo Técnica</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"
                        onclick="pararCamera()"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    <p id="nome_tec_modal" class="font-weight-bold text-uppercase mb-3"></p>
                    <div id="reader"></div>
                    <input type="hidden" id="id_tec_modal">
                </div>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>

    <script>
        let html5QrCode;

        function abrirLeitorQR(nome, id) {
            $('#id_tec_modal').val(id);
            $('#nome_tec_modal').text(nome);
            $('#modalQR').modal('show');

            html5QrCode = new Html5Qrcode("reader");
            html5QrCode.start({
                    facingMode: "environment"
                }, {
                    fps: 10,
                    qrbox: 250
                },
                onScanSuccess
            ).catch(err => {
                console.error(err);
                alert("Erro ao abrir a câmera.");
            });
        }

        function onScanSuccess(decodedText) {
            pararCamera();
            let id_tec = $('#id_tec_modal').val();

            // Envia para o ficheiro que valida o selo
            $.post("validar-tecnica.php", {
                token_professor: decodedText,
                id: id_tec
            }, function(res) {
                if (res.trim() == "Sucesso") {
                    alert("Selo validado com sucesso! OSS!");
                    location.reload();
                } else {
                    alert("Erro na validação: " + res);
                    $('#modalQR').modal('hide');
                }
            });
        }

        function pararCamera() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("Câmara parada.");
                });
            }
        }
    </script>
</body>

</html>