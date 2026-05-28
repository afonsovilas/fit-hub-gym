<?php
/**
 * actions/toggle_user_status.php
 *
 * Ativa ou desativa a conta de um treinador ou membro de staff.
 * Protegido com CSRF.
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

    $target_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    if ($target_id <= 0) {
        set_flash('ID de utilizador inválido.', 'error');
        redirect('/pages/admin/staff.php');
    }

    $stmt = $db->prepare("SELECT name, is_active, role FROM users WHERE id = ?");
    $stmt->execute([$target_id]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('O utilizador não existe na base de dados.', 'error');
        redirect('/pages/admin/staff.php');
    }

    if ($target_id === (int) $_SESSION['user_id']) {
        set_flash('Não é permitido desativar a sua própria conta de administrador!', 'error');
        redirect('/pages/admin/staff.php');
    }

    if ($target_id === 8) {
        set_flash('Não é permitido desativar a conta do administrador principal.', 'error');
        redirect('/pages/admin/staff.php');
    }

    $new_status = $user['is_active'] ? 0 : 1;

    $stmtUpdate = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmtUpdate->execute([$new_status, $target_id]);

    $action_word = $new_status ? 'ativada' : 'desativada';
    set_flash("A conta de " . h($user['name']) . " foi {$action_word} com sucesso na base de dados!", 'success');

} catch (Throwable $e) {
    error_log('[DB TOGGLE STATUS ERROR] ' . $e->getMessage());
    set_flash('Erro ao alterar o estado do utilizador: ' . $e->getMessage(), 'error');
}

redirect('/pages/admin/staff.php');
