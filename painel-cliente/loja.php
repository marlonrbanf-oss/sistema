<?php
@session_start();
require_once("../conexao.php");

// 1. Verificação de Segurança
if (!isset($_SESSION['nivel_usuario']) || $_SESSION['nivel_usuario'] != 'Cliente') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

$nome_usuario = $_SESSION['nome_usuario'];

// 2. Busca de Produtos - CORRIGIDO: Removido 'ativo' e usado 'estoque'
$query_p = $pdo->query("SELECT * FROM produtos WHERE estoque > 0 ORDER BY nome ASC");
$produtos = $query_p->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loja Nova Arte - Kimonos & Acessórios</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <style>
        .product-card {
            border: none;
            border-radius: 15px;
            transition: 0.3s;
            overflow: hidden;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-img {
            height: 200px;
            object-fit: contain;
            background: #f8f9fa;
            padding: 15px;
        }

        .price-tag {
            font-size: 1.4rem;
            color: #28a745;
            font-weight: bold;
        }

        .stock-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            z-index: 10;
        }
    </style>
</head>

<body class="hold-transition bg-light text-sm">

    <div class="wrapper">
        <div class="content-wrapper ml-0 bg-light">
            <section class="content pt-4">
                <div class="container-fluid">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="font-weight-bold"><i class="fas fa-shopping-bag mr-2 text-primary"></i> Loja Nova
                            Arte</h3>
                        <a href="index.php" class="btn btn-dark btn-sm shadow-sm"><i class="fas fa-arrow-left mr-1"></i>
                            Voltar</a>
                    </div>

                    <div class="row">
                        <?php if (count($produtos) > 0): ?>
                            <?php foreach ($produtos as $item):
                                // CORRIGIDO: Nome da coluna no seu banco é 'imagem'
                                $foto = ($item['imagem'] != "") ? $item['imagem'] : 'sem-foto.jpg';
                            ?>
                                <div class="col-md-4 col-sm-6 mb-4">
                                    <div class="card product-card shadow-sm">
                                        <span class="badge badge-dark stock-badge">Estoque:
                                            <?php echo $item['estoque']; ?></span>

                                        <img src="../images/produtos/<?php echo $foto; ?>" class="card-img-top product-img"
                                            alt="<?php echo $item['nome']; ?>">

                                        <div class="card-body d-flex flex-column">
                                            <h5 class="font-weight-bold text-dark mb-1"><?php echo $item['nome']; ?></h5>

                                            <p class="text-muted small flex-grow-1">Produto disponível para retirada imediata.
                                            </p>

                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <span class="price-tag">R$
                                                    <?php echo number_format($item['valor'], 2, ',', '.'); ?></span>

                                                <a href="https://wa.me/5511999999999?text=Olá, tenho interesse no produto: <?php echo $item['nome']; ?>"
                                                    target="_blank" class="btn btn-success btn-sm rounded-pill px-3">
                                                    <i class="fab fa-whatsapp mr-1"></i> Pedir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                <p class="text-muted">Nenhum produto com estoque disponível no momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="alert bg-white border shadow-sm mt-4">
                        <div class="row align-items-center">
                            <div class="col-md-1 text-center">
                                <i class="fas fa-info-circle fa-2x text-primary"></i>
                            </div>
                            <div class="col-md-11">
                                <h6 class="font-weight-bold mb-0">Aviso sobre Pedidos</h6>
                                <p class="small text-muted mb-0">A reserva de produtos é feita via WhatsApp e a
                                    retirada/pagamento ocorre diretamente no balcão da academia.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>