<?php
/**
 * actions/reject_personal_training.php
 *
 * Recusa uma solicitação de Personal Training pendente, guardando uma justificação.
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

    $trainer_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

    if ($trainer_id <= 0 || $_SESSION['user_role'] !== 'trainer') {
        set_flash('Inicie sessão como treinador para gerir as suas marcações.', 'error');
        redirect('/pages/login.php');
    }

    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    if ($booking_id <= 0 || $rejection_reason === '') {
        set_flash('Indique uma justificação para recusar o agendamento.', 'error');
        redirect('/pages/trainer_schedule.php');
    }

    // Obter detalhes da sessão
    $stmtBooking = $db->prepare("
        SELECT ptb.*, u_member.name AS member_name, u_trainer.name AS trainer_name
        FROM personal_training_bookings ptb
        JOIN users u_member ON ptb.member_id = u_member.id
        JOIN users u_trainer ON ptb.trainer_id = u_trainer.id
        WHERE ptb.id = ? AND ptb.trainer_id = ?
    ");
    $stmtBooking->execute([$booking_id, $trainer_id]);
    $booking = $stmtBooking->fetch();

    if (!$booking) {
        set_flash('Agendamento não encontrado.', 'error');
        redirect('/pages/trainer_schedule.php');
    }

    if ($booking['status'] !== 'pending') {
        set_flash('Esta sessão já não se encontra pendente.', 'warning');
        redirect('/pages/trainer_schedule.php');
    }

    $db->beginTransaction();

    // Recusar agendamento e guardar a justificação
    $stmtReject = $db->prepare("
        UPDATE personal_training_bookings 
        SET status = 'rejected', rejection_reason = ? 
        WHERE id = ?
    ");
    $stmtReject->execute([$rejection_reason, $booking_id]);

    // Notificar o Aluno
    $scheduled_time = strtotime($booking['scheduled_at']);
    $formatted_date = date('d/m/Y \à\s H:i', $scheduled_time);

    $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notificationMsg = "O seu treinador '" . $booking['trainer_name'] . "' desmarcou/recusou a sessão de " . $formatted_date . ". Motivo: \"" . $rejection_reason . "\"";
    $stmtNotify->execute([$booking['member_id'], $notificationMsg]);

    $db->commit();
    set_flash("Agendamento de '" . h($booking['member_name']) . "' recusado com sucesso.", 'warning');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB REJECT PT ERROR] ' . $e->getMessage());
    set_flash('Erro ao recusar o agendamento: ' . $e->getMessage(), 'error');
}

redirect('/pages/trainer_schedule.php');
