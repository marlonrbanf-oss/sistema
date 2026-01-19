<?php
@session_start();
require_once("../conexao.php");

// Validação de Segurança
if (@$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor' && @$_SESSION['nivel_usuario'] != 'Balconista') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <title>Alunos Inativos - Convite de Retorno</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
    body {
        font-size: 0.9rem;
        background: #f8f9fa;
    }

    .card-aluno {
        border-radius: 10px;
        transition: transform 0.2s;
        border: none;
    }

    .card-aluno:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .btn-whats {
        background-color: #25d366;
        color: white !important;
        font-weight: bold;
        border-radius: 20px;
    }

    .btn-whats:hover {
        background-color: #128c7e;
    }
    </style>
</head>

<body>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="text-dark"><i class="fas fa-user-slash text-danger mr-2"></i> Alunos Inativos</h3>
            <a href="usuarios.php" class="btn btn-sm btn-dark"><i class="fas fa-arrow-left mr-1"></i> Voltar</a>
        </div>

        <div class="row">
            <?php
        // Busca apenas usuários inativos do nível Cliente
        $query = $pdo->query("SELECT * FROM usuarios WHERE ativo = 'Não' AND nivel = 'Cliente' ORDER BY nome ASC");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $total_inativos = count($res);

        if($total_inativos > 0){
            foreach ($res as $usu) {
                $id = $usu['id'];
                $nome = mb_convert_case($usu['nome'], MB_CASE_TITLE, "UTF-8");
                $tel = preg_replace('/[^0-9]/', '', $usu['telefone'] ?? '');
                
                // Mensagem personalizada para o WhatsApp
                $primeiro_nome = explode(' ', trim($nome))[0];
                $mensagem = "Olá $primeiro_nome! Tudo bem? Notamos que você está afastado dos treinos na Nova Arte BJJ. Estamos passando para saber se você deseja retornar aos tatames! Temos condições especiais para alunos antigos. Vamos treinar?";
                $url_whats = "https://api.whatsapp.com/send?phone=55$tel&text=" . urlencode($mensagem);
        ?>
            <div class="col-md-4 mb-3">
                <div class="card card-aluno shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-1"><?php echo $nome ?></h5>
                        <p class="text-muted small mb-3"><i class="fas fa-phone mr-1"></i>
                            <?php echo $usu['telefone'] ?></p>

                        <?php if($tel != ''): ?>
                        <a href="<?php echo $url_whats ?>" target="_blank" class="btn btn-sm btn-whats btn-block">
                            <i class="fab fa-whatsapp mr-1"></i> Convidar para Voltar
                        </a>
                        <?php else: ?>
                        <button class="btn btn-sm btn-secondary btn-block" disabled>Sem Telefone</button>
                        <?php endif; ?>

                        <div class="mt-2 text-center">
                            <a href="mudar-status.php?id=<?php echo $id ?>&acao=ativar"
                                class="text-primary small font-weight-bold">
                                <i class="fas fa-user-check mr-1"></i> Reativar Manualmente
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            }
        } else {
            echo '<div class="col-12 text-center mt-5"><div class="alert alert-info shadow-sm">Não há alunos inativos no momento.</div></div>';
        }
        ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>