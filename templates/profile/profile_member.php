<?php
/**
 * templates/profile/profile_member.php
 *
 * Página de perfil com visualizações e abas exclusivas para Alunos (Members).
 */

declare(strict_types=1);

// Ensure user is member
if ($user['role'] !== 'member') {
    redirect('/pages/profile.php');
}

// Queries
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

$stmtReviews = $db->prepare("
    SELECT class_id, rating, comment 
    FROM reviews 
    WHERE user_id = ?
");
$stmtReviews->execute([$user_id]);
$reviewsRaw = $stmtReviews->fetchAll();

$reviews = [];
foreach ($reviewsRaw as $review) {
    $reviews[$review['class_id']] = [
        'rating' => $review['rating'],
        'comment' => $review['comment']
    ];
}

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

$stmtNutTrainers = $db->query("
    SELECT u.id, u.name 
    FROM users u
    JOIN trainer_specializations ts ON u.id = ts.trainer_id
    WHERE u.role = 'trainer' AND u.is_active = 1 AND ts.specialization = 'nutricao'
    ORDER BY u.name ASC
");
$nutritionTrainers = $stmtNutTrainers->fetchAll();

$stmtMyNut = $db->prepare("
    SELECT nr.*, u.name AS trainer_name, u.email AS trainer_email
    FROM nutrition_requests nr
    JOIN users u ON nr.trainer_id = u.id
    WHERE nr.member_id = ?
    ORDER BY nr.created_at DESC
");
$stmtMyNut->execute([$user_id]);
$myNutritionRequests = $stmtMyNut->fetchAll();

$page_title = 'Meu Perfil';
$body_class = 'profile-page';
$active_page = 'profile';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
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

        <div class="profile-wrapper">
            <?php require BASE_PATH . '/templates/profile/shared_sidebar.php'; ?>

            <section class="profile-main">
                <!-- Tabs Navigation Bar for Members -->
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
                    <button type="button" class="tab-btn" data-tab="nutricao" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c5.522 0 10 4.477 10 10s-4.478 10-10 10S2 17.523 2 12 6.478 2 12 2zm0 2C7.589 4 4 7.589 4 12s3.589 8 8 8 8-3.589 8-8-3.589-8-8-8zm-1 3h2v6h-2V7zm0 8h2v2h-2v-2z"/></svg>
                        Nutrição
                    </button>
                </div>

                <!-- Tab: Dados Pessoais -->
                <?php require BASE_PATH . '/templates/profile/shared_personal_data.php'; ?>

                <!-- Tab: Aulas de Grupo -->
                <div id="pane-aulas-grupo" class="tab-pane">
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

                    <?php if (!empty($myWaitlists)): ?>
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
                </div>

                <!-- Tab: Treino Personalizado (PT) -->
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
                                        foreach ($nutritionTrainers as $ptTrainer) {
                                            echo '<option value="' . $ptTrainer['id'] . '">' . h($ptTrainer['name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                     <div>
                                         <label for="pt-date">Data:</label>
                                         <input type="date" id="pt-date" min="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; color-scheme: dark;">
                                     </div>
                                     <div>
                                         <label for="pt-time">Hora:</label>
                                         <select id="pt-time" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; color-scheme: dark;">
                                             <option value="">-- Selecione data --</option>
                                         </select>
                                     </div>
                                     <input type="hidden" id="pt-datetime" name="scheduled_at">
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
                                        <?php if ($pt['status'] === 'pending'): ?>
                                            <form method="POST" action="../actions/cancel_personal_training.php" class="profile-cancel-form" data-title="Cancelar Sessão" data-message="Deseja realmente cancelar este agendamento de Personal Training?" style="margin:0; padding:0; display:inline;">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="booking_id" value="<?= $pt['id'] ?>">
                                                <button type="submit" class="btn btn-small btn-cancel" style="cursor: pointer;">Cancelar Agendamento</button>
                                            </form>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Avaliações & Histórico -->
                <div id="pane-avaliacoes-historico" class="tab-pane">
                    <div class="profile-section">
                        <h3>Avaliar Aulas de Grupo</h3>
                        <p class="section-description">Deixe a sua classificação e comentário para as aulas em que participou recentemente:</p>
                        
                        <?php if (empty($pastClasses)): ?>
                            <p style="color:#94a3b8; font-style:italic;">Não tem histórico de aulas anteriores terminadas.</p>
                        <?php else: ?>
                            <div class="class-list">
                                <?php foreach ($pastClasses as $pc): 
                                    $pcTimestamp = strtotime($pc['scheduled_at']);
                                    $hasReviewed = isset($reviews[$pc['id']]);
                                ?>
                                    <article class="class-item" style="border-left: 4px solid <?= $hasReviewed ? '#10b981' : '#f59e0b' ?>;">
                                        <div class="class-header">
                                            <h4 class="class-title"><?= h($pc['name']) ?></h4>
                                            <?php if ($hasReviewed): ?>
                                                <span class="class-type" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600; text-transform: uppercase;">Avaliado</span>
                                            <?php else: ?>
                                                <span class="class-type" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600; text-transform: uppercase;">Pendente</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="class-details">
                                            <p class="class-info">
                                                <strong>Instrutor:</strong> <?= h($pc['instructor_name']) ?>
                                            </p>
                                            <p class="class-info">
                                                <strong>Data da Aula:</strong> <?= date('d/m/Y H:i', $pcTimestamp) ?>
                                            </p>
                                            
                                            <?php if ($hasReviewed): ?>
                                                <div style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 6px; border: 1px dashed rgba(255,255,255,0.05);">
                                                    <p class="class-info" style="margin: 0; color: #f59e0b; font-weight: 600;">
                                                        Classificação: 
                                                        <?php 
                                                        $stars = (int) $reviews[$pc['id']]['rating'];
                                                        for($i=1; $i<=5; $i++) {
                                                            echo $i <= $stars ? '★' : '☆';
                                                        }
                                                        ?>
                                                    </p>
                                                    <?php if (!empty($reviews[$pc['id']]['comment'])): ?>
                                                        <p class="class-info" style="margin: 4px 0 0 0; color: #cbd5e1; font-style: italic;">
                                                            "<?= h($reviews[$pc['id']]['comment']) ?>"
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <form method="POST" action="../actions/submit_review.php" class="review-form" style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                                                    <?= get_csrf_input() ?>
                                                    <input type="hidden" name="class_id" value="<?= $pc['id'] ?>">
                                                    
                                                    <div class="form-group" style="margin: 0;">
                                                        <label style="font-weight: 600; font-size: 0.85em; color: #cbd5e1; display: block; margin-bottom: 4px;">Classificação (1 a 5 estrelas):</label>
                                                        <div class="rating-input">
                                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                                <input type="radio" id="star-<?= $pc['id'] ?>-<?= $i ?>" name="rating" value="<?= $i ?>" class="rating-radio" required>
                                                                <label for="star-<?= $pc['id'] ?>-<?= $i ?>" class="rating-label">★</label>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-group" style="margin: 0;">
                                                        <label for="comment-<?= $pc['id'] ?>" style="font-weight: 600; font-size: 0.85em; color: #cbd5e1;">Comentário (Opcional):</label>
                                                        <div style="position: relative;">
                                                            <textarea id="comment-<?= $pc['id'] ?>" name="comment" rows="2" maxlength="500" placeholder="Partilhe a sua experiência nesta aula..." style="width:100%; padding: 8px; border-radius: 6px; background:#1e293b; border:1px solid #334155; color:#fff; resize: vertical; font-size: 0.9em; padding-bottom: 20px;"></textarea>
                                                            <span class="char-count" style="position: absolute; bottom: 4px; right: 8px; font-size: 0.75em; color: var(--color-text-muted);">0/500</span>
                                                        </div>
                                                    </div>
                                                    
                                                    <div style="margin-top: 4px;">
                                                        <button type="submit" class="btn btn-primary btn-small">Enviar Avaliação</button>
                                                    </div>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Nutrição -->
                <div id="pane-nutricao" class="tab-pane">
                    <div class="profile-section">
                        <h3>Solicitar Plano Alimentar</h3>
                        <p class="section-description">Selecione um nutricionista e solicite um plano alimentar personalizado:</p>
                        <?php if (empty($nutritionTrainers)): ?>
                            <p style="color:#94a3b8; font-style:italic;">Não existem nutricionistas disponíveis de momento.</p>
                        <?php else: ?>
                            <form method="POST" action="../actions/request_nutrition_plan.php" class="profile-form" style="gap: 15px;">
                                <?= get_csrf_input() ?>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nutrition-trainer">Selecionar Nutricionista:</label>
                                        <select id="nutrition-trainer" name="trainer_id" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                                            <option value="">-- Escolha um profissional --</option>
                                            <?php foreach ($nutritionTrainers as $nt): ?>
                                                <option value="<?= $nt['id'] ?>"><?= h($nt['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-actions" style="margin-top: 10px;">
                                    <button type="submit" class="btn btn-primary btn-small">Solicitar Plano</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="profile-section">
                        <h3>O Meu Histórico de Consultas / Planos</h3>
                        <p class="section-description">Acompanhe as suas solicitações de planos de nutrição:</p>
                        <?php if (empty($myNutritionRequests)): ?>
                            <p style="color:#94a3b8; font-style:italic;">Ainda não solicitou nenhum plano de nutrição.</p>
                        <?php else: ?>
                            <div class="class-list">
                                <?php foreach ($myNutritionRequests as $req):
                                    $status_styles = [
                                        'completed' => [
                                            'border' => '#10b981',
                                            'bg' => 'rgba(16, 185, 129, 0.15)',
                                            'color' => '#10b981',
                                            'text' => 'Concluído'
                                        ],
                                        'accepted' => [
                                            'border' => '#3b82f6',
                                            'bg' => 'rgba(59, 130, 246, 0.15)',
                                            'color' => '#60a5fa',
                                            'text' => 'Agendado'
                                        ],
                                        'pending' => [
                                            'border' => '#f59e0b',
                                            'bg' => 'rgba(245, 158, 11, 0.15)',
                                            'color' => '#f59e0b',
                                            'text' => 'Pendente'
                                        ]
                                    ];
                                    $style = $status_styles[$req['status']] ?? $status_styles['pending'];
                                ?>
                                    <article class="class-item" style="border-left: 4px solid <?= $style['border'] ?>;">
                                        <div class="class-header">
                                            <h4 class="class-title">Plano de Nutrição</h4>
                                            <span class="class-type" style="background: <?= $style['bg'] ?>; color: <?= $style['color'] ?>; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600; text-transform: uppercase;"><?= $style['text'] ?></span>
                                        </div>
                                        <div class="class-details">
                                            <p class="class-info">
                                                <strong>Nutricionista:</strong> <?= h($req['trainer_name']) ?> (<?= h($req['trainer_email']) ?>)
                                            </p>
                                            <p class="class-info">
                                                <strong>Data de Pedido:</strong> <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                            </p>
                                            <?php if ($req['status'] === 'accepted' && !empty($req['scheduled_date'])): ?>
                                                <p class="class-info">
                                                    <strong>Data Agendada:</strong> <span style="color: #60a5fa; font-weight: bold;"><?= date('d/m/Y \à\s H:i', strtotime($req['scheduled_date'])) ?></span>
                                                </p>
                                            <?php elseif ($req['status'] === 'completed' && !empty($req['scheduled_date'])): ?>
                                                <p class="class-info">
                                                    <strong>Consulta realizada em:</strong> <?= date('d/m/Y H:i', strtotime($req['scheduled_date'])) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($req['status'] === 'completed'): ?>
                                            <div style="margin-top: 10px;">
                                                <button type="button" class="btn btn-small btn-view-diet" data-plan="<?= h($req['plan_details'] ?? '') ?>" data-trainer="<?= h($req['trainer_name']) ?>" style="background: #10b981; color: white; cursor: pointer;">Ver Plano Alimentar</button>
                                            </div>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<?php 
require_once BASE_PATH . '/templates/profile/shared_modals.php';
$extra_js = ['js/profile.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
