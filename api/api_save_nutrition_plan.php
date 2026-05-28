<?php
/**
 * api/api_save_nutrition_plan.php
 *
 * AJAX endpoint for trainers to save the completed diet plan details.
 * Retorna JSON.
 * 
 * Security:
 * - Checks session authentication (user logged in)
 * - Verifies user role is 'trainer'
 * - Prevents SQL injection using parameterized statement
 * - Prevents Horizontal Privilege Escalation (validates request belongs to trainer)
 * - Sanitizes textual outputs before returning JSON (XSS prevention)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/src/utils.php';
require_once BASE_PATH . '/src/csrf.php';
require_once BASE_PATH . '/database/connection.php';

// Verify CSRF token
$csrf_token = $_POST['csrf_token'] ?? null;
if (!validate_csrf_token($csrf_token)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Token de segurança CSRF ausente ou inválido.'
    ]);
    exit;
}

// Verify user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Não autenticado. Por favor, faça login.'
    ]);
    exit;
}

// Verify user role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'trainer') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas treinadores podem prescrever planos de nutrição.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Utilize POST.'
    ]);
    exit;
}

try {
    $trainer_id = (int) $_SESSION['user_id'];
    $request_id = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
    $plan_details = isset($_POST['plan_details']) ? trim($_POST['plan_details']) : '';

    // Validate inputs
    if ($request_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID de pedido inválido.'
        ]);
        exit;
    }

    if ($plan_details === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'O plano alimentar não pode estar vazio.'
        ]);
        exit;
    }

    $db = get_db();

    // Verify request exists and belongs to the logged-in trainer
    $stmtCheck = $db->prepare("
        SELECT member_id, status FROM nutrition_requests 
        WHERE id = ? AND trainer_id = ?
    ");
    $stmtCheck->execute([$request_id, $trainer_id]);
    $request = $stmtCheck->fetch();

    if (!$request) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso negado. O pedido selecionado não foi encontrado ou não lhe pertence.'
        ]);
        exit;
    }

    $is_edit = ($request['status'] === 'completed');

    // Retrieve trainer name for the notification
    $stmtTrainer = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmtTrainer->execute([$trainer_id]);
    $trainer_name = $stmtTrainer->fetch()['name'] ?? 'O nutricionista';

    $db->beginTransaction();

    // Update the nutrition plan details and set status to completed
    $stmtUpdate = $db->prepare("
        UPDATE nutrition_requests 
        SET status = 'completed', plan_details = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$plan_details, $request_id]);

    // Send notification to member
    $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    if ($is_edit) {
        $notificationMsg = "O seu plano de nutrição foi atualizado por '" . $trainer_name . "'. Já pode consultar as alterações no seu perfil.";
    } else {
        $notificationMsg = "O seu plano de nutrição foi concluído por '" . $trainer_name . "'. Já o pode consultar no seu perfil.";
    }
    $stmtNotify->execute([$request['member_id'], $notificationMsg]);

    $db->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Plano alimentar gravado e concluído com sucesso!'
    ]);
    exit;

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[API SAVE NUTRITION PLAN DB ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro na base de dados ao guardar o plano alimentar.'
    ]);
    exit;
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[API SAVE NUTRITION PLAN UNEXPECTED ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro inesperado no servidor. Por favor tente novamente.'
    ]);
    exit;
}
