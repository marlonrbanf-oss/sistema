<?php
include_once("conexao.php");
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalar Aplicativos - Nova Arte BJJ</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #f4f6f9;
            font-family: sans-serif;
            overflow-x: hidden;
        }

        .app-card {
            background: #fff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: 0.3s;
            position: relative;
        }

        .app-card:hover {
            transform: translateY(-5px);
        }

        .app-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            margin-bottom: 15px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        .btn-install {
            background: #000;
            color: #fff;
            border-radius: 50px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 13px;
            padding: 10px 25px;
            border: none;
            width: 100%;
            transition: 0.3s;
        }

        .btn-install:active {
            background: #333;
        }

        /* Balão de Instrução iOS */
        #ios-ready-advice {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 350px;
            background: #fff;
            color: #333;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            border: 2px solid #000;
        }

        #ios-ready-advice::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            margin-left: -10px;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 10px solid #000;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="font-weight-bold">CENTRAL DE APPS</h2>
            <p class="text-muted">Instale o acesso direto em sua tela inicial</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="app-card">
                    <img src="images/logo_aluno.png" class="app-icon" onerror="this.src='images/icon-512.png'">
                    <h5>Portal do Aluno</h5>
                    <button class="btn-install" onclick="iniciarInstalacao('manifest_aluno.json')">Instalar App</button>
                </div>
            </div>

            <div class="col-md-4">
                <div class="app-card">
                    <img src="images/logo_adm.png" class="app-icon" onerror="this.src='images/icon-512.png'">
                    <h5>Painel Administrativo</h5>
                    <button class="btn-install" onclick="iniciarInstalacao('manifest_adm.json')">Instalar App</button>
                </div>
            </div>

            <div class="col-md-4">
                <div class="app-card">
                    <img src="images/logo_terminal.png" class="app-icon" onerror="this.src='images/icon-512.png'">
                    <h5>Terminal Check-in</h5>
                    <button class="btn-install" onclick="iniciarInstalacao('manifest_terminal.json')">Instalar App</button>
                </div>
            </div>
        </div>
    </div>

    <div id="ios-ready-advice">
        <div class="text-right">
            <button class="close" onclick="document.getElementById('ios-ready-advice').style.display='none'">&times;</button>
        </div>
        <div class="text-center">
            <p class="mb-2"><strong>Instalar no iPhone</strong></p>
            <p class="small">1. Toque no ícone de <strong>Compartilhar</strong> <i class="fas fa-external-link-alt text-primary"></i> (na barra inferior).</p>
            <p class="small">2. Role para baixo e toque em <strong>"Adicionar à Tela de Início"</strong> <i class="far fa-plus-square"></i>.</p>
        </div>
    </div>



    <script>
        let deferredPrompt;
        const isIos = () => {
            const userAgent = window.navigator.userAgent.toLowerCase();
            return /iphone|ipad|ipod/.test(userAgent);
        }
        const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

        // Captura evento no Android/Chrome
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        function iniciarInstalacao(manifestFile) {
            // Troca o manifest para o selecionado
            let link = document.querySelector("link[rel*='manifest']") || document.createElement('link');
            link.rel = 'manifest';
            link.href = manifestFile;
            document.getElementsByTagName('head')[0].appendChild(link);

            if (isIos()) {
                if (!isInStandaloneMode()) {
                    document.getElementById('ios-ready-advice').style.display = 'block';
                } else {
                    alert("O App já está instalado!");
                }
            } else if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Usuário aceitou a instalação');
                    }
                    deferredPrompt = null;
                });
            } else {
                alert("Para instalar:\n1. Clique nos 3 pontos do navegador.\n2. Escolha 'Adicionar à tela inicial' ou 'Instalar Aplicativo'.");
            }
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>

</body>

</html>