<?php
/**
 * actions/join_waitlist.php
 *
 * Adiciona o utilizador logado à lista de espera de uma aula de grupo que já está cheia.
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

    // Obter utilizador logado
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

    if ($user_id <= 0) {
        set_flash('Inicie sessão para entrar na lista de espera.', 'error');
        redirect('/pages/login.php');
    }

    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

    if ($class_id <= 0) {
        set_flash('Aula inválida.', 'error');
        redirect('/pages/schedules.php');
    }

    $stmtClass = $db->prepare("SELECT name, capacity, instructor_id FROM classes WHERE id = ?");
    $stmtClass->execute([$class_id]);
    $class = $stmtClass->fetch();

    if (!$class) {
        set_flash('A aula selecionada não existe.', 'error');
        redirect('/pages/schedules.php');
    }

    // Impedir que o próprio instrutor entre na lista de espera
    if ((int) $class['instructor_id'] === $user_id) {
        set_flash("Não se pode colocar na lista de espera de uma aula que é ministrada por si!", 'error');
        redirect('/pages/schedules.php');
    }

    $db->beginTransaction();

    // Validar se a aula está mesmo cheia
    $stmtCount = $db->prepare("SELECT COUNT(*) AS total FROM bookings WHERE class_id = ?");
    $stmtCount->execute([$class_id]);
    $inscritos = (int) $stmtCount->fetch()['total'];

    if ($inscritos < $class['capacity']) {
        $db->rollBack();
        set_flash("A aula de '" . h($class['name']) . "' ainda tem vagas disponíveis, inscreva-se diretamente!", 'warning');
        redirect('/pages/schedules.php');
    }

    // Verificar se já está inscrito na aula
    $stmtCheckBooked = $db->prepare("SELECT id FROM bookings WHERE user_id = ? AND class_id = ?");
    $stmtCheckBooked->execute([$user_id, $class_id]);
    if ($stmtCheckBooked->fetch()) {
        $db->rollBack();
        set_flash("Já se encontra inscrito nesta aula.", 'info');
        redirect('/pages/schedules.php');
    }

    // Verificar se já está na lista de espera
    $stmtCheckWait = $db->prepare("SELECT id FROM class_waitlists WHERE user_id = ? AND class_id = ?");
    $stmtCheckWait->execute([$user_id, $class_id]);
    if ($stmtCheckWait->fetch()) {
        $db->rollBack();
        set_flash("Já se encontra na lista de espera para a aula de '" . h($class['name']) . "'.", 'info');
        redirect('/pages/schedules.php');
    }

    // Inserir na lista de espera
    $stmtInsert = $db->prepare("INSERT INTO class_waitlists (user_id, class_id, joined_at) VALUES (?, ?, datetime('now'))");
    $stmtInsert->execute([$user_id, $class_id]);

    $db->commit();
    set_flash("Adicionado(a) à lista de espera de '" . h($class['name']) . "' com sucesso!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB JOIN WAITLIST ERROR] ' . $e->getMessage());
    set_flash('Erro ao entrar na lista de espera: ' . $e->getMessage(), 'error');
}

redirect('/pages/schedules.php');
