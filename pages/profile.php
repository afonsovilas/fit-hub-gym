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

                <style>
                .profile-tabs-nav::-webkit-scrollbar {
                    display: none; /* Hide scrollbar for horizontal swipe */
                }
                .tab-btn:hover {
                    color: var(--color-primary) !important;
                }
                .tab-btn.active {
                    color: var(--color-primary) !important;
                    border-bottom-color: var(--color-primary) !important;
                }
                .tab-pane {
                    display: none;
                    animation: tabFadeIn 0.3s ease;
                }
                .tab-pane.active {
                    display: block;
                }
                @keyframes tabFadeIn {
                    from { opacity: 0; transform: translateY(8px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                </style>

                <section class="profile-main">
                    <?php if ($user['role'] === 'member'): ?>
                        <!-- Tabs Navigation Bar -->
                        <div class="profile-tabs-nav" style="display: flex; gap: 8px; border-bottom: 2px solid #334155; padding-bottom: 0px; margin-bottom: 2rem; overflow-x: auto; scrollbar-width: none;">
                            <button type="button" class="tab-btn active" data-tab="dados-pessoais" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Dados Pessoais
                            </button>
                            <button type="button" class="tab-btn" data-tab="aulas-grupo" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                Aulas de Grupo
                            </button>
                            <button type="button" class="tab-btn" data-tab="treino-personalizado" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6.5 6.5 11 11"/><path d="m21 21-1-1"/><path d="m3 3 1 1"/><path d="m18.5 5.5 1.5-1.5"/><path d="m5.5 18.5-1.5 1.5"/><path d="M8.5 2 2 8.5"/><path d="M22 15.5 15.5 22"/></svg>
                                Treino Personalizado (PT)
                            </button>
                            <button type="button" class="tab-btn" data-tab="avaliacoes-historico" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></polygon></svg>
                                Avaliações & Histórico
                            </button>
                        </div>
                    <?php endif; ?>

                    <div id="pane-dados-pessoais" class="<?= $user['role'] === 'member' ? 'tab-pane active' : '' ?>">
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
                                <div style="display: flex; gap: 15px; align-items: center; margin-top: 5px; flex-wrap: wrap;">
                                    <input type="file" id="avatar" name="avatar" accept="image/*" disabled class="file-input" style="flex: 1; min-width: 200px;">
                                    <div id="avatar-preview-container" style="display: none; align-items: center; gap: 8px; cursor: pointer;" title="Clique para ajustar o recorte novamente">
                                        <img id="avatar-preview-img" src="" alt="Previsualização" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--color-primary); box-shadow: 0 4px 10px rgba(207, 90, 43, 0.2);">
                                        <span style="font-size: 0.85em; color: var(--color-text-muted); font-weight: 500;">(Pré-visualização)</span>
                                    </div>
                                </div>
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
                    </div><!-- #pane-dados-pessoais -->

                    <?php if ($user['role'] === 'trainer'): ?>
                        <div class="profile-section">
                            <h3>Avaliações das Minhas Aulas</h3>
                            <p class="section-description">Consulte o feedback e as classificações deixadas pelos membros nas aulas que lecionou:</p>
                            <button type="button" class="btn btn-primary" id="btn-show-reviews" data-trainer-id="<?= h($user['id']) ?>">
                                Ver Avaliações das Minhas Aulas
                            </button>
                            <div id="trainer-reviews-container" style="display: none; margin-top: 20px;"></div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($user['role']) && $user['role'] !== 'admin'): ?>
                        <div id="pane-aulas-grupo" class="<?= $user['role'] === 'member' ? 'tab-pane' : '' ?>">
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
                                        <form method="POST" action="../actions/cancel_booking.php" class="profile-cancel-form" data-title="Cancelar Inscrição" data-message="Deseja realmente cancelar a sua inscrição na aula <?= h($c['name']) ?>?" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/pages/profile.php#aulas-grupo">
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
                                        <form method="POST" action="../actions/cancel_waitlist.php" class="profile-cancel-form" data-title="Sair da Lista de Espera" data-message="Deseja realmente sair da lista de espera para a aula <?= h($cw['name']) ?>?" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $cw['id'] ?>">
                                            <input type="hidden" name="redirect_to" value="/pages/profile.php#aulas-grupo">
                                            <button type="submit" class="btn btn-small btn-cancel" style="cursor: pointer; background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Sair da Lista de Espera</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                        </div><!-- #pane-aulas-grupo -->
                    <?php endif; ?>

                    <?php if ($user['role'] === 'member'): ?>
                        <div id="pane-treino-personalizado" class="tab-pane">
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
                                            <form method="POST" action="../actions/cancel_personal_training.php" class="profile-cancel-form" data-title="<?= $pt['status'] === 'rejected' ? 'Remover do Histórico' : 'Desmarcar Sessão' ?>" data-message="Deseja realmente <?= $pt['status'] === 'rejected' ? 'remover' : 'desmarcar' ?> a sua sessão de Personal Training?" style="margin: 0; padding: 0; display: inline;">
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
                        </div><!-- #pane-treino-personalizado -->
                    <?php endif; ?>

                    <?php if (isset($user['role']) && $user['role'] !== 'admin'): ?>
                        <div id="pane-avaliacoes-historico" class="<?= $user['role'] === 'member' ? 'tab-pane' : '' ?>">
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
                        </div><!-- #pane-avaliacoes-historico -->
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal de Recorte de Imagem (Avatar Cropper Modal) -->
    <div id="avatar-cropper-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: hidden; background-color: rgba(15, 23, 42, 0.85); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(8px);">
        <div style="background: #1e293b; border: 1px solid #334155; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; display: flex; flex-direction: column; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <!-- Header -->
            <div style="padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #fff; font-size: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--color-primary);"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                    Ajustar Foto de Perfil
                </h3>
                <button type="button" id="cropper-close-btn" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; display: flex; align-items: center; transition: color 0.2s;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <!-- Body -->
            <div style="padding: 24px; display: flex; flex-direction: column; align-items: center; gap: 20px;">
                <!-- Viewport (Circular crop mask) -->
                <div id="cropper-viewport" style="position: relative; width: 280px; height: 280px; overflow: hidden; border-radius: 50%; border: 3px solid var(--color-primary); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4); background-color: #0f172a; cursor: grab; display: flex; align-items: center; justify-content: center;">
                    <img id="cropper-preview-img" src="" alt="Ajustar imagem" style="position: absolute; left: 0; top: 0; transform-origin: 0 0; user-select: none; pointer-events: none; max-width: none;">
                </div>
                
                <p style="color: #94a3b8; font-size: 0.85rem; text-align: center; margin: 0; line-height: 1.4;">
                    Arraste a foto para centrar e utilize a barra deslizante abaixo para aproximar.
                </p>

                <!-- Zoom Control -->
                <div style="width: 100%; display: flex; align-items: center; gap: 12px; padding: 0 10px;">
                    <span style="color: #94a3b8; font-size: 0.9rem; font-weight: bold;">-</span>
                    <input type="range" id="cropper-zoom-slider" min="1" max="4" step="0.01" value="1" style="flex: 1; height: 6px; border-radius: 3px; background: #334155; outline: none; cursor: pointer; accent-color: var(--color-primary);">
                    <span style="color: #94a3b8; font-size: 0.9rem; font-weight: bold;">+</span>
                </div>
            </div>
            <!-- Footer -->
            <div style="padding: 20px; border-top: 1px solid #334155; background: #1e293b; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" id="cropper-cancel-btn" class="btn btn-secondary btn-small" style="margin: 0; padding: 8px 16px;">Cancelar</button>
                <button type="button" id="cropper-confirm-btn" class="btn btn-primary btn-small" style="margin: 0; padding: 8px 20px; background: var(--color-primary); border-color: var(--color-primary);">Confirmar</button>
            </div>
        </div>
    </div>

    <style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    #avatar-cropper-modal input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: var(--color-primary);
        cursor: pointer;
        transition: transform 0.1s ease;
    }
    #avatar-cropper-modal input[type="range"]::-webkit-slider-thumb:hover {
        transform: scale(1.2);
    }
    </style>

    <!-- Modal de Confirmação Geral do Perfil (Custom Profile Confirmation Modal) -->
    <div id="profile-confirm-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 450px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <!-- Header -->
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
                <h3 id="profile-confirm-title" style="margin: 0; color: #cf5a2b; font-size: 1.25em; font-weight: 700;">Confirmar Ação</h3>
                <button type="button" id="profile-confirm-close" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>
            <!-- Body -->
            <div style="padding: 20px; color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; background: #0f172a;">
                <p id="profile-confirm-message" style="margin: 0;">Tem a certeza que deseja prosseguir com esta ação?</p>
            </div>
            <!-- Footer -->
            <div style="padding: 15px 20px; border-top: 1px solid #1e293b; display: flex; justify-content: flex-end; gap: 10px; background: #0b0f19;">
                <button type="button" id="profile-confirm-cancel" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px;">Cancelar</button>
                <button type="button" id="profile-confirm-proceed" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px; background-color: #cf5a2b; color: white; font-weight: bold; border: none; cursor: pointer;">Confirmar</button>
            </div>
        </div>
    </div>
</main>

<?php 
$extra_js = ['js/profile.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
