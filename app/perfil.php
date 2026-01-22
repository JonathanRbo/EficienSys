<?php
/**
 * EficienSys - Perfil do Usuario
 */

$pageTitle = 'Meu Perfil';
require_once 'includes/header.php';
requireLogin();

$db = getConnection();
$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Buscar dados do usuario
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Processar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'atualizar_perfil') {
        $nome = sanitize($_POST['nome']);
        $departamento = sanitize($_POST['departamento']);

        $stmt = $db->prepare("UPDATE usuarios SET nome = ?, departamento = ? WHERE id = ?");
        if ($stmt->execute([$nome, $departamento, $userId])) {
            $_SESSION['user_nome'] = $nome;
            $_SESSION['user_departamento'] = $departamento;
            $message = 'Perfil atualizado com sucesso!';

            // Atualizar dados
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } else {
            $error = 'Erro ao atualizar perfil.';
        }
    } elseif ($action === 'alterar_senha') {
        $senhaAtual = $_POST['senha_atual'];
        $novaSenha = $_POST['nova_senha'];
        $confirmarSenha = $_POST['confirmar_senha'];

        if (!verifyPassword($senhaAtual, $user['senha'])) {
            $error = 'Senha atual incorreta.';
        } elseif (strlen($novaSenha) < 6) {
            $error = 'A nova senha deve ter pelo menos 6 caracteres.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $error = 'As senhas nao conferem.';
        } else {
            $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            if ($stmt->execute([hashPassword($novaSenha), $userId])) {
                $message = 'Senha alterada com sucesso!';
            } else {
                $error = 'Erro ao alterar senha.';
            }
        }
    }
}

// Estatisticas do usuario
$stats = [];

$stmt = $db->prepare("SELECT COUNT(*) FROM mensagens WHERE remetente_id = ?");
$stmt->execute([$userId]);
$stats['mensagens_enviadas'] = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM tarefas WHERE responsavel_id = ? AND status = 'concluida'");
$stmt->execute([$userId]);
$stats['tarefas_concluidas'] = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM alertas WHERE criador_id = ?");
$stmt->execute([$userId]);
$stats['alertas_criados'] = $stmt->fetchColumn();
?>

<div class="page-header">
    <h1 class="page-title">Meu Perfil</h1>
    <p class="page-subtitle">Gerencie suas informacoes pessoais</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
<?php endif; ?>

<div class="row gap-20">
    <!-- Info do Usuario -->
    <div class="c-xs-12 c-lg-4">
        <div class="card">
            <div class="card-body text-center p-30-all">
                <div class="user-avatar" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto 20px;">
                    <?= strtoupper(substr($user['nome'], 0, 1)) ?>
                </div>
                <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($user['nome']) ?></h3>
                <p style="color: var(--text-muted);"><?= ucfirst($user['cargo']) ?></p>
                <p style="color: var(--text-secondary); font-size: 0.9rem;"><?= htmlspecialchars($user['departamento'] ?: 'Sem departamento') ?></p>

                <div class="d-flex f-justify-center f-gap-20 m-30-t p-20-t" style="border-top: 1px solid var(--border-color);">
                    <div class="text-center">
                        <h4 style="color: var(--primary);"><?= $stats['mensagens_enviadas'] ?></h4>
                        <small style="color: var(--text-muted);">Mensagens</small>
                    </div>
                    <div class="text-center">
                        <h4 style="color: var(--primary);"><?= $stats['tarefas_concluidas'] ?></h4>
                        <small style="color: var(--text-muted);">Tarefas</small>
                    </div>
                    <div class="text-center">
                        <h4 style="color: var(--primary);"><?= $stats['alertas_criados'] ?></h4>
                        <small style="color: var(--text-muted);">Alertas</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card m-20-t">
            <div class="card-header">
                <h3 class="card-title">Informacoes</h3>
            </div>
            <div class="card-body">
                <p style="margin-bottom: 12px;">
                    <strong>Email:</strong><br>
                    <?= htmlspecialchars($user['email']) ?>
                </p>
                <p style="margin-bottom: 12px;">
                    <strong>Membro desde:</strong><br>
                    <?= date('d/m/Y', strtotime($user['criado_em'])) ?>
                </p>
                <p>
                    <strong>Ultimo acesso:</strong><br>
                    <?= $user['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : 'N/A' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Formularios -->
    <div class="c-xs-12 c-lg-8">
        <!-- Editar Perfil -->
        <div class="card m-20-b">
            <div class="card-header">
                <h3 class="card-title">Editar Perfil</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="form_action" value="atualizar_perfil">

                    <div class="form-group">
                        <label class="form-label">Nome completo</label>
                        <input type="text" name="nome" class="form-input" value="<?= htmlspecialchars($user['nome']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        <small style="color: var(--text-muted);">O email nao pode ser alterado.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Departamento</label>
                        <select name="departamento" class="form-select">
                            <option value="">Selecione...</option>
                            <option value="Producao" <?= $user['departamento'] === 'Producao' ? 'selected' : '' ?>>Producao</option>
                            <option value="Administrativo" <?= $user['departamento'] === 'Administrativo' ? 'selected' : '' ?>>Administrativo</option>
                            <option value="Comercial" <?= $user['departamento'] === 'Comercial' ? 'selected' : '' ?>>Comercial</option>
                            <option value="TI" <?= $user['departamento'] === 'TI' ? 'selected' : '' ?>>TI</option>
                            <option value="RH" <?= $user['departamento'] === 'RH' ? 'selected' : '' ?>>RH</option>
                            <option value="Financeiro" <?= $user['departamento'] === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                            <option value="Logistica" <?= $user['departamento'] === 'Logistica' ? 'selected' : '' ?>>Logistica</option>
                            <option value="Outro" <?= $user['departamento'] === 'Outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span class="iccon-check-1"></span> Salvar Alteracoes
                    </button>
                </form>
            </div>
        </div>

        <!-- Alterar Senha -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alterar Senha</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="form_action" value="alterar_senha">

                    <div class="form-group">
                        <label class="form-label">Senha atual</label>
                        <input type="password" name="senha_atual" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nova senha</label>
                        <input type="password" name="nova_senha" class="form-input" required>
                        <small style="color: var(--text-muted);">Minimo 6 caracteres.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirmar nova senha</label>
                        <input type="password" name="confirmar_senha" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <span class="iccon-lock-1"></span> Alterar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
