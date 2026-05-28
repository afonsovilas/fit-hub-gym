<?php
/**
 * actions/delete_equipment.php
 *
 * Remove um equipamento da base de dados do ginásio.
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

    $equipment_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($equipment_id <= 0) {
        set_flash('ID de equipamento inválido.', 'error');
        redirect('/pages/equipment.php');
    }

    $stmtCheck = $db->prepare("SELECT name FROM equipment WHERE id = ?");
    $stmtCheck->execute([$equipment_id]);
    $equipment = $stmtCheck->fetch();

    if (!$equipment) {
        set_flash('O equipamento não existe na base de dados.', 'error');
        redirect('/pages/equipment.php');
    }

    $db->beginTransaction();

    $stmtDelete = $db->prepare("DELETE FROM equipment WHERE id = ?");
    $stmtDelete->execute([$equipment_id]);

    $db->commit();
    set_flash("O equipamento '" . h($equipment['name']) . "' foi removido com sucesso da base de dados!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB DELETE EQUIPMENT ERROR] ' . $e->getMessage());
    set_flash('Erro ao remover equipamento: ' . $e->getMessage(), 'error');
}

redirect('/pages/equipment.php');
