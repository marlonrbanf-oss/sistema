<?php
require_once("../conexao.php");
@session_start();

// --- FUNÇÃO PARA GERAR SENHA ALEATÓRIA ---
function gerarSenha($tamanho = 6)
{
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, $tamanho);
}

// --- FUNÇÃO PARA TRATAR DUPLICIDADE COM REGISTRO DE ALTERAÇÃO ---
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
                $relatorio[] = [
                    'aluno' => $nome_aluno,
                    'tipo' => "Ajuste de " . strtoupper($campo),
                    'detalhe' => "O valor '$valorOriginal' já existia. Alterado para: <b>$novoValor</b>"
                ];
            }
            return $novoValor;
        }
        $novoValor = $valor . $i;
        $i++;
    }
}

// --- PROCESSAMENTO ---
if (isset($_FILES['arquivo_excel']) && $_FILES['arquivo_excel']['name'] != "") {
    $arquivo = $_FILES['arquivo_excel']['tmp_name'];
    $conteudo = file_get_contents($arquivo);
    $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');

    $handle = fopen('php://memory', 'r+');
    fwrite($handle, $conteudo);
    rewind($handle);

    $primeiraLinha = fgets($handle);
    $delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';

    $cont_sucesso = 0;
    $relatorio_final = [];

    while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {
        if (!isset($dados[0]) || trim((string)$dados[0]) == "") continue;

        $nome    = trim((string)$dados[0]);
        $usuario = trim(strtolower((string)($dados[1] ?? '')));
        $cpf     = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
        $senha   = trim((string)($dados[3] ?? ''));

        // --- LOGICA DE SENHA ---
        if ($senha == "") {
            $senha = gerarSenha();
            $relatorio_final[] = ['aluno' => $nome, 'tipo' => 'Senha Gerada', 'detalhe' => "Senha automática: <b>$senha</b>"];
        }

        // --- TRATAMENTO DE DUPLICADOS ---
        $cpf = ($cpf != "") ? tratarDuplicado($pdo, 'cpf', $cpf, $nome, $relatorio_final) : "";
        $usuario = tratarDuplicado($pdo, 'usuario', $usuario, $nome, $relatorio_final);

        $nivel = (!empty($dados[4]) && strtolower($dados[4]) != 'aluno') ? ucfirst(strtolower($dados[4])) : 'Cliente';
        $telefone = preg_replace('/[^0-9]/', '', (string)($dados[5] ?? ''));
        $venc = (!empty($dados[6])) ? $dados[6] : date('Y-m-d', strtotime('+30 days'));

        try {
            $pdo->beginTransaction();
            $res = $pdo->prepare("INSERT INTO usuarios SET nome = :n, usuario = :u, cpf = :c, senha = :s, nivel = :niv, telefone = :t, ativo = 'Sim', foto = 'sem-perfil.jpg', data_venc = :v, data_cad = CURDATE()");
            $res->execute([":n" => $nome, ":u" => $usuario, ":c" => $cpf, ":s" => $senha, ":niv" => $nivel, ":t" => $telefone, ":v" => $venc]);

            $id_novo = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO graduacoes SET usuario_id = ?, cor_faixa = ?, graus = ?, total_aulas = ?")
                ->execute([$id_novo, ($dados[7] ?? 'Branca'), ($dados[8] ?? 0), ($dados[9] ?? 0)]);

            $pdo->commit();
            $cont_sucesso++;
        } catch (Exception $e) {
            $pdo->rollBack();
            $relatorio_final[] = ['aluno' => $nome, 'tipo' => '<span class="text-danger">ERRO</span>', 'detalhe' => $e->getMessage()];
        }
    }
    fclose($handle);
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../css/bootstrap.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <title>Relatório de Importação</title>
        <style>
            @media print {

                .btn-print,
                .btn-voltar {
                    display: none !important;
                }

                .card {
                    border: none !important;
                    box-shadow: none !important;
                }
            }
        </style>
    </head>

    <body class="bg-light">
        <div class="container mt-4">
            <div class="card shadow">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="m-0"><i class="fas fa-clipboard-check text-warning mr-2"></i> Relatório de Importação</h5>
                    <div>
                        <button onclick="window.print();" class="btn btn-sm btn-info btn-print">
                            <i class="fas fa-print mr-1"></i> Imprimir / PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-success border-0 mb-4">
                        <i class="fas fa-check-circle mr-2"></i> <b><?php echo $cont_sucesso; ?></b> alunos foram
                        processados e inseridos com sucesso.
                    </div>

                    <table class="table table-sm table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Aluno</th>
                                <th>Ocorrência / Campo</th>
                                <th>Ajuste Realizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorio_final as $item): ?>
                                <tr>
                                    <td class="align-middle"><b><?php echo $item['aluno']; ?></b></td>
                                    <td class="align-middle"><span
                                            class="badge badge-secondary"><?php echo $item['tipo']; ?></span></td>
                                    <td class="align-middle small"><?php echo $item['detalhe']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($relatorio_final)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Nenhum ajuste foi necessário. Todos os
                                        dados eram únicos!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="text-center mt-4 btn-voltar">
                        <hr>
                        <a href="index.php?pag=usuarios" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Voltar aos Usuários
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}
?>