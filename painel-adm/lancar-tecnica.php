<?php
@session_start();
require_once("../conexao.php");

// 1. Validação de Segurança para Admin, Professor e Balconista
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. Recuperação de dados do Aluno
$id_aluno = $_GET['id'];
$query = $pdo->query("SELECT nome FROM usuarios WHERE id = '$id_aluno'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) == 0) {
    echo "<script>alert('Aluno não encontrado!'); window.location='usuarios.php';</script>";
    exit();
}
$nome_aluno = $res[0]['nome'];
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

        /* Estilização dos Radio Buttons como Cartões Selecionáveis */
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
            <div class="col-lg-5 col-md-8 col-sm-12">
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
                                <label class="font-weight-bold">Nome da Técnica</label>
                                <input type="text" name="tecnica" class="form-control"
                                    placeholder="Ex: Triângulo, Passagem de Guarda" required>
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold d-flex justify-content-between">
                                    Nível de Domínio <span id="val_range" class="badge badge-primary">50%</span>
                                </label>
                                <input type="range" name="nivel" class="custom-range" min="0" max="100" value="50"
                                    oninput="document.getElementById('val_range').innerHTML = this.value + '%'">
                                <small class="text-muted small">Arraste para definir o progresso.</small>
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

    <script src="../js/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>