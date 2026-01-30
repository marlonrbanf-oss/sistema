<?php
require_once("../conexao.php");
@session_start();

// --- 1. BLOCO PARA DOWNLOAD DO MODELO CSV ---
if (isset($_GET['download_modelo'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=modelo_alunos.csv');
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel
    fputcsv($output, ['Nome', 'Email/Usuario', 'CPF', 'Senha', 'Nivel', 'Telefone', 'Vencimento', 'Faixa', 'Graus', 'Aulas'], ';');
    fputcsv($output, ['Joao Silva', 'joao.silva', '123.456.789-00', '123456', 'Cliente', '48999999999', date('Y-m-d'), 'Branca', '0', '0'], ';');
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
    // Exibe o relatório final após salvar
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <title>Resultado Final</title>
    </head>

    <body class="bg-light p-4">
        <div class="container">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5>Importação Concluída!</h5>
                </div>
                <div class="card-body">
                    <p class="text-success"><b><?php echo count($sucessos); ?></b> alunos cadastrados com sucesso.</p>
                    <?php if (count($erros) > 0): ?>
                        <p class="text-danger"><b><?php echo count($erros); ?></b> falhas ao cadastrar.</p>
                        <table class="table table-sm text-danger">
                            <?php foreach ($erros as $e): ?>
                                <tr>
                                    <td><?php echo $e['nome']; ?></td>
                                    <td><?php echo $e['msg']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                    <a href="index.php?pag=usuarios" class="btn btn-primary btn-block">Voltar para Usuários</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// --- 3. INTERFACE INICIAL COM PASSO A PASSO ---
if (!isset($_FILES['arquivo_excel']) || $_FILES['arquivo_excel']['name'] == "") {
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <title>Importar Alunos</title>
    </head>

    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0"><i class="fas fa-file-csv mr-2 text-warning"></i> Importar Alunos</h5>
                    <a href="?download_modelo=1" class="btn btn-sm btn-outline-light"><i class="fas fa-download mr-1"></i>
                        Baixar Modelo CSV</a>
                </div>
                <div class="card-body">
                    <h6 class="text-primary"><i class="fas fa-info-circle"></i> Passo a Passo para Importação:</h6>
                    <div class="alert alert-secondary small">
                        1. <b>Preencha a Planilha:</b> Utilize o modelo baixado no botão acima.<br>
                        2. <b>Salve como CSV:</b> No Excel, vá em <i>Arquivo > Salvar Como</i> e escolha o formato <b>CSV
                            (Separado por vírgulas)</b>.<br>
                        3. <b>Selecione o Arquivo:</b> Clique no botão abaixo e escolha o arquivo .csv gerado.<br>
                        4. <b>Confira a Prévia:</b> O sistema mostrará o que será feito antes de salvar no banco.<br>
                        5. <b>Confirme:</b> Só após sua autorização os alunos serão cadastrados.
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label><b>Selecione o arquivo CSV:</b></label>
                            <input type="file" name="arquivo_excel" accept=".csv" required class="form-control-file">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Verificar Dados antes de Cadastrar</button>
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
    echo "<script>alert('ERRO: O arquivo deve ser .CSV! Siga o passo a passo na tela.'); window.location='index.php?pag=usuarios';</script>";
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
    <title>Conferir Importação</title>
</head>

<body class="bg-light">
    <div class="container mt-4">
        <div class="card shadow border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5 class="m-0">Relatório de Prévia: <?php echo count($alunos_para_cadastrar); ?> Alunos</h5>
                <button onclick="window.print();" class="btn btn-sm btn-light"><i class="fas fa-print"></i></button>
            </div>
            <div class="card-body">
                <p>Abaixo estão as alterações automáticas sugeridas pelo sistema para evitar erros:</p>
                <table class="table table-sm table-hover border">
                    <thead class="thead-light">
                        <tr>
                            <th>Aluno</th>
                            <th>Ocorrência</th>
                            <th>Ajuste Aplicado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorio_previa as $item): ?>
                            <tr>
                                <td><?php echo $item['aluno']; ?></td>
                                <td><span class="badge badge-warning"><?php echo $item['tipo']; ?></span></td>
                                <td><?php echo $item['detalhe']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($relatorio_previa)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Nenhuma duplicidade ou falta de senha
                                    encontrada. Tudo pronto para o cadastro!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="alert alert-info mt-4 text-center shadow-sm">
                    <h5 class="mb-3 font-weight-bold">Deseja cadastrar esses
                        <?php echo count($alunos_para_cadastrar); ?> alunos agora?</h5>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="dados_alunos"
                            value='<?php echo htmlspecialchars(json_encode($alunos_para_cadastrar), ENT_QUOTES, 'UTF-8'); ?>'>
                        <button type="submit" name="confirmar_importacao" class="btn btn-success btn-lg px-5">
                            <i class="fas fa-check-circle mr-1"></i> Sim, Cadastrar Todos
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