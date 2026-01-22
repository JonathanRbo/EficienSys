<?php
/**
 * EficienSys - Dashboard Principal
 */

$pageTitle = 'Dashboard';
require_once 'includes/header.php';
requireLogin();

$db = getConnection();
$userId = $_SESSION['user_id'];
$isGerente = isManager();

// Estatisticas
$stats = [];

// Total de mensagens nao lidas
$stmt = $db->prepare("SELECT COUNT(*) as total FROM mensagens WHERE destinatario_id = ? AND lida = 0");
$stmt->execute([$userId]);
$stats['mensagens'] = $stmt->fetch()['total'];

// Tarefas pendentes
$stmt = $db->prepare("SELECT COUNT(*) as total FROM tarefas WHERE responsavel_id = ? AND status IN ('pendente', 'em_andamento')");
$stmt->execute([$userId]);
$stats['tarefas'] = $stmt->fetch()['total'];

// Alertas abertos
$stmt = $db->prepare("SELECT COUNT(*) as total FROM alertas WHERE status IN ('aberto', 'em_analise')");
$stmt->execute();
$stats['alertas'] = $stmt->fetch()['total'];

// Usuarios online (gerente)
if ($isGerente) {
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'online'");
    $stats['online'] = $stmt->fetch()['total'];

    // Total de usuarios
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE status != 'inativo'");
    $stats['usuarios'] = $stmt->fetch()['total'];
}

// Ultimas tarefas
$stmt = $db->prepare("
    SELECT t.*, u.nome as responsavel_nome
    FROM tarefas t
    LEFT JOIN usuarios u ON t.responsavel_id = u.id
    WHERE t.responsavel_id = ? OR t.criador_id = ?
    ORDER BY t.criado_em DESC
    LIMIT 5
");
$stmt->execute([$userId, $userId]);
$tarefas = $stmt->fetchAll();

// Ultimos alertas
$stmt = $db->query("
    SELECT a.*, u.nome as criador_nome
    FROM alertas a
    LEFT JOIN usuarios u ON a.criador_id = u.id
    WHERE a.status IN ('aberto', 'em_analise')
    ORDER BY
        CASE a.prioridade
            WHEN 'critica' THEN 1
            WHEN 'alta' THEN 2
            WHEN 'media' THEN 3
            ELSE 4
        END,
        a.criado_em DESC
    LIMIT 5
");
$alertas = $stmt->fetchAll();

// Atividade recente (ultimas mensagens)
$stmt = $db->prepare("
    SELECT m.*,
           ur.nome as remetente_nome,
           ud.nome as destinatario_nome
    FROM mensagens m
    LEFT JOIN usuarios ur ON m.remetente_id = ur.id
    LEFT JOIN usuarios ud ON m.destinatario_id = ud.id
    WHERE m.remetente_id = ? OR m.destinatario_id = ?
    ORDER BY m.criado_em DESC
    LIMIT 5
");
$stmt->execute([$userId, $userId]);
$mensagens = $stmt->fetchAll();
?>

<div class="page-header">
    <h1 class="page-title">Ola, <?= explode(' ', $currentUser['nome'])[0] ?>!</h1>
    <p class="page-subtitle">Aqui esta o resumo das suas atividades</p>
</div>

<!-- Stats Cards -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <span class="iccon-message-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['mensagens'] ?></h3>
            <p>Mensagens nao lidas</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <span class="iccon-check-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['tarefas'] ?></h3>
            <p>Tarefas pendentes</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <span class="iccon-bell-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['alertas'] ?></h3>
            <p>Alertas abertos</p>
        </div>
    </div>

    <?php if ($isGerente): ?>
    <div class="stat-card">
        <div class="stat-icon green">
            <span class="iccon-users-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['online'] ?>/<?= $stats['usuarios'] ?></h3>
            <p>Equipe online</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row gap-20">
    <!-- Alertas Recentes -->
    <div class="c-xs-12 c-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alertas Recentes</h3>
                <a href="alertas.php" class="btn btn-sm btn-secondary">Ver todos</a>
            </div>
            <div class="card-body">
                <?php if (empty($alertas)): ?>
                    <p class="empty-state">Nenhum alerta aberto</p>
                <?php else: ?>
                    <?php foreach ($alertas as $alerta): ?>
                    <div class="alert-item">
                        <div class="alert-icon <?= $alerta['prioridade'] ?>">
                            <span class="iccon-alert-1"></span>
                        </div>
                        <div class="alert-content">
                            <h4 class="alert-title"><?= htmlspecialchars($alerta['titulo']) ?></h4>
                            <p class="alert-desc"><?= htmlspecialchars(substr($alerta['descricao'] ?? '', 0, 80)) ?>...</p>
                            <div class="alert-meta">
                                <span><span class="iccon-user-1"></span> <?= htmlspecialchars($alerta['criador_nome']) ?></span>
                                <span><span class="iccon-clock-1"></span> <?= date('d/m H:i', strtotime($alerta['criado_em'])) ?></span>
                                <span class="priority priority-<?= $alerta['prioridade'] ?>"><?= ucfirst($alerta['prioridade']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tarefas -->
    <div class="c-xs-12 c-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Minhas Tarefas</h3>
                <a href="tarefas.php" class="btn btn-sm btn-secondary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (empty($tarefas)): ?>
                    <p class="empty-state">Nenhuma tarefa pendente</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tarefa</th>
                                    <th>Prioridade</th>
                                    <th>Status</th>
                                    <th>Prazo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tarefas as $tarefa): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tarefa['titulo']) ?></td>
                                    <td><span class="priority priority-<?= $tarefa['prioridade'] ?>"><?= ucfirst($tarefa['prioridade']) ?></span></td>
                                    <td><?= ucfirst(str_replace('_', ' ', $tarefa['status'])) ?></td>
                                    <td><?= $tarefa['prazo'] ? date('d/m/Y', strtotime($tarefa['prazo'])) : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isGerente): ?>
<!-- Acesso Rapido para Gerentes -->
<div class="row gap-20 m-20-t">
    <div class="c-xs-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Acoes Rapidas</h3>
            </div>
            <div class="card-body">
                <div class="d-flex f-gap-15 f-wrap">
                    <a href="tarefas.php?action=nova" class="btn btn-primary">
                        <span class="iccon-plus-1"></span> Nova Tarefa
                    </a>
                    <a href="alertas.php?action=novo" class="btn btn-secondary">
                        <span class="iccon-bell-1"></span> Novo Alerta
                    </a>
                    <a href="chat.php" class="btn btn-secondary">
                        <span class="iccon-message-1"></span> Enviar Mensagem
                    </a>
                    <a href="equipe.php" class="btn btn-secondary">
                        <span class="iccon-users-1"></span> Ver Equipe
                    </a>
                    <a href="relatorios.php" class="btn btn-secondary">
                        <span class="iccon-chart-1"></span> Relatorios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
