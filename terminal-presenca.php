<?php require_once("conexao.php"); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Terminal de Presença - Nova Arte</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
    body {
        background: #111;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        font-family: sans-serif;
        overflow: hidden;
    }

    .teclado-num {
        width: 350px;
        padding: 25px;
        background: #222;
        border-radius: 20px;
        border: 1px solid #333;
    }

    .visor {
        font-size: 2.5rem;
        text-align: center;
        margin-bottom: 20px;
        background: #000 !important;
        color: #00ff00 !important;
        border: 2px solid #444;
        height: 70px;
        letter-spacing: 8px;
        font-weight: bold;
    }

    .btn-num {
        height: 75px;
        font-size: 1.8rem;
        font-weight: bold;
        background: #333;
        color: white;
        border: 1px solid #444;
        border-radius: 12px;
        margin-bottom: 8px;
        width: 100%;
        cursor: pointer;
    }

    .btn-num:active {
        background: #555;
        transform: scale(0.95);
    }

    .btn-success {
        background: #198754 !important;
    }

    .btn-danger {
        background: #dc3545 !important;
    }
    </style>
</head>

<body>

    <div class="teclado-num text-center">
        <div class="mb-3">
            <h4 class="text-white">DIGITE SUA SENHA</h4>
            <small class="text-muted">Acesso exclusivo para alunos</small>
        </div>

        <input type="password" id="senha_aluno" class="form-control visor" readonly placeholder="******">

        <div class="row no-gutters">
            <?php for($i=1; $i<=9; $i++): ?>
            <div class="col-4 p-1">
                <button type="button" class="btn btn-num" onclick="addNum('<?php echo $i ?>')"><?php echo $i ?></button>
            </div>
            <?php endfor; ?>

            <div class="col-4 p-1"><button type="button" class="btn btn-num btn-danger" onclick="limpar()">C</button>
            </div>
            <div class="col-4 p-1"><button type="button" class="btn btn-num" onclick="addNum('0')">0</button></div>
            <div class="col-4 p-1"><button type="button" class="btn btn-num btn-success"
                    onclick="confirmar()">OK</button></div>
        </div>
        <div id="mensagem_retorno" class="mt-3" style="min-height: 50px;"></div>
    </div>

    <script>
    // JS PURO - NÃO DEPENDE DE JQUERY
    function addNum(n) {
        const visor = document.getElementById('senha_aluno');
        if (visor.value.length < 10) visor.value += n;
    }

    function limpar() {
        document.getElementById('senha_aluno').value = "";
        document.getElementById('mensagem_retorno').innerHTML = "";
    }

    async function confirmar() {
        const senhaInput = document.getElementById('senha_aluno');
        const retorno = document.getElementById('mensagem_retorno');
        const senha = senhaInput.value;

        if (!senha) {
            retorno.innerHTML = "<span class='text-warning'>Digite a senha!</span>";
            return;
        }

        retorno.innerHTML = "<div class='spinner-border text-light' role='status'></div>";

        try {
            // Enviando dados via Fetch (Substitui o $.ajax do jQuery)
            const formData = new FormData();
            formData.append('senha', senha);

            const response = await fetch('processar-checkin.php', {
                method: 'POST',
                body: formData
            });

            const texto = await response.text();
            retorno.innerHTML = texto;

            // Limpa o visor se tiver sucesso
            if (texto.includes('alert-success')) {
                setTimeout(limpar, 4000);
            }
        } catch (error) {
            retorno.innerHTML = "<span class='text-danger'>Erro ao processar.</span>";
        }
    }
    </script>
</body>

</html>