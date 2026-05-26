<?php
/**
 * actions/book_class.php
 *
 * Efetua a inscrição do utilizador numa aula de grupo.
 * Valida a capacidade máxima da aula e se o utilizador já está inscrito.
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

    // Simulação do utilizador logado (Aluno Piloto - ID 9) com suporte a sessões futuras
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 9;

    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

    if ($class_id <= 0) {
        set_flash('Aula inválida.', 'error');
        redirect('/pages/schedules.php');
    }

    $stmtClass = $db->prepare("SELECT name, capacity FROM classes WHERE id = ?");
    $stmtClass->execute([$class_id]);
    $class = $stmtClass->fetch();

    if (!$class) {
        set_flash('A aula selecionada não existe.', 'error');
        redirect('/pages/schedules.php');
    }

    $db->beginTransaction();

    $stmtCount = $db->prepare("SELECT COUNT(*) AS total FROM bookings WHERE class_id = ?");
    $stmtCount->execute([$class_id]);
    $inscritos = (int) $stmtCount->fetch()['total'];

    if ($inscritos >= $class['capacity']) {
        $db->rollBack();
        set_flash("A aula de '" . h($class['name']) . "' já se encontra cheia.", 'error');
        redirect('/pages/schedules.php');
    }

    $stmtCheck = $db->prepare("SELECT id FROM bookings WHERE user_id = ? AND class_id = ?");
    $stmtCheck->execute([$user_id, $class_id]);
    if ($stmtCheck->fetch()) {
        $db->rollBack();
        set_flash("Já se encontra inscrito na aula de '" . h($class['name']) . "'.", 'info');
        redirect('/pages/schedules.php');
    }

    $stmtInsert = $db->prepare("INSERT INTO bookings (user_id, class_id) VALUES (?, ?)");
    $stmtInsert->execute([$user_id, $class_id]);

    $db->commit();
    set_flash("Inscrição na aula de '" . h($class['name']) . "' efetuada com sucesso!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB BOOK CLASS ERROR] ' . $e->getMessage());
    set_flash('Erro ao efetuar a inscrição: ' . $e->getMessage(), 'error');
}

redirect('/pages/schedules.php');
