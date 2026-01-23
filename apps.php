<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador de Apps - Nova Arte BJJ</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <link id="manifest-link" rel="manifest" href="manifest_aluno.json">

    <style>
        body {
            background: #1a1a1a;
            color: white;
            font-family: sans-serif;
            padding-top: 50px;
        }

        .card-app {
            background: #262626;
            border: 1px solid #333;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: 0.3s;
            cursor: pointer;
            height: 100%;
        }

        .card-app:hover {
            border-color: #007bff;
            transform: translateY(-5px);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: #333;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 30px;
        }

        .btn-install {
            border-radius: 50px;
            font-weight: bold;
            margin-top: 15px;
            display: none;
        }

        .active-app {
            border-color: #00ff00 !important;
            box-shadow: 0 0 15px rgba(0, 255, 0, 0.2);
        }
    </style>
</head>

<body>

    <div class="container text-center">
        <img src="images/logo_app.png" width="100" class="mb-4 rounded shadow">
        <h2 class="font-weight-bold">Central de Aplicativos</h2>
        <p class="text-muted mb-5">Selecione qual perfil deseja instalar neste dispositivo</p>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card-app" onclick="trocarManifest('manifest_aluno.json', this)">
                    <div class="icon-box text-primary"><i class="fas fa-user-graduate"></i></div>
                    <h5>ÁREA DO ALUNO</h5>
                    <p class="small text-muted">Acesso a treinos, mensalidades e perfil.</p>
                    <button class="btn btn-primary btn-block btn-install">INSTALAR APP</button>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card-app" onclick="trocarManifest('manifest_adm.json', this)">
                    <div class="icon-box text-warning"><i class="fas fa-user-shield"></i></div>
                    <h5>GESTÃO / ADM</h5>
                    <p class="small text-muted">Controle total, financeiro e cadastros.</p>
                    <button class="btn btn-warning btn-block btn-install">INSTALAR APP</button>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card-app" onclick="trocarManifest('manifest_terminal.json', this)">
                    <div class="icon-box text-success"><i class="fas fa-desktop"></i></div>
                    <h5>TERMINAL</h5>
                    <p class="small text-muted">Check-in facial para recepção/tablet.</p>
                    <button class="btn btn-success btn-block btn-install">INSTALAR APP</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deferredPrompt;
        const manifestLink = document.getElementById('manifest-link');

        // Captura o evento de instalação do Chrome
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Mostra os botões de instalar assim que o navegador validar o PWA
            document.querySelectorAll('.btn-install').forEach(btn => btn.style.display = 'block');
        });

        function trocarManifest(arquivo, elemento) {
            // Remove destaque dos outros
            document.querySelectorAll('.card-app').forEach(c => c.classList.remove('active-app'));
            // Destaca o selecionado
            elemento.classList.add('active-app');

            // Troca o manifest dinamicamente
            manifestLink.setAttribute('href', arquivo);

            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Usuário aceitou a instalação');
                    }
                    deferredPrompt = null;
                });
            } else {
                alert(
                    "Para instalar:\n1. Use o Google Chrome\n2. Clique nos 3 pontinhos do navegador\n3. Selecione 'Instalar Aplicativo'"
                );
            }
        }

        // Registro do Service Worker obrigatório
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>

</body>

</html>