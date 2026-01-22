<?php
/**
 * EficienSys - Gerenciamento de Tarefas
 */

$pageTitle = 'Tarefas';
require_once 'includes/header.php';
requireLogin();

$db = getConnection();
$userId = $_SESSION['user_id'];
$isGerente = isManager();
$message = '';
$error = '';

// Processar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'criar') {
        $titulo = sanitize($_POST['titulo']);
        $descricao = sanitize($_POST['descricao']);
        $responsavel = (int)$_POST['responsavel_id'];
        $prioridade = $_POST['prioridade'];
        $prazo = $_POST['prazo'] ?: null;

        $stmt = $db->prepare("
            INSERT INTO tarefas (titulo, descricao, criador_id, responsavel_id, prioridade, prazo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$titulo, $descricao, $userId, $responsavel, $prioridade, $prazo])) {
            // Notificar responsavel
            if ($responsavel != $userId) {
                $notif = $db->prepare("INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, link) VALUES (?, ?, ?, 'tarefa', 'tarefas.php')");
                $notif->execute([$responsavel, 'Nova tarefa atribuida', $_SESSION['user_nome'] . ' atribuiu uma tarefa para voce: ' . $titulo]);
            }
            $message = 'Tarefa criada com sucesso!';
        } else {
            $error = 'Erro ao criar tarefa.';
        }
    } elseif ($action === 'atualizar_status') {
        $tarefaId = (int)$_POST['tarefa_id'];
        $status = $_POST['status'];

        $stmt = $db->prepare("UPDATE tarefas SET status = ? WHERE id = ? AND (responsavel_id = ? OR criador_id = ?)");
        if ($stmt->execute([$status, $tarefaId, $userId, $userId])) {
            $message = 'Status atualizado!';
        }
    }
}

// Buscar usuarios para atribuicao
$usuarios = $db->query("SELECT id, nome, cargo FROM usuarios WHERE status != 'inativo' ORDER BY nome")->fetchAll();

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$filtroPrioridade = $_GET['prioridade'] ?? '';

// Buscar tarefas
$sql = "
    SELECT t.*, u.nome as responsavel_nome, c.nome as criador_nome
    FROM tarefas t
    LEFT JOIN usuarios u ON t.responsavel_id = u.id
    LEFT JOIN usuarios c ON t.criador_id = c.id
    WHERE (t.responsavel_id = ? OR t.criador_id = ?)
";
$params = [$userId, $userId];

if ($filtroStatus) {
    $sql .= " AND t.status = ?";
    $params[] = $filtroStatus;
}

if ($filtroPrioridade) {
    $sql .= " AND t.prioridade = ?";
    $params[] = $filtroPrioridade;
}

$sql .= " ORDER BY
    CASE t.prioridade WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END,
    t.prazo ASC, t.criado_em DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tarefas = $stmt->fetchAll();

$showForm = isset($_GET['action']) && $_GET['action'] === 'nova';
?>

<div class="page-header d-flex f-justify-between f-items-center">
    <div>
        <h1 class="page-title">Tarefas</h1>
        <p class="page-subtitle">Gerencie suas atividades</p>
    </div>
    <a href="?action=nova" class="btn btn-primary">
        <span class="iccon-plus-1"></span> Nova Tarefa
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<?php if ($showForm): ?>
<!-- Formulario Nova Tarefa -->
<div class="card m-20-b">
    <div class="card-header">
        <h3 class="card-title">Nova Tarefa</h3>
        <a href="tarefas.php" class="btn btn-sm btn-secondary">Cancelar</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="form_action" value="criar">

            <div class="row gap-20">
                <div class="c-xs-12 c-md-8">
                    <div class="form-group">
                        <label class="form-label">Titulo *</label>
                        <input type="text" name="titulo" class="form-input" required>
                    </div>
                </div>
                <div class="c-xs-12 c-md-4">
                    <div class="form-group">
                        <label class="form-label">Prioridade</label>
                        <select name="prioridade" class="form-select">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descricao</label>
                <textarea name="descricao" class="form-textarea" rows="3"></textarea>
            </div>

            <div class="row gap-20">
                <div class="c-xs-12 c-md-6">
                    <div class="form-group">
                        <label class="form-label">Responsavel</label>
                        <select name="responsavel_id" class="form-select">
                            <?php foreach ($usuarios as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= $user['id'] == $userId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['nome']) ?> (<?= ucfirst($user['cargo']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="c-xs-12 c-md-6">
                    <div class="form-group">
                        <label class="form-label">Prazo</label>
                        <input type="date" name="prazo" class="form-input">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <span class="iccon-check-1"></span> Criar Tarefa
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card m-20-b">
    <div class="card-body">
        <form method="GET" class="d-flex f-gap-15 f-wrap f-items-end">
            <div class="form-group m-0-b">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" style="width: 150px;">
                    <option value="">Todos</option>
                    <option value="pendente" <?= $filtroStatus === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                    <option value="em_andamento" <?= $filtroStatus === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="concluida" <?= $filtroStatus === 'concluida' ? 'selected' : '' ?>>Concluida</option>
                </select>
            </div>
            <div class="form-group m-0-b">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select" style="width: 150px;">
                    <option value="">Todas</option>
                    <option value="baixa" <?= $filtroPrioridade === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                    <option value="media" <?= $filtroPrioridade === 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="alta" <?= $filtroPrioridade === 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="urgente" <?= $filtroPrioridade === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="tarefas.php" class="btn btn-secondary">Limpar</a>
        </form>
    </div>
</div>

<!-- Lista de Tarefas -->
<div class="card">
    <div class="card-body">
        <?php if (empty($tarefas)): ?>
            <p class="empty-state">Nenhuma tarefa encontrada.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarefa</th>
                            <th>Responsavel</th>
                            <th>Prioridade</th>
                            <th>Prazo</th>
                            <th>Status</th>
                            <th>Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tarefas as $tarefa): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($tarefa['titulo']) ?></strong>
                                <?php if ($tarefa['descricao']): ?>
                                    <br><small style="color: var(--text-muted);"><?= htmlspecialchars(substr($tarefa['descricao'], 0, 50)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($tarefa['responsavel_nome'] ?? '-') ?></td>
                            <td><span class="priority priority-<?= $tarefa['prioridade'] ?>"><?= ucfirst($tarefa['prioridade']) ?></span></td>
                            <td>
                                <?php if ($tarefa['prazo']): ?>
                                    <?php
                                    $prazo = strtotime($tarefa['prazo']);
                                    $hoje = strtotime('today');
                                    $atrasado = $prazo < $hoje && $tarefa['status'] !== 'concluida';
                                    ?>
                                    <span style="color: <?= $atrasado ? 'var(--danger)' : 'inherit' ?>">
                                        <?= date('d/m/Y', $prazo) ?>
                                        <?php if ($atrasado): ?><br><small>Atrasada</small><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="form_action" value="atualizar_status">
                                    <input type="hidden" name="tarefa_id" value="<?= $tarefa['id'] ?>">
                                    <select name="status" class="form-select" style="width: 130px; padding: 6px 10px;" onchange="this.form.submit()">
                                        <option value="pendente" <?= $tarefa['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                        <option value="em_andamento" <?= $tarefa['status'] === 'em_andamento' ? 'selected' : '' ?>>Em Andamento</option>
                                        <option value="concluida" <?= $tarefa['status'] === 'concluida' ? 'selected' : '' ?>>Concluida</option>
                                        <option value="cancelada" <?= $tarefa['status'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <span style="color: var(--text-muted); font-size: 0.8rem;">
                                    Criado por <?= htmlspecialchars($tarefa['criador_nome']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
