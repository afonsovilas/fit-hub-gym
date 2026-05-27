<?php
/**
 * actions/add_trainer.php
 *
 * Adiciona um novo treinador ao sistema.
 * Cria o utilizador básico, o perfil de treinador e associa as especializações.
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

    $name  = trim($_POST['trainer-name'] ?? '');
    $email = trim($_POST['trainer-email'] ?? '');
    $phone = trim($_POST['trainer-phone'] ?? '');
    $specs = $_POST['trainer-specialization'] ?? [];
    $certs = trim($_POST['trainer-certifications'] ?? '');
    $bio   = trim($_POST['trainer-bio'] ?? '');

    if ($name === '' || $email === '') {
        set_flash('Nome e Email são obrigatórios.', 'error');
        redirect('/pages/admin/staff.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('O email inserido não é válido.', 'error');
        redirect('/pages/admin/staff.php');
    }

    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        set_flash('Este email já está registado.', 'error');
        redirect('/pages/admin/staff.php');
    }

    $db->beginTransaction();

    // Gerar um username padrão único a partir do nome
    $base_username = strtolower(str_replace(' ', '', $name));
    $base_username = preg_replace('/[^a-z0-9]/', '', $base_username);
    if (strlen($base_username) === 0) {
        $base_username = 'trainer';
    }
    $username = $base_username;
    $count = 0;
    
    $stmtCheckUsername = $db->prepare("SELECT id FROM users WHERE username = ?");
    while (true) {
        $stmtCheckUsername->execute([$username]);
        if (!$stmtCheckUsername->fetch()) {
            break;
        }
        $count++;
        $username = $base_username . $count;
    }

    $password_hash = password_hash('123456', PASSWORD_BCRYPT);
    $stmtUser = $db->prepare("
        INSERT INTO users (name, email, username, password_hash, role, phone, is_active)
        VALUES (?, ?, ?, ?, 'trainer', ?, 1)
    ");
    $stmtUser->execute([$name, $email, $username, $password_hash, $phone]);
    $new_user_id = (int) $db->lastInsertId();

    $stmtProfile = $db->prepare("
        INSERT INTO trainer_profiles (user_id, bio, certifications)
        VALUES (?, ?, ?)
    ");
    $stmtProfile->execute([$new_user_id, $bio, $certs]);

    if (!empty($specs)) {
        $stmtSpec = $db->prepare("
            INSERT INTO trainer_specializations (trainer_id, specialization)
            VALUES (?, ?)
        ");
        foreach ($specs as $spec) {
            $stmtSpec->execute([$new_user_id, trim($spec)]);
        }
    }

    $db->commit();
    set_flash("Treinador " . h($name) . " registado com sucesso na base de dados!", 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB ADD TRAINER ERROR] ' . $e->getMessage());
    set_flash('Erro ao registar treinador: ' . $e->getMessage(), 'error');
}

redirect('/pages/admin/staff.php');
