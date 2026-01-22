<?php
require_once __DIR__ . '/../config/session.php';
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EficienSys - <?= $pageTitle ?? 'Plataforma' ?></title>
    <link rel="icon" type="image/png" href="../imagens/logoEficieSys.png">
    <link rel="stylesheet" href="https://cdn.squeleton.dev/squeleton.v4.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="https://cdn.squeleton.dev/squeleton-main.v4.min.js"></script>
</head>
<body class="app-body">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="../imagens/logoEficieSys.png" alt="EficienSys" class="sidebar-logo">
            <span class="sidebar-brand">EficienSys</span>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="iccon-home-1"></span>
                <span>Dashboard</span>
            </a>
            <a href="chat.php" class="nav-item <?= $currentPage === 'chat' ? 'active' : '' ?>">
                <span class="iccon-message-1"></span>
                <span>Mensagens</span>
                <span class="badge" id="msg-badge" style="display:none;">0</span>
            </a>
            <a href="tarefas.php" class="nav-item <?= $currentPage === 'tarefas' ? 'active' : '' ?>">
                <span class="iccon-check-1"></span>
                <span>Tarefas</span>
            </a>
            <a href="alertas.php" class="nav-item <?= $currentPage === 'alertas' ? 'active' : '' ?>">
                <span class="iccon-bell-1"></span>
                <span>Alertas</span>
            </a>

            <?php if (isManager()): ?>
            <div class="nav-divider">Gerenciamento</div>
            <a href="equipe.php" class="nav-item <?= $currentPage === 'equipe' ? 'active' : '' ?>">
                <span class="iccon-users-1"></span>
                <span>Equipe</span>
            </a>
            <a href="relatorios.php" class="nav-item <?= $currentPage === 'relatorios' ? 'active' : '' ?>">
                <span class="iccon-chart-1"></span>
                <span>Relatorios</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="perfil.php" class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['nome'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= $currentUser['nome'] ?? 'Usuario' ?></span>
                    <span class="user-role"><?= ucfirst($currentUser['cargo'] ?? 'colaborador') ?></span>
                </div>
            </a>
            <a href="logout.php" class="logout-btn" title="Sair">
                <span class="iccon-logout-1"></span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-wrapper">
        <!-- Top Bar -->
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">
                <span class="iccon-menu-1"></span>
            </button>

            <div class="topbar-search">
                <span class="iccon-search-1"></span>
                <input type="text" placeholder="Buscar..." id="globalSearch">
            </div>

            <div class="topbar-actions">
                <button class="action-btn" id="notificationBtn">
                    <span class="iccon-bell-1"></span>
                    <span class="notification-dot" id="notifDot" style="display:none;"></span>
                </button>
                <div class="dropdown" id="notificationDropdown">
                    <div class="dropdown-header">
                        <span>Notificacoes</span>
                        <a href="#" id="markAllRead">Marcar todas como lidas</a>
                    </div>
                    <div class="dropdown-content" id="notificationList">
                        <p class="empty-state">Nenhuma notificacao</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="main-content">
