<?php
/**
 * api/api_get_trainer_reviews.php
 *
 * AJAX endpoint to retrieve reviews left by members for classes taught by the logged-in trainer.
 * Returns a JSON payload containing the class name, rating, comment, and member name.
 * 
 * Security checks:
 * - Checks authentication (user logged in)
 * - Verifies user role is 'trainer'
 * - Prevents Horizontal Privilege Escalation (validates trainer_id matches logged-in user)
 * - Prepared statement to query SQLite DB (prevents SQL injection)
 * - Sanitizes all text fields with htmlspecialchars before responding (prevents XSS)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/src/utils.php';
require_once BASE_PATH . '/database/connection.php';

// 1. Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Não autenticado. Por favor, faça login.'
    ]);
    exit;
}

// 2. Verify user has the trainer role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'trainer') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas treinadores podem consultar avaliações.'
    ]);
    exit;
}

// Only accept GET requests to fetch data
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Utilize GET.'
    ]);
    exit;
}

try {
    $trainer_id = isset($_GET['trainer_id']) ? (int) $_GET['trainer_id'] : 0;
    $logged_in_id = (int) $_SESSION['user_id'];

    // 3. Validate trainer_id
    if ($trainer_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do treinador inválido.'
        ]);
        exit;
    }

    // 4. Prevent Horizontal Privilege Escalation
    if ($trainer_id !== $logged_in_id) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso negado. Apenas pode visualizar as suas próprias avaliações.'
        ]);
        exit;
    }

    $db = get_db();

    // 5. Query the database using a secure parameterized statement with JOIN
    $stmt = $db->prepare("
        SELECT 
            r.id AS review_id,
            r.rating,
            r.comment,
            r.created_at,
            c.name AS class_name,
            u.name AS member_name
        FROM reviews r
        JOIN classes c ON r.class_id = c.id
        JOIN users u ON r.user_id = u.id
        WHERE c.instructor_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$trainer_id]);
    $reviews = $stmt->fetchAll();

    // 6. Sanitize output data for safety (XSS prevention)
    $sanitized_reviews = [];
    foreach ($reviews as $review) {
        $sanitized_reviews[] = [
            'id' => (int) $review['review_id'],
            'rating' => (int) $review['rating'],
            'comment' => htmlspecialchars((string) ($review['comment'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'created_at' => htmlspecialchars((string) $review['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'class_name' => htmlspecialchars((string) $review['class_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'member_name' => htmlspecialchars((string) $review['member_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        ];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Avaliações carregadas com sucesso.',
        'reviews' => $sanitized_reviews,
        'count' => count($sanitized_reviews)
    ]);
    exit;

} catch (PDOException $e) {
    error_log('[API GET TRAINER REVIEWS DB ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro na base de dados ao obter as avaliações.'
    ]);
    exit;
} catch (Throwable $e) {
    error_log('[API GET TRAINER REVIEWS UNEXPECTED ERROR] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ocorreu um erro inesperado no servidor.'
    ]);
    exit;
}
