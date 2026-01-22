<?php
session_start();

// Se nao estiver logado, volta para login
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario'];

// Logout
if (isset($_GET['sair'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Arquivo de mensagens
$arquivoMensagens = __DIR__ . '/mensagens.json';

// Carregar mensagens
function carregarMensagens() {
    global $arquivoMensagens;
    if (file_exists($arquivoMensagens)) {
        return json_decode(file_get_contents($arquivoMensagens), true) ?? [];
    }
    return [];
}

// Salvar mensagens
function salvarMensagens($msgs) {
    global $arquivoMensagens;
    file_put_contents($arquivoMensagens, json_encode($msgs, JSON_PRETTY_PRINT));
}

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    $texto = trim($_POST['mensagem']);
    if ($texto !== '') {
        $mensagens = carregarMensagens();
        $mensagens[] = [
            'nome' => $usuario['nome'],
            'cargo' => $usuario['cargo'],
            'texto' => htmlspecialchars($texto),
            'data' => date('d/m/Y H:i')
        ];
        // Manter apenas ultimas 50
        $mensagens = array_slice($mensagens, -50);
        salvarMensagens($mensagens);
    }
    header('Location: sistema.php');
    exit;
}

$mensagens = carregarMensagens();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EficienSys - Sistema</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: #166534;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-logo img {
            height: 40px;
        }

        .header-logo h1 {
            font-size: 20px;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-user span {
            font-size: 14px;
        }

        .cargo-badge {
            background: #22c55e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .btn-sair {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-sair:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Container */
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* Card */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            background: #f9f9f9;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #333;
        }

        .card-body {
            padding: 20px;
        }

        /* Boas vindas */
        .welcome {
            background: linear-gradient(135deg, #166534, #22c55e);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .welcome h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .welcome p {
            opacity: 0.9;
        }

        /* Form mensagem */
        .form-mensagem {
            display: flex;
            gap: 10px;
        }

        .form-mensagem input {
            flex: 1;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }

        .form-mensagem input:focus {
            outline: none;
            border-color: #22c55e;
        }

        .form-mensagem button {
            padding: 14px 24px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-mensagem button:hover {
            background: #16a34a;
        }

        /* Lista mensagens */
        .mensagens-lista {
            max-height: 400px;
            overflow-y: auto;
        }

        .mensagem-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .mensagem-item:last-child {
            border-bottom: none;
        }

        .mensagem-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .mensagem-nome {
            font-weight: bold;
            color: #166534;
        }

        .mensagem-cargo {
            background: #e8f5e9;
            color: #166534;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 8px;
        }

        .mensagem-data {
            color: #999;
            font-size: 12px;
        }

        .mensagem-texto {
            color: #444;
            line-height: 1.5;
        }

        .sem-mensagens {
            text-align: center;
            color: #999;
            padding: 40px;
        }

        /* Responsivo */
        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .form-mensagem {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-logo">
            <img src="../imagens/logoEficieSys.png" alt="Logo" onerror="this.style.display='none'">
            <h1>EficienSys</h1>
        </div>

        <div class="header-user">
            <span>
                <?= htmlspecialchars($usuario['nome']) ?>
                <span class="cargo-badge"><?= ucfirst($usuario['cargo']) ?></span>
            </span>
            <a href="?sair=1" class="btn-sair">Sair</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome">
            <h2>Ola, <?= htmlspecialchars(explode(' ', $usuario['nome'])[0]) ?>!</h2>
            <p>Bem-vindo ao sistema de comunicacao da equipe.</p>
        </div>

        <div class="card">
            <div class="card-header">
                Enviar Mensagem para a Equipe
            </div>
            <div class="card-body">
                <form method="POST" class="form-mensagem">
                    <input type="text" name="mensagem" placeholder="Digite sua mensagem aqui..." required autofocus>
                    <button type="submit">Enviar</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Mural de Mensagens (<?= count($mensagens) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($mensagens)): ?>
                    <div class="sem-mensagens">
                        Nenhuma mensagem ainda.<br>Seja o primeiro a enviar!
                    </div>
                <?php else: ?>
                    <div class="mensagens-lista">
                        <?php foreach (array_reverse($mensagens) as $msg): ?>
                            <div class="mensagem-item">
                                <div class="mensagem-header">
                                    <div>
                                        <span class="mensagem-nome"><?= $msg['nome'] ?></span>
                                        <span class="mensagem-cargo"><?= ucfirst($msg['cargo']) ?></span>
                                    </div>
                                    <span class="mensagem-data"><?= $msg['data'] ?></span>
                                </div>
                                <div class="mensagem-texto"><?= $msg['texto'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Atualiza a pagina a cada 30 segundos para ver novas mensagens
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
