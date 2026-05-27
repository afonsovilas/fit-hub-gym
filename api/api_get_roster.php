<?php
/**
 * api/api_get_roster.php
 *
 * Endpoint AJAX para obter o roster de alunos inscritos numa aula.
 * Retorna JSON com a lista de alunos e informações de perfil.
 * 
 * Security:
 * - Verifica autenticação (trainer only)
 * - Valida class_id
 * - Verifica se o treinador é o instrutor da aula
 * - Sanitiza dados com htmlspecialchars antes de retornar JSON
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/src/utils.php';
require_once BASE_PATH . '/database/connection.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Não autenticado. Por favor faça login.'
    ]);
    exit;
}

// Verificar se é treinador
if ($_SESSION['user_role'] !== 'trainer') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Apenas treinadores podem visualizar rosters.'
    ]);
    exit;
}

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

try {
    $trainer_id = (int) $_SESSION['user_id'];
    $class_id = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

    // Validar class_id
    if ($class_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da aula inválido.'
        ]);
        exit;
    }

    $db = get_db();

    // Verificar se a aula existe e pertence ao treinador logado
    $stmtCheckClass = $db->prepare("
        SELECT id FROM classes 
        WHERE id = ? AND instructor_id = ?
    ");
    $stmtCheckClass->execute([$class_id, $trainer_id]);
    
    if (!$stmtCheckClass->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Esta aula não está atribuída a você.'
        ]);
        exit;
    }

    // Fetch all members enrolled in this class
    $stmtRoster = $db->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.avatar
        FROM users u
        JOIN bookings b ON u.id = b.user_id
        WHERE b.class_id = ? AND u.role = 'member'
        ORDER BY u.name ASC
    ");
    $stmtRoster->execute([$class_id]);
    $members = $stmtRoster->fetchAll();

    // Sanitize member data for JSON response
    $sanitized_members = [];
    foreach ($members as $member) {
        $sanitized_members[] = [
            'id' => (int) $member['id'],
            'name' => htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8'),
            'email' => htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8'),
            'avatar' => htmlspecialchars($member['avatar'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }

    // Return success response with roster data
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Roster carregado com sucesso.',
        'members' => $sanitized_members,
        'count' => count($sanitized_members)
    ]);
    exit;

} catch (PDOException $e) {
    error_log('[API ROSTER ERROR - DB] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro na base de dados. Por favor tente novamente.'
    ]);
    exit;

} catch (Throwable $e) {
    error_log('[API ROSTER ERROR - UNEXPECTED] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro inesperado. Por favor tente novamente.'
    ]);
    exit;
}
