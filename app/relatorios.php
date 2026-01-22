<?php
/**
 * EficienSys - Relatorios (Gerentes)
 */

$pageTitle = 'Relatorios';
require_once 'includes/header.php';
requireManager();

$db = getConnection();

// Periodo
$periodo = $_GET['periodo'] ?? '7';
$dataInicio = date('Y-m-d', strtotime("-{$periodo} days"));

// Estatisticas gerais
$stats = [];

// Usuarios
$stats['usuarios_total'] = $db->query("SELECT COUNT(*) FROM usuarios WHERE status != 'inativo'")->fetchColumn();
$stats['usuarios_novos'] = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE criado_em >= ?")->execute([$dataInicio]) ? $db->query("SELECT COUNT(*) FROM usuarios WHERE criado_em >= '$dataInicio'")->fetchColumn() : 0;

// Mensagens
$stats['mensagens_total'] = $db->query("SELECT COUNT(*) FROM mensagens WHERE criado_em >= '$dataInicio'")->fetchColumn();
$stats['mensagens_dia'] = round($stats['mensagens_total'] / max($periodo, 1), 1);

// Tarefas
$stats['tarefas_criadas'] = $db->query("SELECT COUNT(*) FROM tarefas WHERE criado_em >= '$dataInicio'")->fetchColumn();
$stats['tarefas_concluidas'] = $db->query("SELECT COUNT(*) FROM tarefas WHERE status = 'concluida' AND atualizado_em >= '$dataInicio'")->fetchColumn();
$stats['tarefas_pendentes'] = $db->query("SELECT COUNT(*) FROM tarefas WHERE status IN ('pendente', 'em_andamento')")->fetchColumn();

// Alertas
$stats['alertas_total'] = $db->query("SELECT COUNT(*) FROM alertas WHERE criado_em >= '$dataInicio'")->fetchColumn();
$stats['alertas_abertos'] = $db->query("SELECT COUNT(*) FROM alertas WHERE status IN ('aberto', 'em_analise')")->fetchColumn();
$stats['alertas_resolvidos'] = $db->query("SELECT COUNT(*) FROM alertas WHERE status = 'resolvido' AND resolvido_em >= '$dataInicio'")->fetchColumn();

// Top usuarios por mensagens
$topMensagens = $db->query("
    SELECT u.nome, COUNT(m.id) as total
    FROM usuarios u
    LEFT JOIN mensagens m ON u.id = m.remetente_id AND m.criado_em >= '$dataInicio'
    WHERE u.status != 'inativo'
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// Top usuarios por tarefas concluidas
$topTarefas = $db->query("
    SELECT u.nome, COUNT(t.id) as total
    FROM usuarios u
    LEFT JOIN tarefas t ON u.id = t.responsavel_id AND t.status = 'concluida' AND t.atualizado_em >= '$dataInicio'
    WHERE u.status != 'inativo'
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();

// Alertas por tipo
$alertasPorTipo = $db->query("
    SELECT tipo, COUNT(*) as total
    FROM alertas
    WHERE criado_em >= '$dataInicio'
    GROUP BY tipo
    ORDER BY total DESC
")->fetchAll();

// Tarefas por prioridade
$tarefasPorPrioridade = $db->query("
    SELECT prioridade, COUNT(*) as total
    FROM tarefas
    WHERE criado_em >= '$dataInicio'
    GROUP BY prioridade
")->fetchAll();
?>

<div class="page-header d-flex f-justify-between f-items-center">
    <div>
        <h1 class="page-title">Relatorios</h1>
        <p class="page-subtitle">Visao geral das atividades da equipe</p>
    </div>
    <form method="GET" class="d-flex f-gap-10 f-items-center">
        <label>Periodo:</label>
        <select name="periodo" class="form-select" style="width: 150px;" onchange="this.form.submit()">
            <option value="7" <?= $periodo == '7' ? 'selected' : '' ?>>Ultimos 7 dias</option>
            <option value="15" <?= $periodo == '15' ? 'selected' : '' ?>>Ultimos 15 dias</option>
            <option value="30" <?= $periodo == '30' ? 'selected' : '' ?>>Ultimos 30 dias</option>
            <option value="90" <?= $periodo == '90' ? 'selected' : '' ?>>Ultimos 90 dias</option>
        </select>
    </form>
</div>

<!-- Stats Principais -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon green">
            <span class="iccon-users-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['usuarios_total'] ?></h3>
            <p>Usuarios Ativos (+<?= $stats['usuarios_novos'] ?> novos)</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon blue">
            <span class="iccon-message-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['mensagens_total'] ?></h3>
            <p>Mensagens (~<?= $stats['mensagens_dia'] ?>/dia)</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <span class="iccon-check-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['tarefas_concluidas'] ?>/<?= $stats['tarefas_criadas'] ?></h3>
            <p>Tarefas Concluidas/Criadas</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <span class="iccon-bell-1"></span>
        </div>
        <div class="stat-info">
            <h3><?= $stats['alertas_abertos'] ?></h3>
            <p>Alertas em Aberto</p>
        </div>
    </div>
</div>

<div class="row gap-20">
    <!-- Rankings -->
    <div class="c-xs-12 c-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Usuarios - Mensagens</h3>
            </div>
            <div class="card-body">
                <?php if (empty($topMensagens) || $topMensagens[0]['total'] == 0): ?>
                    <p class="empty-state">Sem dados no periodo.</p>
                <?php else: ?>
                    <?php foreach ($topMensagens as $i => $user): ?>
                    <div class="d-flex f-items-center f-justify-between p-10-tb" style="border-bottom: 1px solid var(--border-color);">
                        <div class="d-flex f-items-center f-gap-12">
                            <span style="width: 24px; height: 24px; background: var(--primary-bg); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem;"><?= $i + 1 ?></span>
                            <span><?= htmlspecialchars($user['nome']) ?></span>
                        </div>
                        <span style="font-weight: 600; color: var(--primary);"><?= $user['total'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="c-xs-12 c-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Top Usuarios - Tarefas Concluidas</h3>
            </div>
            <div class="card-body">
                <?php if (empty($topTarefas) || $topTarefas[0]['total'] == 0): ?>
                    <p class="empty-state">Sem dados no periodo.</p>
                <?php else: ?>
                    <?php foreach ($topTarefas as $i => $user): ?>
                    <div class="d-flex f-items-center f-justify-between p-10-tb" style="border-bottom: 1px solid var(--border-color);">
                        <div class="d-flex f-items-center f-gap-12">
                            <span style="width: 24px; height: 24px; background: var(--primary-bg); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem;"><?= $i + 1 ?></span>
                            <span><?= htmlspecialchars($user['nome']) ?></span>
                        </div>
                        <span style="font-weight: 600; color: var(--primary);"><?= $user['total'] ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Distribuicoes -->
    <div class="c-xs-12 c-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alertas por Tipo</h3>
            </div>
            <div class="card-body">
                <?php if (empty($alertasPorTipo)): ?>
                    <p class="empty-state">Sem alertas no periodo.</p>
                <?php else: ?>
                    <?php
                    $totalAlertas = array_sum(array_column($alertasPorTipo, 'total'));
                    foreach ($alertasPorTipo as $item):
                        $percent = $totalAlertas > 0 ? round(($item['total'] / $totalAlertas) * 100) : 0;
                    ?>
                    <div class="m-15-b">
                        <div class="d-flex f-justify-between m-5-b">
                            <span style="text-transform: capitalize;"><?= $item['tipo'] ?></span>
                            <span><?= $item['total'] ?> (<?= $percent ?>%)</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-main); border-radius: 4px; overflow: hidden;">
                            <div style="width: <?= $percent ?>%; height: 100%; background: var(--primary); border-radius: 4px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="c-xs-12 c-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tarefas por Prioridade</h3>
            </div>
            <div class="card-body">
                <?php if (empty($tarefasPorPrioridade)): ?>
                    <p class="empty-state">Sem tarefas no periodo.</p>
                <?php else: ?>
                    <?php
                    $totalTarefas = array_sum(array_column($tarefasPorPrioridade, 'total'));
                    $cores = ['baixa' => '#94a3b8', 'media' => '#3b82f6', 'alta' => '#f59e0b', 'urgente' => '#ef4444'];
                    foreach ($tarefasPorPrioridade as $item):
                        $percent = $totalTarefas > 0 ? round(($item['total'] / $totalTarefas) * 100) : 0;
                    ?>
                    <div class="m-15-b">
                        <div class="d-flex f-justify-between m-5-b">
                            <span style="text-transform: capitalize;"><?= $item['prioridade'] ?></span>
                            <span><?= $item['total'] ?> (<?= $percent ?>%)</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-main); border-radius: 4px; overflow: hidden;">
                            <div style="width: <?= $percent ?>%; height: 100%; background: <?= $cores[$item['prioridade']] ?? 'var(--primary)' ?>; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resumo -->
<div class="card m-20-t">
    <div class="card-header">
        <h3 class="card-title">Resumo do Periodo</h3>
    </div>
    <div class="card-body">
        <div class="row gap-20">
            <div class="c-xs-12 c-md-4">
                <h4 style="color: var(--text-secondary); margin-bottom: 10px;">Comunicacao</h4>
                <p><strong><?= $stats['mensagens_total'] ?></strong> mensagens trocadas</p>
                <p>Media de <strong><?= $stats['mensagens_dia'] ?></strong> mensagens por dia</p>
            </div>
            <div class="c-xs-12 c-md-4">
                <h4 style="color: var(--text-secondary); margin-bottom: 10px;">Produtividade</h4>
                <p><strong><?= $stats['tarefas_criadas'] ?></strong> tarefas criadas</p>
                <p><strong><?= $stats['tarefas_concluidas'] ?></strong> tarefas concluidas</p>
                <p><strong><?= $stats['tarefas_pendentes'] ?></strong> tarefas pendentes</p>
            </div>
            <div class="c-xs-12 c-md-4">
                <h4 style="color: var(--text-secondary); margin-bottom: 10px;">Incidentes</h4>
                <p><strong><?= $stats['alertas_total'] ?></strong> alertas registrados</p>
                <p><strong><?= $stats['alertas_resolvidos'] ?></strong> alertas resolvidos</p>
                <p><strong><?= $stats['alertas_abertos'] ?></strong> alertas em aberto</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
