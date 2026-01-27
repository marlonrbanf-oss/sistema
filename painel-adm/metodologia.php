<?php
@session_start();
require_once("../conexao.php");

// VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// Função para buscar e renderizar os cards por filtro
function renderizarCards($pdo, $titulo_sessao, $query_filtro)
{
    $query = $pdo->query($query_filtro);
    $licoes = $query->fetchAll(PDO::FETCH_ASSOC);

    if (count($licoes) > 0) {
        echo '<div class="col-12 mt-4"><h5 class="font-weight-bold text-secondary border-bottom pb-2">' . $titulo_sessao . '</h5></div>';
        foreach ($licoes as $licao) {
            $id_l = $licao['id'];
            $total_t = $pdo->query("SELECT id FROM tecnicas WHERE licao_id = '$id_l'")->rowCount();
?>
            <div class="col-6 col-md-4 col-lg-3 mb-3 px-2">
                <div class="card card-licao p-2 p-md-3">
                    <span class="numero-licao">#<?php echo $licao['numero']; ?></span>
                    <h6 class="font-weight-bold pr-4 text-uppercase mb-1 text-truncate-2" title="<?php echo $licao['titulo']; ?>">
                        <?php echo $licao['titulo']; ?>
                    </h6>
                    <p class="text-muted small mb-2"><?php echo $total_t; ?> Técnicas</p>
                    <div class="mt-auto d-flex flex-wrap">
                        <button class="btn btn-primary btn-sm mb-1 mr-1 flex-grow-1"
                            onclick="verLicao('<?php echo $id_l; ?>', '<?php echo $licao['titulo']; ?>')"
                            title="Gerenciar Técnicas">
                            <i class="fas fa-cog"></i>
                        </button>

                        <button class="btn btn-warning btn-sm mb-1 mr-1 text-white"
                            onclick="editarLicao('<?php echo $id_l; ?>', '<?php echo $licao['titulo']; ?>', '<?php echo $licao['numero']; ?>', '<?php echo $licao['categoria']; ?>')"
                            title="Renomear Lição">
                            <i class="fas fa-edit"></i>
                        </button>

                        <button class="btn btn-outline-danger btn-sm mb-1" onclick="excluirLicao('<?php echo $id_l; ?>')"
                            title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
<?php
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Metodologia - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        :root {
            --primary-color: #007bff;
            --dark-bg: #1a1a1a;
        }

        body {
            background: #f4f6f9;
            font-size: 0.85rem;
            overflow-x: hidden;
        }

        .titulo-pagina {
            font-size: 1.4rem;
            margin-bottom: 0;
        }

        @media (max-width: 576px) {
            .titulo-pagina {
                font-size: 1.1rem;
            }

            .btn-add-main {
                padding: 8px 15px !important;
                font-size: 0.8rem !important;
            }
        }

        .card-licao {
            transition: all 0.2s ease-in-out;
            border: none;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            height: 100%;
            min-height: 145px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .card-licao:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
        }

        .numero-licao {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--primary-color);
            opacity: 0.12;
            position: absolute;
            right: 8px;
            top: 4px;
        }

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
            height: 2.4em;
        }

        .btn-add-main {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
        }

        .modal-header {
            background: var(--dark-bg);
            color: white;
            padding: 1rem;
        }

        .tecnica-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            border-left: 4px solid var(--primary-color);
            font-size: 0.85rem;
        }

        .container-fluid {
            padding-left: 10px;
            padding-right: 10px;
        }

        .row {
            margin-left: -5px;
            margin-right: -5px;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-2 rounded shadow-sm">
            <h1 class="font-weight-bold text-dark titulo-pagina">
                <i class="fas fa-fort-awesome text-primary"></i> Metodologia
            </h1>
            <div class="d-flex align-items-center">
                <button class="btn btn-success btn-add-main" onclick="novaLicao()">
                    <i class="fas fa-plus"></i> <span class="d-none d-md-inline">NOVA ENTRADA</span>
                </button>
                <a href="index.php" class="btn btn-dark btn-sm rounded-pill ml-2">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        <div class="row mx-0">
            <?php
            renderizarCards($pdo, "🧘 ALONGAMENTOS", "SELECT * FROM licoes WHERE categoria = 'Alongamento' ORDER BY numero ASC");
            renderizarCards($pdo, "🔥 AQUECIMENTOS", "SELECT * FROM licoes WHERE categoria = 'Aquecimento' ORDER BY numero ASC");
            renderizarCards($pdo, "🏗️ FUNDAMENTOS", "SELECT * FROM licoes WHERE categoria = 'Fundamento' ORDER BY numero ASC");
            renderizarCards($pdo, "🎓 EDUCATIVOS", "SELECT * FROM licoes WHERE categoria = 'Educativo' ORDER BY numero ASC");
            renderizarCards($pdo, "⚪ INICIANTES (L01-L20)", "SELECT * FROM licoes WHERE categoria = 'Licao' AND numero BETWEEN 1 AND 20 ORDER BY numero ASC");
            renderizarCards($pdo, "🔵 INTERMEDIÁRIOS (L21-L50)", "SELECT * FROM licoes WHERE categoria = 'Licao' AND numero BETWEEN 21 AND 50 ORDER BY numero ASC");
            renderizarCards($pdo, "⚫ AVANÇADOS (L51+)", "SELECT * FROM licoes WHERE categoria = 'Licao' AND numero > 50 ORDER BY numero ASC");
            ?>
        </div>
    </div>

    <div class="modal fade" id="modalLicao" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <h6 class="modal-title font-weight-bold" id="tituloLicaoModal">Conteúdo</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="input-group mb-3">
                        <input type="text" id="nome_tecnica" class="form-control" placeholder="Nova técnica...">
                        <input type="hidden" id="licao_id_atual">
                        <div class="input-group-append">
                            <button class="btn btn-success" onclick="salvarTecnica()">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </div>
                    <div id="listaTecnicas" class="mt-2" style="max-height: 60vh; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        function novaLicao() {
            let cat = prompt("Módulo:\n1-Licao\n2-Alongamento\n3-Aquecimento\n4-Fundamento\n5-Educativo", "1");
            let mapping = {
                "1": "Licao",
                "2": "Alongamento",
                "3": "Aquecimento",
                "4": "Fundamento",
                "5": "Educativo"
            };
            let categoria_final = mapping[cat] || "Licao";
            let titulo = prompt("Título:");
            if (!titulo) return;
            let numero = prompt("Número de ordem:");
            if (!numero) return;

            window.location.href = "metodologia/inserir-licao.php?titulo=" + encodeURIComponent(titulo) +
                "&numero=" + numero + "&categoria=" + categoria_final;
        }

        // NOVA FUNÇÃO PARA RENOMEAR / EDITAR
        function editarLicao(id, tituloAtual, numeroAtual, categoriaAtual) {
            let novoTitulo = prompt("Novo título para a lição:", tituloAtual);
            if (novoTitulo == null || novoTitulo == "") return;

            let novoNumero = prompt("Novo número de ordem:", numeroAtual);
            if (novoNumero == null || novoNumero == "") return;

            window.location.href = "metodologia/inserir-licao.php?id=" + id +
                "&titulo=" + encodeURIComponent(novoTitulo) +
                "&numero=" + novoNumero +
                "&categoria=" + categoriaAtual;
        }

        function verLicao(id, titulo) {
            $('#tituloLicaoModal').text(titulo);
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
            if (nome == "") return;
            $.ajax({
                url: "metodologia/inserir-tecnica.php",
                method: "POST",
                data: {
                    nome_tecnica: nome,
                    licao_id: id_l
                },
                success: function() {
                    $('#nome_tecnica').val('');
                    listarTecnicas(id_l);
                }
            });
        }

        function excluirLicao(id) {
            if (confirm("Excluir este módulo permanentemente?")) {
                window.location.href = "metodologia/excluir-licao.php?id=" + id;
            }
        }
    </script>
</body>

</html>