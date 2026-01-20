<?php
@session_start();
require_once("../conexao.php");

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. Recuperação de dados do Aluno
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
    echo "<script>alert('Aluno não encontrado!'); window.location='usuarios.php';</script>";
    exit();
}
$nome_aluno = $res[0]['nome'];

// 3. Array de Técnicas Únicas (Extraídas das suas lições anteriores)
$tecnicas_lista = [
    "Princípio da Alavanca",
    "Gravata por trás",
    "Gangorra + Levantada técnica",
    "Defesa de tapa com queda",
    "Montada + Arm-lock",
    "Pegada pelas costas + Mata leão",
    "Rolamento de costas + Fuga de quadril",
    "Upa e roda",
    "Postura de guarda e passagem",
    "Estrangulamento montado",
    "Rolamento de frente",
    "Sprawl",
    "Abraço por cima dos braços por trás",
    "Saída de cotovelo",
    "Estrangulamento na guarda",
    "Raspagem tesourada",
    "Raspagem com mãos nos calcanhares",
    "Abraço por cima dos braços",
    "Reposição de guarda",
    "Raspagem com mão na faixa",
    "Kimura",
    "Koshi Guruma",
    "Baiana",
    "Toreando",
    "Kimura partindo do 100kg",
    "Virada de 4",
    "Americana",
    "Chave de braço girando",
    "Defesa gravata com soco",
    "Defesa de gravata lateral",
    "Montada com chave de braço",
    "Montada com estrangulamento",
    "Educativo para pé na virilha",
    "Raspagem pé na virilha",
    "Balão",
    "Arm-lock invertido",
    "Guilhotina",
    "Raspagem com pêndulo",
    "Chave de braço na guarda",
    "Empurrão no peito",
    "Montada técnica",
    "Defesa de guilhotina",
    "Joelho na barriga",
    "Baiana de joelhos",
    "Passagem para meia guarda",
    "Meia guarda para montada",
    "Abraço pela frente",
    "Estrangulamento com braço enrolado",
    "Chave de braço na guarda em pé",
    "Seat belt",
    "Retenção das costas",
    "Estrangulamento de golas",
    "Saída de gravata lateral",
    "Defesa de pegada de costas",
    "Single leg",
    "Guarda aberta / Triângulo",
    "Raspagem de tripé",
    "Defesa de jab",
    "Cinturada por trás",
    "Estrangulamento relógio",
    "Rolo de 4 apoios",
    "Varredura por dentro",
    "Passagem com joelho por dentro",
    "Ippon Seoi",
    "Raspagem de gancho",
    "Passagem de meia guarda joelho cruzado",
    "Ezequiel da montada",
    "Esgrima",
    "Passagem cruzando o joelho"
];
sort($tecnicas_lista);

// 4. Posições para o Botão Rádio
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

        .custom-range::-webkit-slider-thumb {
            background: #007bff;
        }

        .card-header {
            border-top-left-radius: 15px !important;
            border-top-right-radius: 15px !important;
        }

        .status-options .custom-control-label {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
            display: block;
        }

        .custom-control-input:checked~.custom-control-label {
            border-color: #007bff;
            background-color: rgba(0, 123, 255, 0.05);
            font-weight: bold;
        }

        /* Grid para as posições ficarem organizadas */
        .posicao-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-top: 10px;
        }

        .pos-card label {
            border: 1px solid #ccc;
            padding: 6px;
            border-radius: 5px;
            display: block;
            text-align: center;
            cursor: pointer;
            font-size: 0.8rem;
            background: #fff;
        }

        .pos-card input:checked+label {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }

        @media (max-width: 576px) {
            .container {
                padding: 10px;
                margin-top: 20px !important;
            }
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-dark text-white p-3 text-center">
                        <h5 class="m-0 font-weight-bold">
                            <i class="fas fa-stamp mr-2 text-warning"></i>LANÇAR SELO
                        </h5>
                        <small class="text-light text-uppercase">Aluno: <?php echo $nome_aluno ?></small>
                    </div>
                    <div class="card-body p-4">
                        <form action="salvar-tecnica.php" method="POST">
                            <input type="hidden" name="id_aluno" value="<?php echo $id_aluno ?>">

                            <div class="form-group mb-3">
                                <label class="font-weight-bold">Escolha a Técnica</label>
                                <select name="tecnica" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($tecnicas_lista as $item_t): ?>
                                        <option value="<?php echo $item_t ?>"><?php echo $item_t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold">Posição / Categoria</label>
                                <div class="posicao-container">
                                    <?php foreach ($posicoes_lista as $index_p => $item_p): ?>
                                        <div class="pos-card">
                                            <input type="radio" name="posicao" id="p<?php echo $index_p ?>"
                                                value="<?php echo $item_p ?>" style="display:none;" required>
                                            <label for="p<?php echo $index_p ?>"><?php echo $item_p ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

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
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="status1" name="status" value="Em Estudo"
                                            class="custom-control-input" required>
                                        <label class="custom-control-label" for="status1">🟡 Em Estudo (Amarelo)</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="status2" name="status" value="Aprendido"
                                            class="custom-control-input">
                                        <label class="custom-control-label" for="status2">🔵 Aprendido (Azul)</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="status3" name="status" value="Dominado"
                                            class="custom-control-input">
                                        <label class="custom-control-label" for="status3">🟢 Dominado (Verde)</label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm">
                                <i class="fas fa-check-circle mr-1"></i> GRAVAR SELO
                            </button>

                            <a href="usuarios.php" class="btn btn-light btn-block border mt-2">
                                <i class="fas fa-arrow-left mr-1"></i> Voltar para Usuários
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>