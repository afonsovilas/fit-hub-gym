<?php
/**
 * actions/book_personal_training.php
 *
 * Efetua o agendamento de uma sessão de Personal Training de 60 minutos.
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

    $member_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

    if ($member_id <= 0) {
        set_flash('Inicie sessão para agendar uma sessão de Personal Training.', 'error');
        redirect('/pages/login.php');
    }

    $trainer_id = isset($_POST['trainer_id']) ? (int) $_POST['trainer_id'] : 0;
    $scheduled_at_raw = trim($_POST['scheduled_at'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($trainer_id <= 0 || $scheduled_at_raw === '') {
        set_flash('Por favor, selecione um treinador e uma data/hora válida.', 'error');
        redirect('/pages/profile.php');
    }

    // Validar se o treinador existe e tem o papel correto
    $stmtTrainer = $db->prepare("SELECT name, is_active FROM users WHERE id = ? AND role = 'trainer'");
    $stmtTrainer->execute([$trainer_id]);
    $trainer = $stmtTrainer->fetch();

    if (!$trainer) {
        set_flash('O treinador selecionado não existe ou não está disponível.', 'error');
        redirect('/pages/profile.php');
    }

    if (!(bool) $trainer['is_active']) {
        set_flash('O treinador de momento encontra-se inativo.', 'error');
        redirect('/pages/profile.php');
    }

    // Impedir que o próprio treinador agende uma sessão consigo mesmo
    if ($trainer_id === $member_id) {
        set_flash('Não pode agendar uma sessão consigo mesmo.', 'error');
        redirect('/pages/profile.php');
    }

    // Validar data futura
    $scheduled_time = strtotime($scheduled_at_raw);
    if ($scheduled_time === false || $scheduled_time < time()) {
        set_flash('A data e hora do agendamento deve ser no futuro.', 'error');
        redirect('/pages/profile.php');
    }

    // Formatar data para base de dados SQLite (Y-m-d H:i)
    $scheduled_at = date('Y-m-d H:i:s', $scheduled_time);

    // Obter nome do aluno
    $stmtMemberName = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmtMemberName->execute([$member_id]);
    $member_name = $stmtMemberName->fetch()['name'] ?? 'Um membro';

    $db->beginTransaction();

    // Inserir agendamento
    $stmtInsert = $db->prepare("
        INSERT INTO personal_training_bookings (member_id, trainer_id, scheduled_at, status, notes, created_at)
        VALUES (?, ?, ?, 'pending', ?, datetime('now'))
    ");
    $stmtInsert->execute([$member_id, $trainer_id, $scheduled_at, $notes]);

    // Enviar notificação para o Treinador
    $stmtNotify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notificationMsg = "Novo agendamento de Personal Training pendente recebido de '" . $member_name . "' para o dia " . date('d/m/Y \à\s H:i', $scheduled_time) . ".";
    $stmtNotify->execute([$trainer_id, $notificationMsg]);

    $db->commit();
    set_flash("Agendamento de Personal Training solicitado a '" . h($trainer['name']) . "' com sucesso! A aguardar confirmação.", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB BOOK PT ERROR] ' . $e->getMessage());
    set_flash('Erro ao agendar a sessão: ' . $e->getMessage(), 'error');
}

redirect('/pages/profile.php');
