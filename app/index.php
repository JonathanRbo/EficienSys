<?php
/**
 * EficienSys - Sistema Simples de Comunicacao
 * Sem banco de dados - usa arquivos JSON
 */

session_start();

// Arquivos de dados
$dataDir = __DIR__ . '/data/';
$usersFile = $dataDir . 'users.json';
$messagesFile = $dataDir . 'messages.json';
$alertsFile = $dataDir . 'alerts.json';

// Criar arquivos se nao existirem
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, json_encode([
        ['id' => 1, 'nome' => 'Admin', 'email' => 'admin@eficiensys.com', 'senha' => password_hash('123456', PASSWORD_DEFAULT), 'cargo' => 'gerente'],
        ['id' => 2, 'nome' => 'Colaborador', 'email' => 'colab@eficiensys.com', 'senha' => password_hash('123456', PASSWORD_DEFAULT), 'cargo' => 'colaborador']
    ]));
}
if (!file_exists($messagesFile)) file_put_contents($messagesFile, '[]');
if (!file_exists($alertsFile)) file_put_contents($alertsFile, '[]');

// Funcoes auxiliares
function getUsers() { global $usersFile; return json_decode(file_get_contents($usersFile), true) ?: []; }
function getMessages() { global $messagesFile; return json_decode(file_get_contents($messagesFile), true) ?: []; }
function getAlerts() { global $alertsFile; return json_decode(file_get_contents($alertsFile), true) ?: []; }
function saveMessages($data) { global $messagesFile; file_put_contents($messagesFile, json_encode($data)); }
function saveAlerts($data) { global $alertsFile; file_put_contents($alertsFile, json_encode($data)); }
function saveUsers($data) { global $usersFile; file_put_contents($usersFile, json_encode($data)); }

// Processar acoes
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$error = '';
$success = '';

// Login
if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $users = getUsers();

    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($senha, $user['senha'])) {
            $_SESSION['user'] = $user;
            header('Location: index.php');
            exit;
        }
    }
    $error = 'Email ou senha incorretos';
}

// Registro
if ($action === 'register') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($nome && $email && strlen($senha) >= 4) {
        $users = getUsers();
        foreach ($users as $u) {
            if ($u['email'] === $email) { $error = 'Email ja cadastrado'; break; }
        }
        if (!$error) {
            $users[] = [
                'id' => count($users) + 1,
                'nome' => $nome,
                'email' => $email,
                'senha' => password_hash($senha, PASSWORD_DEFAULT),
                'cargo' => 'colaborador'
            ];
            saveUsers($users);
            $success = 'Cadastro realizado! Faca login.';
        }
    } else {
        $error = 'Preencha todos os campos (senha min. 4 caracteres)';
    }
}

// Logout
if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Enviar mensagem
if ($action === 'send_message' && isset($_SESSION['user'])) {
    $texto = trim($_POST['texto'] ?? '');
    if ($texto) {
        $messages = getMessages();
        $messages[] = [
            'id' => count($messages) + 1,
            'user_id' => $_SESSION['user']['id'],
            'user_nome' => $_SESSION['user']['nome'],
            'texto' => htmlspecialchars($texto),
            'data' => date('d/m/Y H:i')
        ];
        // Manter apenas as ultimas 50 mensagens
        $messages = array_slice($messages, -50);
        saveMessages($messages);
    }
    header('Location: index.php?page=chat');
    exit;
}

// Criar alerta
if ($action === 'send_alert' && isset($_SESSION['user'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $prioridade = $_POST['prioridade'] ?? 'media';

    if ($titulo) {
        $alerts = getAlerts();
        $alerts[] = [
            'id' => count($alerts) + 1,
            'titulo' => htmlspecialchars($titulo),
            'descricao' => htmlspecialchars($descricao),
            'prioridade' => $prioridade,
            'status' => 'aberto',
            'user_nome' => $_SESSION['user']['nome'],
            'data' => date('d/m/Y H:i')
        ];
        saveAlerts($alerts);
        $success = 'Alerta registrado!';
    }
    header('Location: index.php?page=alertas');
    exit;
}

// Resolver alerta (gerente)
if ($action === 'resolve_alert' && isset($_SESSION['user']) && $_SESSION['user']['cargo'] === 'gerente') {
    $alertId = (int)($_POST['alert_id'] ?? 0);
    $alerts = getAlerts();
    foreach ($alerts as &$a) {
        if ($a['id'] === $alertId) {
            $a['status'] = 'resolvido';
            break;
        }
    }
    saveAlerts($alerts);
    header('Location: index.php?page=alertas');
    exit;
}

$page = $_GET['page'] ?? 'home';
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EficienSys</title>
    <link rel="icon" href="../imagens/logoEficieSys.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0fdf4; min-height: 100vh; }

        /* Header */
        .header { background: #166534; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-logo { display: flex; align-items: center; gap: 10px; }
        .header-logo img { height: 40px; }
        .header-logo span { font-size: 1.3rem; font-weight: 700; }
        .header-nav { display: flex; gap: 10px; }
        .header-nav a { color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; transition: 0.2s; }
        .header-nav a:hover, .header-nav a.active { background: rgba(255,255,255,0.2); }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-badge { background: #22c55e; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; }

        /* Container */
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }

        /* Cards */
        .card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 20px; }
        .card-header { background: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 20px; }

        /* Forms */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #374151; }
        .form-input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: 0.2s; }
        .form-input:focus { outline: none; border-color: #22c55e; }
        select.form-input { cursor: pointer; }

        /* Buttons */
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #22c55e; color: white; }
        .btn-primary:hover { background: #16a34a; }
        .btn-secondary { background: #e2e8f0; color: #374151; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        .btn-danger { background: #ef4444; color: white; }

        /* Messages */
        .message-list { max-height: 400px; overflow-y: auto; }
        .message-item { padding: 12px; border-bottom: 1px solid #f1f5f9; }
        .message-item:last-child { border-bottom: none; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .message-user { font-weight: 600; color: #166534; }
        .message-time { font-size: 0.8rem; color: #94a3b8; }
        .message-text { color: #475569; line-height: 1.5; }

        /* Alerts */
        .alert-item { padding: 15px; border-left: 4px solid #22c55e; background: #f8fafc; margin-bottom: 10px; border-radius: 0 8px 8px 0; }
        .alert-item.alta { border-color: #f59e0b; background: #fffbeb; }
        .alert-item.critica { border-color: #ef4444; background: #fef2f2; }
        .alert-item.resolvido { opacity: 0.6; border-color: #94a3b8; }
        .alert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .alert-title { font-weight: 600; }
        .alert-badge { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-media { background: #dbeafe; color: #1d4ed8; }
        .badge-alta { background: #fef3c7; color: #b45309; }
        .badge-critica { background: #fee2e2; color: #dc2626; }
        .badge-resolvido { background: #e2e8f0; color: #64748b; }

        /* Auth page */
        .auth-container { max-width: 400px; margin: 80px auto; padding: 0 20px; }
        .auth-logo { text-align: center; margin-bottom: 30px; }
        .auth-logo img { height: 80px; margin-bottom: 10px; }
        .auth-logo h1 { color: #166534; }
        .auth-tabs { display: flex; margin-bottom: 20px; }
        .auth-tabs a { flex: 1; padding: 12px; text-align: center; text-decoration: none; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        .auth-tabs a.active { color: #166534; border-color: #22c55e; }

        /* Alerts */
        .msg-error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .msg-success { background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: 700; color: #22c55e; }
        .stat-label { color: #64748b; font-size: 0.9rem; }

        /* Empty state */
        .empty { text-align: center; padding: 40px; color: #94a3b8; }

        /* Responsive */
        @media (max-width: 600px) {
            .header { flex-direction: column; gap: 15px; }
            .header-nav { flex-wrap: wrap; justify-content: center; }
        }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- Pagina de Login/Registro -->
<div class="auth-container">
    <div class="auth-logo">
        <img src="../imagens/logoEficieSys.png" alt="EficienSys">
        <h1>EficienSys</h1>
        <p style="color: #64748b;">Comunicacao eficiente para sua equipe</p>
    </div>

    <?php if ($error): ?><div class="msg-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="msg-success"><?= $success ?></div><?php endif; ?>

    <div class="card">
        <div class="auth-tabs">
            <a href="?page=login" class="<?= $page !== 'register' ? 'active' : '' ?>">Entrar</a>
            <a href="?page=register" class="<?= $page === 'register' ? 'active' : '' ?>">Cadastrar</a>
        </div>

        <div class="card-body">
            <?php if ($page === 'register'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Criar conta</button>
            </form>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-input" required value="admin@eficiensys.com">
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" class="form-input" required value="123456">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar</button>
            </form>
            <p style="text-align: center; margin-top: 15px; color: #94a3b8; font-size: 0.85rem;">
                Usuarios teste: admin@eficiensys.com ou colab@eficiensys.com<br>Senha: 123456
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Sistema Logado -->
<header class="header">
    <div class="header-logo">
        <img src="../imagens/logoEficieSys.png" alt="EficienSys">
        <span>EficienSys</span>
    </div>

    <nav class="header-nav">
        <a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>">Inicio</a>
        <a href="?page=chat" class="<?= $page === 'chat' ? 'active' : '' ?>">Mural</a>
        <a href="?page=alertas" class="<?= $page === 'alertas' ? 'active' : '' ?>">Alertas</a>
        <?php if ($user['cargo'] === 'gerente'): ?>
        <a href="?page=equipe" class="<?= $page === 'equipe' ? 'active' : '' ?>">Equipe</a>
        <?php endif; ?>
    </nav>

    <div class="user-info">
        <span><?= htmlspecialchars($user['nome']) ?></span>
        <span class="user-badge"><?= ucfirst($user['cargo']) ?></span>
        <a href="?action=logout" class="btn btn-sm btn-secondary">Sair</a>
    </div>
</header>

<div class="container">
    <?php
    $messages = getMessages();
    $alerts = getAlerts();
    $users = getUsers();
    $alertsAbertos = array_filter($alerts, fn($a) => $a['status'] === 'aberto');
    ?>

    <?php if ($page === 'home'): ?>
    <!-- Dashboard -->
    <h2 style="margin-bottom: 20px;">Ola, <?= htmlspecialchars(explode(' ', $user['nome'])[0]) ?>!</h2>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?= count($messages) ?></div>
            <div class="stat-label">Mensagens</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($alertsAbertos) ?></div>
            <div class="stat-label">Alertas Abertos</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($users) ?></div>
            <div class="stat-label">Usuarios</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Ultimas Mensagens</div>
        <div class="card-body">
            <?php $lastMessages = array_slice(array_reverse($messages), 0, 3); ?>
            <?php if (empty($lastMessages)): ?>
                <p class="empty">Nenhuma mensagem ainda</p>
            <?php else: ?>
                <?php foreach ($lastMessages as $msg): ?>
                <div class="message-item">
                    <div class="message-header">
                        <span class="message-user"><?= $msg['user_nome'] ?></span>
                        <span class="message-time"><?= $msg['data'] ?></span>
                    </div>
                    <div class="message-text"><?= $msg['texto'] ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a href="?page=chat" class="btn btn-secondary btn-sm" style="margin-top: 10px;">Ver todas</a>
        </div>
    </div>

    <?php if (!empty($alertsAbertos)): ?>
    <div class="card">
        <div class="card-header" style="color: #dc2626;">Alertas em Aberto</div>
        <div class="card-body">
            <?php foreach (array_slice($alertsAbertos, 0, 3) as $alert): ?>
            <div class="alert-item <?= $alert['prioridade'] ?>">
                <div class="alert-header">
                    <span class="alert-title"><?= $alert['titulo'] ?></span>
                    <span class="alert-badge badge-<?= $alert['prioridade'] ?>"><?= ucfirst($alert['prioridade']) ?></span>
                </div>
                <p style="color: #64748b; font-size: 0.9rem;"><?= $alert['user_nome'] ?> - <?= $alert['data'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($page === 'chat'): ?>
    <!-- Mural de Mensagens -->
    <div class="card">
        <div class="card-header">Mural de Comunicacao</div>
        <div class="card-body">
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="send_message">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="texto" class="form-input" placeholder="Digite sua mensagem..." required>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </form>

            <div class="message-list">
                <?php if (empty($messages)): ?>
                    <p class="empty">Nenhuma mensagem ainda. Seja o primeiro!</p>
                <?php else: ?>
                    <?php foreach (array_reverse($messages) as $msg): ?>
                    <div class="message-item">
                        <div class="message-header">
                            <span class="message-user"><?= $msg['user_nome'] ?></span>
                            <span class="message-time"><?= $msg['data'] ?></span>
                        </div>
                        <div class="message-text"><?= $msg['texto'] ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php elseif ($page === 'alertas'): ?>
    <!-- Sistema de Alertas -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">Registrar Novo Alerta</div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="send_alert">
                <div class="form-group">
                    <label>Titulo</label>
                    <input type="text" name="titulo" class="form-input" required placeholder="Descreva brevemente">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Descricao</label>
                        <input type="text" name="descricao" class="form-input" placeholder="Detalhes (opcional)">
                    </div>
                    <div class="form-group">
                        <label>Prioridade</label>
                        <select name="prioridade" class="form-input">
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Critica</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Registrar Alerta</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Alertas Registrados</div>
        <div class="card-body">
            <?php if (empty($alerts)): ?>
                <p class="empty">Nenhum alerta registrado</p>
            <?php else: ?>
                <?php foreach (array_reverse($alerts) as $alert): ?>
                <div class="alert-item <?= $alert['prioridade'] ?> <?= $alert['status'] ?>">
                    <div class="alert-header">
                        <span class="alert-title"><?= $alert['titulo'] ?></span>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <?php if ($alert['status'] === 'resolvido'): ?>
                                <span class="alert-badge badge-resolvido">Resolvido</span>
                            <?php else: ?>
                                <span class="alert-badge badge-<?= $alert['prioridade'] ?>"><?= ucfirst($alert['prioridade']) ?></span>
                                <?php if ($user['cargo'] === 'gerente'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="resolve_alert">
                                    <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Resolver</button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($alert['descricao']): ?>
                        <p style="margin: 8px 0; color: #475569;"><?= $alert['descricao'] ?></p>
                    <?php endif; ?>
                    <p style="color: #94a3b8; font-size: 0.85rem;"><?= $alert['user_nome'] ?> - <?= $alert['data'] ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($page === 'equipe' && $user['cargo'] === 'gerente'): ?>
    <!-- Equipe (Gerentes) -->
    <div class="card">
        <div class="card-header">Membros da Equipe</div>
        <div class="card-body">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0;">
                        <th style="text-align: left; padding: 10px;">Nome</th>
                        <th style="text-align: left; padding: 10px;">Email</th>
                        <th style="text-align: left; padding: 10px;">Cargo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px;"><?= htmlspecialchars($u['nome']) ?></td>
                        <td style="padding: 12px; color: #64748b;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="padding: 12px;">
                            <span class="alert-badge" style="background: <?= $u['cargo'] === 'gerente' ? '#dcfce7' : '#e2e8f0' ?>; color: <?= $u['cargo'] === 'gerente' ? '#166534' : '#64748b' ?>;">
                                <?= ucfirst($u['cargo']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
// Auto-refresh do mural a cada 30 segundos
<?php if ($isLoggedIn && $page === 'chat'): ?>
setTimeout(() => location.reload(), 30000);
<?php endif; ?>
</script>

</body>
</html>
