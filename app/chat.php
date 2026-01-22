<?php
/**
 * EficienSys - Sistema de Chat
 */

$pageTitle = 'Mensagens';
require_once 'includes/header.php';
requireLogin();

$db = getConnection();
$userId = $_SESSION['user_id'];

// Buscar usuarios para conversar
$stmt = $db->prepare("
    SELECT id, nome, cargo, departamento, status
    FROM usuarios
    WHERE id != ? AND status != 'inativo'
    ORDER BY status = 'online' DESC, nome ASC
");
$stmt->execute([$userId]);
$usuarios = $stmt->fetchAll();

// Buscar grupos
$stmt = $db->prepare("
    SELECT g.*, COUNT(gm.usuario_id) as membros
    FROM grupos g
    JOIN grupo_membros gm ON g.id = gm.grupo_id
    WHERE gm.usuario_id = ?
    GROUP BY g.id
");
$stmt->execute([$userId]);
$grupos = $stmt->fetchAll();

// Usuario selecionado para conversa
$chatUserId = isset($_GET['user']) ? (int)$_GET['user'] : null;
$chatGrupoId = isset($_GET['grupo']) ? (int)$_GET['grupo'] : null;

$chatUser = null;
$chatGrupo = null;
$mensagens = [];

if ($chatUserId) {
    $stmt = $db->prepare("SELECT id, nome, cargo, status FROM usuarios WHERE id = ?");
    $stmt->execute([$chatUserId]);
    $chatUser = $stmt->fetch();

    if ($chatUser) {
        // Buscar mensagens da conversa
        $stmt = $db->prepare("
            SELECT m.*, u.nome as remetente_nome
            FROM mensagens m
            JOIN usuarios u ON m.remetente_id = u.id
            WHERE (m.remetente_id = ? AND m.destinatario_id = ?)
               OR (m.remetente_id = ? AND m.destinatario_id = ?)
            ORDER BY m.criado_em ASC
            LIMIT 100
        ");
        $stmt->execute([$userId, $chatUserId, $chatUserId, $userId]);
        $mensagens = $stmt->fetchAll();

        // Marcar como lidas
        $db->prepare("UPDATE mensagens SET lida = 1 WHERE destinatario_id = ? AND remetente_id = ?")->execute([$userId, $chatUserId]);
    }
} elseif ($chatGrupoId) {
    $stmt = $db->prepare("SELECT * FROM grupos WHERE id = ?");
    $stmt->execute([$chatGrupoId]);
    $chatGrupo = $stmt->fetch();

    if ($chatGrupo) {
        // Buscar mensagens do grupo
        $stmt = $db->prepare("
            SELECT m.*, u.nome as remetente_nome
            FROM mensagens m
            JOIN usuarios u ON m.remetente_id = u.id
            WHERE m.grupo_id = ?
            ORDER BY m.criado_em ASC
            LIMIT 100
        ");
        $stmt->execute([$chatGrupoId]);
        $mensagens = $stmt->fetchAll();
    }
}

$extraScripts = '<script src="assets/js/chat.js"></script>';
?>

<div class="chat-container">
    <!-- Sidebar de Contatos -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <input type="text" class="form-input" placeholder="Buscar conversa..." id="searchChat">
        </div>

        <div class="chat-list">
            <!-- Grupos -->
            <?php if (!empty($grupos)): ?>
                <div class="nav-divider" style="padding: 10px 16px; font-size: 0.75rem; color: var(--text-muted);">GRUPOS</div>
                <?php foreach ($grupos as $grupo): ?>
                <a href="?grupo=<?= $grupo['id'] ?>" class="chat-item <?= $chatGrupoId == $grupo['id'] ? 'active' : '' ?>">
                    <div class="chat-avatar" style="background: var(--secondary);">
                        <span class="iccon-users-1"></span>
                    </div>
                    <div class="chat-info">
                        <div class="chat-name"><?= htmlspecialchars($grupo['nome']) ?></div>
                        <div class="chat-preview"><?= $grupo['membros'] ?> membros</div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Usuarios -->
            <div class="nav-divider" style="padding: 10px 16px; font-size: 0.75rem; color: var(--text-muted);">CONVERSAS DIRETAS</div>
            <?php foreach ($usuarios as $user): ?>
            <a href="?user=<?= $user['id'] ?>" class="chat-item <?= $chatUserId == $user['id'] ? 'active' : '' ?>">
                <div class="chat-avatar <?= $user['status'] === 'online' ? 'online' : '' ?>">
                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                </div>
                <div class="chat-info">
                    <div class="chat-name"><?= htmlspecialchars($user['nome']) ?></div>
                    <div class="chat-preview"><?= ucfirst($user['cargo']) ?> - <?= $user['departamento'] ?: 'Sem departamento' ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Area de Chat -->
    <div class="chat-main">
        <?php if ($chatUser || $chatGrupo): ?>
            <div class="chat-header">
                <div class="chat-avatar <?= ($chatUser && $chatUser['status'] === 'online') ? 'online' : '' ?>">
                    <?php if ($chatUser): ?>
                        <?= strtoupper(substr($chatUser['nome'], 0, 1)) ?>
                    <?php else: ?>
                        <span class="iccon-users-1"></span>
                    <?php endif; ?>
                </div>
                <div class="chat-info">
                    <div class="chat-name"><?= htmlspecialchars($chatUser['nome'] ?? $chatGrupo['nome']) ?></div>
                    <div class="chat-preview">
                        <?php if ($chatUser): ?>
                            <span class="status status-<?= $chatUser['status'] === 'online' ? 'online' : 'offline' ?>">
                                <?= ucfirst($chatUser['status']) ?>
                            </span>
                        <?php else: ?>
                            Canal de grupo
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages" data-user="<?= $chatUserId ?>" data-grupo="<?= $chatGrupoId ?>">
                <?php if (empty($mensagens)): ?>
                    <p class="empty-state" style="margin: auto;">Nenhuma mensagem ainda. Inicie a conversa!</p>
                <?php else: ?>
                    <?php foreach ($mensagens as $msg): ?>
                    <div class="message <?= $msg['remetente_id'] == $userId ? 'sent' : '' ?>">
                        <div class="message-avatar">
                            <?= strtoupper(substr($msg['remetente_nome'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="message-content">
                                <?= nl2br(htmlspecialchars($msg['conteudo'])) ?>
                            </div>
                            <div class="message-time">
                                <?= date('H:i', strtotime($msg['criado_em'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form class="chat-input" id="chatForm">
                <input type="hidden" name="destinatario" value="<?= $chatUserId ?>">
                <input type="hidden" name="grupo" value="<?= $chatGrupoId ?>">
                <input type="text" name="mensagem" placeholder="Digite sua mensagem..." autocomplete="off" required>
                <button type="submit">
                    <span class="iccon-send-1"></span>
                </button>
            </form>
        <?php else: ?>
            <div class="d-flex f-items-center f-justify-center h-100">
                <div class="text-center">
                    <span class="iccon-message-1" style="font-size: 4rem; color: var(--text-muted);"></span>
                    <h3 style="margin-top: 16px; color: var(--text-secondary);">Selecione uma conversa</h3>
                    <p style="color: var(--text-muted);">Escolha um usuario ou grupo para iniciar</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
