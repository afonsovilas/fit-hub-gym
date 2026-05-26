<?php
/**
 * actions/cancel_booking.php
 *
 * Cancela a inscrição de um utilizador numa aula de grupo.
 * Remove o registo correspondente da tabela bookings do SQLite.
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

    $user_id = 9;

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

    $stmtDelete = $db->prepare("DELETE FROM bookings WHERE user_id = ? AND class_id = ?");
    $stmtDelete->execute([$user_id, $class_id]);

    if ($stmtDelete->rowCount() > 0) {
        set_flash("Inscrição na aula de '" . h($class_name) . "' cancelada com sucesso!", 'success');
    } else {
        set_flash("Não se encontra inscrito na aula de '" . h($class_name) . "'.", 'warning');
    }

} catch (Throwable $e) {
    error_log('[DB CANCEL BOOKING ERROR] ' . $e->getMessage());
    set_flash('Erro ao cancelar a inscrição: ' . $e->getMessage(), 'error');
}

redirect($redirect_to);
