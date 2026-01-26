<?php
require_once("../conexao.php");
@session_start();

// Configurações iniciais
$delimitador = ',';
$cerca = '"';

if (isset($_FILES['arquivo_excel']) && $_FILES['arquivo_excel']['name'] != "") {

    $arquivo = $_FILES['arquivo_excel']['tmp_name'];

    // Abre o arquivo para leitura
    $handle = fopen($arquivo, 'r');

    // 1. Detecta o separador (vírgula ou ponto-e-vírgula)
    $primeiraLinha = fgets($handle);
    if (strpos($primeiraLinha, ';') !== false) {
        $delimitador = ';';
    }
    rewind($handle); // Volta para o início

    // 2. Lê e pula o cabeçalho
    fgetcsv($handle, 1000, $delimitador, $cerca);

    $cont_sucesso = 0;
    $cont_erro = 0;
    $ja_cadastrados = 0;

    // 3. Loop pelas linhas do arquivo
    while (($dados = fgetcsv($handle, 1000, $delimitador, $cerca)) !== FALSE) {

        // Se a linha estiver vazia ou o nome estiver em branco, pula
        if (empty($dados[0]) || trim($dados[0]) == "") continue;

        // Mapeamento e Limpeza (Tratando acentuação com utf8_encode se necessário)
        $nome        = trim(utf8_encode($dados[0]));
        $usuario     = trim($dados[1]); // E-mail
        $cpf         = preg_replace('/[^0-9]/', '', $dados[2]); // Limpa CPF
        $senha       = trim($dados[3]);
        $nivel       = (!empty($dados[4])) ? ucfirst(strtolower(trim($dados[4]))) : 'Cliente';
        $telefone    = preg_replace('/[^0-9]/', '', $dados[5] ?? ''); // Limpa Telefone
        $data_venc   = (!empty($dados[6])) ? trim($dados[6]) : date('Y-m-d', strtotime('+30 days'));

        // Padronização: se na planilha estiver 'Aluno', vira 'Cliente' para o banco
        if ($nivel == 'Aluno') $nivel = 'Cliente';

        // Dados de Graduação (Colunas H, I, J)
        $cor_faixa   = (!empty($dados[7])) ? trim($dados[7]) : 'Branca';
        $graus       = (int)($dados[8] ?? 0);
        $total_aulas = (int)($dados[9] ?? 0);

        // Verificar se o Usuário/E-mail já existe
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $query->bindValue(":u", $usuario);
        $query->execute();

        if ($query->rowCount() == 0) {
            try {
                // Inserir na tabela USUARIOS
                $res = $pdo->prepare("INSERT INTO usuarios SET 
                    nome = :nome, 
                    usuario = :usuario, 
                    cpf = :cpf, 
                    senha = :senha, 
                    nivel = :nivel, 
                    telefone = :telefone, 
                    ativo = 'Sim', 
                    foto = 'sem-perfil.jpg',
                    data_venc = :venc");

                $res->bindValue(":nome", $nome);
                $res->bindValue(":usuario", $usuario);
                $res->bindValue(":cpf", $cpf);
                $res->bindValue(":senha", $senha);
                $res->bindValue(":nivel", $nivel);
                $res->bindValue(":telefone", $telefone);
                $res->bindValue(":venc", $data_venc);
                $res->execute();

                $id_novo_usuario = $pdo->lastInsertId();

                // Inserir na tabela GRADUACOES se for Cliente
                if ($nivel == 'Cliente') {
                    $res_g = $pdo->prepare("INSERT INTO graduacoes SET 
                        usuario_id = :id, 
                        cor_faixa = :cor, 
                        graus = :graus, 
                        total_aulas = :aulas");

                    $res_g->bindValue(":id", $id_novo_usuario);
                    $res_g->bindValue(":cor", $cor_faixa);
                    $res_g->bindValue(":graus", $graus);
                    $res_g->bindValue(":aulas", $total_aulas);
                    $res_g->execute();
                }

                $cont_sucesso++;
            } catch (Exception $e) {
                // Log de erro pode ser adicionado aqui: error_log($e->getMessage());
                $cont_erro++;
            }
        } else {
            $ja_cadastrados++;
        }
    }

    fclose($handle);

    // Feedback visual
    echo "<script>
            alert('Processamento concluído!\\n\\n✅ Importados: $cont_sucesso\\n⚠️ Já existiam: $ja_cadastrados\\n❌ Erros: $cont_erro'); 
            window.location='usuarios.php';
          </script>";
} else {
    echo "<script>alert('Selecione um arquivo CSV válido!'); window.location='usuarios.php';</script>";
}
