<?php
@session_start();
require_once("../conexao.php");

// 1. Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$id_usuario = isset($_GET['id']) ? $_GET['id'] : '';

// Inicialização de variáveis para evitar erros em branco
$nome = $usuario = $cpf = $senha = $telefone = $data_venc = "";
$nivel_atual = "cliente";
$faixa_atual = "branca";
$graus_atual = 0;
$aulas_atual = 0;

// 2. Busca dados atuais se houver um ID
if ($id_usuario != "") {
    $query = $pdo->prepare("SELECT u.*, g.cor_faixa, g.graus, g.total_aulas 
                            FROM usuarios as u 
                            LEFT JOIN graduacoes as g ON u.id = g.usuario_id 
                            WHERE u.id = :id");
    $query->bindValue(":id", $id_usuario);
    $query->execute();
    $res = $query->fetchAll(PDO::FETCH_ASSOC);

    if (count($res) > 0) {
        $nome = $res[0]['nome'];
        $usuario = $res[0]['usuario'];
        $cpf = $res[0]['cpf'];
        $senha = $res[0]['senha'];
        $telefone = $res[0]['telefone'] ?? '';
        $data_venc = $res[0]['data_venc'] ?? '';

        $nivel_atual = strtolower(trim($res[0]['nivel']));
        $faixa_atual = strtolower(trim($res[0]['cor_faixa'] ?? 'branca'));
        $graus_atual = $res[0]['graus'] ?? 0;
        $aulas_atual = $res[0]['total_aulas'] ?? 0;
    }
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
                                <input type="text" name="nome" class="form-control" value="<?php echo $nome ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-bold">CPF</label>
                                    <input type="text" name="cpf" class="form-control" value="<?php echo $cpf ?>">
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-bold">E-mail (Login)</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo $usuario ?>" required>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Telefone</label>
                                    <input type="text" name="telefone" class="form-control" value="<?php echo $telefone ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Data de Vencimento</label>
                                    <input type="date" name="data_venc" class="form-control" value="<?php echo $data_venc ?>">
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-5 form-group">
                                    <label class="font-weight-bold">Senha</label>
                                    <input type="text" name="senha" class="form-control" value="<?php echo $senha ?>" required>
                                </div>
                                <div class="col-md-7">
                                    <label class="secao-titulo font-weight-bold">Nível de Acesso</label>
                                    <div class="opcoes-flex">
                                        <label class="item-selecao">
                                            <input type="radio" name="nivel" value="Cliente" <?php if ($nivel_atual == 'cliente') echo 'checked'; ?> required> Aluno
                                        </label>
                                        <label class="item-selecao">
                                            <input type="radio" name="nivel" value="Professor" <?php if ($nivel_atual == 'professor') echo 'checked'; ?>> Professor
                                        </label>
                                        <label class="item-selecao">
                                            <input type="radio" name="nivel" value="Admin" <?php if ($nivel_atual == 'admin') echo 'checked'; ?>> Admin
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="secao_graduacao" style="display: <?php echo ($nivel_atual == 'cliente') ? 'block' : 'none'; ?>;">
                                <hr class="my-4">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="secao-titulo font-weight-bold"><i class="fas fa-medal mr-1"></i>Cor da Faixa</label>
                                        <div class="opcoes-flex">
                                            <?php
                                            $faixas = ['Branca', 'Azul', 'Roxa', 'Marrom', 'Preta'];
                                            foreach ($faixas as $f):
                                                $selected = ($faixa_atual == strtolower($f)) ? 'checked' : '';
                                            ?>
                                                <label class="item-selecao">
                                                    <input type="radio" name="cor_faixa" value="<?php echo $f ?>" <?php echo $selected ?>> <?php echo $f ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group mt-2">
                                        <label class="font-weight-bold">Graus na Faixa</label>
                                        <input type="number" name="graus" class="form-control" value="<?php echo $graus_atual ?>" min="0" max="4">
                                    </div>
                                    <div class="col-md-6 form-group mt-2">
                                        <label class="font-weight-bold">Total de Aulas (Frequência)</label>
                                        <input type="number" name="total_aulas" class="form-control" value="<?php echo $aulas_atual ?>" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary btn-block py-2 font-weight-bold shadow-sm">
                                    <i class="fas fa-check-circle mr-2"></i>SALVAR ALTERAÇÕES
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
        // Lógica para mostrar/esconder graduação baseado no nível
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