<?php
/**
 * actions/accept_nutrition_request.php
 *
 * Processes a trainer accepting a pending nutrition request and scheduling a date.
 * Protected with CSRF token verification.
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
    $user_role = $_SESSION['user_role'] ?? '';

    if ($trainer_id <= 0) {
        set_flash('Inicie sessão para gerir os pedidos.', 'error');
        redirect('/pages/login.php');
    }

    if ($user_role !== 'trainer') {
        set_flash('Acesso negado. Apenas treinadores podem aceitar pedidos de nutrição.', 'error');
        redirect('/pages/profile.php');
    }

    $redirect_to = trim($_POST['redirect_to'] ?? '/pages/profile.php#nutricao');

    $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
    $scheduled_date_raw = trim($_POST['scheduled_date'] ?? '');

    if ($request_id <= 0 || $scheduled_date_raw === '') {
        set_flash('Por favor, indique uma data e hora válidas para o agendamento.', 'error');
        redirect($redirect_to);
    }

    // Verify the request exists, is pending, and belongs to this trainer
    $stmtCheck = $db->prepare("
        SELECT member_id, status FROM nutrition_requests 
        WHERE id = ? AND trainer_id = ?
    ");
    $stmtCheck->execute([$request_id, $trainer_id]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        set_flash('O pedido selecionado não foi encontrado ou não lhe está associado.', 'error');
        redirect($redirect_to);
    }

    if ($request['status'] !== 'pending') {
        set_flash('Este pedido já foi processado anteriormente.', 'error');
        redirect($redirect_to);
    }

    // Validate scheduled date (must be in the future)
    $scheduled_time = strtotime($scheduled_date_raw);
    if ($scheduled_time === false || $scheduled_time < time()) {
        set_flash('A data e hora do agendamento deve ser no futuro.', 'error');
        redirect($redirect_to);
    }

    $time_validation = validate_schedule_time($scheduled_date_raw, 60);
    if (!$time_validation['success']) {
        set_flash($time_validation['message'], 'error');
        redirect($redirect_to);
    }

    $scheduled_date = date('Y-m-d H:i:s', $scheduled_time);

    // Get trainer name for notification
    $stmtTrainer = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmtTrainer->execute([$trainer_id]);
    $trainer_name = $stmtTrainer->fetch()['name'] ?? 'O treinador';

    $db->beginTransaction();

    // Update status to accepted and set scheduled_date
    $stmtUpdate = $db->prepare("
        UPDATE nutrition_requests 
        SET status = 'accepted', scheduled_date = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$scheduled_date, $request_id]);

    // Send notification to member
    $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notificationMsg = "O seu pedido de plano de nutrição foi aceite por '" . $trainer_name . "' e agendado para " . date('d/m/Y \à\s H:i', $scheduled_time) . ".";
    $stmtNotify->execute([$request['member_id'], $notificationMsg]);

    $db->commit();
    set_flash('Pedido de consulta de nutrição agendado e aceite com sucesso!', 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[ACCEPT NUTRITION REQUEST ERROR] ' . $e->getMessage());
    set_flash('Erro ao aceitar o pedido: ' . $e->getMessage(), 'error');
}

redirect($redirect_to);
