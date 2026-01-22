<?php
session_start();

// Se ja estiver logado, vai para o sistema
if (isset($_SESSION['usuario'])) {
    header('Location: sistema.php');
    exit;
}

$erro = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Usuarios fixos (simples, sem banco de dados)
    $usuarios = [
        ['email' => 'admin@eficiensys.com', 'senha' => '123456', 'nome' => 'Administrador', 'cargo' => 'gerente'],
        ['email' => 'joao@eficiensys.com', 'senha' => '123456', 'nome' => 'Joao Silva', 'cargo' => 'colaborador'],
        ['email' => 'maria@eficiensys.com', 'senha' => '123456', 'nome' => 'Maria Santos', 'cargo' => 'colaborador'],
    ];

    // Verificar credenciais
    $encontrou = false;
    foreach ($usuarios as $user) {
        if ($user['email'] === $email && $user['senha'] === $senha) {
            $_SESSION['usuario'] = $user;
            header('Location: sistema.php');
            exit;
        }
    }

    $erro = 'Email ou senha incorretos!';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EficienSys - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #166534 0%, #22c55e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            width: 80px;
            height: 80px;
        }

        .logo h1 {
            color: #166534;
            margin-top: 10px;
            font-size: 24px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #22c55e;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #16a34a;
        }

        .erro {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .usuarios-teste {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .usuarios-teste h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .usuarios-teste table {
            width: 100%;
            font-size: 13px;
        }

        .usuarios-teste td {
            padding: 5px;
            color: #888;
        }

        .usuarios-teste .email {
            color: #166534;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">
            <img src="../imagens/logoEficieSys.png" alt="Logo" onerror="this.style.display='none'">
            <h1>EficienSys</h1>
            <p>Sistema de Comunicacao</p>
        </div>

        <?php if ($erro): ?>
            <div class="erro"><?= $erro ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="Digite seu email">
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required placeholder="Digite sua senha">
            </div>

            <button type="submit" class="btn-login">ENTRAR</button>
        </form>

        <div class="usuarios-teste">
            <h3>Usuarios para teste:</h3>
            <table>
                <tr>
                    <td class="email">admin@eficiensys.com</td>
                    <td>123456</td>
                    <td>(Gerente)</td>
                </tr>
                <tr>
                    <td class="email">joao@eficiensys.com</td>
                    <td>123456</td>
                    <td>(Colaborador)</td>
                </tr>
                <tr>
                    <td class="email">maria@eficiensys.com</td>
                    <td>123456</td>
                    <td>(Colaborador)</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
