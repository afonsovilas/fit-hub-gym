<?php
/**
 * templates/profile/profile_trainer.php
 *
 * Página de perfil com visualizações e abas exclusivas para Treinadores (Trainers).
 */

declare(strict_types=1);

// Ensure user is trainer
if ($user['role'] !== 'trainer') {
    redirect('/pages/profile.php');
}

// Queries
// 1. Fetch trainer profile info
$trainer_bio = '';
$trainer_certs = '';
$stmtTp = $db->prepare("SELECT bio, certifications FROM trainer_profiles WHERE user_id = ?");
$stmtTp->execute([$user_id]);
$tp = $stmtTp->fetch();
if ($tp) {
    $trainer_bio = $tp['bio'] ?? '';
    $trainer_certs = $tp['certifications'] ?? '';
}

// 2. Fetch specializations
$mySpecs = [];
$stmtSpecs = $db->prepare("SELECT specialization FROM trainer_specializations WHERE trainer_id = ?");
$stmtSpecs->execute([$user_id]);
$specsRaw = $stmtSpecs->fetchAll();
foreach ($specsRaw as $spec) {
    $mySpecs[] = $spec['specialization'];
}
$isNutritionTrainer = in_array('nutricao', $mySpecs);

// 3. Fetch trainer's accepted and completed nutrition requests
$trainerNutritionRequests = [];
if ($isNutritionTrainer) {
    $stmtTrNut = $db->prepare("
        SELECT nr.*, u.name AS member_name, u.email AS member_email
        FROM nutrition_requests nr
        JOIN users u ON nr.member_id = u.id
        WHERE nr.trainer_id = ? AND nr.status IN ('accepted', 'completed')
        ORDER BY 
            CASE nr.status
                WHEN 'accepted' THEN 1
                WHEN 'completed' THEN 2
            END ASC,
            nr.created_at DESC
    ");
    $stmtTrNut->execute([$user_id]);
    $trainerNutritionRequests = $stmtTrNut->fetchAll();
}

// 4. Fetch group classes where the trainer might be registered as a client
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

// 5. Fetch waitlisted classes
$stmtMyWaitlists = $db->prepare("
    SELECT c.*, u.name AS instructor_name 
    FROM class_waitlists cw
    JOIN classes c ON cw.class_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE cw.user_id = ? AND c.scheduled_at >= ?
    ORDER BY c.scheduled_at ASC
");
$stmtMyWaitlists->execute([$user_id, date('Y-m-d H:i')]);
$myWaitlists = $stmtMyWaitlists->fetchAll();

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
            <p>Gerencie suas informações profissionais e consulte o feedback das suas aulas</p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>

        <div class="profile-wrapper">
            <?php require BASE_PATH . '/templates/profile/shared_sidebar.php'; ?>

            <section class="profile-main">
                <!-- Tabs Navigation Bar for Trainers -->
                <div class="profile-tabs-nav" style="display: flex; gap: 8px; border-bottom: 2px solid #334155; padding-bottom: 0px; margin-bottom: 2rem; overflow-x: auto; scrollbar-width: none;">
                    <button type="button" class="tab-btn active" data-tab="dados-pessoais" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Dados Pessoais
                    </button>
                    <button type="button" class="tab-btn" data-tab="dados-profissionais" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Dados Profissionais
                    </button>
                    <button type="button" class="tab-btn" data-tab="aulas-grupo" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Aulas de Grupo
                    </button>
                    <button type="button" class="tab-btn" data-tab="avaliacoes-aulas" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></polygon></svg>
                        Avaliações das Aulas
                    </button>
                    <?php if ($isNutritionTrainer): ?>
                    <button type="button" class="tab-btn" data-tab="nutricao" style="background: none; border: none; padding: 12px 20px; color: #94a3b8; font-weight: 600; font-size: 0.95rem; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 3px solid transparent; transition: all 0.2s; white-space: nowrap;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c5.522 0 10 4.477 10 10s-4.478 10-10 10S2 17.523 2 12 6.478 2 12 2zm0 2C7.589 4 4 7.589 4 12s3.589 8 8 8 8-3.589 8-8-3.589-8-8-8zm-1 3h2v6h-2V7zm0 8h2v2h-2v-2z"/></svg>
                        Nutrição
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Tab: Dados Pessoais -->
                <?php require BASE_PATH . '/templates/profile/shared_personal_data.php'; ?>

                <!-- Tab: Dados Profissionais -->
                <form class="profile-form" id="profile-form-professional" method="POST" action="../actions/update_profile_professional.php">
                    <?= get_csrf_input() ?>
                    <div id="pane-dados-profissionais" class="tab-pane">
                        <div class="profile-section">
                            <div class="profile-section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                <h3 style="color: var(--color-text); font-size: 1.5rem; margin: 0;">Informações de Treinador</h3>
                                <button type="button" class="btn btn-primary btn-small edit-profile-btn" id="edit-profile-btn-professional">Editar</button>
                            </div>
                            
                            <div class="trainer-extra-info" style="display: flex; flex-direction: column; gap: 15px;">
                                <div class="form-group">
                                    <label for="bio">Biografia / Apresentação:</label>
                                    <textarea id="bio" name="bio" rows="4" placeholder="Escreva uma breve apresentação profissional..." disabled style="resize: vertical; width: 100%;"><?= h($trainer_bio) ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="certifications">Certificações e Formação (uma por linha):</label>
                                    <textarea id="certifications" name="certifications" rows="3" placeholder="Ex: Licenciatura em Ciências do Desporto, Certificação Pilates..." disabled style="resize: vertical; width: 100%;"><?= h($trainer_certs) ?></textarea>
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
                                            'personal' => 'Treino Personalizado',
                                            'nutricao' => 'Nutrição'
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
                            <div class="form-actions" style="margin-top: 1.5rem;">
                                <button type="submit" class="btn btn-primary" id="save-btn-professional" style="display: none;">Guardar Alterações</button>
                                <button type="button" class="btn btn-secondary btn-small" id="cancel-btn-professional" style="display: none;">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </form>

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

                <!-- Tab: Avaliações das Aulas -->
                <div id="pane-avaliacoes-aulas" class="tab-pane">
                    <div class="profile-section">
                        <h3>Avaliações das Minhas Aulas</h3>
                        <p class="section-description">Consulte o feedback e as classificações deixadas pelos membros nas aulas que lecionou:</p>
                        <div id="trainer-reviews-container" data-trainer-id="<?= h($user['id']) ?>" style="margin-top: 20px;"></div>
                    </div>
                </div>

                <!-- Tab: Nutrição -->
                <?php if ($isNutritionTrainer): ?>
                    <div id="pane-nutricao" class="tab-pane">
                        <div class="profile-section">
                            <h3>Gestão de Pedidos de Nutrição</h3>
                            <p class="section-description">Consulte e responda aos pedidos de planos alimentares efetuados pelos membros:</p>
                            <?php if (empty($trainerNutritionRequests)): ?>
                                <p style="color:#94a3b8; font-style:italic;">Ainda não recebeu nenhuma solicitação de plano de nutrição.</p>
                            <?php else: ?>
                                <div class="class-list">
                                    <?php foreach ($trainerNutritionRequests as $req):
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
                                                <h4 class="class-title">Consulta de Nutrição</h4>
                                                <span class="class-type" style="background: <?= $style['bg'] ?>; color: <?= $style['color'] ?>; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: 600; text-transform: uppercase;"><?= $style['text'] ?></span>
                                            </div>
                                            <div class="class-details">
                                                <p class="class-info">
                                                    <strong>Membro:</strong> <?= h($req['member_name']) ?> (<?= h($req['member_email']) ?>)
                                                </p>
                                                <p class="class-info">
                                                    <strong>Data de Pedido:</strong> <?= date('d/m/Y H:i', strtotime($req['created_at'])) ?>
                                                </p>
                                                <?php if ($req['status'] === 'accepted' && !empty($req['scheduled_date'])): ?>
                                                    <p class="class-info">
                                                        <strong>Agendado para:</strong> <span style="color: #60a5fa; font-weight: bold;"><?= date('d/m/Y \à\s H:i', strtotime($req['scheduled_date'])) ?></span>
                                                    </p>
                                                <?php elseif ($req['status'] === 'completed' && !empty($req['scheduled_date'])): ?>
                                                    <p class="class-info">
                                                        <strong>Consulta realizada em:</strong> <?= date('d/m/Y H:i', strtotime($req['scheduled_date'])) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($req['status'] === 'completed'): ?>
                                                <div style="margin-top: 10px;">
                                                    <button type="button" class="btn btn-small btn-view-diet" data-plan="<?= h($req['plan_details'] ?? '') ?>" data-trainer="<?= h($req['member_name']) ?>" style="background: #10b981; color: white; cursor: pointer;">Ver Plano Prescrito</button>
                                                </div>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<?php 
require_once BASE_PATH . '/templates/profile/shared_modals.php';
$extra_js = ['js/profile.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
