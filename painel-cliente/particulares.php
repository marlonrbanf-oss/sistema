<?php
@session_start();
require_once("../conexao.php");

// Proteção de acesso
if (!isset($_SESSION['id_usuario'])) {
    echo "<script>window.location='../login.php';</script>";
    exit();
}

$id_aluno = $_SESSION['id_usuario'];

/**
 * Como você restaurou o banco, vamos usar diretamente a coluna 'nivel'.
 * Mantivemos uma pequena verificação apenas por segurança extrema.
 */
$coluna_nivel = 'nivel';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aulas Particulares - Nova Arte</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        .prof-card {
            border-radius: 15px;
            transition: transform 0.2s;
            border: none;
        }

        .prof-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .img-prof {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border: 3px solid #6f42c1;
        }
    </style>
</head>

<body class="hold-transition bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="font-weight-bold"><i class="fas fa-user-tie mr-2 text-primary"></i> Aulas Particulares</h3>
            <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-home mr-1"></i> Home</a>
        </div>

        <div class="row">
            <?php
            // Busca professores e balconistas usando a coluna 'nivel' restaurada
            $query = $pdo->query("SELECT * FROM usuarios WHERE nivel = 'Professor' OR nivel = 'Balconista' ORDER BY nome ASC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);

            if (count($res) > 0) {
                foreach ($res as $prof) {
                    $telefone = preg_replace('/[^0-9]/', '', $prof['telefone'] ?? '');

                    // Formatação do link do WhatsApp
                    if (strlen($telefone) > 0 && substr($telefone, 0, 2) !== "55") {
                        $telefone = "55" . $telefone;
                    }

                    $foto_prof = (!empty($prof['foto'])) ? $prof['foto'] : 'usuario-icone-claro.png';
            ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card prof-card shadow-sm h-100">
                            <div class="card-body text-center">
                                <img src="../images/perfil/<?php echo $foto_prof; ?>" class="img-prof img-circle mb-3 shadow">
                                <h5 class="font-weight-bold mb-1"><?php echo mb_strtoupper($prof['nome']); ?></h5>
                                <p class="text-muted small">Especialista Nova Arte</p>
                                <hr>

                                <div class="row mt-3">
                                    <div class="col-6">
                                        <?php if (!empty($telefone)): ?>
                                            <a href="https://wa.me/<?php echo $telefone; ?>?text=Olá, gostaria de agendar uma aula!"
                                                target="_blank" class="btn btn-outline-success btn-sm btn-block rounded-pill">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-sm btn-block rounded-pill" disabled>
                                                <i class="fas fa-phone-slash"></i> S/ Tel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <button
                                            onclick="abrirModalPedido('<?php echo $prof['id']; ?>', '<?php echo $prof['nome']; ?>')"
                                            class="btn btn-primary btn-sm btn-block rounded-pill shadow-sm">
                                            <i class="fas fa-calendar-plus"></i> Agendar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12 text-center text-muted p-5">Nenhum instrutor disponível para agendamento.</div>';
            }
            ?>
        </div>
    </div>

    <div class="modal fade" id="modalAgendar" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">Solicitar Aula</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="form-pedido-aula">
                    <div class="modal-body">
                        <p class="text-center text-muted">Solicitando com: <strong id="nome_prof_modal"
                                class="text-dark"></strong></p>
                        <input type="hidden" name="id_professor" id="id_prof_modal">

                        <div class="form-group">
                            <label class="small font-weight-bold">DATA DESEJADA</label>
                            <input type="date" name="data_aula" class="form-control" required
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">HORÁRIO</label>
                            <input type="time" name="horario_aula" class="form-control" required>
                        </div>
                        <div id="mensagem-pedido"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" id="btn-enviar-pedido" class="btn btn-primary btn-block shadow-sm">ENVIAR
                            SOLICITAÇÃO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        function abrirModalPedido(id, nome) {
            $('#id_prof_modal').val(id);
            $('#nome_prof_modal').text(nome);
            $('#mensagem-pedido').html('');
            $('#modalAgendar').modal('show');
        }

        $("#form-pedido-aula").submit(function(e) {
            e.preventDefault();
            let btn = $('#btn-enviar-pedido');
            btn.text('Processando...').attr('disabled', true);

            $.ajax({
                url: "enviar-pedido-aula.php",
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.trim() == "Sucesso") {
                        alert("Pedido enviado com sucesso!");
                        $('#modalAgendar').modal('hide');
                        location.reload();
                    } else {
                        $('#mensagem-pedido').html('<div class="alert alert-danger mt-2 small">' + res +
                            '</div>');
                        btn.text('ENVIAR SOLICITAÇÃO').attr('disabled', false);
                    }
                }
            });
        });
    </script>
</body>

</html>