<?php
/**
 * actions/save_user.php
 *
 * Processador unificado para criação ou atualização de qualquer utilizador (Membro, Treinador, Staff, Admin).
 * Gere dados gerais e dados específicos de perfis no SQLite.
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

    // Dados gerais
    $user_id  = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : 0;
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    // Dados de Treinador
    $bio            = trim($_POST['bio'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');
    $specs          = $_POST['specializations'] ?? [];

    // Dados de Staff
    $position   = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Validações gerais básicas
    if ($name === '' || $email === '' || $username === '' || $role === '') {
        set_flash('Por favor, preencha todos os campos obrigatórios (*).', 'error');
        redirect('/pages/admin/staff.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('O endereço de email introduzido não é válido.', 'error');
        redirect('/pages/admin/staff.php');
    }

    // Validar username (letras, números e sublinhados)
    $clean_username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
    if ($username !== $clean_username) {
        set_flash('O nome de utilizador apenas pode conter letras, números e sublinhados (_).', 'error');
        redirect('/pages/admin/staff.php');
    }

    // Validar role permitido
    $allowed_roles = ['member', 'trainer', 'staff', 'admin'];
    if (!in_array($role, $allowed_roles, true)) {
        set_flash('A função selecionada é inválida.', 'error');
        redirect('/pages/admin/staff.php');
    }

    // Validações de campos obrigatórios específicas por role
    if ($role === 'staff') {
        if ($position === '' || $department === '') {
            set_flash('Para o Pessoal (Staff), a posição e o departamento são obrigatórios.', 'error');
            redirect('/pages/admin/staff.php');
        }
    }

    $db->beginTransaction();

    if ($user_id <= 0) {
        // ──────────────────────────────────────────────────────────────────────
        // MODO: CRIAR NOVO UTILIZADOR
        // ──────────────────────────────────────────────────────────────────────

        // Password é obrigatória
        if ($password === '') {
            set_flash('A palavra-passe é obrigatória para novos utilizadores.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        if (strlen($password) < 6) {
            set_flash('A palavra-passe deve conter pelo menos 6 caracteres.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        // Verificar email único
        $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmtCheckEmail->execute([$email]);
        if ($stmtCheckEmail->fetch()) {
            set_flash('Este email já se encontra registado por outra conta.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        // Verificar username único
        $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmtCheckUser->execute([$username]);
        if ($stmtCheckUser->fetch()) {
            set_flash('Este nome de utilizador já se encontra registado por outra conta.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmtInsert = $db->prepare("
            INSERT INTO users (name, email, username, password_hash, role, phone, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmtInsert->execute([$name, $email, $username, $password_hash, $role, $phone]);
        $new_id = (int) $db->lastInsertId();

        // Guardar dados específicos de perfil do novo utilizador
        if ($role === 'trainer') {
            // Perfil do treinador
            $stmtProfile = $db->prepare("INSERT INTO trainer_profiles (user_id, bio, certifications) VALUES (?, ?, ?)");
            $stmtProfile->execute([$new_id, $bio, $certifications]);

            // Especializações
            if (!empty($specs) && is_array($specs)) {
                $stmtSpec = $db->prepare("INSERT INTO trainer_specializations (trainer_id, specialization) VALUES (?, ?)");
                foreach ($specs as $spec) {
                    $specClean = trim(strtolower($spec));
                    if ($specClean !== '') {
                        $stmtSpec->execute([$new_id, $specClean]);
                    }
                }
            }
        } elseif ($role === 'staff') {
            // Perfil do staff
            $stmtProfile = $db->prepare("INSERT INTO staff_profiles (user_id, position, department) VALUES (?, ?, ?)");
            $stmtProfile->execute([$new_id, $position, $department]);
        }

        $db->commit();
        set_flash("Utilizador '" . h($name) . "' criado com sucesso!", 'success');

    } else {
        // ──────────────────────────────────────────────────────────────────────
        // MODO: EDITAR UTILIZADOR EXISTENTE
        // ──────────────────────────────────────────────────────────────────────
        
        // Verificar se utilizador existe
        $stmtCheckExist = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmtCheckExist->execute([$user_id]);
        $existing_user = $stmtCheckExist->fetch();
        if (!$existing_user) {
            set_flash('O utilizador a editar não existe na base de dados.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        $old_role = $existing_user['role'];

        // Verificar email único
        $stmtCheckEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmtCheckEmail->execute([$email, $user_id]);
        if ($stmtCheckEmail->fetch()) {
            set_flash('Este email já está a ser utilizado por outra conta.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        // Verificar username único
        $stmtCheckUser = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmtCheckUser->execute([$username, $user_id]);
        if ($stmtCheckUser->fetch()) {
            set_flash('Este nome de utilizador já está a ser utilizado por outra conta.', 'error');
            $db->rollBack();
            redirect('/pages/admin/staff.php');
        }

        // Atualizar dados gerais do utilizador
        $stmtUpdate = $db->prepare("
            UPDATE users
            SET name = ?, email = ?, username = ?, role = ?, phone = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([$name, $email, $username, $role, $phone, $user_id]);

        // Se password foi fornecida, atualizar password_hash
        if ($password !== '') {
            if (strlen($password) < 6) {
                set_flash('A nova palavra-passe deve conter pelo menos 6 caracteres.', 'error');
                $db->rollBack();
                redirect('/pages/admin/staff.php');
            }
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmtUpdatePass = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmtUpdatePass->execute([$password_hash, $user_id]);
        }

        // Limpeza de tabelas antigas se a role mudou
        if ($old_role !== $role) {
            if ($old_role === 'trainer') {
                $db->prepare("DELETE FROM trainer_profiles WHERE user_id = ?")->execute([$user_id]);
                $db->prepare("DELETE FROM trainer_specializations WHERE trainer_id = ?")->execute([$user_id]);
            } elseif ($old_role === 'staff') {
                $db->prepare("DELETE FROM staff_profiles WHERE user_id = ?")->execute([$user_id]);
            }
        }

        // Gravar ou atualizar os novos dados específicos do perfil
        if ($role === 'trainer') {
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

            // Atualizar especializações
            $db->prepare("DELETE FROM trainer_specializations WHERE trainer_id = ?")->execute([$user_id]);
            if (!empty($specs) && is_array($specs)) {
                $stmtInsertSpec = $db->prepare("INSERT INTO trainer_specializations (trainer_id, specialization) VALUES (?, ?)");
                foreach ($specs as $spec) {
                    $specClean = trim(strtolower($spec));
                    if ($specClean !== '') {
                        $stmtInsertSpec->execute([$user_id, $specClean]);
                    }
                }
            }

        } elseif ($role === 'staff') {
            $stmtCheckProfile = $db->prepare("SELECT 1 FROM staff_profiles WHERE user_id = ?");
            $stmtCheckProfile->execute([$user_id]);
            if ($stmtCheckProfile->fetch()) {
                $stmtUpdateProfile = $db->prepare("
                    UPDATE staff_profiles 
                    SET position = ?, department = ? 
                    WHERE user_id = ?
                ");
                $stmtUpdateProfile->execute([$position, $department, $user_id]);
            } else {
                $stmtInsertProfile = $db->prepare("
                    INSERT INTO staff_profiles (user_id, position, department) 
                    VALUES (?, ?, ?)
                ");
                $stmtInsertProfile->execute([$user_id, $position, $department]);
            }
        }

        $db->commit();
        set_flash("Utilizador '" . h($name) . "' atualizado com sucesso!", 'success');
    }

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB SAVE USER ERROR] ' . $e->getMessage());
    set_flash('Erro ao guardar utilizador: ' . $e->getMessage(), 'error');
}

redirect('/pages/admin/staff.php');
