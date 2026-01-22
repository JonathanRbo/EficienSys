<?php
/**
 * EficienSys - Gerenciamento de Sessao
 */

session_start();

require_once __DIR__ . '/database.php';

// Verificar se usuario esta logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Obter dados do usuario logado
function getCurrentUser() {
    if (!isLoggedIn()) return null;

    $db = getConnection();
    $stmt = $db->prepare("SELECT id, nome, email, cargo, departamento, avatar, status FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Verificar se usuario e gerente
function isManager() {
    if (!isLoggedIn()) return false;
    return isset($_SESSION['user_cargo']) && $_SESSION['user_cargo'] === 'gerente';
}

// Login do usuario
function loginUser($email, $password) {
    $db = getConnection();
    $stmt = $db->prepare("SELECT id, nome, email, senha, cargo, departamento FROM usuarios WHERE email = ? AND status != 'inativo'");
    $stmt->execute([sanitize($email)]);
    $user = $stmt->fetch();

    if ($user && verifyPassword($password, $user['senha'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_cargo'] = $user['cargo'];
        $_SESSION['user_departamento'] = $user['departamento'];

        // Atualizar ultimo acesso e status
        $update = $db->prepare("UPDATE usuarios SET ultimo_acesso = NOW(), status = 'online' WHERE id = ?");
        $update->execute([$user['id']]);

        return true;
    }
    return false;
}

// Registrar novo usuario
function registerUser($nome, $email, $password, $cargo = 'colaborador', $departamento = '') {
    $db = getConnection();

    // Verificar se email ja existe
    $check = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([sanitize($email)]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'Este email ja esta cadastrado.'];
    }

    // Inserir usuario
    $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha, cargo, departamento) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        sanitize($nome),
        sanitize($email),
        hashPassword($password),
        $cargo,
        sanitize($departamento)
    ]);

    if ($result) {
        $userId = $db->lastInsertId();

        // Adicionar ao grupo geral
        $addGroup = $db->prepare("INSERT INTO grupo_membros (grupo_id, usuario_id) VALUES (1, ?)");
        $addGroup->execute([$userId]);

        return ['success' => true, 'message' => 'Cadastro realizado com sucesso!', 'user_id' => $userId];
    }

    return ['success' => false, 'message' => 'Erro ao cadastrar usuario.'];
}

// Logout
function logoutUser() {
    if (isLoggedIn()) {
        $db = getConnection();
        $stmt = $db->prepare("UPDATE usuarios SET status = 'ativo' WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }

    session_unset();
    session_destroy();
}

// Redirecionar se nao estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirecionar se nao for gerente
function requireManager() {
    requireLogin();
    if (!isManager()) {
        header('Location: dashboard.php');
        exit;
    }
}
