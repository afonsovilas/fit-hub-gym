<?php
/**
 * actions/update_profile.php
 *
 * Processa a atualização dos dados do perfil do utilizador.
 * Efetua validações básicas, hashing de password (se alterada) e grava no SQLite.
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

    // Obter ID do utilizador logado a partir da sessão activa, com fallback para utilizador 9
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 9;

    $stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$user_id]);
    $roleRow = $stmtRole->fetch();
    $user_role = $roleRow ? $roleRow['role'] : 'member';

    $name  = trim($_POST['full-name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password-confirm'] ?? '';

    if ($name === '' || $email === '' || $username === '') {
        set_flash('Nome, Email e Nome de Utilizador são campos obrigatórios.', 'error');
        redirect('/pages/profile.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('O email inserido não é válido.', 'error');
        redirect('/pages/profile.php');
    }

    // Validar formato e unicidade do username
    $username = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));
    if (strlen($username) < 3) {
        set_flash('O nome de utilizador deve ter pelo menos 3 caracteres alfanuméricos ou sublinhados.', 'error');
        redirect('/pages/profile.php');
    }

    $stmtCheckUsername = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmtCheckUsername->execute([$username, $user_id]);
    if ($stmtCheckUsername->fetch()) {
        set_flash('Este nome de utilizador já se encontra em uso.', 'error');
        redirect('/pages/profile.php');
    }

    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmtCheck->execute([$email, $user_id]);
    if ($stmtCheck->fetch()) {
        set_flash('Este email já se encontra registado por outro utilizador.', 'error');
        redirect('/pages/profile.php');
    }

    $db->beginTransaction();

    $stmtUpdate = $db->prepare("
        UPDATE users 
        SET name = ?, email = ?, username = ?, phone = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$name, $email, $username, $phone, $user_id]);

    // Processamento de Upload do Avatar (Foto de Perfil)
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erro no upload do ficheiro de imagem. Código: " . $file['error']);
        }
        
        // Validar tamanho (máx 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception("A imagem de perfil deve ter no máximo 2MB.");
        }
        
        // Validar tipo MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Apenas são permitidas imagens JPG, PNG, GIF ou WEBP.");
        }
        
        // Gerar caminho e nome de ficheiro
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = "user_" . $user_id . "_" . time() . "." . $ext;
        
        $uploadDir = __DIR__ . '/../assets/images/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . $fileName;
        $dbPath = "assets/images/avatars/" . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception("Falha ao mover a imagem para a diretoria final.");
        }
        
        // Gravar na base de dados
        $stmtAvatar = $db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmtAvatar->execute([$dbPath, $user_id]);
    }

    if ($password !== '') {
        if ($password !== $password_confirm) {
            $db->rollBack();
            set_flash('A confirmação da nova senha não coincide.', 'error');
            redirect('/pages/profile.php');
        }

        if (strlen($password) < 6) {
            $db->rollBack();
            set_flash('A nova senha deve ter pelo menos 6 caracteres.', 'error');
            redirect('/pages/profile.php');
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmtPass = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmtPass->execute([$password_hash, $user_id]);
    }

    // Processar campos adicionais de perfil de Treinador
    if ($user_role === 'trainer') {
        $bio = trim($_POST['bio'] ?? '');
        $certifications = trim($_POST['certifications'] ?? '');
        $specializations = $_POST['specializations'] ?? [];

        $stmtCheckProfile = $db->prepare("SELECT 1 FROM trainer_profiles WHERE user_id = ?");
        $stmtCheckProfile->execute([$user_id]);
        if ($stmtCheckProfile->fetch()) {
            $stmtUpdateTp = $db->prepare("UPDATE trainer_profiles SET bio = ?, certifications = ? WHERE user_id = ?");
            $stmtUpdateTp->execute([$bio, $certifications, $user_id]);
        } else {
            $stmtInsertTp = $db->prepare("INSERT INTO trainer_profiles (user_id, bio, certifications) VALUES (?, ?, ?)");
            $stmtInsertTp->execute([$user_id, $bio, $certifications]);
        }

        $stmtDeleteSpecs = $db->prepare("DELETE FROM trainer_specializations WHERE trainer_id = ?");
        $stmtDeleteSpecs->execute([$user_id]);

        if (is_array($specializations) && !empty($specializations)) {
            $stmtInsertSpec = $db->prepare("INSERT INTO trainer_specializations (trainer_id, specialization) VALUES (?, ?)");
            foreach ($specializations as $spec) {
                $specClean = trim(strtolower($spec));
                if ($specClean !== '') {
                    $stmtInsertSpec->execute([$user_id, $specClean]);
                }
            }
        }
    }

    $db->commit();
    
    // Atualizar dados de sessão
    $_SESSION['user_name'] = $name;

    set_flash('Perfil atualizado com sucesso na base de dados!', 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB UPDATE PROFILE ERROR] ' . $e->getMessage());
    set_flash('Erro ao gravar dados na base de dados: ' . $e->getMessage(), 'error');
}

redirect('/pages/profile.php');
