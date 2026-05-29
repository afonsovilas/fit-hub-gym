<?php
/**
 * actions/request_nutrition_plan.php
 *
 * Processes a new nutrition plan request from a member.
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

    $member_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $member_role = $_SESSION['user_role'] ?? '';

    if ($member_id <= 0) {
        set_flash('Inicie sessão para solicitar um plano de nutrição.', 'error');
        redirect('/pages/login.php');
    }

    if ($member_role !== 'member') {
        set_flash('Apenas membros podem solicitar planos de nutrição.', 'error');
        redirect('/pages/profile.php');
    }

    $trainer_id = isset($_POST['trainer_id']) ? (int) $_POST['trainer_id'] : 0;

    if ($trainer_id <= 0) {
        set_flash('Por favor, selecione um nutricionista válido.', 'error');
        redirect('/pages/profile.php#nutricao');
    }

    // Verify that the trainer exists, is active, and has the 'nutricao' specialization
    $stmtTrainer = $db->prepare("
        SELECT u.name, u.is_active 
        FROM users u
        JOIN trainer_specializations ts ON u.id = ts.trainer_id
        WHERE u.id = ? AND u.role = 'trainer' AND ts.specialization = 'nutricao'
    ");
    $stmtTrainer->execute([$trainer_id]);
    $trainer = $stmtTrainer->fetch();

    if (!$trainer) {
        set_flash('O treinador selecionado não existe ou não possui especialidade em Nutrição.', 'error');
        redirect('/pages/profile.php#nutricao');
    }

    if (!(bool) $trainer['is_active']) {
        set_flash('O treinador/nutricionista de momento encontra-se inativo.', 'error');
        redirect('/pages/profile.php#nutricao');
    }

    // Get member name for notification
    $stmtMember = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmtMember->execute([$member_id]);
    $member_name = $stmtMember->fetch()['name'] ?? 'Membro';

    $db->beginTransaction();

    // Insert nutrition request
    $stmtInsert = $db->prepare("
        INSERT INTO nutrition_requests (member_id, trainer_id, status, created_at)
        VALUES (?, ?, 'pending', ?)
    ");
    $stmtInsert->execute([$member_id, $trainer_id, date('Y-m-d H:i:s')]);

    // Notify the trainer
    $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notificationMsg = "Novo pedido de plano de nutrição pendente recebido de '" . $member_name . "'.";
    $stmtNotify->execute([$trainer_id, $notificationMsg]);

    $db->commit();
    set_flash("Solicitação de plano de nutrição enviada a '" . h($trainer['name']) . "' com sucesso!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[REQUEST NUTRITION PLAN ERROR] ' . $e->getMessage());
    set_flash('Erro ao solicitar o plano de nutrição: ' . $e->getMessage(), 'error');
}

redirect('/pages/profile.php#nutricao');
