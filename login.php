<?php
session_start();
require_once 'config.php';

// Se o utilizador já estiver logado, redireciona direto para o painel
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: admin.php");
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'];
    $senha = $_POST['senha'];

    // Procura o utilizador no banco de dados (Tabela usuarios)
    $sql = "SELECT * FROM usuarios WHERE login = :login AND senha = :senha";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['login' => $login, 'senha' => $senha]);
    $user = $stmt->fetch();

    if ($user) {
        // Cria a sessão de segurança
        $_SESSION['logado'] = true;
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['login'];
        
        header("Location: admin.php");
        exit;
    } else {
        $erro = "Utilizador ou senha incorretos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão Administrativa Fatec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { margin-top: 100px; max-width: 400px; }
        .card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card-header { background-color: #b00000; color: white; border-radius: 12px 12px 0 0 !important; }
        .btn-fatec { background-color: #b00000; color: white; border: none; }
        .btn-fatec:hover { background-color: #8a0000; color: white; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="login-container w-100">
        <div class="card">
            <div class="card-header text-center py-3">
                <h4 class="mb-0 fw-bold">Mural Digital Fatec</h4>
                <small>Gestão Administrativa</small>
            </div>
            <div class="card-body p-4">
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger text-center small"><?= $erro ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Utilizador</label>
                        <input type="text" name="login" class="form-control" placeholder="Ex: admin" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Senha</label>
                        <input type="password" name="senha" class="form-control" placeholder="••••••" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-fatec fw-bold">Entrar no Sistema</button>
                    </div>
                </form>
                
            </div>
            <div class="card-footer text-center text-muted small py-3">
                &copy; 2026 - Fatec Presidente Prudente
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>