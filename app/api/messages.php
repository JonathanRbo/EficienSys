<?php
/**
 * EficienSys - API de Mensagens
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
    case 'send':
        // Enviar mensagem
        $destinatario = isset($_POST['destinatario']) ? (int)$_POST['destinatario'] : null;
        $grupo = isset($_POST['grupo']) ? (int)$_POST['grupo'] : null;
        $mensagem = trim($_POST['mensagem'] ?? '');

        if (empty($mensagem)) {
            echo json_encode(['error' => 'Mensagem vazia']);
            exit;
        }

        if (!$destinatario && !$grupo) {
            echo json_encode(['error' => 'Destinatario invalido']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO mensagens (remetente_id, destinatario_id, grupo_id, conteudo)
            VALUES (?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $userId,
            $destinatario ?: null,
            $grupo ?: null,
            sanitize($mensagem)
        ]);

        if ($result) {
            // Criar notificacao para destinatario
            if ($destinatario) {
                $notifStmt = $db->prepare("
                    INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, link)
                    VALUES (?, 'Nova mensagem', ?, 'mensagem', ?)
                ");
                $notifStmt->execute([
                    $destinatario,
                    $_SESSION['user_nome'] . ' enviou uma mensagem',
                    'chat.php?user=' . $userId
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $db->lastInsertId(),
                    'conteudo' => $mensagem,
                    'remetente_nome' => $_SESSION['user_nome'],
                    'criado_em' => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Erro ao enviar mensagem']);
        }
        break;

    case 'get_new':
        // Buscar novas mensagens
        $chatUserId = isset($_GET['user']) && $_GET['user'] ? (int)$_GET['user'] : null;
        $grupoId = isset($_GET['grupo']) && $_GET['grupo'] ? (int)$_GET['grupo'] : null;
        $lastId = isset($_GET['last']) ? (int)$_GET['last'] : 0;

        $messages = [];

        if ($chatUserId) {
            $stmt = $db->prepare("
                SELECT m.*, u.nome as remetente_nome,
                       (m.remetente_id = ?) as is_mine
                FROM mensagens m
                JOIN usuarios u ON m.remetente_id = u.id
                WHERE ((m.remetente_id = ? AND m.destinatario_id = ?)
                    OR (m.remetente_id = ? AND m.destinatario_id = ?))
                AND m.remetente_id != ?
                AND m.lida = 0
                ORDER BY m.criado_em ASC
            ");
            $stmt->execute([$userId, $userId, $chatUserId, $chatUserId, $userId, $userId]);
            $messages = $stmt->fetchAll();

            // Marcar como lidas
            if (!empty($messages)) {
                $db->prepare("
                    UPDATE mensagens SET lida = 1
                    WHERE destinatario_id = ? AND remetente_id = ?
                ")->execute([$userId, $chatUserId]);
            }
        } elseif ($grupoId) {
            $stmt = $db->prepare("
                SELECT m.*, u.nome as remetente_nome,
                       (m.remetente_id = ?) as is_mine
                FROM mensagens m
                JOIN usuarios u ON m.remetente_id = u.id
                WHERE m.grupo_id = ?
                AND m.remetente_id != ?
                AND m.criado_em > DATE_SUB(NOW(), INTERVAL 10 SECOND)
                ORDER BY m.criado_em ASC
            ");
            $stmt->execute([$userId, $grupoId, $userId]);
            $messages = $stmt->fetchAll();
        }

        echo json_encode(['messages' => $messages]);
        break;

    case 'unread_count':
        // Contar mensagens nao lidas
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM mensagens WHERE destinatario_id = ? AND lida = 0");
        $stmt->execute([$userId]);
        echo json_encode($stmt->fetch());
        break;

    default:
        echo json_encode(['error' => 'Acao invalida']);
}
