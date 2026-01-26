<?php
@session_start();
require_once("../conexao.php");

// Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// --- BUSCA DAS LIÇÕES ---
$query = $pdo->query("SELECT * FROM licoes ORDER BY numero ASC");
$licoes = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Metodologia de Treino - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #f4f6f9;
            font-size: 0.85rem;
        }

        .card-licao {
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 100%;
            position: relative;
        }

        .card-licao:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .numero-licao {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            opacity: 0.2;
            position: absolute;
            right: 15px;
            top: 10px;
        }

        .btn-add {
            border: 2px dashed #ccc;
            background: transparent;
            color: #888;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            min-height: 150px;
        }

        .btn-add:hover {
            background: #eee;
            color: #333;
        }

        .modal-header {
            background: #1a1a1a;
            color: white;
        }

        .tecnica-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #007bff;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="font-weight-bold"><i class="fas fa-book-open text-primary"></i> Metodologia de Ensino</h2>
            <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left"></i> Voltar
                Dashboard</a>
        </div>

        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card card-licao btn-add" onclick="novaLicao()">
                    <i class="fas fa-plus-circle fa-3x mb-2"></i>
                    <span class="font-weight-bold">ADICIONAR LIÇÃO</span>
                </div>
            </div>

            <?php foreach ($licoes as $licao):
                $id_l = $licao['id'];
                $total_t = $pdo->query("SELECT id FROM tecnicas WHERE licao_id = '$id_l'")->rowCount();
            ?>
                <div class="col-md-3 mb-4">
                    <div class="card card-licao p-3">
                        <span class="numero-licao">#<?php echo $licao['numero']; ?></span>
                        <h5 class="font-weight-bold pr-5"><?php echo mb_strtoupper($licao['titulo']); ?></h5>
                        <p class="text-muted small"><?php echo $total_t; ?> Técnicas cadastradas</p>

                        <div class="mt-auto d-flex justify-content-between">
                            <button class="btn btn-primary btn-sm btn-block mr-2"
                                onclick="verLicao('<?php echo $licao['id']; ?>', '<?php echo $licao['titulo']; ?>')">
                                <i class="fas fa-eye"></i> Gerenciar
                            </button>
                            <button class="btn btn-outline-danger btn-sm"
                                onclick="excluirLicao('<?php echo $licao['id']; ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="modal fade" id="modalLicao" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloLicaoModal">Lição</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold">Nova Técnica:</h6>
                            <div class="input-group">
                                <input type="text" id="nome_tecnica" class="form-control"
                                    placeholder="Ex: Passagem de guarda emborrachando">
                                <input type="hidden" id="licao_id_atual">
                                <div class="input-group-append">
                                    <button class="btn btn-success" onclick="salvarTecnica()">
                                        <i class="fas fa-plus"></i> Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="listaTecnicas">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        function verLicao(id, titulo) {
            $('#tituloLicaoModal').text('Lição: ' + titulo);
            $('#licao_id_atual').val(id);
            $('#modalLicao').modal('show');
            listarTecnicas(id);
        }

        function listarTecnicas(licao_id) {
            $.ajax({
                url: "metodologia/listar-tecnicas.php",
                method: "POST",
                data: {
                    id: licao_id
                },
                success: function(result) {
                    $('#listaTecnicas').html(result);
                }
            });
        }

        function salvarTecnica() {
            var nome = $('#nome_tecnica').val();
            var id_l = $('#licao_id_atual').val();

            if (nome == "") {
                alert("Digite o nome da técnica!");
                return;
            }

            $.ajax({
                url: "metodologia/inserir-tecnica.php",
                method: "POST",
                data: {
                    nome_tecnica: nome,
                    licao_id: id_l
                },
                success: function(msg) {
                    $('#nome_tecnica').val('');
                    listarTecnicas(id_l);
                }
            });
        }

        function excluirTecnica(id) {
            if (confirm("Excluir esta técnica?")) {
                var id_l = $('#licao_id_atual').val();
                $.ajax({
                    url: "metodologia/excluir-tecnica.php",
                    method: "POST",
                    data: {
                        id: id
                    },
                    success: function() {
                        listarTecnicas(id_l);
                    }
                });
            }
        }

        function novaLicao() {
            let titulo = prompt("Digite o título da nova lição:");
            let numero = prompt("Digite o número da lição:");
            if (titulo && numero) {
                window.location.href = "metodologia/inserir-licao.php?titulo=" + encodeURIComponent(titulo) + "&numero=" +
                    numero;
            }
        }

        function excluirLicao(id) {
            if (confirm("Deseja realmente excluir esta lição e TODAS as técnicas dela?")) {
                window.location.href = "metodologia/excluir-licao.php?id=" + id;
            }
        }
    </script>
</body>

</html>