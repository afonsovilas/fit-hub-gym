<?php
/**
 * actions/update_profile.php
 *
 * Processa a atualização dos dados do perfil do utilizador.
 * Efetua validações básicas, hashing de password (se alterada) e grava no SQLite.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

csrf_protect();

try {
    $db = get_db();

    // Obter ID do utilizador logado a partir da sessão activa, com fallback para utilizador 9
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 9;

    $name  = trim($_POST['full-name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password-confirm'] ?? '';

    if ($name === '' || $email === '') {
        set_flash('Nome e Email são campos obrigatórios.', 'error');
        redirect('/pages/profile.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('O email inserido não é válido.', 'error');
        redirect('/pages/profile.php');
    }

    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtCheck->execute([$email, $user_id]);
    if ($stmtCheck->fetch()) {
        set_flash('Este email já se encontra registado por outro utilizador.', 'error');
        redirect('/pages/profile.php');
    }

    $db->beginTransaction();

    $stmtUpdate = $db->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$name, $email, $phone, $user_id]);

    if ($password !== '') {
        if ($password !== $password_confirm) {
            $db->rollBack();
            set_flash('A confirmação da nova senha não coincide.', 'error');
            redirect('/pages/profile.php');
        }

        if (strlen($password) < 6) {
            $db->rollBack();
            set_flash('A nova senha deve ter pelo menos 6 caracteres.', 'error');
            redirect('/pages/profile.php');
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmtPass = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtPass->execute([$password_hash, $user_id]);
    }

    $db->commit();
    set_flash('Perfil atualizado com sucesso na base de dados!', 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB UPDATE PROFILE ERROR] ' . $e->getMessage());
    set_flash('Erro ao gravar dados na base de dados: ' . $e->getMessage(), 'error');
}

redirect('/pages/profile.php');
