<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA (Apenas quem tem acesso ao painel pode ver)
if (@$_SESSION['nivel_usuario'] != 'Balconista' && @$_SESSION['nivel_usuario'] != 'Admin' && @$_SESSION['nivel_usuario'] != 'Professor') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECUPERA O ID DO ALUNO (Vem do link em usuarios.php)
$id_aluno = @$_GET['id'];

if ($id_aluno == "") {
    echo "<div class='alert alert-danger'>Erro: ID do aluno não informado.</div>";
    exit();
}

// 3. BUSCA DADOS DO ALUNO
$query = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$query->bindValue(":id", $id_aluno);
$query->execute();
$dados = $query->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    echo "<div class='alert alert-danger'>Erro: Aluno não encontrado no banco de dados.</div>";
    exit();
}

// 4. BUSCA DADOS DE GRADUAÇÃO (Para exibir na ficha)
$query_g = $pdo->prepare("SELECT * FROM graduacoes WHERE usuario_id = :id");
$query_g->bindValue(":id", $id_aluno);
$query_g->execute();
$res_g = $query_g->fetch(PDO::FETCH_ASSOC);

$faixa = $res_g['cor_faixa'] ?? 'Branca';
$graus = $res_g['graus'] ?? 0;
$aulas = $res_g['total_aulas'] ?? 0;
?>

<div class="container-fluid pt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0">
            <li class="breadcrumb-item"><a href="index.php?pag=usuarios">Alunos</a></li>
            <li class="breadcrumb-item active" aria-current="page">Perfil de <?php echo $dados['nome']; ?></li>
        </ol>
    </nav>

    <div class="card card-outline card-primary shadow-sm" style="border-radius: 15px;">
        <div class="card-header bg-white border-0 pt-4">
            <h3 class="card-title font-weight-bold text-primary">
                <i class="fas fa-id-card mr-2"></i>Ficha Cadastral do Aluno
            </h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-4 border-right">
                    <img src="../img/perfis/<?php echo $dados['foto'] ?: 'usuario-icone-claro.png'; ?>"
                        class="img-fluid rounded-circle img-thumbnail mb-3 shadow-sm"
                        style="width: 160px; height: 160px; object-fit: cover; border: 4px solid #f8f9fa;">

                    <h5 class="font-weight-bold mb-1"><?php echo mb_strtoupper($dados['nome']); ?></h5>
                    <span class="badge badge-dark p-2 mb-3">MATRÍCULA: #<?php echo $dados['id']; ?></span>

                    <div class="bg-light rounded p-2 text-left">
                        <small class="text-muted d-block">Graduação Atual:</small>
                        <i class="fas fa-ribbon mr-1 text-primary"></i> <b><?php echo strtoupper($faixa); ?></b>
                        (<?php echo $graus; ?>G)<br>
                        <i class="fas fa-calendar-check mr-1 text-success"></i> <b><?php echo $aulas; ?></b> Aulas
                        Totais
                    </div>
                </div>

                <div class="col-md-9 px-4">
                    <div class="row">
                        <div class="col-sm-6 mb-4">
                            <label class="text-muted small d-block mb-1 text-uppercase font-weight-bold">E-mail /
                                Usuário</label>
                            <div class="p-2 bg-light rounded"><i class="fas fa-envelope mr-2 text-muted"></i>
                                <?php echo $dados['usuario']; ?></div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <label
                                class="text-muted small d-block mb-1 text-uppercase font-weight-bold">Telefone</label>
                            <div class="p-2 bg-light rounded"><i class="fas fa-phone mr-2 text-muted"></i>
                                <?php echo $dados['telefone'] ?: 'Não cadastrado'; ?></div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <label class="text-muted small d-block mb-1 text-uppercase font-weight-bold">CPF</label>
                            <div class="p-2 bg-light rounded"><i class="fas fa-address-card mr-2 text-muted"></i>
                                <?php echo $dados['cpf'] ?: '---.---.------'; ?></div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <label class="text-muted small d-block mb-1 text-uppercase font-weight-bold">Vencimento
                                Mensalidade</label>
                            <?php
                            $vencido = (strtotime($dados['data_venc']) < strtotime(date('Y-m-d')));
                            $cor_venc = $vencido ? 'text-danger font-weight-bold' : 'text-success';
                            ?>
                            <div class="p-2 bg-light rounded <?php echo $cor_venc; ?>">
                                <i class="fas fa-calendar-alt mr-2 text-muted"></i>
                                <?php echo date('d/m/Y', strtotime($dados['data_venc'])); ?>
                                <?php if ($vencido) echo ' <small>(PENDENTE)</small>'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 mt-2 small">
                        <i class="fas fa-info-circle mr-2"></i> Esta visualização é exclusiva para professores e
                        administradores.
                        Alterações cadastrais devem ser feitas via painel administrativo.
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 text-right pb-4">
            <a href="index.php?pag=usuarios" class="btn btn-outline-dark px-4 shadow-sm">
                <i class="fas fa-arrow-left mr-2"></i>Voltar para Lista
            </a>
            <a href="index.php?pag=relatorio-graduacao&id=<?php echo $id_aluno; ?>"
                class="btn btn-warning px-4 shadow-sm font-weight-bold">
                <i class="fas fa-chart-line mr-2"></i>Análise Técnica
            </a>
        </div>
    </div>
</div>