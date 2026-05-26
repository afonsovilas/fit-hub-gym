<?php
/**
 * actions/submit_review.php
 *
 * Processa o envio de avaliações e comentários sobre aulas.
 * Apenas utilizadores autenticados podem submeter reviews.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/src/utils.php';
require_once BASE_PATH . '/database/connection.php';
require_once BASE_PATH . '/src/csrf.php';

// Verificar se utilizador está autenticado
if (!isset($_SESSION['user_id'])) {
    set_flash('Inicie sessão para deixar uma avaliação.', 'error');
    redirect('/pages/login.php');
}

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/profile.php');
}

// Validar CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('Token de segurança inválido. Por favor tente novamente.', 'error');
    redirect('/pages/profile.php');
}

try {
    $user_id = (int) $_SESSION['user_id'];
    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validar class_id
    if ($class_id <= 0) {
        throw new Exception('ID da aula inválido.');
    }

    // Validar rating (1-5)
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Avaliação deve ser entre 1 e 5 estrelas.');
    }

    // Validar que o utilizador estava realmente inscrito nesta aula
    $db = get_db();
    
    $stmtCheckBooking = $db->prepare("
        SELECT 1 FROM bookings 
        WHERE user_id = ? AND class_id = ?
    ");
    $stmtCheckBooking->execute([$user_id, $class_id]);
    
    if (!$stmtCheckBooking->fetch()) {
        throw new Exception('Você não está inscrito nesta aula.');
    }

    // Validar que a aula já ocorreu
    $stmtCheckPastClass = $db->prepare("
        SELECT 1 FROM classes 
        WHERE id = ? AND scheduled_at < datetime('now')
    ");
    $stmtCheckPastClass->execute([$class_id]);
    
    if (!$stmtCheckPastClass->fetch()) {
        throw new Exception('Apenas pode avaliar aulas que já ocorreram.');
    }

    // Verificar se já existe uma review para esta combinação user + class
    $stmtCheckExisting = $db->prepare("
        SELECT id FROM reviews 
        WHERE user_id = ? AND class_id = ?
    ");
    $stmtCheckExisting->execute([$user_id, $class_id]);
    $existingReview = $stmtCheckExisting->fetch();

    if ($existingReview) {
        // Atualizar review existente
        $stmtUpdate = $db->prepare("
            UPDATE reviews 
            SET rating = ?, comment = ? 
            WHERE user_id = ? AND class_id = ?
        ");
        $stmtUpdate->execute([$rating, $comment, $user_id, $class_id]);
        set_flash('Avaliação atualizada com sucesso!', 'success');
    } else {
        // Inserir nova review
        $stmtInsert = $db->prepare("
            INSERT INTO reviews (user_id, class_id, rating, comment) 
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$user_id, $class_id, $rating, $comment]);
        set_flash('Avaliação enviada com sucesso! Obrigado pela sua opinião.', 'success');
    }

} catch (Exception $e) {
    set_flash('Erro ao processar a avaliação: ' . $e->getMessage(), 'error');
} catch (PDOException $e) {
    error_log('[DB ERROR] ' . $e->getMessage());
    set_flash('Erro ao guardar a avaliação na base de dados. Por favor tente novamente.', 'error');
} catch (Throwable $e) {
    error_log('[UNEXPECTED ERROR] ' . $e->getMessage());
    set_flash('Erro inesperado ao processar a avaliação.', 'error');
}

// Redirecionar de volta para o perfil
redirect('/pages/profile.php');
