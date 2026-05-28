<?php
/**
 * actions/cancel_waitlist.php
 *
 * Remove o utilizador logado da lista de espera de uma aula de grupo.
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

    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    
    if ($user_id <= 0) {
        set_flash('Inicie sessão para gerir a sua lista de espera.', 'error');
        redirect('/pages/login.php');
    }

    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $redirect_to = $_POST['redirect_to'] ?? '/pages/profile.php';

    if ($class_id <= 0) {
        set_flash('Aula inválida.', 'error');
        redirect($redirect_to);
    }

    $stmtClass = $db->prepare("SELECT name FROM classes WHERE id = ?");
    $stmtClass->execute([$class_id]);
    $class = $stmtClass->fetch();
    $class_name = $class ? $class['name'] : 'Aula';

    $stmtDelete = $db->prepare("DELETE FROM class_waitlists WHERE user_id = ? AND class_id = ?");
    $stmtDelete->execute([$user_id, $class_id]);

    if ($stmtDelete->rowCount() > 0) {
        set_flash("Retirado(a) da lista de espera da aula de '" . h($class_name) . "' com sucesso!", 'success');
    } else {
        set_flash("Não se encontra na lista de espera para a aula de '" . h($class_name) . "'.", 'warning');
    }

} catch (Throwable $e) {
    error_log('[DB CANCEL WAITLIST ERROR] ' . $e->getMessage());
    set_flash('Erro ao sair da lista de espera: ' . $e->getMessage(), 'error');
}

redirect($redirect_to);
