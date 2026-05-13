<?php
require_once 'config.php';

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } else {
        // Verifica se o login já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE login = ?");
        $stmt->execute([$login]);
        
        if ($stmt->fetch()) {
            $erro = "Este nome de utilizador já está em uso!";
        } else {
            // Insere o novo usuário
            $sql = "INSERT INTO usuarios (login, senha) VALUES (:login, :senha)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute(['login' => $login, 'senha' => $senha])) {
                $sucesso = "Usuário cadastrado com sucesso! Redirecionando...";
                header("refresh:2;url=login.php");
            } else {
                $erro = "Erro ao cadastrar usuário.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Mural Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --fatec: #b00000; }
        body { background: #f4f7f6; height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card-header { background: var(--fatec); color: white; border-radius: 15px 15px 0 0 !important; }
        .btn-fatec { background: var(--fatec); color: white; border: none; }
        .btn-fatec:hover { background: #8a0000; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header text-center py-3">
                    <h4 class="mb-0 fw-bold">Novo Usuário</h4>
                </div>
                <div class="card-body p-4">
                    
                    <?php if ($erro): ?>
                        <div class="alert alert-danger text-center small"><?= $erro ?></div>
                    <?php endif; ?>

                    <?php if ($sucesso): ?>
                        <div class="alert alert-success text-center small"><?= $sucesso ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nome de Utilizador</label>
                            <input type="text" name="login" class="form-control" placeholder="Ex: joao_fatec" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Senha</label>
                            <input type="password" name="senha" class="form-control" placeholder="••••••" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small">Confirmar Senha</label>
                            <input type="password" name="confirmar_senha" class="form-control" placeholder="••••••" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-fatec fw-bold">Finalizar Cadastro</button>
                            <a href="login.php" class="btn btn-link btn-sm text-muted">Já tenho conta</a>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>