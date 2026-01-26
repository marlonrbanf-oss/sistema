<?php
@session_start();
require_once("../conexao.php");

if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// --- BUSCA DINÂMICA DE COMPONENTES ---

// 1. Lições (Técnicas principais)
$licoes = $pdo->query("SELECT * FROM licoes ORDER BY numero ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Aquecimentos (Buscando por categoria no banco ou tabela específica)
// Ajuste a query conforme sua tabela (ex: SELECT * FROM tecnicas WHERE categoria = 'Aquecimento')
$aquecimentos = $pdo->query("SELECT * FROM tecnicas WHERE categoria = 'Aquecimento' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Educativos
$educativos = $pdo->query("SELECT * FROM tecnicas WHERE categoria = 'Educativo' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Plano de Aula - Nova Arte BJJ</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        .card-aula {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .select-step {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            height: 100%;
        }

        .list-selectable {
            max-height: 200px;
            overflow-y: auto;
        }

        .item-check {
            display: none;
        }

        .item-label {
            display: block;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #eee;
            transition: 0.2s;
        }

        .item-check:checked+.item-label {
            background: #343a40;
            color: #fff;
            border-color: #343a40;
        }

        .preview-plano {
            background: #e9ecef;
            border-radius: 10px;
            padding: 20px;
        }

        .badge-step {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-3">
        <form action="lancar-plano-aula.php" method="POST" id="formPlano">
            <div class="card card-aula">
                <div class="card-header bg-dark text-white d-flex justify-content-between">
                    <h5 class="mb-0">🥋 MONTAR PLANO DE AULA COLETIVO</h5>
                    <a href="index.php" class="btn btn-outline-light btn-sm">Sair</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="select-step">
                                <h6 class="font-weight-bold"><span class="badge badge-warning badge-step mr-2">1</span>
                                    Aquecimento</h6>
                                <select name="aquecimento_id" class="form-control mb-2 select-dinamico">
                                    <option value="">Nenhum selecionado</option>
                                    <?php foreach ($aquecimentos as $aq): ?>
                                        <option value="<?php echo $aq['id']; ?>"><?php echo $aq['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step">
                                <h6 class="font-weight-bold"><span class="badge badge-info badge-step mr-2">2</span>
                                    Educativo</h6>
                                <select name="educativo_id" class="form-control mb-2 select-dinamico">
                                    <option value="">Nenhum selecionado</option>
                                    <?php foreach ($educativos as $ed): ?>
                                        <option value="<?php echo $ed['id']; ?>"><?php echo $ed['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="select-step">
                                <h6 class="font-weight-bold"><span class="badge badge-primary badge-step mr-2">3</span>
                                    Lição Técnica</h6>
                                <select name="licao_id" class="form-control mb-2 select-dinamico" required>
                                    <option value="">Selecione a Lição...</option>
                                    <?php foreach ($licoes as $li): ?>
                                        <option value="<?php echo $li['numero']; ?>">Lição <?php echo $li['numero']; ?> -
                                            <?php echo $li['categoria']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 bg-white">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-users mr-2"></i> Selecionar Alunos
                                    Presentes</h6>
                                <input type="text" id="buscaAluno" class="form-control mb-3"
                                    placeholder="Filtrar por nome...">
                                <div class="list-selectable" style="height: 300px;">
                                    <?php
                                    $alunos = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($alunos as $aluno): ?>
                                        <div class="aluno-row">
                                            <input type="checkbox" name="alunos[]" id="aluno_<?php echo $aluno['id']; ?>"
                                                value="<?php echo $aluno['id']; ?>" class="item-check">
                                            <label for="aluno_<?php echo $aluno['id']; ?>" class="item-label">
                                                <i class="far fa-square mr-2 unchecked"></i>
                                                <i class="fas fa-check-square mr-2 checked d-none"></i>
                                                <?php echo mb_strtoupper($aluno['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="preview-plano h-100">
                                <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3">Resumo da Aula</h6>
                                <div id="resumo-conteudo">
                                    <p class="text-muted italic">Aguardando seleções...</p>
                                </div>

                                <div class="mt-4">
                                    <h6 class="font-weight-bold small">STATUS DO LANÇAMENTO:</h6>
                                    <select name="status" class="form-control shadow-sm">
                                        <option value="Em Estudo">🟡 Em Estudo (Padrão)</option>
                                        <option value="Revisão">🔵 Revisão</option>
                                        <option value="Concluído">🟢 Concluído</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light">
                    <button type="submit" class="btn btn-success btn-lg btn-block py-3 font-weight-bold shadow">
                        <i class="fas fa-save mr-2"></i> REGISTRAR AULA E NOTIFICAR ALUNOS
                    </button>
                </div>
            </div>
        </form>
    </div>



    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Atualiza o Resumo em tempo real
        $('.select-dinamico').change(function() {
            const aq = $('select[name="aquecimento_id"] option:selected').text();
            const ed = $('select[name="educativo_id"] option:selected').text();
            const li = $('select[name="licao_id"] option:selected').text();

            let html = '<ul class="list-unstyled">';
            if ($('select[name="aquecimento_id"]').val()) html += `<li><strong>🔥 Aquecimento:</strong> ${aq}</li>`;
            if ($('select[name="educativo_id"]').val()) html += `<li><strong>⚙️ Educativo:</strong> ${ed}</li>`;
            if ($('select[name="licao_id"]').val()) html += `<li><strong>📖 Técnica:</strong> ${li}</li>`;
            html += '</ul>';

            $('#resumo-conteudo').html(html);
        });

        // Filtro de Alunos
        $("#buscaAluno").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".aluno-row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Estética dos Checkboxes
        $('input[name="alunos[]"]').change(function() {
            const label = $(this).next('label');
            if ($(this).is(':checked')) {
                label.find('.unchecked').addClass('d-none');
                label.find('.checked').removeClass('d-none');
                label.addClass('bg-success text-white');
            } else {
                label.find('.unchecked').removeClass('d-none');
                label.find('.checked').addClass('d-none');
                label.removeClass('bg-success text-white');
            }
        });
    </script>
</body>

</html>