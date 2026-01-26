<?php
require_once("../conexao.php");
@session_start();

if (isset($_FILES['arquivo_excel']) && $_FILES['arquivo_excel']['name'] != "") {
    $arquivo = $_FILES['arquivo_excel']['tmp_name'];

    // 1. CARREGAMENTO E LIMPEZA DE CODIFICAÇÃO
    // Lemos o arquivo como string para limpar caracteres binários que causam o Erro 3854
    $conteudo = file_get_contents($arquivo);

    // Converte de codificações comuns de Excel (Windows/ISO) para UTF-8 limpo
    $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');

    // Cria um stream em memória para ler o CSV já tratado
    $handle = fopen('php://memory', 'r+');
    fwrite($handle, $conteudo);
    rewind($handle);

    // 2. DETECÇÃO DE SEPARADOR
    $primeiraLinha = fgets($handle);
    $delimitador = (strpos($primeiraLinha, ';') !== false) ? ';' : ',';
    rewind($handle);

    // 3. PULAR CABEÇALHOS (Seu arquivo tem 2 linhas antes dos dados)
    fgetcsv($handle, 1000, $delimitador); // Pula linha: A(0), B(1)...
    fgetcsv($handle, 1000, $delimitador); // Pula linha: Nome, Email, CPF...

    $cont_sucesso = 0;
    $cont_erro = 0;
    $ja_cadastrados = 0;

    // 4. PROCESSAMENTO DAS LINHAS DE DADOS
    while (($dados = fgetcsv($handle, 1000, $delimitador)) !== FALSE) {

        // Verifica se a linha tem o nome preenchido, senão pula
        if (!isset($dados[0]) || trim((string)$dados[0]) == "") continue;

        // Mapeamento Seguro com Casting (Garante compatibilidade PHP 8.1+)
        $nome        = trim((string)$dados[0]);
        $usuario     = trim((string)($dados[1] ?? '')); // E-mail
        $cpf         = preg_replace('/[^0-9]/', '', (string)($dados[2] ?? ''));
        $senha       = trim((string)($dados[3] ?? '123'));
        $nivel       = (!empty($dados[4])) ? ucfirst(strtolower(trim((string)$dados[4]))) : 'Cliente';
        $telefone    = preg_replace('/[^0-9]/', '', (string)($dados[5] ?? ''));
        $data_venc   = (!empty($dados[6])) ? trim((string)$dados[6]) : date('Y-m-d', strtotime('+30 days'));

        // Normalização de nível
        if (strtolower($nivel) == 'aluno') $nivel = 'Cliente';

        // Dados de Graduação (Colunas H, I, J)
        $cor_faixa   = trim((string)($dados[7] ?? 'Branca'));
        $graus       = (int)($dados[8] ?? 0);
        $total_aulas = (int)($dados[9] ?? 0);

        if ($usuario == "") continue;

        // 5. VERIFICAÇÃO DE EXISTÊNCIA
        $query = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :u");
        $query->bindValue(":u", $usuario);
        $query->execute();

        if ($query->rowCount() == 0) {
            try {
                // Inserção do Usuário
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

                // Inserção da Graduação (apenas se for Cliente)
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
            } catch (PDOException $e) {
                // Se o erro de conversão ainda ocorrer em alguma célula específica, incrementa erro
                $cont_erro++;
            }
        } else {
            $ja_cadastrados++;
        }
    }
    fclose($handle);

    echo "<script>
            alert('Importação Concluída!\\nSucesso: $cont_sucesso\\nExistentes: $ja_cadastrados\\nErros: $cont_erro'); 
            window.location='index.php?pag=usuarios';
          </script>";
} else {
    echo "<script>alert('Arquivo inválido!'); window.location='index.php?pag=usuarios';</script>";
}
