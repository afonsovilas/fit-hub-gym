<?php
/**
 * actions/save_class.php
 *
 * Processador unificado para criação ou atualização de aulas de grupo.
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

    $class_id      = isset($_POST['class_id']) && $_POST['class_id'] !== '' ? (int) $_POST['class_id'] : 0;
    $name          = trim($_POST['name'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $instructor_id = isset($_POST['instructor_id']) ? (int) $_POST['instructor_id'] : 0;
    $capacity      = isset($_POST['capacity']) ? (int) $_POST['capacity'] : 20;
    $scheduled_at  = trim($_POST['scheduled_at'] ?? '');
    $duration_min  = isset($_POST['duration_min']) ? (int) $_POST['duration_min'] : 60;
    $room          = trim($_POST['room'] ?? 'Sala 1');

    // Validações gerais básicas
    if ($name === '' || $instructor_id <= 0 || $scheduled_at === '' || $room === '') {
        set_flash('Por favor, preencha todos os campos obrigatórios (*).', 'error');
        redirect('/pages/schedules.php');
    }

    if ($capacity <= 0 || $duration_min <= 0) {
        set_flash('A capacidade e a duração da aula devem ser superiores a zero.', 'error');
        redirect('/pages/schedules.php');
    }

    // Verificar se o instrutor realmente existe e é um treinador ativo
    $stmtTrainer = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'trainer' AND is_active = 1");
    $stmtTrainer->execute([$instructor_id]);
    if (!$stmtTrainer->fetch()) {
        set_flash('O treinador selecionado é inválido ou está inativo.', 'error');
        redirect('/pages/schedules.php');
    }

    // Converter datetime-local (Y-m-d\TH:i) para SQLite format (Y-m-d H:i)
    $db_scheduled_at = str_replace('T', ' ', $scheduled_at);

    // Impedir agendamento retroativo (no passado)
    if (strtotime($db_scheduled_at) < time()) {
        set_flash('Não é permitido marcar ou agendar uma aula com data/hora anterior à atual.', 'error');
        redirect('/pages/schedules.php');
    }

    $db->beginTransaction();

    if ($class_id <= 0) {
        // MODO: CRIAR NOVA AULA
        $stmtInsert = $db->prepare("
            INSERT INTO classes (name, description, instructor_id, capacity, scheduled_at, duration_min, room)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$name, $description, $instructor_id, $capacity, $db_scheduled_at, $duration_min, $room]);

        $db->commit();
        set_flash("Aula de grupo '" . h($name) . "' criada com sucesso no catálogo!", 'success');

    } else {
        // MODO: EDITAR AULA EXISTENTE
        $stmtCheck = $db->prepare("SELECT id FROM classes WHERE id = ?");
        $stmtCheck->execute([$class_id]);
        if (!$stmtCheck->fetch()) {
            set_flash('A aula a editar não existe na base de dados.', 'error');
            $db->rollBack();
            redirect('/pages/schedules.php');
        }

        $stmtUpdate = $db->prepare("
            UPDATE classes
            SET name = ?, description = ?, instructor_id = ?, capacity = ?, scheduled_at = ?, duration_min = ?, room = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([$name, $description, $instructor_id, $capacity, $db_scheduled_at, $duration_min, $room, $class_id]);

        $db->commit();
        set_flash("Aula '" . h($name) . "' atualizada com sucesso no catálogo!", 'success');
    }

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB SAVE CLASS ERROR] ' . $e->getMessage());
    set_flash('Erro ao guardar aula no catálogo: ' . $e->getMessage(), 'error');
}

redirect('/pages/schedules.php');
