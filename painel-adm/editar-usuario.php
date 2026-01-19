<?php
@session_start();
require_once("../conexao.php");

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = $_GET['id'];

// 2. Busca dados atuais
$query = $pdo->query("SELECT u.*, g.cor_faixa, g.graus FROM usuarios as u LEFT JOIN graduacoes as g ON u.id = g.usuario_id WHERE u.id = '$id_usuario'");
$res = $query->fetchAll(PDO::FETCH_ASSOC);

if (count($res) > 0) {
    $nome = $res[0]['nome'];
    $usuario = $res[0]['usuario'];
    $cpf = $res[0]['cpf'];
    $senha = $res[0]['senha'];

    // Tratamento para garantir a seleção (minúsculo e sem espaços)
    $nivel_atual = strtolower(trim($res[0]['nivel']));
    $faixa_atual = strtolower(trim($res[0]['cor_faixa'] ?? 'branca'));
    $graus_atual = $res[0]['graus'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Editar Usuário - Nova Arte</title>
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
            border: none;
        }

        /* Estilização das Caixas de Seleção */
        .opcoes-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background: #fff;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
        }

        .item-selecao {
            display: flex;
            align-items: center;
            cursor: pointer;
            margin-bottom: 0;
            font-weight: 500;
        }

        .item-selecao input {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .secao-titulo {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #6c757d;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: block;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-11">
                <div class="card shadow-lg">
                    <div class="card-header bg-dark text-white text-center p-3">
                        <h5 class="m-0"><i class="fas fa-user-edit mr-2 text-warning"></i>EDITAR USUÁRIO</h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="salvar-usuario.php">
                            <input type="hidden" name="id" value="<?php echo $id_usuario ?>">

                            <div class="form-group mb-4">
                                <label class="font-weight-bold">Nome Completo</label>
                                <input type="text" name="nome" class="form-control" value="<?php echo $nome ?>"
                                    required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-bold">CPF</label>
                                    <input type="text" name="cpf" class="form-control" value="<?php echo $cpf ?>"
                                        required>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-bold">E-mail (Login)</label>
                                    <input type="email" name="usuario" class="form-control"
                                        value="<?php echo $usuario ?>" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-5 form-group">
                                    <label class="font-weight-bold">Senha</label>
                                    <input type="text" name="senha" class="form-control" value="<?php echo $senha ?>"
                                        required>
                                </div>
                                <div class="col-md-7">
                                    <label class="secao-titulo font-weight-bold">Nível de Acesso</label>
                                    <div class="opcoes-flex">
                                        <label class="item-selecao">
                                            <input type="radio" name="nivel" value="Cliente"
                                                <?php if ($nivel_atual == 'cliente' || $nivel_atual == 'aluno') echo 'checked'; ?>
                                                required> Aluno
                                        </label>
                                        <label class="item-selecao">
                                            <input type="radio" name="nivel" value="Professor"
                                                <?php if ($nivel_atual == 'professor' || $nivel_atual == 'balconista') echo 'checked'; ?>>
                                            Professor
                                        </label>
                                        <label class="item-selecao">
                                            <input type="radio" name="nivel" value="Admin"
                                                <?php if ($nivel_atual == 'admin' || $nivel_atual == 'administrador') echo 'checked'; ?>>
                                            Admin
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="secao_graduacao"
                                style="display: <?php echo ($nivel_atual == 'cliente' || $nivel_atual == 'aluno') ? 'block' : 'none'; ?>;">
                                <hr class="my-4">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="secao-titulo font-weight-bold"><i
                                                class="fas fa-medal mr-1"></i>Cor da Faixa</label>
                                        <div class="opcoes-flex">
                                            <label class="item-selecao"><input type="radio" name="cor_faixa"
                                                    value="Branca"
                                                    <?php if ($faixa_atual == 'branca') echo 'checked'; ?>>
                                                Branca</label>
                                            <label class="item-selecao"><input type="radio" name="cor_faixa"
                                                    value="Azul" <?php if ($faixa_atual == 'azul') echo 'checked'; ?>>
                                                Azul</label>
                                            <label class="item-selecao"><input type="radio" name="cor_faixa"
                                                    value="Roxa" <?php if ($faixa_atual == 'roxa') echo 'checked'; ?>>
                                                Roxa</label>
                                            <label class="item-selecao"><input type="radio" name="cor_faixa"
                                                    value="Marrom"
                                                    <?php if ($faixa_atual == 'marrom') echo 'checked'; ?>>
                                                Marrom</label>
                                            <label class="item-selecao"><input type="radio" name="cor_faixa"
                                                    value="Preta" <?php if ($faixa_atual == 'preta') echo 'checked'; ?>>
                                                Preta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group mt-2">
                                        <label class="font-weight-bold">Graus na Faixa</label>
                                        <input type="number" name="graus" class="form-control"
                                            value="<?php echo $graus_atual ?>" min="0" max="9">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold shadow-sm">
                                    <i class="fas fa-check-circle mr-2"></i>ATUALIZAR DADOS
                                </button>
                                <a href="usuarios.php" class="btn btn-light btn-block border mt-2">
                                    <i class="fas fa-arrow-left mr-1"></i> Voltar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Gerencia a exibição da graduação
        $('input[name="nivel"]').change(function() {
            if ($(this).val() == 'Cliente') {
                $('#secao_graduacao').fadeIn();
            } else {
                $('#secao_graduacao').fadeOut();
            }
        });
    </script>
</body>

</html>