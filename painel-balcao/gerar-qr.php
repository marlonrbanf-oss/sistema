<?php
@session_start();

// A CHAVE DEVE SER IDENTICA À DO ARQUIVO validar-tecnica.php
$chave_secreta = "MINHA_NOVA_ARTE_2026"; 

// Gera o token do dia
$token_dia = md5(date('Y-m-d') . $chave_secreta);

// Nova URL de API (QuickChart - Mais estável que a do Google)
$url_qr = "https://quickchart.io/qr?text=" . urlencode($token_dia) . "&size=300&margin=2";
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code de Validação</title>
    <link rel="stylesheet" href="../css/bootstrap.css">
    <style>
    body {
        background-color: #1a1a1a;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
    }

    .qr-container {
        background: white;
        padding: 20px;
        border-radius: 20px;
        text-align: center;
        color: #333;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        max-width: 350px;
    }

    .qr-image {
        width: 100%;
        height: auto;
        border: 1px solid #eee;
        margin: 15px 0;
    }
    </style>
</head>

<body>
    <div class="qr-container">
        <h4 class="font-weight-bold mb-0">VALIDAÇÃO NOVA ARTE</h4>
        <p class="text-muted small">Mestre: <?php echo $_SESSION['nome_usuario']; ?></p>

        <img src="<?php echo $url_qr; ?>" class="qr-image" alt="Gerando QR Code...">

        <div class="alert alert-dark py-1 small mb-3">
            <strong>VÁLIDO APENAS HOJE</strong><br>
            <?php echo date('d/m/Y'); ?>
        </div>

        <button onclick="window.close()" class="btn btn-danger btn-sm btn-block">FECHAR</button>
    </div>
</body>

</html>