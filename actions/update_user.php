<?php
/**
 * actions/update_user.php
 *
 * Processa a atualização de dados de Treinadores e Staff pela Administração.
 * Protegido por CSRF e com verificação de role admin.
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

// Apenas Administradores podem realizar esta ação
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Acesso negado.');
}

try {
    $db = get_db();

    $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($user_id <= 0 || $name === '' || $email === '') {
        set_flash('O ID, Nome e Email são campos obrigatórios.', 'error');
        redirect('/pages/admin/staff.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('O email inserido não é válido.', 'error');
        redirect('/pages/admin/staff.php');
    }

    // Verificar se o email já está em uso por outra conta
    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtCheck->execute([$email, $user_id]);
    if ($stmtCheck->fetch()) {
        set_flash('Este email já se encontra em uso por outro utilizador.', 'error');
        redirect('/pages/admin/staff.php');
    }

    $stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$user_id]);
    $user = $stmtRole->fetch();

    if (!$user) {
        set_flash('O utilizador a editar não existe.', 'error');
        redirect('/pages/admin/staff.php');
    }

    $role = $user['role'];

    $db->beginTransaction();

    $stmtUpdateUser = $db->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone = ? 
        WHERE id = ?
    ");
    $stmtUpdateUser->execute([$name, $email, $phone, $user_id]);

    if ($role === 'trainer') {
        $bio = trim($_POST['bio'] ?? '');
        $certifications = trim($_POST['certifications'] ?? '');

        // Garantir que existe o registo na tabela de perfil
        $stmtCheckProfile = $db->prepare("SELECT 1 FROM trainer_profiles WHERE user_id = ?");
        $stmtCheckProfile->execute([$user_id]);
        if ($stmtCheckProfile->fetch()) {
            $stmtUpdateProfile = $db->prepare("
                UPDATE trainer_profiles 
                SET bio = ?, certifications = ? 
                WHERE user_id = ?
            ");
            $stmtUpdateProfile->execute([$bio, $certifications, $user_id]);
        } else {
            $stmtInsertProfile = $db->prepare("
                INSERT INTO trainer_profiles (user_id, bio, certifications) 
                VALUES (?, ?, ?)
            ");
            $stmtInsertProfile->execute([$user_id, $bio, $certifications]);
        }

        if (isset($_POST['specializations']) && is_array($_POST['specializations'])) {
            $stmtDeleteSpecs = $db->prepare("DELETE FROM trainer_specializations WHERE trainer_id = ?");
            $stmtDeleteSpecs->execute([$user_id]);

            $stmtInsertSpec = $db->prepare("INSERT INTO trainer_specializations (trainer_id, specialization) VALUES (?, ?)");
            foreach ($_POST['specializations'] as $spec) {
                $specClean = trim(strtolower($spec));
                if ($specClean !== '') {
                    $stmtInsertSpec->execute([$user_id, $specClean]);
                }
            }
        }
    } elseif ($role === 'staff') {
        $position = trim($_POST['position'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($position === '' || $department === '') {
            $db->rollBack();
            set_flash('Posição e Departamento são obrigatórios para funcionários do Staff.', 'error');
            redirect('/pages/admin/staff.php');
        }

        $stmtCheckStaff = $db->prepare("SELECT 1 FROM staff_profiles WHERE user_id = ?");
        $stmtCheckStaff->execute([$user_id]);
        if ($stmtCheckStaff->fetch()) {
            $stmtUpdateStaff = $db->prepare("
                UPDATE staff_profiles 
                SET position = ?, department = ? 
                WHERE user_id = ?
            ");
            $stmtUpdateStaff->execute([$position, $department, $user_id]);
        } else {
            $stmtInsertStaff = $db->prepare("
                INSERT INTO staff_profiles (user_id, position, department) 
                VALUES (?, ?, ?)
            ");
            $stmtInsertStaff->execute([$user_id, $position, $department]);
        }
    }

    $db->commit();
    set_flash("Perfil de " . h($name) . " atualizado com sucesso pela administração!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[ADMIN UPDATE USER ERROR] ' . $e->getMessage());
    set_flash('Erro ao atualizar utilizador: ' . $e->getMessage(), 'error');
}

redirect('/pages/admin/staff.php');
