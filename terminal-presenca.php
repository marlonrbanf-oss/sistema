<?php require_once("conexao.php"); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Terminal Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            background: #111;
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: sans-serif;
        }

        .teclado-num {
            width: 350px;
            padding: 20px;
            background: #222;
            border-radius: 20px;
            border: 1px solid #333;
        }

        #area-camera {
            width: 100%;
            height: 250px;
            background: #000;
            border-radius: 15px;
            margin-bottom: 15px;
            overflow: hidden;
            border: 2px solid #444;
            position: relative;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .visor {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 15px;
            background: #000 !important;
            color: #00ff00 !important;
            border: 2px solid #444;
            height: 60px;
            letter-spacing: 5px;
        }

        .btn-num {
            height: 65px;
            font-size: 1.5rem;
            background: #333;
            color: white;
            border: 1px solid #444;
            border-radius: 12px;
            margin-bottom: 8px;
            width: 100%;
        }

        .btn-alternar {
            background: none;
            border: none;
            color: #888;
            text-decoration: underline;
            font-size: 0.9rem;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <div class="teclado-num text-center">
        <h4 id="titulo">CHECK-IN FACIAL</h4>

        <div id="modo-facial">
            <div id="area-camera">
                <video id="video" autoplay playsinline></video>
                <canvas id="canvas" style="display:none;"></canvas>
            </div>
            <button class="btn btn-success btn-lg btn-block mb-3" onclick="capturarEEnviar()">
                <i class="fas fa-camera"></i> RECONHECER AGORA
            </button>
            <button class="btn-alternar" onclick="alternarModo('senha')">Usar senha manual</button>
        </div>

        <div id="modo-senha" style="display:none;">
            <input type="password" id="senha_aluno" class="form-control visor" readonly placeholder="******">
            <div class="row no-gutters">
                <?php for ($i = 1; $i <= 9; $i++): ?>
                    <div class="col-4 p-1"><button class="btn btn-num"
                            onclick="addNum('<?php echo $i ?>')"><?php echo $i ?></button></div>
                <?php endfor; ?>
                <div class="col-4 p-1"><button class="btn btn-num btn-danger" onclick="limpar()">C</button></div>
                <div class="col-4 p-1"><button class="btn btn-num" onclick="addNum('0')">0</button></div>
                <div class="col-4 p-1"><button class="btn btn-num btn-success" onclick="confirmarSenha()">OK</button>
                </div>
            </div>
            <button class="btn-alternar mt-2" onclick="alternarModo('facial')">Voltar para Câmera</button>
        </div>

        <div id="mensagem_retorno" class="mt-3" style="min-height: 50px;"></div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        // Inicia a câmera automaticamente
        async function iniciarCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "user"
                    }
                });
                video.srcObject = stream;
            } catch (err) {
                console.error("Erro ao acessar câmera: ", err);
                alternarModo('senha');
            }
        }

        function alternarModo(modo) {
            if (modo === 'senha') {
                document.getElementById('modo-facial').style.display = 'none';
                document.getElementById('modo-senha').style.display = 'block';
                document.getElementById('titulo').innerText = 'DIGITE SUA SENHA';
            } else {
                document.getElementById('modo-facial').style.display = 'block';
                document.getElementById('modo-senha').style.display = 'none';
                document.getElementById('titulo').innerText = 'CHECK-IN FACIAL';
            }
        }

        function addNum(n) {
            document.getElementById('senha_aluno').value += n;
        }

        function limpar() {
            document.getElementById('senha_aluno').value = "";
        }

        async function capturarEEnviar() {
            const retorno = document.getElementById('mensagem_retorno');
            retorno.innerHTML = "<div class='spinner-border text-light'></div>";

            // Tira o print do vídeo
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const fotoBase64 = canvas.toDataURL('image/jpeg');

            const formData = new FormData();
            formData.append('foto', fotoBase64);
            formData.append('modo', 'facial');

            fetch('processar-checkin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.text())
                .then(t => {
                    retorno.innerHTML = t;
                })
                .catch(() => {
                    retorno.innerHTML = "Erro ao processar.";
                });
        }

        async function confirmarSenha() {
            const senha = document.getElementById('senha_aluno').value;
            const formData = new FormData();
            formData.append('senha', senha);
            formData.append('modo', 'senha');

            fetch('processar-checkin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.text())
                .then(t => {
                    document.getElementById('mensagem_retorno').innerHTML = t;
                });
        }

        iniciarCamera();
    </script>
</body>

</html>