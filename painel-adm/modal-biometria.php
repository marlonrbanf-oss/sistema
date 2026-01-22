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
                <div style="position: relative; width: 100%; height: 300px; background: #000;" class="rounded mb-3">
                    <video id="video-bio" autoplay muted playsinline
                        style="width: 100%; height: 100%; object-fit: cover;"></video>
                    <canvas id="overlay-bio" style="position: absolute; top: 0; left: 0;"></canvas>
                </div>
                <div id="status-bio" class="alert alert-info py-1 small">Aguardando detecção...</div>
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

        try {
            // Garante que os modelos estão carregados (caso ainda não estejam)
            const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
            if (!faceapi.nets.tinyFaceDetector.params) {
                status.innerText = "Carregando Inteligência...";
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            }

            streamBio = await navigator.mediaDevices.getUserMedia({
                video: {}
            });
            video.srcObject = streamBio;
            status.innerText = "Câmera Ativa. Posicione o rosto.";
        } catch (err) {
            status.className = "alert alert-danger py-1 small";
            status.innerText = "Erro ao acessar câmera.";
        }
    }

    // Para a câmera ao fechar o modal para não gastar recurso
    $('#modalBiometria').on('hidden.bs.modal', function() {
        if (streamBio) {
            streamBio.getTracks().forEach(track => track.stop());
        }
    });

    async function salvarBiometria() {
        const video = document.getElementById('video-bio');
        const id = document.getElementById('id-aluno-bio').value;
        const btn = document.getElementById('btn-capturar');
        const status = document.getElementById('status-bio');

        btn.disabled = true;
        status.innerText = "Processando biometria...";

        // Detecta o rosto e gera o Descriptor (assinatura numérica)
        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (detection) {
            // Converte o Float32Array para uma string JSON para salvar no banco
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
                    location.reload(); // Recarrega para atualizar o status na lista
                },
                error: function() {
                    alert("Erro ao salvar no servidor.");
                    btn.disabled = false;
                }
            });
        } else {
            alert("Nenhum rosto detectado. Tente centralizar mais o rosto.");
            btn.disabled = false;
            status.innerText = "Erro: Rosto não encontrado.";
        }
    }
</script>