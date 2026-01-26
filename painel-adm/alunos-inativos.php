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
    <title>Alunos Inativos - Nova Arte BJJ</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <style>
        body {
            font-size: 0.9rem;
            background: #f4f6f9;
        }

        .card-aluno {
            border-radius: 12px;
            transition: all 0.3s;
            border: none;
            overflow: hidden;
        }

        .card-aluno:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12) !important;
        }

        .btn-whats {
            background-color: #25d366;
            color: white !important;
            font-weight: bold;
            border-radius: 20px;
            transition: 0.3s;
        }

        .btn-whats:hover {
            background-color: #128c7e;
            transform: scale(1.02);
        }

        .badge-count {
            font-size: 1rem;
            background-color: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 50px;
        }

        .empty-state {
            padding: 50px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="text-dark mb-0 font-weight-bold">
                    <i class="fas fa-user-slash text-danger mr-2"></i> Alunos Inativos
                </h3>
                <small class="text-muted">Gerencie quem está afastado e facilite o retorno.</small>
            </div>
            <div class="d-flex align-items-center">
                <a href="usuarios.php" class="btn btn-dark shadow-sm mr-2">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar
                </a>
            </div>
        </div>

        <div class="row">
            <?php
            // Busca usuários inativos do nível Cliente
            $query = $pdo->query("SELECT * FROM usuarios WHERE ativo = 'Não' AND nivel = 'Cliente' ORDER BY nome ASC");
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            $total_inativos = count($res);

            if ($total_inativos > 0) {
                foreach ($res as $usu) {
                    $id = $usu['id'];
                    // Formata nome para exibição (Primeira letra maiúscula)
                    $nome = mb_convert_case($usu['nome'], MB_CASE_TITLE, "UTF-8");

                    // Limpa o telefone para o link do WhatsApp (mantém apenas números)
                    $tel_limpo = preg_replace('/[^0-9]/', '', $usu['telefone'] ?? '');

                    // Lógica para capturar primeiro nome com segurança
                    $nome_partes = explode(' ', trim($nome));
                    $primeiro_nome = $nome_partes[0];

                    // Mensagem personalizada
                    $mensagem = "Olá *$primeiro_nome*! Tudo bem? 👋\n\nNotamos que você está afastado dos treinos na *Nova Arte BJJ*. Estamos passando para saber se você deseja retornar aos tatames!🥋\n\nTemos condições especiais para alunos antigos. Vamos treinar? *OSS!*";

                    // Verifica se o telefone tem o formato mínimo esperado
                    $url_whats = "https://api.whatsapp.com/send?phone=55$tel_limpo&text=" . urlencode($mensagem);
            ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card card-aluno shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-3">
                                    <h5 class="card-title font-weight-bold text-dark mb-1"><?php echo $nome ?></h5>
                                    <span class="badge badge-light border text-muted">
                                        <i class="fas fa-phone-alt mr-1"></i> <?php echo !empty($usu['telefone']) ? $usu['telefone'] : 'Não informado' ?>
                                    </span>
                                </div>

                                <div class="mt-auto">
                                    <?php if (!empty($tel_limpo) && strlen($tel_limpo) >= 10): ?>
                                        <a href="<?php echo $url_whats ?>" target="_blank" class="btn btn-whats btn-block shadow-sm">
                                            <i class="fab fa-whatsapp mr-2"></i> Convidar para Voltar
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-block" disabled>
                                            <i class="fas fa-exclamation-circle mr-1"></i> Telefone Inválido
                                        </button>
                                    <?php endif; ?>

                                    <div class="mt-3 text-center">
                                        <hr>
                                        <a href="mudar-status.php?id=<?php echo $id ?>&acao=ativar"
                                            class="text-primary small font-weight-bold text-decoration-none"
                                            onclick="return confirm('Deseja reativar este aluno?')">
                                            <i class="fas fa-user-check mr-1"></i> REATIVAR MANUALMENTE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '
                <div class="col-12">
                    <div class="empty-state text-center">
                        <i class="fas fa-users text-muted fa-3x mb-3"></i>
                        <h4 class="text-secondary">Tudo em dia!</h4>
                        <p class="text-muted">Não há alunos inativos registrados no sistema.</p>
                        <a href="usuarios.php" class="btn btn-outline-primary btn-sm">Ver todos os alunos</a>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>