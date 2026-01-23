<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Instalador Nova Arte BJJ</title>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link id="manifest-link" rel="manifest" href="manifest_aluno.json">
    <style>
        body {
            background: #111;
            color: white;
            padding-top: 50px;
        }

        .card-app {
            background: #222;
            border: 2px solid #333;
            border-radius: 20px;
            padding: 25px;
            transition: 0.3s;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .selected {
            border-color: #007bff;
            background: #2a2a2a;
        }

        .icon-box {
            font-size: 40px;
            margin-bottom: 10px;
        }

        #install-btn {
            display: none;
            border-radius: 50px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container text-center">
        <h3 class="font-weight-bold mb-4">Instalar Aplicativo</h3>
        <div class="row">
            <div class="col-12">
                <div class="card-app selected" onclick="mudarApp('manifest_aluno.json', this)">
                    <div class="icon-box text-primary"><i class="fas fa-user"></i></div>
                    <h5>ÁREA DO ALUNO</h5>
                </div>
            </div>
            <div class="col-12">
                <div class="card-app" onclick="mudarApp('manifest_adm.json', this)">
                    <div class="icon-box text-warning"><i class="fas fa-user-shield"></i></div>
                    <h5>PAINEL ADM</h5>
                </div>
            </div>
        </div>
        <button id="install-btn" class="btn btn-primary btn-lg btn-block mt-4 shadow">INSTALAR SELECIONADO</button>
    </div>
    <script>
        let deferredPrompt;
        const btn = document.getElementById('install-btn');
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            btn.style.display = 'block';
        });

        function mudarApp(manifest, elem) {
            document.querySelectorAll('.card-app').forEach(c => c.classList.remove('selected'));
            elem.classList.add('selected');
            document.getElementById('manifest-link').setAttribute('href', manifest);
        }
        btn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                await deferredPrompt.userChoice;
                deferredPrompt = null;
                btn.style.display = 'none';
            }
        });
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>

</html>