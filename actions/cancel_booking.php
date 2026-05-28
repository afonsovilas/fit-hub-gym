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

    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 9;

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

    $db->beginTransaction();

    $stmtDelete = $db->prepare("DELETE FROM bookings WHERE user_id = ? AND class_id = ?");
    $stmtDelete->execute([$user_id, $class_id]);

    if ($stmtDelete->rowCount() > 0) {
        set_flash("Inscrição na aula de '" . h($class_name) . "' cancelada com sucesso!", 'success');

        // Promoção automática da lista de espera
        $stmtWait = $db->prepare("SELECT id, user_id FROM class_waitlists WHERE class_id = ? ORDER BY joined_at ASC LIMIT 1");
        $stmtWait->execute([$class_id]);
        $nextInLine = $stmtWait->fetch();

        if ($nextInLine) {
            $waitlist_entry_id = (int) $nextInLine['id'];
            $promoted_user_id = (int) $nextInLine['user_id'];

            // Promover: inserir em bookings
            $stmtPromote = $db->prepare("INSERT INTO bookings (user_id, class_id) VALUES (?, ?)");
            $stmtPromote->execute([$promoted_user_id, $class_id]);

            // Remover da lista de espera
            $stmtRemoveWait = $db->prepare("DELETE FROM class_waitlists WHERE id = ?");
            $stmtRemoveWait->execute([$waitlist_entry_id]);

            // Enviar notificação ao utilizador promovido
            $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notificationMsg = "Foi promovido(a) da lista de espera e inscrito(a) na aula de '" . $class_name . "'!";
            $stmtNotify->execute([$promoted_user_id, $notificationMsg]);
        }

        $db->commit();
    } else {
        $db->rollBack();
        set_flash("Não se encontra inscrito na aula de '" . h($class_name) . "'.", 'warning');
    }

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB CANCEL BOOKING ERROR] ' . $e->getMessage());
    set_flash('Erro ao cancelar a inscrição: ' . $e->getMessage(), 'error');
}

redirect($redirect_to);
