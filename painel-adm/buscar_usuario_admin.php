<?php
require_once("../conexao.php");
$busca = '%' . $_POST['busca'] . '%';

$query = $pdo->prepare("SELECT u.*, g.cor_faixa, g.graus, g.total_aulas 
                        FROM usuarios u 
                        LEFT JOIN graduacoes g ON u.id = g.usuario_id 
                        WHERE u.nome LIKE :b OR u.email LIKE :b LIMIT 5");
$query->execute([':b' => $busca]);
$res = $query->fetchAll(PDO::FETCH_ASSOC);

foreach ($res as $u) {
    echo '
    <div class="card border mb-3">
        <div class="card-body bg-light">
            <div class="row">
                <div class="col-md-3">
                    <label>Nível de Acesso</label>
                    <select id="nivel_' . $u['id'] . '" class="form-control form-control-sm">
                        <option ' . ($u['nivel_usuario'] == 'Cliente' ? 'selected' : '') . '>Cliente</option>
                        <option ' . ($u['nivel_usuario'] == 'Balconista' ? 'selected' : '') . '>Balconista</option>
                        <option ' . ($u['nivel_usuario'] == 'Admin' ? 'selected' : '') . '>Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Faixa</label>
                    <select id="faixa_' . $u['id'] . '" class="form-control form-control-sm">
                        <option value="white" ' . ($u['cor_faixa'] == 'white' ? 'selected' : '') . '>Branca</option>
                        <option value="blue" ' . ($u['cor_faixa'] == 'blue' ? 'selected' : '') . '>Azul</option>
                        <option value="purple" ' . ($u['cor_faixa'] == 'purple' ? 'selected' : '') . '>Roxa</option>
                        <option value="brown" ' . ($u['cor_faixa'] == 'brown' ? 'selected' : '') . '>Marrom</option>
                        <option value="black" ' . ($u['cor_faixa'] == 'black' ? 'selected' : '') . '>Preta</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Graus</label>
                    <input type="number" id="graus_' . $u['id'] . '" class="form-control form-control-sm" value="' . ($u['graus'] ?? 0) . '">
                </div>
                <div class="col-md-2">
                    <label>Total Aulas</label>
                    <input type="number" id="aulas_' . $u['id'] . '" class="form-control form-control-sm" value="' . ($u['total_aulas'] ?? 0) . '">
                </div>
                <div class="col-md-4 text-right">
                    <br>
                    <button class="btn btn-dark btn-sm" onclick="salvarAlteracoes(' . $u['id'] . ')">
                        <i class="fas fa-save"></i> SALVAR ALTERAÇÕES
                    </button>
                </div>
            </div>
            <small class="text-muted">Usuário: ' . $u['nome'] . ' | E-mail: ' . $u['email'] . '</small>
        </div>
    </div>';
}
