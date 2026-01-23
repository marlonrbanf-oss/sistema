<?php
include_once("conexao.php");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Nova Arte BJJ - Acesso Restrito</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta charset="utf-8">

    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="manifest" href="../manifest_adm.json">

    <style>
        body {
            background-color: #1a1a1a;
            font-family: 'Source Sans Pro', sans-serif;
        }

        .login-block {
            float: left;
            width: 100%;
            padding: 80px 0;
        }

        .login-sec {
            padding: 50px 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .btn-staff {
            background-color: #000;
            border-color: #000;
            color: #fff;
            border-radius: 4px;
            padding: 12px;
            font-weight: bold;
            width: 100%;
            transition: 0.3s;
        }

        .btn-staff:hover {
            background-color: #333;
            color: #fff;
        }

        .label-input {
            font-weight: 700;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-field {
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            outline: none;
        }

        .input-field:focus {
            border-color: #000;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            font-size: 13px;
            color: #666;
        }

        .badge-staff {
            background: #000;
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 10px;
            margin-bottom: 15px;
            display: inline-block;
        }

        /* Botão de Instalação Discreto */
        #btn-instalar-adm {
            display: none;
            /* Só aparece via JS se for instalável */
            margin-top: 15px;
            font-size: 12px;
            text-decoration: underline;
            color: #000;
            cursor: pointer;
            background: none;
            border: none;
            font-weight: bold;
        }

        /* Balão iOS */
        #ios-advice-adm {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 320px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            border: 2px solid #000;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="container">
        <section class="login-block">
            <div class="row justify-content-center">
                <div class="col-md-5 login-sec text-center">
                    <div class="badge-staff">ÁREA RESTRITA</div>
                    <h4 class="mb-4" style="font-weight: 900; letter-spacing: 2px;">ADMINISTRAÇÃO</h4>

                    <form method="post" action="autenticar.php">
                        <div class="text-left">
                            <span class="label-input">E-mail Profissional</span>
                            <input class="input-field" type="text" name="username" placeholder="seu@email.com" required>

                            <span class="label-input">Senha de Acesso</span>
                            <input class="input-field" type="password" name="pass" placeholder="******" required>
                        </div>

                        <button class="btn btn-staff">AUTENTICAR EQUIPE</button>
                    </form>

                    <button id="btn-instalar-adm" onclick="dispararInstalacao()">
                        <i class="fas fa-mobile-alt"></i> INSTALAR APP GESTÃO
                    </button>

                    <a href="login.php" class="back-link"><i class="fas fa-arrow-left"></i> Voltar para Acesso Aluno</a>
                </div>
            </div>
        </section>
    </div>

    <div id="ios-advice-adm" class="text-center">
        <p class="mb-2"><strong>Instalar no iPhone</strong></p>
        <p class="small">Toque em <strong>Compartilhar</strong> <i class="fas fa-external-link-alt text-primary"></i> e
            depois em <strong>"Adicionar à Tela de Início"</strong> <i class="far fa-plus-square"></i>.</p>
        <button class="btn btn-sm btn-dark mt-2" onclick="this.parentElement.style.display='none'">Entendi</button>
    </div>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js"></script>

    <script>
        let installPrompt;
        const isIos = () => /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        const isStandalone = () => window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches;

        // 1. Ouvir evento de instalação (Android/Chrome)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            installPrompt = e;
            // Se não estiver instalado, mostra o botão de instalação
            if (!isStandalone()) {
                document.getElementById('btn-instalar-adm').style.display = 'inline-block';
            }
        });

        // 2. Mostrar botão se for iOS e não estiver instalado
        if (isIos() && !isStandalone()) {
            document.getElementById('btn-instalar-adm').style.display = 'inline-block';
        }

        function dispararInstalacao() {
            if (isIos()) {
                document.getElementById('ios-advice-adm').style.display = 'block';
            } else if (installPrompt) {
                installPrompt.prompt();
                installPrompt.userChoice.then((result) => {
                    installPrompt = null;
                    document.getElementById('btn-instalar-adm').style.display = 'none';
                });
            } else {
                alert("Para instalar este painel, use a opção 'Adicionar à tela inicial' no menu do seu navegador.");
            }
        }

        // 3. Registrar Service Worker (Necessário para PWA)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js');
        }
    </script>
</body>

</html>