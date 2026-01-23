<?php require_once("conexao.php"); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Terminal de Presença - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <link rel="manifest" href="manifest_terminal.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#111111">

    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Check-in BJJ">
    <link rel="apple-touch-icon" href="images/logo_app.png">

    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">

    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

    <style>
        body {
            background: #111;
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: sans-serif;
            overflow: hidden;
            margin: 0;
        }

        .teclado-num {
            width: 350px;
            padding: 20px;
            background: #222;
            border-radius: 20px;
            border: 1px solid #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        #area-camera {
            position: relative;
            width: 100%;
            height: 250px;
            background: #000;
            border-radius: 15px;
            margin-bottom: 15px;
            overflow: hidden;
            border: 2px solid #444;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
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
            height: 60px;
            font-size: 1.5rem;
            background: #333;
            color: white;
            border: 1px solid #444;
            border-radius: 12px;
            margin-bottom: 8px;
            width: 100%;
            transition: 0.2s;
        }

        .btn-num:active {
            background: #555;
            transform: scale(0.95);
        }

        .btn-alternar {
            background: none;
            border: none;
            color: #888;
            text-decoration: underline;
            font-size: 0.9rem;
            cursor: pointer;
        }

        #status-ia {
            font-weight: bold;
            min-height: 20px;
        }
    </style>
</head>

<body>

    <div class="teclado-num text-center">
        <h4 id="titulo" class="mb-3">CHECK-IN</h4>

        <div id="modo-facial">
            <div id="area-camera">
                <video id="video" autoplay muted playsinline></video>
                <canvas id="overlay"></canvas>
            </div>
            <div id="status-ia" class="small text-muted mb-2">Carregando IA...</div>
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
            <button class="btn-alternar mt-3" onclick="alternarModo('facial')">Voltar para Câmera</button>
        </div>

        <div id="mensagem_retorno" class="mt-3" style="min-height: 50px;"></div>
    </div>

    <script>
        // Registro do Service Worker para PWA
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('Service Worker registrado!'))
                .catch(err => console.log('Erro no Service Worker', err));
        }

        const video = document.getElementById('video');
        const canvas = document.getElementById('overlay');
        const statusIA = document.getElementById('status-ia');
        let faceMatcher = null;
        let scanAtivo = true;

        async function iniciarTerminal() {
            const MODEL_URL = 'models';

            try {
                statusIA.innerText = "Sincronizando modelos...";
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

                statusIA.innerText = "Buscando dados...";
                const res = await fetch('listar-biometrias.php');
                const dadosAlunos = await res.json();

                if (dadosAlunos.length > 0) {
                    const labeledDescriptors = dadosAlunos.map(d => {
                        const descriptorArray = new Float32Array(JSON.parse(d.biometria));
                        return new faceapi.LabeledFaceDescriptors(d.id.toString(), [descriptorArray]);
                    });
                    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
                    statusIA.innerText = "Aproxime o rosto";
                    statusIA.className = "small text-success mb-2";
                } else {
                    statusIA.innerText = "Nenhum aluno cadastrado.";
                    statusIA.className = "small text-warning mb-2";
                }

                iniciarCamera();

            } catch (err) {
                console.error("Erro Crítico:", err);
                statusIA.innerText = "Erro ao iniciar IA local.";
                statusIA.className = "small text-danger mb-2";
            }
        }

        async function iniciarCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "user",
                        width: {
                            ideal: 640
                        },
                        height: {
                            ideal: 480
                        }
                    }
                });
                video.srcObject = stream;
                await video.play();
            } catch (err) {
                console.error("Erro Câmera:", err);
                statusIA.innerText = "Acesso à câmera negado.";
                alternarModo('senha');
            }
        }

        video.addEventListener('play', () => {
            const displaySize = {
                width: video.clientWidth,
                height: video.clientHeight
            };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                if (!scanAtivo || !faceMatcher) return;

                const detections = await faceapi.detectAllFaces(video, new faceapi
                        .TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                faceapi.draw.drawDetections(canvas, resizedDetections);

                resizedDetections.forEach(detection => {
                    const result = faceMatcher.findBestMatch(detection.descriptor);
                    if (result.label !== 'unknown') {
                        processarCheckin(result.label);
                    }
                });
            }, 600);
        });

        function processarCheckin(id) {
            scanAtivo = false;
            const msg = document.getElementById('mensagem_retorno');
            msg.innerHTML = "<div class='spinner-border text-light spinner-border-sm'></div> Validando...";

            const formData = new FormData();
            formData.append('id_facial', id);

            fetch('processar-checkin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.text())
                .then(t => {
                    msg.innerHTML = t;
                    setTimeout(() => {
                        msg.innerHTML = "";
                        scanAtivo = true;
                    }, 5000);
                });
        }

        function confirmarSenha() {
            const senha = document.getElementById('senha_aluno').value;
            const msg = document.getElementById('mensagem_retorno');
            const formData = new FormData();
            formData.append('senha', senha);

            fetch('processar-checkin.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.text())
                .then(t => {
                    msg.innerHTML = t;
                    limpar();
                    if (t.includes('alert-success')) {
                        setTimeout(() => {
                            alternarModo('facial');
                            msg.innerHTML = "";
                        }, 3000);
                    }
                });
        }

        function alternarModo(modo) {
            document.getElementById('modo-facial').style.display = modo === 'facial' ? 'block' : 'none';
            document.getElementById('modo-senha').style.display = modo === 'senha' ? 'block' : 'none';
            document.getElementById('titulo').innerText = modo === 'facial' ? 'CHECK-IN' : 'DIGITE A SENHA';
            document.getElementById('mensagem_retorno').innerHTML = "";
        }

        function addNum(n) {
            document.getElementById('senha_aluno').value += n;
        }

        function limpar() {
            document.getElementById('senha_aluno').value = "";
        }

        iniciarTerminal();
    </script>
</body>

</html>