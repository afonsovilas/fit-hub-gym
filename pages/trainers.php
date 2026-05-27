<?php
/**
 * pages/trainers.php
 *
 * Página dinâmica que exibe todos os treinadores do ginásio com suas especialidades,
 * certificações e aulas.
 * 
 * Ligada à base de dados SQLite (FitHub).
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Treinadores';
$body_class  = 'trainers-page';
$active_page = 'trainers';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/database/connection.php';

try {
    $db = get_db();
    
    $stmtTrainers = $db->prepare("
        SELECT 
            u.id,
            u.name,
            u.avatar,
            u.phone,
            tp.bio,
            tp.certifications
        FROM users u
        LEFT JOIN trainer_profiles tp ON u.id = tp.user_id
        WHERE u.role = 'trainer' AND u.is_active = 1
        ORDER BY u.name ASC
    ");
    $stmtTrainers->execute();
    $trainers = $stmtTrainers->fetchAll();
    
} catch (Throwable $e) {
    $error = $e->getMessage();
    $trainers = [];
}
?>

<main>
    <section class="page-header page-header--trainers">
        <div class="container">
            <h1>Nossos Treinadores</h1>
            <p class="page-subtitle">Conheça a nossa equipa de especialistas dedicados ao seu sucesso</p>
        </div>
    </section>

    <?php render_flash(); ?>

    <section class="trainers-section">
        <div class="container">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert--error" role="alert">
                    <p>Erro ao carregar os treinadores: <?= h($error) ?></p>
                </div>
            <?php elseif (empty($trainers)): ?>
                <div class="alert alert--info" role="alert">
                    <p>Ainda não existem treinadores contratados no momento.</p>
                </div>
            <?php else: ?>
                <div class="trainers-grid">
                    <?php foreach ($trainers as $trainer): 
                        $trainer_id = $trainer['id'];
                        $trainer_name = h($trainer['name']);
                        $avatar_path = $trainer['avatar'] ? BASE_PATH . '/' . $trainer['avatar'] : '';
                        $trainer_avatar = ($trainer['avatar'] && file_exists($avatar_path)) 
                            ? h(BASE_URL_PATH . $trainer['avatar']) 
                            : BASE_URL_PATH . 'assets/images/avatars/profile-placeholder.png';
                        $trainer_bio = $trainer['bio'] ? h($trainer['bio']) : '';
                        $trainer_certifications = $trainer['certifications'] ? h($trainer['certifications']) : '';
                        $trainer_phone = $trainer['phone'] ? h($trainer['phone']) : '';
                        
                        try {
                            $stmtSpecializations = $db->prepare("
                                SELECT specialization 
                                FROM trainer_specializations 
                                WHERE trainer_id = ? 
                                ORDER BY specialization ASC
                            ");
                            $stmtSpecializations->execute([$trainer_id]);
                            $specializations = $stmtSpecializations->fetchAll();
                        } catch (Throwable $e) {
                            $specializations = [];
                        }
                    ?>
                        <article class="trainer-card">
                            <div class="trainer-avatar-wrapper">
                                <img 
                                    src="<?= $trainer_avatar ?>" 
                                    alt="Foto de <?= $trainer_name ?>"
                                    class="trainer-avatar"
                                    loading="lazy"
                                >
                            </div>
                            
                            <div class="trainer-content">
                                <h2 class="trainer-name"><?= $trainer_name ?></h2>
                                
                                <?php if ($trainer_bio): ?>
                                    <p class="trainer-bio"><?= $trainer_bio ?></p>
                                <?php endif; ?>
                                
                                <?php if ($trainer_certifications): ?>
                                    <div class="trainer-certifications">
                                        <h3 class="trainer-section-title">Certificações</h3>
                                        <p class="certifications-text"><?= $trainer_certifications ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($specializations)): ?>
                                    <div class="trainer-specializations">
                                        <h3 class="trainer-section-title">Especializações</h3>
                                        <ul class="specialization-tags">
                                            <?php foreach ($specializations as $spec): ?>
                                                <li class="specialization-tag">
                                                    <?= h($spec['specialization']) ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($trainer_phone): ?>
                                    <div class="trainer-contact">
                                        <p><strong>Contacto:</strong> <?= $trainer_phone ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>
