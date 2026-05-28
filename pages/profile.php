<?php
/**
 * pages/profile.php
 *
 * Página de perfil do utilizador logado.
 * Integrada com a base de dados SQLite (FitHub).
 * 
*/

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Meu Perfil';
$body_class  = 'profile-page';
$active_page = 'profile';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';
require_once BASE_PATH . '/database/connection.php';

if (!isset($_SESSION['user_id'])) {
    set_flash('Inicie sessão para aceder ao seu perfil.', 'error');
    redirect('/pages/login.php');
}

try {
    $db = get_db();

    $user_id = $_SESSION['user_id'];

    $stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();

    if (!$user) {
        throw new Exception("Utilizador não encontrado na base de dados.");
    }

    $stmtMyClasses = $db->prepare("
        SELECT c.*, u.name AS instructor_name 
        FROM bookings b
        JOIN classes c ON b.class_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE b.user_id = ? AND c.scheduled_at >= ?
        ORDER BY c.scheduled_at ASC
    ");
    $stmtMyClasses->execute([$user_id, date('Y-m-d H:i')]);
    $myClasses = $stmtMyClasses->fetchAll();

    // Fetch past classes for review history
    $stmtPastClasses = $db->prepare("
        SELECT c.id, c.name, c.scheduled_at, c.duration_min, c.room, u.name AS instructor_name, u.id AS instructor_id
        FROM bookings b
        JOIN classes c ON b.class_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE b.user_id = ? AND c.scheduled_at < ?
        ORDER BY c.scheduled_at DESC
    ");
    $stmtPastClasses->execute([$user_id, date('Y-m-d H:i')]);
    $pastClasses = $stmtPastClasses->fetchAll();

    // Fetch reviews for past classes
    $stmtReviews = $db->prepare("
        SELECT class_id, rating, comment 
        FROM reviews 
        WHERE user_id = ?
    ");
    $stmtReviews->execute([$user_id]);
    $reviewsRaw = $stmtReviews->fetchAll();
    
    // Convert to associative array keyed by class_id
    $reviews = [];
    foreach ($reviewsRaw as $review) {
        $reviews[$review['class_id']] = [
            'rating' => $review['rating'],
            'comment' => $review['comment']
        ];
    }

    // Fetch my waitlisted classes
    $myWaitlists = [];
    if ($user && $user['role'] === 'member') {
        $stmtMyWaitlists = $db->prepare("
            SELECT c.*, u.name AS instructor_name 
            FROM class_waitlists cw
            JOIN classes c ON cw.class_id = c.id
            JOIN users u ON c.instructor_id = u.id
            WHERE cw.user_id = ? AND c.scheduled_at >= datetime('now')
            ORDER BY c.scheduled_at ASC
        ");
        $stmtMyWaitlists->execute([$user_id]);
        $myWaitlists = $stmtMyWaitlists->fetchAll();
    }

    // Fetch trainer profile info if user is a trainer
    $trainer_bio = '';
    $trainer_certs = '';
    $mySpecs = [];
    if ($user && $user['role'] === 'trainer') {
        $stmtTp = $db->prepare("SELECT bio, certifications FROM trainer_profiles WHERE user_id = ?");
        $stmtTp->execute([$user_id]);
        $tp = $stmtTp->fetch();
        if ($tp) {
            $trainer_bio = $tp['bio'] ?? '';
            $trainer_certs = $tp['certifications'] ?? '';
        }

        $stmtSpecs = $db->prepare("SELECT specialization FROM trainer_specializations WHERE trainer_id = ?");
        $stmtSpecs->execute([$user_id]);
        $specsRaw = $stmtSpecs->fetchAll();
        foreach ($specsRaw as $spec) {
            $mySpecs[] = $spec['specialization'];
        }
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Meu Perfil</h1>
            <p>Gerencie suas informações pessoais e consulte suas marcações de aulas</p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>

        <?php if (isset($error)): ?>
            <div class="flash flash--error">Erro na base de dados: <?= h($error) ?></div>
        <?php else: ?>
            <div class="profile-wrapper">
                
                <aside class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-header">
                             <img src="<?= $user['avatar'] ? h(BASE_URL_PATH . $user['avatar']) : '../assets/images/avatars/profile-placeholder.png' ?>" 
                                  alt="Foto de perfil de <?= h($user['name']) ?>" 
                                  class="profile-photo"
                                  onerror="this.src='../assets/images/avatars/profile-placeholder.png';">
                            <div class="profile-info">
                                <h2 class="profile-name"><?= h($user['name']) ?></h2>
                                <p class="profile-username">@<?= h($user['username'] ?? strtolower(str_replace(' ', '', $user['name']))) ?></p>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-detail">
                                <span class="detail-label">Email:</span>
                                <span><?= h($user['email']) ?></span>
                            </p>
                            <p class="profile-detail">
                                <span class="detail-label">Membro desde:</span>
                                <span><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                            </p>
                            <p class="profile-detail">
                                <span class="detail-label">Função:</span>
                                <span class="badge badge-<?= h($user['role']) ?>"><?= h($user['role']) ?></span>
                            </p>
                            <p class="profile-detail">
                                <span class="detail-label">Status:</span>
                                <span class="status-badge status-active"><?= $user['is_active'] ? 'Ativo' : 'Inativo' ?></span>
                            </p>
                        </div>
                    </div>
                </aside>

                <section class="profile-main">
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <h3>Editar Perfil (Base de Dados)</h3>
                            <button type="button" class="btn btn-primary btn-small" id="edit-profile-btn">Editar</button>
                        </div>
                        <form class="profile-form" id="profile-form" method="POST" action="../actions/update_profile.php" enctype="multipart/form-data">
                            <?= get_csrf_input() ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full-name">Nome Completo:</label>
                                    <input type="text" id="full-name" name="full-name" value="<?= h($user['name']) ?>" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Nome de Utilizador:</label>
                                    <input type="text" id="username" name="username" value="<?= h($user['username'] ?? '') ?>" disabled required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" id="email" name="email" value="<?= h($user['email']) ?>" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Telefone:</label>
                                    <input type="tel" id="phone" name="phone" value="<?= h($user['phone'] ?? '+351 900 000 000') ?>" disabled>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Nova Senha (deixe em branco para não alterar):</label>
                                <input type="password" id="password" name="password" placeholder="••••••••" disabled>
                            </div>

                            <div class="form-group">
                                <label for="password-confirm">Confirmar Senha:</label>
                                <input type="password" id="password-confirm" name="password-confirm" placeholder="••••••••" disabled>
                            </div>

                            <div class="form-group" style="margin-top: 15px;">
                                <label for="avatar">Foto de Perfil (Avatar):</label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" disabled class="file-input">
                                <small class="form-hint" style="color: #94a3b8; font-size: 0.8em; margin-top: 4px; display: block;">Envie uma imagem JPG, PNG ou GIF de até 2MB.</small>
                            </div>

                            <?php if ($user['role'] === 'trainer'): ?>
                                <div class="trainer-extra-info" style="border-top: 1px solid #334155; padding-top: 1.5rem; margin-top: 1.5rem; display: flex; flex-direction: column; gap: 15px;">
                                    <h4 style="margin: 0; color: #38bdf8; font-size: 1.1rem; font-weight: 600;">Informações de Treinador</h4>
                                    
                                    <div class="form-group">
                                        <label for="bio">Biografia / Apresentação:</label>
                                        <textarea id="bio" name="bio" rows="4" placeholder="Escreva uma breve apresentação profissional..." disabled style="resize: vertical;"><?= h($trainer_bio) ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="certifications">Certificações e Formação (uma por linha):</label>
                                        <textarea id="certifications" name="certifications" rows="3" placeholder="Ex: Licenciatura em Ciências do Desporto, Certificação Pilates..." disabled style="resize: vertical;"><?= h($trainer_certs) ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="margin-bottom: 8px; display: block;">Especialidades Ativas:</label>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 8px;">
                                            <?php 
                                            $availableSpecs = [
                                                'yoga' => 'Yoga',
                                                'spinning' => 'Spinning',
                                                'hiit' => 'HIIT',
                                                'pilates' => 'Pilates',
                                                'boxing' => 'Boxe',
                                                'personal' => 'Treino Personalizado'
                                            ];
                                            foreach ($availableSpecs as $value => $label): 
                                                $checked = in_array($value, $mySpecs) ? 'checked' : '';
                                            ?>
                                                <label style="display: flex; align-items: center; gap: 8px; font-weight: 500; cursor: pointer; color: #cbd5e1;">
                                                    <input type="checkbox" name="specializations[]" value="<?= $value ?>" <?= $checked ?> disabled style="width: auto; cursor: pointer; height: 18px; width: 18px; accent-color: #38bdf8;">
                                                    <?= $label ?>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="save-btn" style="display: none;">Guardar Alterações</button>
                                <button type="reset" class="btn btn-secondary btn-small" id="cancel-btn" style="display: none;">Cancelar</button>
                            </div>
                        </form>
                    </div>

                    <?php if (isset($user['role']) && $user['role'] !== 'admin'): ?>
                        <div class="profile-section">
                        <h3>As Minhas Aulas Agendadas</h3>
                        <p class="section-description">Tens inscrições confirmadas nas seguintes aulas:</p>
                        
                        <?php if (empty($myClasses)): ?>
                            <p style="color:#94a3b8; font-style:italic;">Não tens nenhuma inscrição ativa para os próximos dias.</p>
                        <?php else: ?>
                            <div class="class-list">
                                <?php foreach ($myClasses as $c): 
                                    $timestamp = strtotime($c['scheduled_at']);
                                    $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                                    $dia_extenso = $dias_semana[date('w', $timestamp)];
                                ?>
                                    <article class="class-item" id="class-item-<?= $c['id'] ?>">
                                        <div class="class-header">
                                            <h4 class="class-title"><?= h($c['name']) ?></h4>
                                            <span class="class-type" style="text-transform: capitalize;"><?= h(strtolower(str_replace(' Express', '', str_replace(' Matinal', '', str_replace(' Intenso', '', str_replace(' Clássico', '', $c['name'])))))) ?></span>
                                        </div>
                                        <div class="class-details">
                                            <p class="class-info">
                                                <strong>Instrutor:</strong> <?= h($c['instructor_name']) ?>
                                            </p>
                                            <p class="class-info">
                                                <strong>Data:</strong> <?= $dia_extenso ?>, <?= date('d/m/Y', $timestamp) ?> às <?= date('H:i', $timestamp) ?>
                                            </p>
                                            <p class="class-info">
                                                <strong>Localização:</strong> <?= h($c['room']) ?>
                                            </p>
                                            <p class="class-info">
                                                <strong>Duração:</strong> <?= $c['duration_min'] ?> minutos
                                            </p>
                                        </div>
                                        <form method="POST" action="../actions/cancel_booking.php" onsubmit="return confirm('Deseja realmente cancelar a sua inscrição na aula <?= h($c['name']) ?>?');" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-cancel" style="cursor: pointer;">Cancelar Inscrição</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($user['role'] === 'member' && !empty($myWaitlists)): ?>
                        <div class="profile-section">
                            <h3>Aulas em Lista de Espera</h3>
                            <p class="section-description">Estás na lista de espera para as seguintes aulas (serás inscrito automaticamente se surgir uma vaga):</p>
                            <div class="class-list">
                                <?php foreach ($myWaitlists as $cw): 
                                    $timestamp = strtotime($cw['scheduled_at']);
                                    $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                                    $dia_extenso = $dias_semana[date('w', $timestamp)];
                                ?>
                                    <article class="class-item" style="border-left: 4px solid #f59e0b;">
                                        <div class="class-header">
                                            <h4 class="class-title"><?= h($cw['name']) ?></h4>
                                            <span class="class-type" style="text-transform: capitalize; background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600;">Lista de Espera</span>
                                        </div>
                                        <div class="class-details">
                                            <p class="class-info">
                                                <strong>Instrutor:</strong> <?= h($cw['instructor_name']) ?>
                                            </p>
                                            <p class="class-info">
                                                <strong>Data:</strong> <?= $dia_extenso ?>, <?= date('d/m/Y', $timestamp) ?> às <?= date('H:i', $timestamp) ?>
                                            </p>
                                        </div>
                                        <form method="POST" action="../actions/cancel_waitlist.php" onsubmit="return confirm('Deseja realmente sair da lista de espera para a aula <?= h($cw['name']) ?>?');" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $cw['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-cancel" style="cursor: pointer; background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Sair da Lista de Espera</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($user['role'] === 'member'): ?>
                        <div class="profile-section">
                            <h3>Marcar Treino Personalizado</h3>
                            <p class="section-description">Agende uma sessão individual de 60 minutos com um dos nossos treinadores especializados:</p>
                            
                            <form method="POST" action="../actions/book_personal_training.php" class="profile-form" style="gap: 15px;">
                                <?= get_csrf_input() ?>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="pt-trainer">Selecionar Treinador:</label>
                                        <select id="pt-trainer" name="trainer_id" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                                            <option value="">-- Escolha um treinador --</option>
                                            <?php 
                                            // Fetch active trainers from database
                                            try {
                                                $stmtPtTrainers = $db->query("SELECT id, name FROM users WHERE role = 'trainer' AND is_active = 1 ORDER BY name ASC");
                                                $ptTrainers = $stmtPtTrainers->fetchAll();
                                                foreach ($ptTrainers as $ptTrainer) {
                                                    echo '<option value="' . $ptTrainer['id'] . '">' . h($ptTrainer['name']) . '</option>';
                                                }
                                            } catch (Throwable $e) {}
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="pt-datetime">Data e Hora:</label>
                                        <input type="datetime-local" id="pt-datetime" name="scheduled_at" required min="<?= date('Y-m-d\TH:i') ?>" style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; color-scheme: dark;">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="pt-notes">Notas / Objetivos do Treino:</label>
                                    <textarea id="pt-notes" name="notes" rows="2" placeholder="Ex: Foco em pernas, recuperação de lesão, etc." style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; resize: vertical;"></textarea>
                                </div>
                                
                                <div class="form-actions" style="margin-top: 10px;">
                                    <button type="submit" class="btn btn-primary btn-small">Confirmar Agendamento</button>
                                </div>
                            </form>
                        </div>

                        <div class="profile-section">
                            <h3>Sessões de Personal Training Agendadas</h3>
                            <p class="section-description">Suas solicitações e sessões individuais agendadas:</p>
                            
                            <?php 
                            try {
                                $stmtMyPt = $db->prepare("
                                    SELECT ptb.*, u.name AS trainer_name, u.phone AS trainer_phone
                                    FROM personal_training_bookings ptb
                                    JOIN users u ON ptb.trainer_id = u.id
                                    WHERE ptb.member_id = ? AND ptb.status IN ('pending', 'confirmed', 'rejected') AND ptb.scheduled_at >= ?
                                    ORDER BY ptb.scheduled_at ASC
                                ");
                                $stmtMyPt->execute([$user_id, date('Y-m-d H:i')]);
                                $myPts = $stmtMyPt->fetchAll();
                            } catch (Throwable $e) {
                                $myPts = [];
                            }
                            
                            if (empty($myPts)): 
                            ?>
                                <p style="color:#94a3b8; font-style:italic;">Não tens nenhuma sessão de Personal Training agendada.</p>
                            <?php else: ?>
                                <div class="class-list">
                                    <?php foreach ($myPts as $pt): 
                                        $ptTimestamp = strtotime($pt['scheduled_at']);
                                        $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                                        $ptDiaExtenso = $dias_semana[date('w', $ptTimestamp)];
                                        
                                        $status_styles = [
                                            'confirmed' => [
                                                'border' => '#10b981',
                                                'bg' => 'rgba(16, 185, 129, 0.15)',
                                                'color' => '#10b981',
                                                'text' => 'Confirmado'
                                            ],
                                            'pending' => [
                                                'border' => '#f59e0b',
                                                'bg' => 'rgba(245, 158, 11, 0.15)',
                                                'color' => '#f59e0b',
                                                'text' => 'Pendente de Confirmação'
                                            ],
                                            'rejected' => [
                                                'border' => '#ef4444',
                                                'bg' => 'rgba(239, 68, 68, 0.15)',
                                                'color' => '#f87171',
                                                'text' => 'Recusado pelo Treinador'
                                            ]
                                        ];
                                        
                                        $style = $status_styles[$pt['status']] ?? $status_styles['pending'];
                                    ?>
                                        <article class="class-item" style="border-left: 4px solid <?= $style['border'] ?>;">
                                            <div class="class-header">
                                                <h4 class="class-title">Sessão Personal Training</h4>
                                                <span class="class-type" style="text-transform: capitalize; background: <?= $style['bg'] ?>; color: <?= $style['color'] ?>; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600;"><?= $style['text'] ?></span>
                                            </div>
                                            <div class="class-details">
                                                <p class="class-info">
                                                    <strong>Treinador:</strong> <?= h($pt['trainer_name']) ?> (<?= h($pt['trainer_phone'] ?? 'Sem telefone') ?>)
                                                </p>
                                                <p class="class-info">
                                                    <strong>Data:</strong> <?= $ptDiaExtenso ?>, <?= date('d/m/Y', $ptTimestamp) ?> às <?= date('H:i', $ptTimestamp) ?>
                                                </p>
                                                <?php if (!empty($pt['notes'])): ?>
                                                    <p class="class-info">
                                                        <strong>Minhas Notas:</strong> <em>"<?= h($pt['notes']) ?>"</em>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($pt['status'] === 'rejected' && !empty($pt['rejection_reason'])): ?>
                                                    <p class="class-info" style="color: #f87171; background: rgba(239,68,68,0.08); padding: 8px; border-radius: 6px; border: 1px solid rgba(239,68,68,0.15); margin-top: 6px;">
                                                        <strong>Motivo da Recusa:</strong> <em>"<?= h($pt['rejection_reason']) ?>"</em>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <form method="POST" action="../actions/cancel_personal_training.php" onsubmit="return confirm('Deseja realmente <?= $pt['status'] === 'rejected' ? 'remover' : 'desmarcar' ?> a sua sessão de Personal Training?');" style="margin: 0; padding: 0; display: inline;">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="booking_id" value="<?= $pt['id'] ?>">
                                                <button type="submit" class="btn btn-small btn-cancel" style="cursor: pointer; background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">
                                                    <?= $pt['status'] === 'rejected' ? 'Remover do Histórico' : 'Desmarcar Sessão' ?>
                                                </button>
                                            </form>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="profile-section">
                        <h3>Histórico de Aulas</h3>
                        <p class="section-description">Deixe a sua opinião sobre as aulas que frequentou:</p>

                        <?php if (empty($pastClasses)): ?>
                            <p style="color:#94a3b8; font-style:italic;">Ainda não frequentaste nenhuma aula.</p>
                        <?php else: ?>
                            <div class="past-classes-list">
                                <?php foreach ($pastClasses as $pastClass):
                                    $class_id = $pastClass['id'];
                                    $timestamp = strtotime($pastClass['scheduled_at']);
                                    $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                                    $dia_extenso = $dias_semana[date('w', $timestamp)];
                                    
                                    $hasReview = isset($reviews[$class_id]);
                                ?>
                                    <article class="past-class-card">
                                        <div class="past-class-header">
                                            <div class="past-class-info">
                                                <h4 class="past-class-title"><?= h($pastClass['name']) ?></h4>
                                                <p class="past-class-meta">
                                                    <?= $dia_extenso ?>, <?= date('d/m/Y', $timestamp) ?> às <?= date('H:i', $timestamp) ?> 
                                                    <span class="past-class-trainer">| <?= h($pastClass['instructor_name']) ?></span>
                                                </p>
                                            </div>
                                            <?php if ($hasReview): ?>
                                                <span class="review-badge">✓ Avaliada</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($hasReview): ?>
                                            <div class="review-display">
                                                <div class="review-rating">
                                                    <span class="review-stars">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?= $i <= $reviews[$class_id]['rating'] ? 'filled' : '' ?>">★</span>
                                                        <?php endfor; ?>
                                                    </span>
                                                    <span class="rating-text"><?= $reviews[$class_id]['rating'] ?>/5</span>
                                                </div>
                                                <div class="review-comment">
                                                    <p><?= h($reviews[$class_id]['comment']) ?></p>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <form method="POST" action="../actions/submit_review.php" class="review-form">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="class_id" value="<?= $class_id ?>">

                                                <div class="form-group">
                                                    <label for="rating-<?= $class_id ?>">Avaliação (1-5 estrelas):</label>
                                                    <div class="rating-input">
                                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <input 
                                                                type="radio" 
                                                                id="rating-<?= $class_id ?>-<?= $i ?>" 
                                                                name="rating" 
                                                                value="<?= $i ?>"
                                                                required
                                                                class="rating-radio"
                                                            >
                                                            <label for="rating-<?= $class_id ?>-<?= $i ?>" class="rating-label" title="<?= $i ?> estrela<?= $i !== 1 ? 's' : '' ?>">★</label>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>

                                                <div class="form-group">
                                                    <label for="comment-<?= $class_id ?>">Deixe a sua opinião para melhorar a aula:</label>
                                                    <textarea 
                                                        id="comment-<?= $class_id ?>" 
                                                        name="comment" 
                                                        placeholder="Partilhe a sua experiência, dicas ou sugestões..."
                                                        rows="4"
                                                        maxlength="500"
                                                    ></textarea>
                                                    <small class="char-count">0/500</small>
                                                </div>

                                                <div class="form-actions">
                                                    <button type="submit" class="btn btn-primary btn-small">Enviar Avaliação</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php 
$extra_js = ['js/profile.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
