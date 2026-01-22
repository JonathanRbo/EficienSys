<?php
/**
 * EficienSys - API de Notificacoes
 */

header('Content-Type: application/json');

require_once '../config/session.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Nao autorizado']);
    exit;
}

$db = getConnection();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Buscar notificacoes
        $stmt = $db->prepare("
            SELECT * FROM notificacoes
            WHERE usuario_id = ?
            ORDER BY criado_em DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();

        echo json_encode(['notifications' => $notifications]);
        break;

    case 'read':
        // Marcar como lida
        $notifId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($notifId) {
            $stmt = $db->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$notifId, $userId]);
        }

        echo json_encode(['success' => true]);
        break;

    case 'read_all':
        // Marcar todas como lidas
        $stmt = $db->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true]);
        break;

    case 'count':
        // Contar nao lidas
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notificacoes WHERE usuario_id = ? AND lida = 0");
        $stmt->execute([$userId]);

        echo json_encode($stmt->fetch());
        break;

    default:
        echo json_encode(['error' => 'Acao invalida']);
}
