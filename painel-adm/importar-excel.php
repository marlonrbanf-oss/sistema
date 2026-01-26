<?php
require_once("../conexao.php");
@session_start();

// Caso o formulário não tenha sido enviado, exibe o manual de orientação
if (!isset($_FILES['arquivo_excel'])) {
?>
    <!DOCTYPE html>
    <html lang="pt-br">

    <head>
        <link rel="stylesheet" href="../css/bootstrap.css">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
        <title>Manual de Importação</title>
    </head>

    <body class="bg-light p-4">
        <div class="container">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5 class="m-0"><i class="fas fa-info-circle mr-2 text-warning"></i> MANUAL DE IMPORTAÇÃO VIA EXCEL</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Siga as instruções abaixo para que seus dados sejam cadastrados sem erros:</p>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">1. Formato do Arquivo</h6>
                            <p>O arquivo deve ser salvo obrigatoriamente no formato <b>CSV (Separado por vírgulas)</b>. Arquivos .xlsx diretos não são aceitos.</p>

                            <h6 class="font-weight-bold">2. Estrutura de Linhas</h6>
                            <p>O sistema ignora as <b>2 primeiras linhas</b> do arquivo (metadados e títulos). Comece a preencher os dados dos alunos na <b>Linha 3</b>.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">3. Colunas Obrigatórias (A até J)</h6>
                            <ul class="small">
                                <li><b>Coluna A:</b> Nome Completo</li>
                                <li><b>Coluna B:</b> Email (Login Único)</li>
                                <li><b>Coluna E:</b> Nível (Escreva "aluno" ou "professor")</li>
                                <li><b>Coluna G:</b> Vencimento (Use o formato AAAA-MM-DD)</li>
                                <li><b>Coluna H:</b> Faixa (Ex: Branca, Azul, Roxa...)</li>
                            </ul>
                        </div>
                    </div>
                    <hr>
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle"></i> <b>Importante:</b> Se o e-mail já existir no sistema, o cadastro dessa linha será ignorado para evitar duplicidade.
                    </div>
                    <a href="index.php?pag=usuarios" class="btn btn-secondary btn-sm">Voltar para Usuários</a>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit();
}

// --- INÍCIO DO PROCESSAMENTO DO ARQUIVO ---

if ($_FILES['arquivo_excel']['name'] != "") {
    $arquivo = $_FILES['arquivo_excel']['tmp_name'];

    // 1. TRATAMENTO DE CODIFICAÇÃO (Resolve o Erro 3854 e acentuação)
    $conteudo = file_get_contents($arquivo);
    $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');

    $handle = fopen('php://memory', 'r+');
    fwrite($handle, $conteudo);
    rewind($handle);

    // 2. DETECTA DELIMITADOR (, ou ;)
    $primeiraLinha = fgets($handle);
    $delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';
    rewind($handle);

    // 3. PULA AS DUAS PRIMEIRAS LINHAS DO MODELO
    fgetcsv($handle, 1000, $delimitador);
    fgetcsv($handle, 1000, $delimitador);

    $cont_sucesso = 0;
    $cont_erro = 0;
    $ja_cadastrados = 0;

    // 4. PROCESSAMENTO DOS DADOS
    while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {

        if (!isset($dados[0]) || trim((string)$dados[0]) == "") continue;

        $nome        = trim((string)$dados[0]);
        $usuario     = trim(strtolower((string)($dados[1] ?? '')));
        $cpf         = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
        $senha       = trim((string)($dados[3] ?? '123'));
        $nivel       = (!empty($dados[4])) ? ucfirst(strtolower(trim((string)$dados[4]))) : 'Cliente';
        $telefone    = preg_replace('/[^0-9]/', '', (string)($dados[5] ?? ''));
        $data_venc   = (!empty($dados[6])) ? trim((string)$dados[6]) : date('Y-m-d', strtotime('+30 days'));

        if (strtolower($nivel) == 'aluno') $nivel = 'Cliente';
        if (strtolower($nivel) == 'professor') $nivel = 'Professor';

        $cor_faixa   = trim((string)($dados[7] ?? 'Branca'));
        $graus       = (int)($dados[8] ?? 0);
        $total_aulas = (int)($dados[9] ?? 0);

        if ($usuario == "") continue;

        $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $query->bindValue(":u", $usuario);
        $query->execute();

        if ($query->rowCount() == 0) {
            try {
                $res = $pdo->prepare("INSERT INTO usuarios SET 
                    nome = :nome, usuario = :usuario, cpf = :cpf, senha = :senha, 
                    nivel = :nivel, telefone = :telefone, ativo = 'Sim', 
                    foto = 'sem-perfil.jpg', data_venc = :venc");

                $res->bindValue(":nome", $nome);
                $res->bindValue(":usuario", $usuario);
                $res->bindValue(":cpf", $cpf);
                $res->bindValue(":senha", $senha);
                $res->bindValue(":nivel", $nivel);
                $res->bindValue(":telefone", $telefone);
                $res->bindValue(":venc", $data_venc);
                $res->execute();

                $id_novo = $pdo->lastInsertId();

                if ($nivel == 'Cliente') {
                    $res_g = $pdo->prepare("INSERT INTO graduacoes SET 
                        usuario_id = :id, cor_faixa = :cor, graus = :graus, total_aulas = :aulas");
                    $res_g->bindValue(":id", $id_novo);
                    $res_g->bindValue(":cor", $cor_faixa);
                    $res_g->bindValue(":graus", $graus);
                    $res_g->bindValue(":aulas", $total_aulas);
                    $res_g->execute();
                }
                $cont_sucesso++;
            } catch (PDOException $e) {
                $cont_erro++;
            }
        } else {
            $ja_cadastrados++;
        }
    }
    fclose($handle);

    echo "<script>
            alert('IMPORTAÇÃO CONCLUÍDA\\n✅ Cadastrados: $cont_sucesso\\n⚠️ Já Existiam: $ja_cadastrados\\n❌ Erros: $cont_erro'); 
            window.location='index.php?pag=usuarios';
          </script>";
}
?>