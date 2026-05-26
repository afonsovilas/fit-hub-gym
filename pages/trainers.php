<?php
/**
 * pages/trainers.php
 *
 * Página dinâmica que exibe todos os treinadores do ginásio com suas especialidades,
 * certificações e aulas que ministram.
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
    
    // Fetch all active trainers with their profile data
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
    <!-- Page Header -->
    <section class="page-header page-header--trainers">
        <div class="container">
            <h1>Nossos Treinadores</h1>
            <p class="page-subtitle">Conheça a nossa equipa de especialistas dedicados ao seu sucesso</p>
        </div>
    </section>

    <?php render_flash(); ?>

    <!-- Trainers List Section -->
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
                        $trainer_avatar = $trainer['avatar'] ? h($trainer['avatar']) : 'assets/images/placeholder-avatar.jpg';
                        $trainer_bio = $trainer['bio'] ? h($trainer['bio']) : '';
                        $trainer_certifications = $trainer['certifications'] ? h($trainer['certifications']) : '';
                        $trainer_phone = $trainer['phone'] ? h($trainer['phone']) : '';
                        
                        // Fetch specializations for this trainer
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
                        
                        // Fetch classes taught by this trainer
                        try {
                            $stmtClasses = $db->prepare("
                                SELECT id, name, scheduled_at 
                                FROM classes 
                                WHERE instructor_id = ? 
                                ORDER BY name ASC
                            ");
                            $stmtClasses->execute([$trainer_id]);
                            $classes = $stmtClasses->fetchAll();
                        } catch (Throwable $e) {
                            $classes = [];
                        }
                    ?>
                        <article class="trainer-card">
                            <!-- Trainer Avatar -->
                            <div class="trainer-avatar-wrapper">
                                <img 
                                    src="<?= $trainer_avatar ?>" 
                                    alt="Foto de <?= $trainer_name ?>"
                                    class="trainer-avatar"
                                    loading="lazy"
                                >
                            </div>
                            
                            <!-- Trainer Info -->
                            <div class="trainer-content">
                                <h2 class="trainer-name"><?= $trainer_name ?></h2>
                                
                                <?php if ($trainer_bio): ?>
                                    <p class="trainer-bio"><?= $trainer_bio ?></p>
                                <?php endif; ?>
                                
                                <!-- Certifications -->
                                <?php if ($trainer_certifications): ?>
                                    <div class="trainer-certifications">
                                        <h3 class="trainer-section-title">Certificações</h3>
                                        <p class="certifications-text"><?= $trainer_certifications ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Specializations -->
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
                                
                                <!-- Classes Taught -->
                                <?php if (!empty($classes)): ?>
                                    <div class="trainer-classes">
                                        <h3 class="trainer-section-title">Aulas Ministradas</h3>
                                        <ul class="class-list">
                                            <?php foreach ($classes as $class): ?>
                                                <li class="class-item">
                                                    <span class="class-name"><?= h($class['name']) ?></span>
                                                    <?php if ($class['scheduled_at']): ?>
                                                        <span class="class-date">
                                                            (<?= date('d/m/Y H:i', strtotime($class['scheduled_at'])) ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Contact Info -->
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
