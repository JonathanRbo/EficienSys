<?php
/**
 * EficienSys - Pagina de Registro
 */

require_once 'config/session.php';

// Se ja estiver logado, redirecionar
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $departamento = $_POST['departamento'] ?? '';

    if (empty($nome) || empty($email) || empty($password)) {
        $error = 'Preencha todos os campos obrigatorios.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'As senhas nao conferem.';
    } else {
        $result = registerUser($nome, $email, $password, 'colaborador', $departamento);

        if ($result['success']) {
            $success = 'Cadastro realizado! Redirecionando...';
            // Login automatico
            loginUser($email, $password);
            header('Refresh: 2; URL=dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EficienSys - Cadastro</title>
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
                <h2>Crie sua conta</h2>
                <p>Preencha os dados para comecar</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Nome completo *</label>
                    <input type="text" name="nome" class="form-input" placeholder="Seu nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" placeholder="seu@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Departamento</label>
                    <select name="departamento" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="Producao">Producao</option>
                        <option value="Administrativo">Administrativo</option>
                        <option value="Comercial">Comercial</option>
                        <option value="TI">TI</option>
                        <option value="RH">RH</option>
                        <option value="Financeiro">Financeiro</option>
                        <option value="Logistica">Logistica</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Senha *</label>
                    <input type="password" name="password" class="form-input" placeholder="Minimo 6 caracteres" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar senha *</label>
                    <input type="password" name="password_confirm" class="form-input" placeholder="Repita a senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <span class="iccon-user-1"></span>
                    Criar conta
                </button>
            </form>

            <div class="auth-footer">
                Ja tem uma conta? <a href="login.php">Faca login</a>
            </div>
        </div>
    </div>
</body>
</html>
