<?php
/**
 * api/api_admin_actions.php
 *
 * AJAX API Endpoint for Admin User Management Actions
 * Handles: toggle_status, make_admin
 * Security: Requires admin role, POST method, CSRF validation
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once BASE_PATH . '/database/connection.php';
require_once BASE_PATH . '/src/csrf.php';

// ──────────────────────────────────────────────────────────────────────────
// Security Checks
// ──────────────────────────────────────────────────────────────────────────

// JSON response helper
function json_response(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message,
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    json_response(false, 'Não autenticado. Por favor inicie sessão.');
}

// Check CSRF Token
$csrf_token = $_POST['csrf_token'] ?? null;
if (!validate_csrf_token($csrf_token)) {
    http_response_code(403);
    json_response(false, 'Acesso negado. Token de segurança CSRF ausente ou inválido.');
}

// Check admin role
if ($_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    json_response(false, 'Acesso negado. Apenas administradores podem realizar esta ação.');
}

// Check HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Método não permitido. Use POST.');
}

// ──────────────────────────────────────────────────────────────────────────
// Get Request Data
// ──────────────────────────────────────────────────────────────────────────

$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : null;
$action  = isset($_POST['action']) ? trim($_POST['action']) : null;

if (!$user_id || !$action) {
    http_response_code(400);
    json_response(false, 'Parâmetros inválidos. user_id e action são obrigatórios.');
}

// ──────────────────────────────────────────────────────────────────────────
// Process Actions
// ──────────────────────────────────────────────────────────────────────────

try {
    $db = get_db();

    if ($action === 'toggle_status') {
        // Toggle user active/inactive status
        
        // Get current status
        $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            json_response(false, 'Utilizador não encontrado.');
        }

        $new_status = $user['is_active'] ? 0 : 1;
        
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
        
        $status_text = $new_status ? 'Ativo' : 'Inativo';
        
        json_response(
            true,
            "Utilizador " . strtolower($status_text) . " com sucesso.",
            [
                'user_id'   => $user_id,
                'new_status' => $new_status,
                'status_text' => $status_text,
            ]
        );

    } elseif ($action === 'make_admin') {
        // Promote user to admin role
        
        // Get user details first
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            json_response(false, 'Utilizador não encontrado.');
        }

        if ($user['role'] === 'admin') {
            http_response_code(400);
            json_response(false, 'Este utilizador já é um administrador.');
        }

        // Update role to admin
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute(['admin', $user_id]);
        
        json_response(
            true,
            'Utilizador promovido a administrador com sucesso.',
            [
                'user_id'   => $user_id,
                'new_role'  => 'admin',
                'role_text' => 'Administrador',
            ]
        );

    } else {
        http_response_code(400);
        json_response(false, 'Ação não reconhecida.');
    }

} catch (Throwable $e) {
    error_log('[ADMIN ACTIONS API ERROR] ' . $e->getMessage());
    http_response_code(500);
    json_response(false, 'Erro no servidor. Por favor tente novamente.');
}
