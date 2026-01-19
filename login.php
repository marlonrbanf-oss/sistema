<?php
include_once("conexao.php");

if (isset($_POST['email2']) and $_POST['email2'] != '') {
    $email_rec = $_POST['email2'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Academia Nova Arte - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">

    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-block {
            padding: 50px 0;
        }

        .login-sec {
            padding: 50px 30px;
            background: #fff;
            border-radius: 10px 0 0 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .banner-sec {
            background: #000;
            border-radius: 0 10px 10px 0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .banner-sec img {
            max-width: 100%;
            height: auto;
        }

        .btn-primary {
            background-color: #000;
            border-color: #000;
            border-radius: 5px;
            padding: 10px;
            font-weight: bold;
            width: 100%;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background-color: #333;
            border-color: #333;
        }

        .label-input100 {
            font-weight: 700;
            font-size: 12px;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 5px;
            display: block;
        }

        .input100 {
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            outline: none;
        }

        .input100:focus {
            border-color: #000;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .copy-text {
            font-size: 13px;
            color: #666;
            text-align: center;
            margin-top: 20px;
        }

        .link-staff {
            display: block;
            text-align: center;
            margin-top: 25px;
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .link-staff:hover {
            color: #000;
            text-decoration: none;
        }

        .modal-header {
            background: #000;
            color: #fff;
            border-bottom: none;
        }

        .modal-content {
            border-radius: 10px;
            overflow: hidden;
        }

        .btn-dark-modal {
            background: #000;
            color: #fff;
            border: none;
            padding: 10px 25px;
        }
    </style>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container">
        <section class="login-block mt-5">
            <div class="container shadow-lg" style="border-radius: 10px; background: #fff;">
                <div class="row">
                    <div class="col-md-4 login-sec">
                        <h4 class="text-center mb-4" style="font-weight: 800; letter-spacing: 1px;">NOVA ARTE BJJ</h4>

                        <form method="post" action="autenticar.php">
                            <span class="label-input100">Usuário (E-mail)</span>
                            <input class="input100" type="text" name="username" id="username" placeholder="Seu e-mail"
                                required>

                            <span class="label-input100">Senha</span>
                            <input class="input100" type="password" id="pass" name="pass" placeholder="******" required>

                            <button class="btn btn-primary">ENTRAR NO PAINEL</button>
                        </form>

                        <div class="copy-text">
                            Ainda não é aluno?
                            <a href="" class="text-dark font-weight-bold" data-toggle="modal"
                                data-target="#modal-login">Matricule-se</a>
                        </div>

                        <div class="text-center mt-3">
                            <a class="text-danger small" href="" data-toggle="modal" data-target="#modal-rec">Esqueceu
                                sua senha?</a>
                        </div>

                        <a href="login_staff.php" class="link-staff"><i class="fas fa-user-shield"></i> Área da
                            Equipe</a>
                    </div>

                    <div class="col-md-8 banner-sec d-none d-md-flex">
                        <img src="images/34.png" alt="Academia Nova Arte">
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="modal-login" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Cadastro de Aluno</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="post" id="form-cadastro">
                        <div class="form-group">
                            <label class="text-dark font-weight-bold small">NOME COMPLETO</label>
                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome do aluno"
                                required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-dark font-weight-bold small">CPF</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf"
                                        placeholder="000.000.000-00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-dark font-weight-bold small">TELEFONE / WHATSAPP</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone"
                                        placeholder="(00) 00000-0000" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="text-dark font-weight-bold small">E-MAIL (SERÁ SEU USUÁRIO)</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                value="<?php echo @$email_rec ?>">
                        </div>

                        <div class="form-group">
                            <label class="text-dark font-weight-bold small">ENDEREÇO</label>
                            <input type="text" class="form-control" id="endereco" name="endereco"
                                placeholder="Rua, Número, Bairro" required>
                        </div>

                        <div class="form-group">
                            <label class="text-dark font-weight-bold small">CRIE UMA SENHA</label>
                            <input type="password" class="form-control" id="senha" name="senha"
                                placeholder="Mínimo 6 caracteres" required>
                        </div>

                        <div align="center" id="mensagem" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                    <button id="btn-cadastro" class="btn btn-dark-modal">FINALIZAR MATRÍCULA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-rec" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Recuperar Senha</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="form-group">
                            <label class="text-dark">Digite seu e-mail cadastrado</label>
                            <input type="email" class="form-control" id="email-recuperar" name="email-recuperar"
                                required>
                        </div>
                        <div align="center" id="mensagem2"></div>
                </div>
                <div class="modal-footer">
                    <button id="btn-rec" class="btn btn-dark-modal">ENVIAR SENHA</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>
    <script src="js/mascaras.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#btn-cadastro').click(function(event) {
                event.preventDefault();
                $.ajax({
                    url: "cadastrar-usuario.php",
                    method: "post",
                    data: $('#form-cadastro').serialize(),
                    dataType: "text",
                    success: function(mensagem) {
                        $('#mensagem').removeClass()
                        if (mensagem.trim() == 'Cadastrado com Sucesso!!') {
                            $('#mensagem').addClass('text-success')
                            document.getElementById('username').value = document.getElementById(
                                'email').value;
                            document.getElementById('pass').value = document.getElementById(
                                'senha').value;
                            alert("Cadastro realizado! Você já pode entrar.");
                            $('#modal-login').modal('hide');
                        } else {
                            $('#mensagem').addClass('text-danger')
                            $('#mensagem').text(mensagem)
                        }
                    },
                })
            })
        })
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#btn-rec').click(function(event) {
                event.preventDefault();
                $.ajax({
                    url: "recuperar.php",
                    method: "post",
                    data: $('form').serialize(),
                    dataType: "text",
                    success: function(mensagem) {
                        $('#mensagem2').removeClass()
                        if (mensagem.trim() == 'Senha enviada para o seu Email!') {
                            $('#mensagem2').addClass('text-success')
                            $('#email-recuperar').val('')
                        } else {
                            $('#mensagem2').addClass('text-danger')
                        }
                        $('#mensagem2').text(mensagem)
                    },
                })
            })
        })
    </script>
</body>

</html>