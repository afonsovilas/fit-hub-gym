<?php
/**
 * actions/cancel_personal_training.php
 *
 * Cancela (desmarca) uma sessão de Personal Training agendada.
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
        set_flash('Inicie sessão para gerir os seus agendamentos.', 'error');
        redirect('/pages/login.php');
    }

    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;

    if ($booking_id <= 0) {
        set_flash('Agendamento inválido.', 'error');
        redirect('/pages/profile.php#treino-personalizado');
    }

    // Obter detalhes do agendamento
    $stmtBooking = $db->prepare("
        SELECT ptb.*, u_member.name AS member_name, u_trainer.name AS trainer_name
        FROM personal_training_bookings ptb
        JOIN users u_member ON ptb.member_id = u_member.id
        JOIN users u_trainer ON ptb.trainer_id = u_trainer.id
        WHERE ptb.id = ?
    ");
    $stmtBooking->execute([$booking_id]);
    $booking = $stmtBooking->fetch();

    if (!$booking) {
        set_flash('O agendamento selecionado não existe.', 'error');
        redirect('/pages/profile.php#treino-personalizado');
    }

    // Verificar se o utilizador logado é o aluno ou o treinador deste agendamento
    $is_member = ((int) $booking['member_id'] === $user_id);
    $is_trainer = ((int) $booking['trainer_id'] === $user_id);

    if (!$is_member && !$is_trainer) {
        set_flash('Não tem permissão para cancelar este agendamento.', 'error');
        redirect('/pages/profile.php#treino-personalizado');
    }

    $db->beginTransaction();

    // Atualizar estado do agendamento para 'cancelled'
    $stmtCancel = $db->prepare("UPDATE personal_training_bookings SET status = 'cancelled' WHERE id = ?");
    $stmtCancel->execute([$booking_id]);

    // Enviar notificações
    $scheduled_time = strtotime($booking['scheduled_at']);
    $formatted_date = date('d/m/Y \à\s H:i', $scheduled_time);

    $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");

    if ($is_member) {
        // Aluno cancelou -> Notificar Treinador
        $notificationMsg = "A sessão de Personal Training de '" . $booking['member_name'] . "' agendada para " . $formatted_date . " foi desmarcada.";
        $stmtNotify->execute([$booking['trainer_id'], $notificationMsg]);
    } else {
        // Treinador desmarcou -> Notificar Aluno
        $notificationMsg = "Lamentamos, mas o seu treinador '" . $booking['trainer_name'] . "' desmarcou a sessão de Personal Training de " . $formatted_date . ".";
        $stmtNotify->execute([$booking['member_id'], $notificationMsg]);
    }

    $db->commit();
    set_flash('Sessão de Personal Training desmarcada com sucesso!', 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB CANCEL PT ERROR] ' . $e->getMessage());
    set_flash('Erro ao desmarcar a sessão: ' . $e->getMessage(), 'error');
}

redirect('/pages/profile.php#treino-personalizado');
