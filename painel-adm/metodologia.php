<?php
@session_start();
require_once("../conexao.php");

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
        echo '<div class="col-12 mt-4"><h4 class="font-weight-bold text-dark border-bottom pb-2">' . $titulo_sessao . '</h4></div>';
        foreach ($licoes as $licao) {
            $id_l = $licao['id'];
            $total_t = $pdo->query("SELECT id FROM tecnicas WHERE licao_id = '$id_l'")->rowCount();
?>
            <div class="col-md-3 mb-4">
                <div class="card card-licao p-3">
                    <span class="numero-licao">#<?php echo $licao['numero']; ?></span>
                    <h5 class="font-weight-bold pr-5 text-uppercase" style="font-size: 0.9rem;"><?php echo $licao['titulo']; ?></h5>
                    <p class="text-muted small mb-3"><?php echo $total_t; ?> Técnicas</p>
                    <div class="mt-auto d-flex justify-content-between">
                        <button class="btn btn-primary btn-sm btn-block mr-2"
                            onclick="verLicao('<?php echo $id_l; ?>', '<?php echo $licao['titulo']; ?>')">
                            <i class="fas fa-cog"></i> Gerenciar
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="excluirLicao('<?php echo $id_l; ?>')">
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
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            min-height: 140px;
            display: flex;
            flex-direction: column;
        }

        .card-licao:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .numero-licao {
            font-size: 1.8rem;
            font-weight: bold;
            color: #007bff;
            opacity: 0.15;
            position: absolute;
            right: 10px;
            top: 5px;
        }

        .btn-add-main {
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
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
            border-left: 4px solid #007bff;
        }
    </style>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="font-weight-bold text-dark"><i class="fas fa-fort-awesome text-primary"></i> Metodologia</h2>
            <div>
                <button class="btn btn-success btn-add-main" onclick="novaLicao()">
                    <i class="fas fa-plus"></i> NOVA ENTRADA
                </button>
                <a href="index.php" class="btn btn-dark btn-sm rounded-pill ml-2">Voltar</a>
            </div>
        </div>

        <div class="row">
            <?php
            // 1. Módulos Específicos (Aquecimentos, Alongamentos, etc)
            renderizarCards($pdo, "🧘 ALONGAMENTOS", "SELECT * FROM licoes WHERE categoria = 'Alongamento' ORDER BY numero ASC");
            renderizarCards($pdo, "🔥 AQUECIMENTOS", "SELECT * FROM licoes WHERE categoria = 'Aquecimento' ORDER BY numero ASC");
            renderizarCards($pdo, "🏗️ FUNDAMENTOS", "SELECT * FROM licoes WHERE categoria = 'Fundamento' ORDER BY numero ASC");
            renderizarCards($pdo, "🎓 EDUCATIVOS", "SELECT * FROM licoes WHERE categoria = 'Educativo' ORDER BY numero ASC");

            // 2. Lições por Nível
            renderizarCards($pdo, "⚪ INICIANTES (L-01 a L-20)", "SELECT * FROM licoes WHERE categoria = 'Licao' AND numero BETWEEN 1 AND 20 ORDER BY numero ASC");
            renderizarCards($pdo, "🔵 INTERMEDIÁRIOS (L-21 a L-50)", "SELECT * FROM licoes WHERE categoria = 'Licao' AND numero BETWEEN 21 AND 50 ORDER BY numero ASC");
            renderizarCards($pdo, "⚫ AVANÇADOS (L-51+)", "SELECT * FROM licoes WHERE categoria = 'Licao' AND numero > 50 ORDER BY numero ASC");
            ?>
        </div>
    </div>

    <div class="modal fade" id="modalLicao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloLicaoModal">Gerenciar Conteúdo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" id="nome_tecnica" class="form-control" placeholder="Nome da técnica/exercício">
                        <input type="hidden" id="licao_id_atual">
                        <div class="input-group-append">
                            <button class="btn btn-success" onclick="salvarTecnica()">ADICIONAR</button>
                        </div>
                    </div>
                    <div id="listaTecnicas"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        function novaLicao() {
            let cat = prompt("Escolha o Módulo:\n1-Licao\n2-Alongamento\n3-Aquecimento\n4-Fundamento\n5-Educativo", "1");
            let mapping = {
                "1": "Licao",
                "2": "Alongamento",
                "3": "Aquecimento",
                "4": "Fundamento",
                "5": "Educativo"
            };
            let categoria_final = mapping[cat] || "Licao";

            let titulo = prompt("Título:");
            let numero = prompt("Número de ordem:");

            if (titulo && numero) {
                window.location.href = "metodologia/inserir-licao.php?titulo=" + encodeURIComponent(titulo) +
                    "&numero=" + numero + "&categoria=" + categoria_final;
            }
        }

        // Funções verLicao, listarTecnicas, salvarTecnica, excluirTecnica e excluirLicao (mantidas as mesmas do seu código original)
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