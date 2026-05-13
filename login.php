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

    // Procura o utilizador no banco de dados
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
    <title>Login - Mural Digital Fatec</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --fatec: #b00000; }
        body { 
            background: #f4f7f6; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card { 
            border: none; 
            border-radius: 15px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .card-header { 
            background: var(--fatec); 
            color: white; 
            border: none;
            padding: 2rem 1rem;
        }
        .btn-fatec { 
            background: var(--fatec); 
            color: white; 
            border: none;
            padding: 12px;
            transition: 0.3s;
        }
        .btn-fatec:hover { 
            background: #8a0000; 
            color: white; 
            transform: translateY(-2px);
        }
        .form-control:focus {
            border-color: var(--fatec);
            box-shadow: 0 0 0 0.25rem rgba(176, 0, 0, 0.25);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="card p-0">
            <div class="card-header text-center">
                <h4 class="mb-0 fw-bold">Mural Digital Fatec</h4>
                <small class="opacity-75">Gestão Administrativa</small>
            </div>
            <div class="card-body p-4">
                
                <?php if ($erro): ?>
                    <div class="alert alert-danger text-center small py-2"><?= $erro ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Utilizador</label>
                        <input type="text" name="login" class="form-control" placeholder="Ex: admin" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Senha</label>
                        <input type="password" name="senha" class="form-control" placeholder="••••••" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-fatec fw-bold">Entrar no Sistema</button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <span class="small text-muted">Não tem acesso?</span>
                    <a href="cadastro.php" class="small fw-bold text-danger text-decoration-none ms-1">Cadastre-se aqui</a>
                </div>
                
            </div>
            <div class="card-footer text-center text-muted small py-3 bg-white border-0">
                &copy; 2026 - Fatec Presidente Prudente
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>