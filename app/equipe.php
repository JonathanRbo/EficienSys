<?php
/**
 * EficienSys - Gerenciamento de Equipe (Gerentes)
 */

$pageTitle = 'Equipe';
require_once 'includes/header.php';
requireManager();

$db = getConnection();
$message = '';
$error = '';

// Processar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'atualizar_cargo') {
        $usuarioId = (int)$_POST['usuario_id'];
        $cargo = $_POST['cargo'];

        $stmt = $db->prepare("UPDATE usuarios SET cargo = ? WHERE id = ?");
        if ($stmt->execute([$cargo, $usuarioId])) {
            $message = 'Cargo atualizado com sucesso!';
        }
    } elseif ($action === 'atualizar_status') {
        $usuarioId = (int)$_POST['usuario_id'];
        $status = $_POST['status'];

        $stmt = $db->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $usuarioId])) {
            $message = 'Status atualizado!';
        }
    }
}

// Filtros
$filtroDepartamento = $_GET['departamento'] ?? '';
$filtroCargo = $_GET['cargo'] ?? '';
$filtroStatus = $_GET['status'] ?? '';

// Buscar usuarios
$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($filtroDepartamento) {
    $sql .= " AND departamento = ?";
    $params[] = $filtroDepartamento;
}

if ($filtroCargo) {
    $sql .= " AND cargo = ?";
    $params[] = $filtroCargo;
}

if ($filtroStatus) {
    $sql .= " AND status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY status = 'online' DESC, nome ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Estatisticas
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM usuarios WHERE status != 'inativo'")->fetchColumn(),
    'online' => $db->query("SELECT COUNT(*) FROM usuarios WHERE status = 'online'")->fetchColumn(),
    'gerentes' => $db->query("SELECT COUNT(*) FROM usuarios WHERE cargo = 'gerente' AND status != 'inativo'")->fetchColumn(),
    'colaboradores' => $db->query("SELECT COUNT(*) FROM usuarios WHERE cargo = 'colaborador' AND status != 'inativo'")->fetchColumn()
];

// Departamentos
$departamentos = $db->query("SELECT DISTINCT departamento FROM usuarios WHERE departamento IS NOT NULL AND departamento != '' ORDER BY departamento")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
    <h1 class="page-title">Equipe</h1>
    <p class="page-subtitle">Gerencie os membros da sua equipe</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <span class="iccon-users-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['total'] ?></h3>
            <p>Total de Usuarios</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <span class="iccon-check-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['online'] ?></h3>
            <p>Online Agora</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <span class="iccon-star-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['gerentes'] ?></h3>
            <p>Gerentes</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <span class="iccon-user-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['colaboradores'] ?></h3>
            <p>Colaboradores</p>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card m-20-b">
    <div class="card-body">
        <form method="GET" class="d-flex f-gap-15 f-wrap f-items-end">
            <div class="form-group m-0-b">
                <label class="form-label">Departamento</label>
                <select name="departamento" class="form-select" style="width: 160px;">
                    <option value="">Todos</option>
                    <?php foreach ($departamentos as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>" <?= $filtroDepartamento === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group m-0-b">
                <label class="form-label">Cargo</label>
                <select name="cargo" class="form-select" style="width: 140px;">
                    <option value="">Todos</option>
                    <option value="gerente" <?= $filtroCargo === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    <option value="colaborador" <?= $filtroCargo === 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                </select>
            </div>
            <div class="form-group m-0-b">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" style="width: 130px;">
                    <option value="">Todos</option>
                    <option value="online" <?= $filtroStatus === 'online' ? 'selected' : '' ?>>Online</option>
                    <option value="ativo" <?= $filtroStatus === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                    <option value="ausente" <?= $filtroStatus === 'ausente' ? 'selected' : '' ?>>Ausente</option>
                    <option value="inativo" <?= $filtroStatus === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="equipe.php" class="btn btn-secondary">Limpar</a>
        </form>
    </div>
</div>

<!-- Lista de Usuarios -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Departamento</th>
                        <th>Cargo</th>
                        <th>Status</th>
                        <th>Ultimo Acesso</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex f-items-center f-gap-10">
                                <div class="chat-avatar <?= $user['status'] === 'online' ? 'online' : '' ?>" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                                </div>
                                <span><?= htmlspecialchars($user['nome']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['departamento'] ?: '-') ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="form_action" value="atualizar_cargo">
                                <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
                                <select name="cargo" class="form-select" style="width: 120px; padding: 4px 8px;" onchange="this.form.submit()">
                                    <option value="colaborador" <?= $user['cargo'] === 'colaborador' ? 'selected' : '' ?>>Colaborador</option>
                                    <option value="gerente" <?= $user['cargo'] === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <span class="status status-<?= $user['status'] === 'online' ? 'online' : ($user['status'] === 'ausente' ? 'away' : 'offline') ?>">
                                <?= ucfirst($user['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $user['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : 'Nunca' ?>
                        </td>
                        <td>
                            <a href="chat.php?user=<?= $user['id'] ?>" class="btn btn-sm btn-secondary" title="Enviar mensagem">
                                <span class="iccon-message-1"></span>
                            </a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="form_action" value="atualizar_status">
                                <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
                                <input type="hidden" name="status" value="<?= $user['status'] === 'inativo' ? 'ativo' : 'inativo' ?>">
                                <button type="submit" class="btn btn-sm <?= $user['status'] === 'inativo' ? 'btn-primary' : 'btn-danger' ?>" title="<?= $user['status'] === 'inativo' ? 'Ativar' : 'Desativar' ?>">
                                    <span class="iccon-<?= $user['status'] === 'inativo' ? 'check-1' : 'x-1' ?>"></span>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
