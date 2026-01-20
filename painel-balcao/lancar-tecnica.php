<?php
@session_start();
require_once("../conexao.php");

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

if (count($res) == 0) {
    exit();
}
$nome_aluno = $res[0]['nome'];

// Mapeamento de Técnicas x Posições (Ajuste conforme sua preferência)
$mapa_tecnicas = [
    'Quedas' => ['Baiana', 'Single leg', 'Ippon Seoi', 'Koshi Guruma', 'Sprawl', 'Defesa de tapa com queda'],
    'passagem de guarda' => ['Toreando', 'Passagem com joelho por dentro', 'Passagem de meia guarda joelho cruzado', 'Postura de guarda e passagem'],
    'guarda fechada por baixo' => ['Raspagem tesourada', 'Chave de braço na guarda', 'Triângulo', 'Kimura', 'Guilhotina'],
    'montada ataque (por cima)' => ['Ezequiel da montada', 'Arm-lock', 'Estrangulamento montado', 'Americana'],
    'Defesa Pessoal' => ['Gravata por trás', 'Abraço por trás', 'Saída de gravata lateral', 'Defesa de jab'],
    'Fundamentos' => ['Princípio da Alavanca', 'Rolamento de frente', 'Fuga de quadril', 'Levantada técnica']
];

$posicoes_lista = [
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
        }
    </style>
</head>

<body>
    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-dark text-white text-center p-3">
                        <h5 class="m-0 font-weight-bold">LANÇAR SELO</h5>
                        <small>Aluno: <?php echo $nome_aluno ?></small>
                    </div>
                    <div class="card-body p-4">
                        <form action="salvar-tecnica.php" method="POST">
                            <input type="hidden" name="id_aluno" value="<?php echo $id_aluno ?>">

                            <div class="section-title">Escolha a Técnica</div>
                            <div class="grid-select">
                                <?php foreach ($mapa_tecnicas as $pos_ref => $tecs): ?>
                                    <?php foreach ($tecs as $t): ?>
                                        <div class="opt-card">
                                            <input type="radio" name="tecnica" id="t_<?php echo md5($t) ?>"
                                                value="<?php echo $t ?>" data-posicao="<?php echo $pos_ref ?>"
                                                onclick="selecionarPosicao(this)">
                                            <label for="t_<?php echo md5($t) ?>"><?php echo $t ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold">Outras Técnicas (Escrita manual)</label>
                                <input type="text" name="tecnica_manual" id="tecnica_manual" class="form-control"
                                    placeholder="Digite aqui se não estiver acima..." oninput="limparRadiosTecnica()">
                            </div>

                            <div class="section-title">Posição / Categoria</div>
                            <div class="grid-select" id="container_posicoes">
                                <?php foreach ($posicoes_lista as $idx => $p): ?>
                                    <div class="opt-card">
                                        <input type="radio" name="posicao" id="p_<?php echo $idx ?>"
                                            value="<?php echo $p ?>" required>
                                        <label for="p_<?php echo $idx ?>"><?php echo $p ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold d-flex justify-content-between">
                                    Nível de Domínio <span id="val_range" class="badge badge-primary">50%</span>
                                </label>
                                <input type="range" name="nivel" class="custom-range" min="0" max="100" value="50"
                                    oninput="document.getElementById('val_range').innerHTML = this.value + '%'">
                            </div>

                            <div class="form-group mb-4">
                                <label class="font-weight-bold mb-2">Status do Selo</label>
                                <div class="status-options">
                                    <div class="custom-radio custom-control">
                                        <input type="radio" id="s1" name="status" value="Em Estudo"
                                            class="custom-control-input" required>
                                        <label class="custom-control-label" for="s1">🟡 Em Estudo</label>
                                    </div>
                                    <div class="custom-radio custom-control">
                                        <input type="radio" id="s2" name="status" value="Aprendido"
                                            class="custom-control-input">
                                        <label class="custom-control-label" for="s2">🔵 Aprendido</label>
                                    </div>
                                    <div class="custom-radio custom-control">
                                        <input type="radio" id="s3" name="status" value="Dominado"
                                            class="custom-control-input">
                                        <label class="custom-control-label" for="s3">🟢 Dominado</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold">GRAVAR
                                SELO</button>
                            <a href="usuarios.php" class="btn btn-light btn-block border mt-2">Voltar</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selecionarPosicao(el) {
            // Limpa o campo manual ao selecionar uma técnica pronta
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
            // Desmarca os botões de rádio da técnica se o professor escrever manualmente
            let radiosTec = document.getElementsByName('tecnica');
            for (let r of radiosTec) {
                r.checked = false;
            }
        }
    </script>
</body>

</html>