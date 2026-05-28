<?php
/**
 * actions/login_action.php
 *
 * Efetua a autenticação (login) dos utilizadores.
 * Se credenciais corretas, inicia a sessão e define dados de perfil.
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

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        set_flash('Ambos os campos de Email e Senha são obrigatórios.', 'error');
        redirect('/pages/login.php');
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('As credenciais inseridas estão incorretas.', 'error');
        redirect('/pages/login.php');
    }

    if (!$user['is_active']) {
        set_flash('Esta conta encontra-se desativada pela administração.', 'error');
        redirect('/pages/login.php');
    }

    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];

    $redirect_to = trim($_POST['redirect_to'] ?? '');
    if ($redirect_to !== '' && strpos($redirect_to, '/') === 0 && strpos($redirect_to, '//') !== 0) {
        
    } else {
        $redirect_to = '/pages/profile.php';
    }

    set_flash("Sessão iniciada com sucesso! Bem-vindo(a), " . h($user['name']) . "!", 'success');
    redirect($redirect_to);

} catch (Throwable $e) {
    error_log('[DB LOGIN ERROR] ' . $e->getMessage());
    set_flash('Erro interno ao processar autenticação: ' . $e->getMessage(), 'error');
    redirect('/pages/login.php');
}
