<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<div class="modal fade" id="modalBiometria" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Cadastrar Facial: <span id="nome-aluno-bio"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <div style="position: relative; width: 100%; height: 300px; background: #000;" class="rounded mb-3 border border-secondary">
                    <video id="video-bio" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <canvas id="overlay-bio" style="position: absolute; top: 0; left: 0;"></canvas>
                </div>
                <div id="status-bio" class="alert alert-info py-1 small">Aguardando...</div>
                <input type="hidden" id="id-aluno-bio">
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btn-capturar" onclick="salvarBiometria()">
                    <i class="fas fa-camera mr-2"></i>SALVAR BIOMETRIA
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let streamBio = null;

    async function iniciarCameraBio() {
        const video = document.getElementById('video-bio');
        const status = document.getElementById('status-bio');
        const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';

        try {
            status.innerText = "Iniciando IA...";
            status.className = "alert alert-warning py-1 small";

            // Carrega os modelos se ainda não estiverem na memória
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

            status.innerText = "Abrindo câmera...";

            // Solicita acesso à câmera
            streamBio = await navigator.mediaDevices.getUserMedia({
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

            video.srcObject = streamBio;

            // Força o play do vídeo (importante para navegadores mobile)
            await video.play();

            status.innerText = "Câmera Ativa. Posicione o rosto.";
            status.className = "alert alert-success py-1 small";
        } catch (err) {
            console.error("Erro Biometria:", err);
            status.className = "alert alert-danger py-1 small";
            status.innerText = "Erro: Sem acesso à câmera ou falha na IA.";
        }
    }

    // Fecha a câmera ao fechar o modal
    $('#modalBiometria').on('hidden.bs.modal', function() {
        if (streamBio) {
            streamBio.getTracks().forEach(track => track.stop());
            streamBio = null;
        }
        document.getElementById('status-bio').innerText = "Aguardando...";
        document.getElementById('btn-capturar').disabled = false;
    });

    async function salvarBiometria() {
        const video = document.getElementById('video-bio');
        const id = document.getElementById('id-aluno-bio').value;
        const btn = document.getElementById('btn-capturar');
        const status = document.getElementById('status-bio');

        if (!video.srcObject) {
            alert("A câmera não está ativa.");
            return;
        }

        btn.disabled = true;
        status.innerText = "Analisando rosto...";

        // Captura o descriptor facial
        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (detection) {
            status.innerText = "Salvando no banco...";
            const bioData = JSON.stringify(Array.from(detection.descriptor));

            $.ajax({
                url: 'acoes/salvar_biometria.php',
                method: 'POST',
                data: {
                    id: id,
                    biometria: bioData
                },
                success: function(response) {
                    alert("Biometria cadastrada com sucesso!");
                    $('#modalBiometria').modal('hide');
                    location.reload();
                },
                error: function() {
                    alert("Erro de conexão com o servidor.");
                    btn.disabled = false;
                    status.innerText = "Erro ao salvar.";
                }
            });
        } else {
            alert("Rosto não detectado. Tente ficar em um local mais iluminado e olhe para a câmera.");
            btn.disabled = false;
            status.innerText = "Rosto não encontrado.";
        }
    }
</script>