<?php
require_once("../conexao.php");
@session_start();

// --- 1. BLOCO PARA DOWNLOAD DO MODELO CSV ---
if (isset($_GET['download_modelo'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=modelo_importacao.csv');
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para acentuação correta
    fputcsv($output, ['Nome', 'Email/Usuario', 'CPF', 'Senha', 'Nivel', 'Telefone', 'Vencimento', 'Faixa', 'Graus', 'Aulas'], ';');
    fputcsv($output, ['Exemplo Aluno', 'aluno.teste', '000.000.000-00', '123456', 'Cliente', '48999999999', date('Y-m-d'), 'Branca', '0', '0'], ';');
    fclose($output);
    exit();
}

// --- FUNÇÕES DE APOIO ---
function gerarSenha($tamanho = 6)
{
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, $tamanho);
}

function tratarDuplicado($pdo, $campo, $valor, $nome_aluno, &$relatorio)
{
    $valorOriginal = $valor;
    $novoValor = $valor;
    $i = 1;
    while (true) {
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE $campo = :v");
        $query->bindValue(":v", $novoValor);
        $query->execute();
        if ($query->rowCount() == 0) {
            if ($valorOriginal !== $novoValor) {
                $relatorio[] = ['aluno' => $nome_aluno, 'tipo' => "Ajuste de " . strtoupper($campo), 'detalhe' => "O valor '$valorOriginal' já existia. Alterado para: <b>$novoValor</b>"];
            }
            return $novoValor;
        }
        $novoValor = $valor . $i;
        $i++;
    }
}

// --- 2. EXECUÇÃO FINAL APÓS CONFIRMAÇÃO DO USUÁRIO ---
if (isset($_POST['confirmar_importacao'])) {
    $alunos = json_decode($_POST['dados_alunos'], true);
    $sucessos = [];
    $erros = [];

    foreach ($alunos as $aluno) {
        try {
            $pdo->beginTransaction();
            $res = $pdo->prepare("INSERT INTO usuarios SET nome = :n, usuario = :u, cpf = :c, senha = :s, nivel = :niv, telefone = :t, ativo = 'Sim', foto = 'sem-perfil.jpg', data_venc = :v, data_cad = CURDATE()");
            $res->execute([
                ":n" => $aluno['nome'],
                ":u" => $aluno['usuario'],
                ":c" => $aluno['cpf'],
                ":s" => $aluno['senha'],
                ":niv" => $aluno['nivel'],
                ":t" => $aluno['telefone'],
                ":v" => $aluno['venc']
            ]);

            $id_novo = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO graduacoes SET usuario_id = ?, cor_faixa = ?, graus = ?, total_aulas = ?")
                ->execute([$id_novo, $aluno['faixa'], $aluno['graus'], $aluno['aulas']]);

            $pdo->commit();
            $sucessos[] = $aluno['nome'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $erros[] = ['nome' => $aluno['nome'], 'msg' => "Erro no banco de dados"];
        }
    }
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <title>Resultado da Importação</title>
    </head>

    <body class="bg-light p-4">
        <div class="container">
            <div class="card shadow border-0">
                <div class="card-header bg-success text-white">
                    <h5>Importação Finalizada!</h5>
                </div>
                <div class="card-body text-center">
                    <h4 class="text-success"><b><?php echo count($sucessos); ?></b> alunos cadastrados com sucesso!</h4>
                    <?php if (count($erros) > 0): ?>
                        <hr>
                        <h5 class="text-danger">Falhas ao cadastrar (<?php echo count($erros); ?>):</h5>
                        <table class="table table-sm text-left">
                            <?php foreach ($erros as $e): ?>
                                <tr>
                                    <td><?php echo $e['nome']; ?></td>
                                    <td><?php echo $e['msg']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                    <a href="index.php?pag=usuarios" class="btn btn-primary mt-3">Voltar para Lista de Usuários</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// --- 3. INTERFACE INICIAL COM GUIA PASSO A PASSO ---
if (!isset($_FILES['arquivo_excel']) || $_FILES['arquivo_excel']['name'] == "") {
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <title>Importar Planilha CSV</title>
    </head>

    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0"><i class="fas fa-file-csv mr-2 text-warning"></i> Importar Planilha .CSV</h5>
                    <a href="?download_modelo=1" class="btn btn-sm btn-info shadow-sm">
                        <i class="fas fa-download mr-1"></i> Baixar Planilha Modelo
                    </a>
                </div>
                <div class="card-body">

                    <h5 class="text-primary mb-3"><i class="fas fa-tasks mr-2"></i> Passo a Passo para Importar:</h5>
                    <div class="list-group mb-4 shadow-sm">
                        <div class="list-group-item"><i class="fas fa-1 mr-2 text-primary"></i> <b>Baixe o modelo:</b>
                            Clique no botão azul acima para obter a planilha padrão.</div>
                        <div class="list-group-item"><i class="fas fa-2 mr-2 text-primary"></i> <b>Preencha os dados:</b>
                            Coloque as informações dos seus alunos na planilha.</div>
                        <div class="list-group-item"><i class="fas fa-3 mr-2 text-primary"></i> <b>Salve como .CSV:</b> No
                            seu editor de planilhas, escolha "Salvar como" e selecione o tipo <b>CSV (Separado por
                                vírgulas)</b>.</div>
                        <div class="list-group-item"><i class="fas fa-4 mr-2 text-primary"></i> <b>Suba o arquivo:</b>
                            Selecione o arquivo salvo no campo abaixo.</div>
                        <div class="list-group-item"><i class="fas fa-5 mr-2 text-primary"></i> <b>Verifique e Confirme:</b>
                            Analise o relatório de prévia e clique em confirmar para gravar no sistema.</div>
                    </div>

                    <form method="post" enctype="multipart/form-data" class="bg-white p-4 border rounded shadow-sm">
                        <div class="form-group">
                            <label class="font-weight-bold">Selecione o arquivo .CSV do seu computador:</label>
                            <input type="file" name="arquivo_excel" accept=".csv" required
                                class="form-control-file border p-2 w-100">
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block shadow">
                            <i class="fas fa-search mr-2"></i>Verificar Dados antes de Cadastrar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// --- 4. GERAÇÃO DA PRÉVIA VISUAL ---
$extensao = pathinfo($_FILES['arquivo_excel']['name'], PATHINFO_EXTENSION);
if (strtolower($extensao) != 'csv') {
    echo "<script>alert('ERRO: O sistema aceita apenas arquivos .CSV! Siga o Guia Passo a Passo.'); window.location='index.php?pag=usuarios';</script>";
    exit();
}

$handle = fopen($_FILES['arquivo_excel']['tmp_name'], 'r');
$primeiraLinha = fgets($handle);
$delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';
rewind($handle);

$alunos_para_cadastrar = [];
$relatorio_previa = [];

while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
    if (!isset($dados[0]) || trim((string)$dados[0]) == "" || strtolower($dados[0]) == 'nome') continue;

    $nome = trim((string)$dados[0]);
    $user_temp = trim(strtolower((string)($dados[1] ?? '')));
    $cpf_temp = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
    $senha_temp = trim((string)($dados[3] ?? ''));

    if ($senha_temp == "") {
        $senha_temp = gerarSenha();
        $relatorio_previa[] = ['aluno' => $nome, 'tipo' => 'Senha Gerada', 'detalhe' => "Senha automática: <b>$senha_temp</b>"];
    }

    $cpf_final = ($cpf_temp != "") ? tratarDuplicado($pdo, 'cpf', $cpf_temp, $nome, $relatorio_previa) : "";
    $user_final = tratarDuplicado($pdo, 'usuario', $user_temp, $nome, $relatorio_previa);

    $alunos_para_cadastrar[] = [
        'nome' => $nome,
        'usuario' => $user_final,
        'cpf' => $cpf_final,
        'senha' => $senha_temp,
        'nivel' => (!empty($dados[4]) && strtolower($dados[4]) != 'aluno') ? ucfirst(strtolower($dados[4])) : 'Cliente',
        'telefone' => preg_replace('/[^0-9]/', '', (string)($dados[5] ?? '')),
        'venc' => (!empty($dados[6])) ? $dados[6] : date('Y-m-d', strtotime('+30 days')),
        'faixa' => ($dados[7] ?? 'Branca'),
        'graus' => ($dados[8] ?? 0),
        'aulas' => ($dados[9] ?? 0)
    ];
}
fclose($handle);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/bootstrap.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <title>Conferir Planilha</title>
</head>

<body class="bg-light">
    <div class="container mt-4 mb-5">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5 class="m-0">Prévia da Importação: <?php echo count($alunos_para_cadastrar); ?> Alunos</h5>
                <button onclick="window.print();" class="btn btn-sm btn-light shadow-sm"><i class="fas fa-print"></i>
                    Imprimir</button>
            </div>
            <div class="card-body">
                <p class="text-muted small">Verifique abaixo se houve algum ajuste automático para evitar CPFs ou
                    Usuários repetidos:</p>
                <table class="table table-sm table-hover border">
                    <thead class="thead-light">
                        <tr>
                            <th>Aluno</th>
                            <th>Ação Necessária</th>
                            <th>Resultado do Ajuste</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorio_previa as $item): ?>
                            <tr>
                                <td><b><?php echo $item['aluno']; ?></b></td>
                                <td><span class="badge badge-warning"><?php echo $item['tipo']; ?></span></td>
                                <td class="small"><?php echo $item['detalhe']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($relatorio_previa)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Nenhum conflito encontrado. Todos os
                                    dados estão prontos!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="alert alert-warning mt-4 text-center border-warning shadow">
                    <h5 class="mb-3 font-weight-bold">Você confirma o cadastro de todos os alunos acima?</h5>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="dados_alunos"
                            value='<?php echo htmlspecialchars(json_encode($alunos_para_cadastrar), ENT_QUOTES, 'UTF-8'); ?>'>
                        <button type="submit" name="confirmar_importacao" class="btn btn-success btn-lg px-5 mr-2">
                            <i class="fas fa-check-circle mr-1"></i> Sim, Confirmar Cadastro
                        </button>
                    </form>
                    <a href="index.php?pag=usuarios" class="btn btn-danger btn-lg px-5">
                        <i class="fas fa-times-circle mr-1"></i> Não, Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>