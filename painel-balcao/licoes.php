<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA
if (@$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// --- BUSCA DINÂMICA DE COMPONENTES ---

try {
    // 1. Lições (Técnicas principais)
    $licoes = $pdo->query("SELECT * FROM licoes ORDER BY numero ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Aquecimentos e Educativos
    // Nota: Ajustamos para buscar da tabela 'tecnicas'. 
    // Se a coluna no seu banco for 'posicao' em vez de 'categoria', o código abaixo trata isso.

    // Verificamos se a coluna 'posicao' existe para evitar novo erro 1054
    $res_col = $pdo->query("SHOW COLUMNS FROM tecnicas LIKE 'posicao'");
    $tem_coluna_posicao = $res_col->rowCount() > 0;

    if ($tem_coluna_posicao) {
        $aquecimentos = $pdo->query("SELECT * FROM tecnicas WHERE posicao = 'Aquecimento' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $educativos = $pdo->query("SELECT * FROM tecnicas WHERE posicao = 'Educativo' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Se as listas acima vierem vazias, carregamos todas para não deixar o select em branco
        if (count($aquecimentos) == 0) {
            $aquecimentos = $pdo->query("SELECT * FROM tecnicas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
        if (count($educativos) == 0) {
            $educativos = $aquecimentos;
        }
    } else {
        // Se não houver coluna de filtro, carrega a lista geral em ambos
        $aquecimentos = $pdo->query("SELECT * FROM tecnicas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
        $educativos = $aquecimentos;
    }
} catch (Exception $e) {
    echo "Erro ao carregar dados: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <title>Plano de Aula - Nova Arte BJJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
            max-height: 350px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .item-check {
            display: none;
        }

        .item-label {
            display: block;
            padding: 10px 15px;
            margin-bottom: 5px;
            border-radius: 6px;
            cursor: pointer;
            border: 1px solid #eee;
            transition: 0.2s;
            background: #fdfdfd;
        }

        .item-check:checked+.item-label {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .preview-plano {
            background: #f1f3f5;
            border-radius: 10px;
            padding: 20px;
            border: 1px dashed #ced4da;
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

        .aluno-row {
            margin-bottom: 2px;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-3 mb-5">
        <form action="lancar-plano-aula.php" method="POST" id="formPlano">
            <div class="card card-aula">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🥋 MONTAR PLANO DE AULA COLETIVO</h5>
                    <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill">Sair</a>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="select-step">
                                <h6 class="font-weight-bold text-warning"><span
                                        class="badge badge-warning badge-step mr-2 text-white">1</span> Aquecimento</h6>
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
                                <h6 class="font-weight-bold text-info"><span
                                        class="badge badge-info badge-step mr-2">2</span> Educativo</h6>
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
                                <h6 class="font-weight-bold text-primary"><span
                                        class="badge badge-primary badge-step mr-2">3</span> Lição Técnica</h6>
                                <select name="licao_id" class="form-control mb-2 select-dinamico" required>
                                    <option value="">Selecione a Lição...</option>
                                    <?php foreach ($licoes as $li): ?>
                                        <option value="<?php echo $li['numero']; ?>">Lição <?php echo $li['numero']; ?> -
                                            <?php echo @$li['categoria'] ?: @$li['posicao']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="border rounded p-3 bg-white shadow-sm">
                                <h6 class="font-weight-bold mb-3"><i class="fas fa-users mr-2 text-primary"></i> Alunos
                                    Presentes</h6>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" id="buscaAluno" class="form-control"
                                        placeholder="Filtrar por nome...">
                                </div>
                                <div class="list-selectable p-2 bg-light">
                                    <?php
                                    $alunos = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'Cliente' AND ativo = 'Sim' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($alunos as $aluno): ?>
                                        <div class="aluno-row">
                                            <input type="checkbox" name="alunos[]" id="aluno_<?php echo $aluno['id']; ?>"
                                                value="<?php echo $aluno['id']; ?>" class="item-check">
                                            <label for="aluno_<?php echo $aluno['id']; ?>" class="item-label mb-1">
                                                <i class="far fa-square mr-2 unchecked"></i>
                                                <i class="fas fa-check-circle mr-2 checked d-none"></i>
                                                <?php echo mb_strtoupper($aluno['nome']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="preview-plano h-100">
                                <h6 class="font-weight-bold text-uppercase border-bottom border-secondary pb-2 mb-3">
                                    <i class="fas fa-clipboard-list mr-2"></i> Resumo do Lançamento
                                </h6>
                                <div id="resumo-conteudo" class="mb-4">
                                    <p class="text-muted font-italic">Nenhum componente selecionado.</p>
                                </div>

                                <div class="card bg-white shadow-sm">
                                    <div class="card-body">
                                        <h6 class="font-weight-bold small text-muted text-uppercase mb-3">Status do
                                            Progresso:</h6>
                                        <select name="status" class="form-control form-control-lg border-primary">
                                            <option value="Em Estudo">🟡 Em Estudo</option>
                                            <option value="Revisão">🔵 Revisão</option>
                                            <option value="Concluído">🟢 Concluído (Selo Ouro)</option>
                                        </select>
                                        <p class="small text-muted mt-2 mb-0">Isso definirá a cor do selo no painel do
                                            aluno.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3">
                    <button type="submit" class="btn btn-success btn-lg btn-block py-3 font-weight-bold shadow-lg"
                        id="btnSalvar">
                        <i class="fas fa-save mr-2"></i> FINALIZAR E NOTIFICAR ALUNOS
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Atualiza o Resumo em tempo real
        $('.select-dinamico').change(function() {
            const aq = $('select[name="aquecimento_id"] option:selected').val() ? $(
                'select[name="aquecimento_id"] option:selected').text() : null;
            const ed = $('select[name="educativo_id"] option:selected').val() ? $(
                'select[name="educativo_id"] option:selected').text() : null;
            const li = $('select[name="licao_id"] option:selected').val() ? $(
                'select[name="licao_id"] option:selected').text() : null;

            let html = '<div class="list-group list-group-flush">';
            if (aq) html +=
                `<div class="list-group-item bg-transparent px-0"><i class="fas fa-fire text-warning mr-2"></i> <b>Aquecimento:</b> ${aq}</div>`;
            if (ed) html +=
                `<div class="list-group-item bg-transparent px-0"><i class="fas fa-cogs text-info mr-2"></i> <b>Educativo:</b> ${ed}</div>`;
            if (li) html +=
                `<div class="list-group-item bg-transparent px-0"><i class="fas fa-book text-primary mr-2"></i> <b>Lição:</b> ${li}</div>`;
            html += '</div>';

            if (!aq && !ed && !li) {
                $('#resumo-conteudo').html('<p class="text-muted font-italic">Nenhum componente selecionado.</p>');
            } else {
                $('#resumo-conteudo').html(html);
            }
        });

        // Filtro de Alunos
        $("#buscaAluno").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $(".aluno-row").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Estética dos Checkboxes e Contador
        $('input[name="alunos[]"]').change(function() {
            const label = $(this).next('label');
            if ($(this).is(':checked')) {
                label.find('.unchecked').addClass('d-none');
                label.find('.checked').removeClass('d-none');
            } else {
                label.find('.unchecked').removeClass('d-none');
                label.find('.checked').addClass('d-none');
            }
        });

        $('#formPlano').submit(function() {
            if ($('input[name="alunos[]"]:checked').length === 0) {
                alert("Por favor, selecione pelo menos um aluno presente.");
                return false;
            }
            $('#btnSalvar').html('<i class="fas fa-spinner fa-spin mr-2"></i> GRAVANDO...').attr('disabled', true);
            return true;
        });
    </script>
</body>

</html>