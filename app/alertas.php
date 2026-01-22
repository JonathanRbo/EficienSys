<?php
/**
 * EficienSys - Sistema de Alertas e Incidentes
 */

$pageTitle = 'Alertas';
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
        $tipo = $_POST['tipo'];
        $prioridade = $_POST['prioridade'];

        $stmt = $db->prepare("
            INSERT INTO alertas (titulo, descricao, tipo, prioridade, criador_id)
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$titulo, $descricao, $tipo, $prioridade, $userId])) {
            // Notificar gerentes
            $gerentes = $db->query("SELECT id FROM usuarios WHERE cargo = 'gerente' AND status != 'inativo'")->fetchAll();
            $notifStmt = $db->prepare("INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, link) VALUES (?, ?, ?, 'alerta', 'alertas.php')");

            foreach ($gerentes as $gerente) {
                if ($gerente['id'] != $userId) {
                    $notifStmt->execute([
                        $gerente['id'],
                        'Novo alerta: ' . ucfirst($prioridade),
                        $_SESSION['user_nome'] . ' reportou: ' . $titulo
                    ]);
                }
            }

            $message = 'Alerta registrado com sucesso!';
        } else {
            $error = 'Erro ao registrar alerta.';
        }
    } elseif ($action === 'atualizar_status' && $isGerente) {
        $alertaId = (int)$_POST['alerta_id'];
        $status = $_POST['status'];
        $responsavel = $status === 'em_analise' ? $userId : null;

        $stmt = $db->prepare("UPDATE alertas SET status = ?, responsavel_id = COALESCE(?, responsavel_id) WHERE id = ?");
        if ($stmt->execute([$status, $responsavel, $alertaId])) {
            if ($status === 'resolvido') {
                $db->prepare("UPDATE alertas SET resolvido_em = NOW() WHERE id = ?")->execute([$alertaId]);
            }
            $message = 'Status do alerta atualizado!';
        }
    }
}

// Filtros
$filtroStatus = $_GET['status'] ?? '';
$filtroTipo = $_GET['tipo'] ?? '';
$filtroPrioridade = $_GET['prioridade'] ?? '';

// Buscar alertas
$sql = "
    SELECT a.*, u.nome as criador_nome, r.nome as responsavel_nome
    FROM alertas a
    LEFT JOIN usuarios u ON a.criador_id = u.id
    LEFT JOIN usuarios r ON a.responsavel_id = r.id
    WHERE 1=1
";
$params = [];

if ($filtroStatus) {
    $sql .= " AND a.status = ?";
    $params[] = $filtroStatus;
}

if ($filtroTipo) {
    $sql .= " AND a.tipo = ?";
    $params[] = $filtroTipo;
}

if ($filtroPrioridade) {
    $sql .= " AND a.prioridade = ?";
    $params[] = $filtroPrioridade;
}

$sql .= " ORDER BY
    CASE a.prioridade WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END,
    a.criado_em DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$alertas = $stmt->fetchAll();

$showForm = isset($_GET['action']) && $_GET['action'] === 'novo';
?>

<div class="page-header d-flex f-justify-between f-items-center">
    <div>
        <h1 class="page-title">Alertas e Incidentes</h1>
        <p class="page-subtitle">Reporte e acompanhe ocorrencias</p>
    </div>
    <a href="?action=novo" class="btn btn-primary">
        <span class="iccon-plus-1"></span> Novo Alerta
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<?php if ($showForm): ?>
<!-- Formulario Novo Alerta -->
<div class="card m-20-b">
    <div class="card-header">
        <h3 class="card-title">Registrar Alerta</h3>
        <a href="alertas.php" class="btn btn-sm btn-secondary">Cancelar</a>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="form_action" value="criar">

            <div class="row gap-20">
                <div class="c-xs-12 c-md-6">
                    <div class="form-group">
                        <label class="form-label">Titulo *</label>
                        <input type="text" name="titulo" class="form-input" placeholder="Descreva brevemente o alerta" required>
                    </div>
                </div>
                <div class="c-xs-12 c-md-3">
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-select">
                            <option value="geral">Geral</option>
                            <option value="incidente">Incidente</option>
                            <option value="manutencao">Manutencao</option>
                            <option value="seguranca">Seguranca</option>
                        </select>
                    </div>
                </div>
                <div class="c-xs-12 c-md-3">
                    <div class="form-group">
                        <label class="form-label">Prioridade</label>
                        <select name="prioridade" class="form-select">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                            <option value="critica">Critica</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descricao detalhada</label>
                <textarea name="descricao" class="form-textarea" rows="4" placeholder="Descreva o alerta com detalhes..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <span class="iccon-bell-1"></span> Registrar Alerta
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
                <select name="status" class="form-select" style="width: 140px;">
                    <option value="">Todos</option>
                    <option value="aberto" <?= $filtroStatus === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                    <option value="em_analise" <?= $filtroStatus === 'em_analise' ? 'selected' : '' ?>>Em Analise</option>
                    <option value="resolvido" <?= $filtroStatus === 'resolvido' ? 'selected' : '' ?>>Resolvido</option>
                    <option value="fechado" <?= $filtroStatus === 'fechado' ? 'selected' : '' ?>>Fechado</option>
                </select>
            </div>
            <div class="form-group m-0-b">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" style="width: 140px;">
                    <option value="">Todos</option>
                    <option value="geral" <?= $filtroTipo === 'geral' ? 'selected' : '' ?>>Geral</option>
                    <option value="incidente" <?= $filtroTipo === 'incidente' ? 'selected' : '' ?>>Incidente</option>
                    <option value="manutencao" <?= $filtroTipo === 'manutencao' ? 'selected' : '' ?>>Manutencao</option>
                    <option value="seguranca" <?= $filtroTipo === 'seguranca' ? 'selected' : '' ?>>Seguranca</option>
                </select>
            </div>
            <div class="form-group m-0-b">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select" style="width: 130px;">
                    <option value="">Todas</option>
                    <option value="baixa" <?= $filtroPrioridade === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                    <option value="media" <?= $filtroPrioridade === 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="alta" <?= $filtroPrioridade === 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="critica" <?= $filtroPrioridade === 'critica' ? 'selected' : '' ?>>Critica</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="alertas.php" class="btn btn-secondary">Limpar</a>
        </form>
    </div>
</div>

<!-- Lista de Alertas -->
<div class="card">
    <div class="card-body">
        <?php if (empty($alertas)): ?>
            <p class="empty-state">Nenhum alerta encontrado.</p>
        <?php else: ?>
            <?php foreach ($alertas as $alerta): ?>
            <div class="alert-item">
                <div class="alert-icon <?= $alerta['prioridade'] ?>">
                    <?php
                    $iconMap = [
                        'incidente' => 'iccon-alert-1',
                        'manutencao' => 'iccon-tool-1',
                        'seguranca' => 'iccon-shield-1',
                        'geral' => 'iccon-bell-1'
                    ];
                    ?>
                    <span class="<?= $iconMap[$alerta['tipo']] ?? 'iccon-bell-1' ?>"></span>
                </div>
                <div class="alert-content" style="flex: 1;">
                    <div class="d-flex f-justify-between f-items-start">
                        <div>
                            <h4 class="alert-title"><?= htmlspecialchars($alerta['titulo']) ?></h4>
                            <?php if ($alerta['descricao']): ?>
                                <p class="alert-desc"><?= nl2br(htmlspecialchars($alerta['descricao'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($isGerente && $alerta['status'] !== 'fechado'): ?>
                        <form method="POST" style="flex-shrink: 0;">
                            <input type="hidden" name="form_action" value="atualizar_status">
                            <input type="hidden" name="alerta_id" value="<?= $alerta['id'] ?>">
                            <select name="status" class="form-select" style="width: 130px; padding: 6px 10px;" onchange="this.form.submit()">
                                <option value="aberto" <?= $alerta['status'] === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                                <option value="em_analise" <?= $alerta['status'] === 'em_analise' ? 'selected' : '' ?>>Em Analise</option>
                                <option value="resolvido" <?= $alerta['status'] === 'resolvido' ? 'selected' : '' ?>>Resolvido</option>
                                <option value="fechado" <?= $alerta['status'] === 'fechado' ? 'selected' : '' ?>>Fechado</option>
                            </select>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="alert-meta m-10-t">
                        <span><span class="iccon-user-1"></span> <?= htmlspecialchars($alerta['criador_nome']) ?></span>
                        <span><span class="iccon-clock-1"></span> <?= date('d/m/Y H:i', strtotime($alerta['criado_em'])) ?></span>
                        <span class="priority priority-<?= $alerta['prioridade'] ?>"><?= ucfirst($alerta['prioridade']) ?></span>
                        <span style="text-transform: capitalize; background: var(--bg-main); padding: 4px 10px; border-radius: 100px;"><?= ucfirst($alerta['tipo']) ?></span>
                        <?php if ($alerta['responsavel_nome']): ?>
                            <span><span class="iccon-check-1"></span> Responsavel: <?= htmlspecialchars($alerta['responsavel_nome']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
