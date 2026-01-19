<?php
@session_start();
require_once("../conexao.php");

$id_aluno = $_SESSION['id_usuario']; // ID de quem está logado
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
    }

    .prof-card:hover {
        transform: translateY(-5px);
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
            <h3><i class="fas fa-user-tie mr-2 text-primary"></i> Aulas Particulares</h3>
            <a href="index.php" class="btn btn-dark btn-sm shadow"><i class="fas fa-home mr-1"></i> Home</a>
        </div>

        <div class="row">
            <?php
            $query = $pdo->query("SELECT * FROM usuarios WHERE nivel = 'Balconista' OR nivel = 'Admin' ORDER BY nome ASC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($res as $prof) {
                $telefone = preg_replace('/[^0-9]/', '', $prof['telefone'] ?? '5511999999999');
                $foto_prof = (!empty($prof['foto'])) ? $prof['foto'] : 'usuario-icone-claro.png';
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card prof-card shadow-sm border-0">
                    <div class="card-body text-center">
                        <img src="../images/perfil/<?php echo $foto_prof; ?>" class="img-prof img-circle mb-3 shadow">
                        <h5 class="font-weight-bold mb-1"><?php echo $prof['nome']; ?></h5>
                        <p class="text-muted small">Especialista em BJJ & No-Gi</p>
                        <hr>

                        <div class="row">
                            <div class="col-6">
                                <a href="https://wa.me/<?php echo $telefone; ?>?text=Olá Professor, gostaria de agendar uma aula!"
                                    target="_blank" class="btn btn-outline-success btn-block btn-sm rounded-pill">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                            <div class="col-6">
                                <button
                                    onclick="abrirModalPedido('<?php echo $prof['id']; ?>', '<?php echo $prof['nome']; ?>')"
                                    class="btn btn-primary btn-block btn-sm rounded-pill shadow-sm">
                                    <i class="fas fa-calendar-plus"></i> Agendar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="modal fade" id="modalAgendar" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Solicitar Aula Particular</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="form-pedido-aula">
                    <div class="modal-body">
                        <p class="text-muted">Professor: <strong id="nome_prof_modal"></strong></p>
                        <input type="hidden" name="id_professor" id="id_prof_modal">

                        <div class="form-group">
                            <label>Data Desejada</label>
                            <input type="date" name="data_aula" class="form-control" required
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Horário</label>
                            <input type="time" name="horario_aula" class="form-control" required>
                        </div>
                        <div id="mensagem-pedido"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-block shadow">ENVIAR SOLICITAÇÃO</button>
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
        $('#modalAgendar').modal('show');
    }

    $("#form-pedido-aula").submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: "enviar-pedido-aula.php",
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.trim() == "Sucesso") {
                    alert("Pedido enviado! O professor visualizará no painel dele.");
                    location.reload();
                } else {
                    $('#mensagem-pedido').html('<div class="alert alert-danger mt-2">' + res +
                        '</div>');
                }
            }
        });
    });
    </script>
</body>

</html>