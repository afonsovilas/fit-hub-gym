<?php
/**
 * pages/trainer_schedule.php
 *
 * Página do horário de aulas do treinador.
 * Mostra todas as aulas atribuídas e permite visualizar o roster de alunos.
 * Integrada com a base de dados SQLite (FitHub) e AJAX.
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Meu Horário';
$body_class  = 'trainer-schedule-page';
$active_page = 'trainer';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';
require_once BASE_PATH . '/database/connection.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    set_flash('Inicie sessão para aceder ao seu horário.', 'error');
    redirect('/pages/login.php');
}

// Verificar se é treinador
if ($_SESSION['user_role'] !== 'trainer') {
    set_flash('Apenas treinadores podem aceder a esta página.', 'error');
    redirect('/index.php');
}

$trainer_id = (int) $_SESSION['user_id'];
$classes = [];
$classes_by_day = [];
$error = null;

// Calcular os limites da semana atual (Segunda a Sábado) de forma robusta
$today = time();
$dayOfWeek = (int) date('w', $today);
$daysToSubtract = ($dayOfWeek === 0) ? 6 : ($dayOfWeek - 1);
$mondayTimestamp = $today - ($daysToSubtract * 86400);

$startOfWeek = date('Y-m-d 00:00:00', $mondayTimestamp);
$saturdayTimestamp = $mondayTimestamp + (5 * 86400);
$endOfWeek = date('Y-m-d 23:59:59', $saturdayTimestamp);

$formatted_start = date('d/m/Y', $mondayTimestamp);
$formatted_end = date('d/m/Y', $saturdayTimestamp);

try {
    $db = get_db();

    $stmtClasses = $db->prepare("
        SELECT 
            id,
            name,
            description,
            scheduled_at,
            duration_min,
            room,
            capacity
        FROM classes
        WHERE instructor_id = ? AND scheduled_at >= ? AND scheduled_at <= ?
        ORDER BY scheduled_at ASC
    ");
    $stmtClasses->execute([$trainer_id, $startOfWeek, $endOfWeek]);
    $classes = $stmtClasses->fetchAll();

    // Query Personal Training Bookings
    $stmtPT = $db->prepare("
        SELECT 
            ptb.id,
            'Sessão Personal Training' AS name,
            ptb.notes AS description,
            ptb.scheduled_at,
            60 AS duration_min,
            'Zona PT' AS room,
            1 AS capacity,
            u.name AS member_name,
            u.email AS member_email,
            'pt' AS type
        FROM personal_training_bookings ptb
        JOIN users u ON ptb.member_id = u.id
        WHERE ptb.trainer_id = ? AND ptb.status = 'confirmed' AND ptb.scheduled_at >= ? AND ptb.scheduled_at <= ?
        ORDER BY ptb.scheduled_at ASC
    ");
    $stmtPT->execute([$trainer_id, $startOfWeek, $endOfWeek]);
    $ptBookings = $stmtPT->fetchAll();

    // Merge both group classes and PT sessions
    $all_sessions = array_merge($classes, $ptBookings);

    // Group sessions by day of week
    // Keys: 0=Monday, 1=Tuesday, ..., 5=Saturday
    // Monday=1 in PHP date('w'), so we need to convert
    $classes_by_day = array_fill(0, 6, []);
    
    foreach ($all_sessions as $session) {
        $timestamp = strtotime($session['scheduled_at']);
        // Convert PHP's w format (0=Sunday) to our format (0=Monday)
        $php_day = (int) date('w', $timestamp);
        $our_day = ($php_day - 1 + 7) % 7; // Convert: Sun=0 to Mon=0
        
        if ($our_day < 6) { // Only include Mon-Sat (0-5)
            $classes_by_day[$our_day][] = $session;
        }
    }
    
    // Sort each day's sessions by time
    foreach ($classes_by_day as &$day_classes) {
        usort($day_classes, function($a, $b) {
            $time_a = strtotime($a['scheduled_at']);
            $time_b = strtotime($b['scheduled_at']);
            return $time_a - $time_b;
        });
    }

    // Fetch pending PT sessions
    $stmtPendingPt = $db->prepare("
        SELECT ptb.*, u.name AS member_name, u.email AS member_email, u.avatar AS member_avatar
        FROM personal_training_bookings ptb
        JOIN users u ON ptb.member_id = u.id
        WHERE ptb.trainer_id = ? AND ptb.status = 'pending' AND ptb.scheduled_at >= ?
        ORDER BY ptb.scheduled_at ASC
    ");
    $stmtPendingPt->execute([$trainer_id, date('Y-m-d H:i')]);
    $pendingPts = $stmtPendingPt->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
    error_log('[TRAINER SCHEDULE ERROR] ' . $error);
}
?>

<main>
    <section class="page-header page-header--trainer">
        <div class="container">
            <h1>Meu Horário de Aulas</h1>
            <p class="page-subtitle">Semana de <strong><?= $formatted_start ?></strong> a <strong><?= $formatted_end ?></strong></p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>



        <?php if (!empty($error)): ?>
            <div class="alert alert--error" role="alert">
                <p>Erro ao carregar o horário: <?= h($error) ?></p>
            </div>
        <?php elseif (empty($all_sessions)): ?>
            <div class="alert alert--info" role="alert">
                <p>Você não tem aulas ou sessões de treino agendadas no momento.</p>
            </div>
        <?php else: ?>
            <section class="trainer-schedule-section">
                <div class="schedule-summary">
                    <div class="summary-card">
                        <span class="summary-number"><?= count($all_sessions) ?></span>
                        <span class="summary-label">Aulas / Sessões na Semana</span>
                    </div>
                </div>

                <div class="weekly-schedule-wrapper">
                    <div class="weekly-schedule-grid">
                        <?php 
                        $dias_semana_pt = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                        $color_classes = ['color-1', 'color-2', 'color-3', 'color-4', 'color-5'];
                        $color_index = 0;
                        
                        // Day headers
                        foreach ($dias_semana_pt as $index => $dia_nome): 
                        ?>
                            <div class="day-header">
                                <h3><?= $dia_nome ?></h3>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($classes_by_day as $day_index => $day_classes): ?>
                            <div class="day-column" data-day="<?= $day_index ?>">
                                <?php if (empty($day_classes)): ?>
                                    <div class="no-classes">
                                        <p>Sem aulas</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($day_classes as $class):
                                        $is_pt = isset($class['type']) && $class['type'] === 'pt';
                                        $class_id = (int) $class['id'];
                                        $class_name = h($class['name']);
                                        $class_description = h($class['description'] ?? '');
                                        $class_room = h($class['room']);
                                        $class_capacity = (int) $class['capacity'];
                                        $class_duration = (int) $class['duration_min'];
                                        
                                        $timestamp = strtotime($class['scheduled_at']);
                                        $class_time = date('H:i', $timestamp);
                                        $class_date_full = date('d/m/Y', $timestamp);
                                        
                                        $color_class = $is_pt ? 'color-pt' : $color_classes[$color_index % count($color_classes)];
                                        if (!$is_pt) $color_index++;
                                        
                                        $member_count = 0;
                                        if (!$is_pt) {
                                            try {
                                                $stmtCount = $db->prepare("
                                                    SELECT COUNT(*) as total FROM bookings WHERE class_id = ?
                                                ");
                                                $stmtCount->execute([$class_id]);
                                                $countResult = $stmtCount->fetch();
                                                $member_count = $countResult['total'] ?? 0;
                                            } catch (Throwable $e) {
                                                $member_count = 0;
                                            }
                                        }
                                    ?>
                                        <article class="class-block <?= $color_class ?>" id="<?= $is_pt ? 'pt-' : 'class-' ?><?= $class_id ?>" style="<?= $is_pt ? 'border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.08);' : '' ?>">
                                            <div class="class-block-header">
                                                <span class="class-time"><?= $class_time ?></span>
                                                <span class="class-duration"><?= $class_duration ?>m</span>
                                            </div>
                                            
                                            <div class="class-block-content">
                                                <h4 class="class-block-title"><?= $class_name ?></h4>
                                                <?php if ($is_pt): ?>
                                                    <p class="class-block-room" style="margin-bottom: 2px;">👤 <strong>Membro:</strong> <?= h($class['member_name']) ?></p>
                                                    <p class="class-block-room" style="font-size: 0.85em; opacity: 0.8;">✉️ <?= h($class['member_email']) ?></p>
                                                    <?php if (!empty($class_description)): ?>
                                                        <p class="class-block-room" style="font-style: italic; color: #94a3b8; font-size: 0.85em; margin-top: 5px; overflow-wrap: break-word; word-break: break-word;">"<?= $class_description ?>"</p>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <p class="class-block-room">🏠 <?= $class_room ?></p>
                                                    <p class="class-block-capacity">
                                                        <span class="capacity-badge"><?= $member_count ?>/<?= $class_capacity ?> alunos</span>
                                                    </p>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!$is_pt): ?>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-small btn-roster"
                                                    data-class-id="<?= $class_id ?>"
                                                    data-class-name="<?= $class_name ?>"
                                                    aria-label="Ver roster de alunos para <?= $class_name ?>"
                                                >
                                                    👥 Alunos
                                                </button>
                                                
                                                <div class="roster-container" id="roster-<?= $class_id ?>" style="display: none;">
                                                    <div class="roster-loading">
                                                        <span class="spinner"></span>
                                                        <p>Carregando...</p>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 0.8em; padding: 2px 8px; border-radius: 4px; font-weight: bold; display: inline-block; margin-top: 8px; text-transform: uppercase;">PT Confirmado</span>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($pendingPts)): ?>
            <section class="pending-requests-section" style="margin-top: 3.5rem; padding-top: 2rem; border-top: 2px dashed var(--color-border); margin-bottom: 2rem;">
                <div class="section-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-size: 1.6rem; color: var(--color-text); margin: 0; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 1.8rem; animation: pulse 2s infinite; display: inline-block;">⏳</span> 
                            Solicitações de Treino Personalizado
                        </h2>
                        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-top: 4px;">Tem <?= count($pendingPts) ?> agendamento<?= count($pendingPts) > 1 ? 's' : '' ?> individual<?= count($pendingPts) > 1 ? 's' : '' ?> a aguardar a sua confirmação:</p>
                    </div>
                    <span class="badge-pending-count" style="background: rgba(207, 90, 43, 0.1); color: var(--color-primary); padding: 6px 16px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; border: 1px solid rgba(207, 90, 43, 0.2);">
                        <?= count($pendingPts) ?> pendente<?= count($pendingPts) > 1 ? 's' : '' ?>
                    </span>
                </div>

                <div class="pending-requests-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px;">
                    <?php foreach ($pendingPts as $pt): 
                        $ptTimestamp = strtotime($pt['scheduled_at']);
                        $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                        $ptDiaExtenso = $dias_semana[date('w', $ptTimestamp)];
                        
                        $rawAvatar = $pt['member_avatar'] ? (strpos($pt['member_avatar'], 'http') === 0 || strpos($pt['member_avatar'], '../') === 0 ? $pt['member_avatar'] : '../' . $pt['member_avatar']) : '../assets/images/avatars/profile-placeholder.png';
                    ?>
                        <article class="pending-request-card" style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 22px; border-radius: 16px; display: flex; flex-direction: column; gap: 16px; box-shadow: var(--shadow-soft); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left: 5px solid var(--color-primary); position: relative; overflow: hidden;">
                            <div style="display: flex; gap: 14px; align-items: center;">
                                <div style="position: relative;">
                                    <img src="<?= h($rawAvatar) ?>" alt="Foto de <?= h($pt['member_name']) ?>" style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(207, 90, 43, 0.15); box-shadow: 0 4px 10px rgba(0,0,0,0.05);" onerror="this.src='../assets/images/avatars/profile-placeholder.png';">
                                    <span style="position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; background: #eab308; border: 2px solid var(--color-surface); border-radius: 50%;" title="Pendente"></span>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h4 style="margin: 0; color: var(--color-text); font-size: 1.15rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($pt['member_name']) ?></h4>
                                    <span style="font-size: 0.85em; color: var(--color-text-muted); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($pt['member_email']) ?></span>
                                </div>
                            </div>

                            <div style="background: var(--color-surface-soft); padding: 14px; border-radius: 12px; border: 1px solid rgba(0, 0, 0, 0.03); display: flex; flex-direction: column; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px; color: var(--color-text);">
                                    <span style="font-size: 1.1rem; line-height: 1;">📅</span>
                                    <span style="font-size: 0.95rem; font-weight: 500;">
                                        <?= $ptDiaExtenso ?>, <strong style="color: var(--color-primary);"><?= date('d/m/Y', $ptTimestamp) ?></strong> às <strong style="color: var(--color-primary);"><?= date('H:i', $ptTimestamp) ?></strong>
                                    </span>
                                </div>
                                
                                <?php if (!empty($pt['notes'])): ?>
                                    <div style="margin-top: 6px; padding-top: 8px; border-top: 1px solid var(--color-border);">
                                        <span style="color: var(--color-text-muted); font-size: 0.8em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Mensagem do Aluno:</span>
                                        <p style="margin: 4px 0 0 0; color: var(--color-text); font-style: italic; font-size: 0.92rem; line-height: 1.5; background: rgba(207, 90, 43, 0.03); padding: 8px 12px; border-radius: 8px; border-left: 3px solid rgba(207, 90, 43, 0.3);">
                                            "<?= h($pt['notes']) ?>"
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: auto;">
                                <form method="POST" action="../actions/accept_personal_training.php" style="margin: 0; padding: 0; display: inline; flex: 1;">
                                    <?= get_csrf_input() ?>
                                    <input type="hidden" name="booking_id" value="<?= $pt['id'] ?>">
                                    <button type="submit" class="btn-accept-pt" style="width: 100%; background: #10b981; color: #fff; border: none; cursor: pointer; padding: 10px 16px; font-weight: 600; border-radius: 8px; font-size: 0.95rem; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                                        <span>✓</span> Confirmar
                                    </button>
                                </form>

                                <div style="display: inline; flex: 1;">
                                    <button type="button" class="btn-reject-pt" onclick="openRejectModal(<?= $pt['id'] ?>)" style="width: 100%; background: rgba(239, 68, 68, 0.08); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); cursor: pointer; padding: 10px 16px; font-weight: 600; border-radius: 8px; font-size: 0.95rem; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <span>✗</span> Recusar
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <style>
                .pending-request-card:hover {
                    transform: translateY(-5px);
                    box-shadow: var(--shadow-card);
                    border-color: var(--color-primary);
                }
                .btn-accept-pt:hover {
                    background-color: #059669 !important;
                    transform: translateY(-1px);
                    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3) !important;
                }
                .btn-reject-pt:hover {
                    background-color: rgba(239, 68, 68, 0.15) !important;
                    border-color: rgba(239, 68, 68, 0.4) !important;
                    color: #dc2626 !important;
                    transform: translateY(-1px);
                }
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.08); }
                }
            </style>
        <?php endif; ?>
    </div>
</main>

<div class="roster-modal" id="roster-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeRosterModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Roster de Alunos</h2>
            <button type="button" class="modal-close" onclick="closeRosterModal()" aria-label="Fechar modal">&times;</button>
        </div>
        <div class="modal-body" id="modal-roster-content">
        </div>
    </div>
</div>

<script>

    document.addEventListener('DOMContentLoaded', function() {
        const rosterButtons = document.querySelectorAll('.btn-roster');

        rosterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.dataset.classId;
                const className = this.dataset.className;
                const rosterId = 'roster-' + classId;
                const rosterContainer = document.getElementById(rosterId);

                if (!rosterContainer) return;

                const existingRoster = rosterContainer.querySelector('.roster-list');
                if (existingRoster) {
                    // Toggle visibility
                    if (rosterContainer.style.display === 'none') {
                        rosterContainer.style.display = '';
                    } else {
                        rosterContainer.style.display = 'none';
                    }
                    return;
                }

                rosterContainer.style.display = '';

                fetchRoster(classId, className, rosterId);
            });
        });
    });

    function fetchRoster(classId, className, rosterId) {
        const rosterContainer = document.getElementById(rosterId);

        fetch('<?= BASE_URL_PATH ?>api/api_get_roster.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'class_id=' + encodeURIComponent(classId)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na resposta do servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            displayRoster(data, className, rosterContainer);
        })
        .catch(error => {
            console.error('Erro ao carregar roster:', error);
            rosterContainer.innerHTML = `
                <div class="roster-error">
                    <p>Erro ao carregar o roster. Por favor tente novamente.</p>
                </div>
            `;
        });
    }

    function displayRoster(data, className, rosterContainer) {
        if (!data.success) {
            rosterContainer.innerHTML = `
                <div class="roster-error">
                    <p>${data.message || 'Erro ao carregar o roster.'}</p>
                </div>
            `;
            return;
        }

        const members = data.members || [];

        if (members.length === 0) {
            rosterContainer.innerHTML = `
                <div class="roster-empty">
                    <p>Nenhum aluno inscrito nesta aula.</p>
                </div>
            `;
            return;
        }

        let rosterHTML = '<div class="roster-list">';
        rosterHTML += `<h4 class="roster-title">${escapeHtml(className)} - ${members.length} aluno${members.length !== 1 ? 's' : ''}</h4>`;
        rosterHTML += '<ul class="roster-members">';

        members.forEach(member => {
            const memberName = escapeHtml(member.name);
            const rawAvatar = member.avatar ? (member.avatar.startsWith('http') || member.avatar.startsWith('../') ? member.avatar : '../' + member.avatar) : '../assets/images/avatars/profile-placeholder.png';
            const memberAvatar = escapeHtml(rawAvatar);
            const memberEmail = escapeHtml(member.email);
            const memberRole = escapeHtml(member.role || 'member');
            
            let roleBadge = '';
            if (memberRole !== 'member') {
                const roleLabels = {
                    'trainer': 'Treinador',
                    'staff': 'Staff',
                    'admin': 'Admin'
                };
                const label = roleLabels[memberRole] || memberRole;
                
                let badgeStyle = 'background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3);';
                if (memberRole === 'trainer') {
                    badgeStyle = 'background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);';
                } else if (memberRole === 'staff') {
                    badgeStyle = 'background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3);';
                } else if (memberRole === 'admin') {
                    badgeStyle = 'background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);';
                }
                
                roleBadge = `<span class="badge" style="font-size:0.75em; padding:2px 6px; border-radius:4px; margin-left:6px; display:inline-block; font-weight:bold; vertical-align:middle; text-transform:uppercase; ${badgeStyle}">${label}</span>`;
            }
            
            rosterHTML += `
                <li class="roster-member">
                    <img src="${memberAvatar}" alt="Foto de ${memberName}" class="member-avatar" onerror="this.src='../assets/images/avatars/profile-placeholder.png'">
                    <div class="member-info">
                        <p class="member-name">${memberName}${roleBadge}</p>
                        <p class="member-email">${memberEmail}</p>
                    </div>
                </li>
            `;
        });

        rosterHTML += '</ul></div>';
        rosterContainer.innerHTML = rosterHTML;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function closeRosterModal() {
        const modal = document.getElementById('roster-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function openRejectModal(bookingId) {
        const modal = document.getElementById('reject-pt-modal');
        const inputId = document.getElementById('reject-booking-id');
        const reasonInput = document.getElementById('reject-reason-input');
        
        if (modal && inputId) {
            inputId.value = bookingId;
            reasonInput.value = '';
            modal.style.display = 'flex'; // Display as centered flex overlay
            setTimeout(() => reasonInput.focus(), 50);
        }
    }

    function closeRejectModal() {
        const modal = document.getElementById('reject-pt-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeRosterModal();
            closeRejectModal();
        }
    });

    // Close reject modal when clicking on the overlay backdrop
    document.addEventListener('DOMContentLoaded', function() {
        const rejectModal = document.getElementById('reject-pt-modal');
        if (rejectModal) {
            rejectModal.addEventListener('click', function(event) {
                if (event.target === rejectModal) {
                    closeRejectModal();
                }
            });
        }
    });
</script>

<!-- Custom Modal for Rejecting PT Requests -->
<div id="reject-pt-modal" class="modal" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
    <div style="background-color: var(--color-surface); border: 1px solid var(--color-border); border-radius: 16px; max-width: 480px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.4); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh; animation: modalFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
        <div style="padding: 20px; border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; background: var(--color-surface-soft);">
            <h3 id="reject-modal-title" style="margin: 0; color: #ef4444; font-size: 1.25rem; font-weight: 700; display: flex; align-items: center; gap: 8px; font-family: var(--font-heading);">
                <span>✗</span> Recusar Solicitação
            </h3>
            <button type="button" onclick="closeRejectModal()" style="background: none; border: none; color: var(--color-text-muted); font-size: 1.8rem; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='var(--color-text-muted)'">&times;</button>
        </div>
        <div style="padding: 20px; overflow-y: auto;">
            <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 1.2rem; line-height: 1.5; margin-top: 0;">
                Por favor, indique a justificação para a recusa (ex: incompatibilidade de horário). Esta mensagem será enviada ao aluno:
            </p>
            <form id="reject-pt-form" method="POST" action="../actions/reject_personal_training.php" style="display: flex; flex-direction: column; gap: 15px; margin: 0;">
                <?= get_csrf_input() ?>
                <input type="hidden" name="booking_id" id="reject-booking-id" value="">
                
                <textarea 
                    name="rejection_reason" 
                    id="reject-reason-input" 
                    required 
                    placeholder="Escreva aqui o motivo da recusa (ex: Indiquei no calendário que não estarei disponível neste horário...)" 
                    style="width: 100%; min-height: 120px; padding: 12px; border-radius: 8px; border: 1px solid var(--color-border); background: var(--color-surface-soft); color: var(--color-text); font-family: inherit; font-size: 0.95rem; resize: vertical; outline: none; transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--color-primary)'"
                    onblur="this.style.borderColor='var(--color-border)'"
                ></textarea>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 5px;">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal()" style="padding: 10px 20px; font-weight: 600; border-radius: 8px; font-size: 0.95rem; cursor: pointer; border: 1px solid var(--color-border); background: var(--color-surface-soft); color: var(--color-text);">
                        Cancelar
                    </button>
                    <button type="submit" class="btn" style="background: #ef4444; color: #fff; border: none; padding: 10px 20px; font-weight: 600; border-radius: 8px; font-size: 0.95rem; cursor: pointer; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25); transition: background-color 0.2s;">
                        Confirmar Recusa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>

<!-- Floating bottom-left toast indicator if there are pending requests -->
<?php if (!empty($pendingPts)): ?>
    <div id="pending-pt-floating-toast" style="position: fixed; bottom: 25px; left: 25px; background: var(--color-surface); border: 1px solid var(--color-border); border-left: 5px solid var(--color-primary); padding: 16px 20px; border-radius: 16px; box-shadow: var(--shadow-card); display: flex; align-items: center; gap: 14px; z-index: 998; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); animation: slideUpToast 0.5s cubic-bezier(0.4, 0, 0.2, 1);">
        <div style="background: rgba(207, 90, 43, 0.08); width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
            <span style="animation: pulse 2s infinite; display: inline-block;">⏳</span>
        </div>
        <div style="display: flex; flex-direction: column; gap: 2px;">
            <span style="font-weight: 700; font-size: 0.95rem; color: var(--color-text); font-family: var(--font-heading);">Pedidos Pendentes</span>
            <span style="font-size: 0.82rem; color: var(--color-text-muted);">Tem <?= count($pendingPts) ?> treino<?= count($pendingPts) > 1 ? 's' : '' ?> a aguardar resposta.</span>
        </div>
        <div style="margin-left: 8px; color: var(--color-primary); font-weight: 700; font-size: 1.1rem; transition: transform 0.2s;" class="toast-arrow">
            &rarr;
        </div>
    </div>

    <style>
        #pending-pt-floating-toast:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 35px rgba(36, 32, 29, 0.16);
            border-color: var(--color-primary);
        }
        #pending-pt-floating-toast:hover .toast-arrow {
            transform: translateX(4px);
        }
        @keyframes slideUpToast {
            from {
                transform: translateY(100px) scale(0.9);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('pending-pt-floating-toast');
            if (toast) {
                toast.addEventListener('click', function() {
                    const section = document.querySelector('.pending-requests-section');
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth' });
                        // Highlight the section header
                        const header = section.querySelector('h2');
                        if (header) {
                            header.style.transition = 'color 0.3s ease';
                            header.style.color = 'var(--color-primary)';
                            setTimeout(() => {
                                header.style.color = '';
                            }, 1500);
                        }
                    }
                });
            }
        });
    </script>
<?php endif; ?>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>
