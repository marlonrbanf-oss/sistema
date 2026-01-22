<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<div class="teclado-num text-center">
    <h4 id="titulo">IDENTIFICANDO ATLETA...</h4>

    <div id="modo-facial">
        <div id="area-camera">
            <video id="video" autoplay muted playsinline></video>
            <canvas id="overlay" style="position: absolute; top: 0; left: 0;"></canvas>
        </div>
        <div id="status-ia" class="small text-muted mb-2">Carregando biometria...</div>
        <button class="btn-alternar" onclick="alternarModo('senha')">Usar senha manual</button>
    </div>

    <div id="modo-senha" style="display:none;">
        <input type="password" id="senha_aluno" class="form-control visor" readonly placeholder="******">
        <button class="btn btn-num btn-success" onclick="confirmarSenha()">OK</button>
        <button class="btn-alternar mt-2" onclick="alternarModo('facial')">Voltar para Biometria</button>
    </div>

    <div id="mensagem_retorno" class="mt-3"></div>
</div>

<script>
    let faceMatcher;
    let scanAtivo = true;

    async function iniciarTerminal() {
        const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';

        // Carrega a inteligência
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);

        // Busca biometrias cadastradas no banco
        const res = await fetch('listar-biometrias.php');
        const dadosAlunos = await res.json();

        if (dadosAlunos.length > 0) {
            const labeledDescriptors = dadosAlunos.map(d => {
                return new faceapi.LabeledFaceDescriptors(d.id, [new Float32Array(Object.values(JSON.parse(d
                    .biometria)))]);
            });
            faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
            document.getElementById('status-ia').innerText = "Biometria Pronta!";
        }

        iniciarCamera();
    }

    async function iniciarCamera() {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {}
        });
        video.srcObject = stream;
    }

    video.addEventListener('play', () => {
        const canvas = document.getElementById('overlay');
        const displaySize = {
            width: video.clientWidth,
            height: video.clientHeight
        };
        faceapi.matchDimensions(canvas, displaySize);

        setInterval(async () => {
            if (!scanAtivo || !faceMatcher) return;

            const detections = await faceapi.detectAllFaces(video, new faceapi
                .TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
            const resizedDetections = faceapi.resizeResults(detections, displaySize);

            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

            resizedDetections.forEach(detection => {
                const result = faceMatcher.findBestMatch(detection.descriptor);
                if (result.label !== 'unknown') {
                    reconhecimentoSucesso(result.label);
                }
            });
        }, 600);
    });

    function reconhecimentoSucesso(id) {
        scanAtivo = false; // Para o scan para não repetir
        const formData = new FormData();
        formData.append('id_facial', id);

        fetch('processar-checkin.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(t => {
                document.getElementById('mensagem_retorno').innerHTML = t;
                setTimeout(() => {
                    scanAtivo = true;
                    document.getElementById('mensagem_retorno').innerHTML = "";
                }, 5000);
            });
    }

    iniciarTerminal();
</script>