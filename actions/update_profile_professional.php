<?php
/**
 * actions/update_profile_professional.php
 *
 * Processa a atualização dos dados profissionais do perfil de Treinador.
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

    // Obter ID do utilizador logado a partir da sessão activa
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    if ($user_id === null) {
        throw new Exception("Utilizador não autenticado.");
    }

    $stmtRole = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmtRole->execute([$user_id]);
    $roleRow = $stmtRole->fetch();
    $user_role = $roleRow ? $roleRow['role'] : 'member';

    if ($user_role !== 'trainer') {
        throw new Exception("Permissão negada. Apenas treinadores podem atualizar dados profissionais.");
    }

    $bio = trim($_POST['bio'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');
    $specializations = $_POST['specializations'] ?? [];

    $db->beginTransaction();

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

    $db->commit();
    set_flash('Dados profissionais atualizados com sucesso!', 'success');

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[DB UPDATE PROFILE PROFESSIONAL ERROR] ' . $e->getMessage());
    set_flash('Erro ao gravar dados profissionais: ' . $e->getMessage(), 'error');
}

redirect('/pages/profile.php#dados-profissionais');
