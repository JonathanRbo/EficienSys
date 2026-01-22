<?php
/**
 * EficienSys - Pagina de Login
 */

require_once 'config/session.php';

// Se ja estiver logado, redirecionar
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Preencha todos os campos.';
    } elseif (loginUser($email, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Email ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EficienSys - Login</title>
    <link rel="icon" type="image/png" href="../imagens/logoEficieSys.png">
    <link rel="stylesheet" href="https://cdn.squeleton.dev/squeleton.v4.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <img src="../imagens/logoEficieSys.png" alt="EficienSys">
                <h1>EficienSys</h1>
            </div>

            <div class="auth-title">
                <h2>Bem-vindo de volta!</h2>
                <p>Entre com suas credenciais para acessar</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Senha</label>
                    <input type="password" name="password" class="form-input" placeholder="Sua senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <span class="iccon-login-1"></span>
                    Entrar
                </button>
            </form>

            <div class="auth-footer">
                Nao tem uma conta? <a href="registro.php">Cadastre-se</a>
            </div>
        </div>
    </div>
</body>
</html>
