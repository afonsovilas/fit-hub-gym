<?php
/**
 * actions/save_equipment.php
 *
 * Permite a criação ou atualização de equipamentos da zona de treino livre.
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

    $equipment_id = isset($_POST['equipment_id']) && $_POST['equipment_id'] !== '' ? (int) $_POST['equipment_id'] : 0;
    $name         = trim($_POST['name'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $status       = trim($_POST['status'] ?? 'available');
    $description  = trim($_POST['description'] ?? '');

    // Validações básicas
    if ($name === '' || $category === '' || $status === '') {
        set_flash('Por favor, preencha todos os campos obrigatórios (*).', 'error');
        redirect('/pages/equipment.php');
    }

    $allowed_statuses = ['available', 'in_use', 'maintenance'];
    if (!in_array($status, $allowed_statuses, true)) {
        set_flash('O estado selecionado é inválido.', 'error');
        redirect('/pages/equipment.php');
    }

    $db->beginTransaction();

    if ($equipment_id <= 0) {
        // MODO: CRIAR NOVO EQUIPAMENTO
        $stmtInsert = $db->prepare("
            INSERT INTO equipment (name, category, status, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmtInsert->execute([$name, $category, $status, $description]);

        $db->commit();
        set_flash("Equipamento '" . h($name) . "' adicionado com sucesso!", 'success');

    } else {
        // MODO: EDITAR EQUIPAMENTO EXISTENTE
        $stmtCheck = $db->prepare("SELECT id FROM equipment WHERE id = ?");
        $stmtCheck->execute([$equipment_id]);
        if (!$stmtCheck->fetch()) {
            set_flash('O equipamento a editar não existe na base de dados.', 'error');
            $db->rollBack();
            redirect('/pages/equipment.php');
        }

        $stmtUpdate = $db->prepare("
            UPDATE equipment
            SET name = ?, category = ?, status = ?, description = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([$name, $category, $status, $description, $equipment_id]);

        $db->commit();
        set_flash("Equipamento '" . h($name) . "' atualizado com sucesso!", 'success');
    }

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB SAVE EQUIPMENT ERROR] ' . $e->getMessage());
    set_flash('Erro ao guardar equipamento: ' . $e->getMessage(), 'error');
}

redirect('/pages/equipment.php');
