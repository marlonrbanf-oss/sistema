<?php
@session_start();
require_once("../conexao.php");

// 1. VALIDAÇÃO DE SEGURANÇA AJUSTADA
// Agora permite 'Cliente' ou 'Atleta' visualizar, além de Admin, Professor e Balconista
$nivel = @$_SESSION['nivel_usuario'];
if ($nivel != 'Balconista' && $nivel != 'Admin' && $nivel != 'Professor' && $nivel != 'Cliente' && $nivel != 'Atleta') {
    echo "<script language='javascript'>window.location='../login.php'; </script>";
    exit();
}

// 2. RECUPERA O ID DO ALUNO
// Se for o painel do cliente, ele pega o ID da própria sessão caso o GET venha vazio
$id_aluno = @$_GET['id'];
if ($id_aluno == "") {
    $id_aluno = $_SESSION['id_usuario'];
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

// 4. BUSCA DADOS DE GRADUAÇÃO
$query_g = $pdo->prepare("SELECT * FROM graduacoes WHERE usuario_id = :id");
$query_g->bindValue(":id", $id_aluno);
$query_g->execute();
$res_g = $query_g->fetch(PDO::FETCH_ASSOC);

$faixa = $res_g['cor_faixa'] ?? 'Branca';
$graus = $res_g['graus'] ?? 0;
$aulas = $res_g['total_aulas'] ?? 0;

// 5. BUSCA SELOS/HABILIDADES (Para mostrar ao aluno também)
$query_h = $pdo->prepare("SELECT * FROM habilidades WHERE usuario_id = :id ORDER BY posicao ASC");
$query_h->bindValue(":id", $id_aluno);
$query_h->execute();
$habilidades = $query_h->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid pt-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent p-0">
            <li class="breadcrumb-item"><a href="index.php">Início</a></li>
            <li class="breadcrumb-item active">Meu Perfil</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-4">
            <div class="card card-outline card-primary shadow-sm text-center p-3" style="border-radius: 15px;">
                <img src="../img/perfis/<?php echo $dados['foto'] ?: 'usuario-icone-claro.png'; ?>"
                    class="img-fluid rounded-circle img-thumbnail mb-3 mx-auto shadow-sm"
                    style="width: 140px; height: 140px; object-fit: cover; border: 4px solid #f8f9fa;">

                <h5 class="font-weight-bold mb-1"><?php echo mb_strtoupper($dados['nome']); ?></h5>
                <span class="badge badge-dark p-2 mb-3">MATRÍCULA: #<?php echo $dados['id']; ?></span>

                <div class="bg-light rounded p-3 text-left">
                    <small class="text-muted d-block text-uppercase font-weight-bold">Status Atual:</small>
                    <hr class="my-2">
                    <i class="fas fa-ribbon mr-1 text-primary"></i> Faixa: <b><?php echo strtoupper($faixa); ?></b><br>
                    <i class="fas fa-certificate mr-1 text-warning"></i> Grau: <b><?php echo $graus; ?>º Grau</b><br>
                    <i class="fas fa-calendar-check mr-1 text-success"></i> Aulas: <b><?php echo $aulas; ?></b>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white">
                    <h6 class="font-weight-bold text-primary mb-0"><i class="fas fa-info-circle mr-2"></i>Informações
                        Cadastrais</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label class="small text-muted mb-0">E-mail</label>
                            <p class="font-weight-bold"><?php echo $dados['usuario']; ?></p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="small text-muted mb-0">Telefone</label>
                            <p class="font-weight-bold"><?php echo $dados['telefone'] ?: 'N/A'; ?></p>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <label class="small text-muted mb-0">Próximo Vencimento</label>
                            <p
                                class="font-weight-bold <?php echo (strtotime($dados['data_venc']) < strtotime(date('Y-m-d'))) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo date('d/m/Y', strtotime($dados['data_venc'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-3" style="border-radius: 15px;">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0 small font-weight-bold"><i class="fas fa-medal mr-2"></i>Minhas Técnicas Concluídas
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="bg-light small">
                                <tr>
                                    <th>POSIÇÃO</th>
                                    <th>TÉCNICA</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                <?php foreach ($habilidades as $h):
                                    $dominado = (mb_strtolower($h['status']) == 'dominado');
                                ?>
                                    <tr>
                                        <td><?php echo strtoupper($h['posicao']); ?></td>
                                        <td><?php echo $h['tecnica']; ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo $dominado ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo strtoupper($h['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>