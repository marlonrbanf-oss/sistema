<?php
@session_start();
require_once("../conexao.php");

// Validação de nível (mantida)
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>window.location='usuarios.php';</script>";
    exit();
}

$id_aluno = $_GET['id'];
$query = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
$query->bindValue(":id", $id_aluno);
$query->execute();
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) == 0) exit();
$nome_aluno = $res[0]['nome'];

// --- BUSCA DINÂMICA DE MÓDULOS (CATEGORIAS) ---
// Buscamos as categorias únicas que existem na tabela licoes
$query_cat = $pdo->query("SELECT DISTINCT categoria FROM licoes ORDER BY categoria ASC");
$categorias_bd = $query_cat->fetchAll(PDO::FETCH_ASSOC);

// --- BUSCA DINÂMICA DE LIÇÕES E TÉCNICAS ---
$licoes_array = [];
$query_l = $pdo->query("SELECT * FROM licoes ORDER BY numero ASC");
$todas_licoes = $query_l->fetchAll(PDO::FETCH_ASSOC);

foreach ($todas_licoes as $licao) {
    $id_l = $licao['id'];
    $cat_l = $licao['categoria'];

    $query_t = $pdo->query("SELECT nome FROM tecnicas WHERE licao_id = '$id_l' ORDER BY id ASC");
    $tecs = $query_t->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tecs as $t) {
        $licoes_array[] = [
            'nome' => $t['nome'],
            'categoria' => $cat_l
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Lançar Técnica - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #f4f6f9;
            font-size: 0.9rem;
        }

        .card {
            border-radius: 15px;
        }

        .grid-select {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-bottom: 15px;
        }

        .opt-card input {
            display: none;
        }

        .opt-card label {
            border: 1px solid #ccc;
            padding: 6px;
            border-radius: 5px;
            display: block;
            text-align: center;
            cursor: pointer;
            font-size: 0.75rem;
            background: #fff;
            transition: 0.2s;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .opt-card input:checked+label {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
            font-weight: bold;
        }

        .section-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 20px;
        }

        .status-badge {
            border-radius: 20px;
            padding: 8px 5px;
            cursor: pointer;
            border: 2px solid #ddd;
            display: block;
            text-align: center;
            transition: 0.3s;
            font-size: 0.8rem;
        }

        #s1:checked+label {
            background: #ffc107;
            color: #000;
            border-color: #ffc107;
            font-weight: bold;
        }

        #s2:checked+label {
            background: #17a2b8;
            color: #fff;
            border-color: #17a2b8;
            font-weight: bold;
        }

        #s3:checked+label {
            background: #28a745;
            color: #fff;
            border-color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-dark text-white text-center p-3">
                        <h5 class="m-0 font-weight-bold text-uppercase">Lançar Conteúdo Individual</h5>
                        <small>Aluno: <?php echo $nome_aluno ?></small>
                    </div>
                    <div class="card-body p-4">
                        <form action="salvar-tecnica.php" method="POST">
                            <input type="hidden" name="id_aluno" value="<?php echo $id_aluno ?>">

                            <div class="section-title">1. Pesquisar Técnica</div>
                            <div class="input-group mb-3 shadow-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i
                                            class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" id="busca_tecnica" class="form-control border-left-0"
                                    placeholder="Ex: Passagem, Raspagem..." onkeyup="filtrarTecnicas()">
                            </div>

                            <div class="grid-select" id="lista_tecnicas"
                                style="max-height: 250px; overflow-y: auto; padding: 5px; border: 1px solid #eee; border-radius: 10px;">
                                <?php foreach ($licoes_array as $item): ?>
                                    <div class="opt-card item-tecnica">
                                        <input type="radio" name="tecnica" id="t_<?php echo md5($item['nome']) ?>"
                                            value="<?php echo $item['nome'] ?>"
                                            data-posicao="<?php echo $item['categoria'] ?>"
                                            onclick="selecionarPosicao(this)">
                                        <label for="t_<?php echo md5($item['nome']) ?>"><?php echo $item['nome'] ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="section-title">2. Módulo / Posição</div>
                            <div class="grid-select" id="container_posicoes">
                                <?php foreach ($categorias_bd as $cat):
                                    $c = $cat['categoria'];
                                ?>
                                    <div class="opt-card">
                                        <input type="radio" name="posicao" id="p_<?php echo md5($c) ?>"
                                            value="<?php echo $c ?>" required>
                                        <label for="p_<?php echo md5($c) ?>"><?php echo $c ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-group mt-3">
                                <label class="small font-weight-bold text-muted text-uppercase">Técnica Manual (Caso não
                                    esteja na lista):</label>
                                <input type="text" name="tecnica_manual" id="tecnica_manual" class="form-control"
                                    placeholder="Nome da técnica..." oninput="limparRadiosTecnica()">
                            </div>

                            <hr>

                            <div class="section-title">3. Status do Conhecimento</div>
                            <div class="row no-gutters mb-4">
                                <div class="col-4 px-1">
                                    <input type="radio" id="s1" name="status" value="Em Estudo" class="d-none" checked>
                                    <label class="status-badge" for="s1">🟡 Estudo</label>
                                </div>
                                <div class="col-4 px-1">
                                    <input type="radio" id="s2" name="status" value="Revisão" class="d-none">
                                    <label class="status-badge" for="s2">🔵 Revisão</label>
                                </div>
                                <div class="col-4 px-1">
                                    <input type="radio" id="s3" name="status" value="Concluído" class="d-none">
                                    <label class="status-badge" for="s3">🟢 Concluído</label>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="font-weight-bold d-flex justify-content-between">
                                    Nível de Domínio <span id="val_range" class="badge badge-primary">50%</span>
                                </label>
                                <input type="range" name="nivel" class="custom-range" min="0" max="100" value="50"
                                    oninput="document.getElementById('val_range').innerHTML = this.value + '%'">
                            </div>

                            <button type="submit" class="btn btn-primary btn-block py-3 font-weight-bold shadow">GRAVAR
                                SELO</button>
                            <a href="usuarios.php" class="btn btn-light btn-block border mt-2 text-muted">Voltar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filtrarTecnicas() {
            let input = document.getElementById('busca_tecnica').value.toLowerCase();
            let itens = document.getElementsByClassName('item-tecnica');
            for (let i = 0; i < itens.length; i++) {
                let label = itens[i].getElementsByTagName('label')[0].innerText.toLowerCase();
                itens[i].style.display = label.includes(input) ? "" : "none";
            }
        }

        function selecionarPosicao(el) {
            document.getElementById('tecnica_manual').value = "";
            let posicaoAlvo = el.getAttribute('data-posicao');
            let radiosPosicao = document.getElementsByName('posicao');
            for (let r of radiosPosicao) {
                if (r.value === posicaoAlvo) {
                    r.checked = true;
                    break;
                }
            }
        }

        function limparRadiosTecnica() {
            let radiosTec = document.getElementsByName('tecnica');
            for (let r of radiosTec) {
                r.checked = false;
            }
        }
    </script>
</body>

</html>