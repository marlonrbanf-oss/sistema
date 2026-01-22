<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<div id="modo-facial">
    <div id="area-camera" style="position: relative;">
        <video id="video" autoplay muted style="width: 100%; height: 250px; object-fit: cover;"></video>
        <canvas id="overlay" style="position: absolute; top: 0; left: 0;"></canvas>
    </div>
    <div id="status-facial" class="small text-muted mb-2">Iniciando Biometria...</div>
    <button class="btn-alternar" onclick="alternarModo('senha')">Usar senha manual</button>
</div>

<script>
    // Lógica para carregar as fotos de perfil e comparar
    async function iniciarBiometria() {
        const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

        // 1. Aqui você buscaria via AJAX uma lista com {ID, NOME, FOTO_URL} dos alunos
        // Para este exemplo, vamos focar na lógica de detecção
        const video = document.getElementById('video');

        video.addEventListener('play', () => {
            const canvas = document.getElementById('overlay');
            const displaySize = {
                width: video.clientWidth,
                height: video.clientHeight
            };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptors();
                const resizedDetections = faceapi.resizeResults(detections, displaySize);

                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                faceapi.draw.drawDetections(canvas, resizedDetections);

                if (detections.length > 0) {
                    // Ao detectar um rosto, ele tira o "print" e envia para o PHP
                    // Para não sobrecarregar, você pode colocar um botão de "Confirmar Rosto"
                    document.getElementById('status-facial').innerHTML = "<span class='text-success'>ROSTO IDENTIFICADO!</span>";
                }
            }, 500);
        });
    }
</script>