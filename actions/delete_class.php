<?php
/**
 * actions/delete_class.php
 *
 * Remove uma aula de grupo do catálogo de horários do ginásio.
 * Segurança: Autenticação obrigatória, apenas Administradores, proteção CSRF e validações estritas.
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

// Apenas administradores
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Acesso negado. Apenas administradores podem realizar esta ação.');
}

try {
    $db = get_db();

    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

    if ($class_id <= 0) {
        set_flash('ID de aula inválido.', 'error');
        redirect('/pages/schedules.php');
    }

    $stmtCheck = $db->prepare("SELECT name FROM classes WHERE id = ?");
    $stmtCheck->execute([$class_id]);
    $class = $stmtCheck->fetch();

    if (!$class) {
        set_flash('A aula de grupo não existe na base de dados.', 'error');
        redirect('/pages/schedules.php');
    }

    $db->beginTransaction();

    // 1. Remover inscrições (bookings) associadas
    $stmtDeleteBookings = $db->prepare("DELETE FROM bookings WHERE class_id = ?");
    $stmtDeleteBookings->execute([$class_id]);

    // 2. Remover lista de espera (class_waitlists) associadas
    $stmtDeleteWaitlist = $db->prepare("DELETE FROM class_waitlists WHERE class_id = ?");
    $stmtDeleteWaitlist->execute([$class_id]);

    // 3. Remover a aula em si
    $stmtDeleteClass = $db->prepare("DELETE FROM classes WHERE id = ?");
    $stmtDeleteClass->execute([$class_id]);

    $db->commit();
    set_flash("A aula '" . h($class['name']) . "' e todas as inscrições associadas foram removidas com sucesso do catálogo!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB DELETE CLASS ERROR] ' . $e->getMessage());
    set_flash('Erro ao remover aula do catálogo: ' . $e->getMessage(), 'error');
}

redirect('/pages/schedules.php');
