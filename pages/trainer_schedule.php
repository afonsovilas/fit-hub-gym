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

try {
    $db = get_db();

    // Fetch all classes assigned to this trainer, ordered by date and time
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
        WHERE instructor_id = ?
        ORDER BY scheduled_at ASC
    ");
    $stmtClasses->execute([$trainer_id]);
    $classes = $stmtClasses->fetchAll();

    // Group classes by day of week
    // Keys: 0=Monday, 1=Tuesday, ..., 5=Saturday
    // Monday=1 in PHP date('w'), so we need to convert
    $classes_by_day = array_fill(0, 6, []);
    
    foreach ($classes as $class) {
        $timestamp = strtotime($class['scheduled_at']);
        // Convert PHP's w format (0=Sunday) to our format (0=Monday)
        $php_day = (int) date('w', $timestamp);
        $our_day = ($php_day - 1 + 7) % 7; // Convert: Sun=0 to Mon=0
        
        if ($our_day < 6) { // Only include Mon-Sat (0-5)
            $classes_by_day[$our_day][] = $class;
        }
    }
    
    // Sort each day's classes by time
    foreach ($classes_by_day as &$day_classes) {
        usort($day_classes, function($a, $b) {
            $time_a = strtotime($a['scheduled_at']);
            $time_b = strtotime($b['scheduled_at']);
            return $time_a - $time_b;
        });
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
    error_log('[TRAINER SCHEDULE ERROR] ' . $error);
}
?>

<main>
    <!-- Page Header -->
    <section class="page-header page-header--trainer">
        <div class="container">
            <h1>Meu Horário de Aulas</h1>
            <p class="page-subtitle">Gerencie suas aulas e visualize o roster de alunos inscritos</p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert--error" role="alert">
                <p>Erro ao carregar o horário: <?= h($error) ?></p>
            </div>
        <?php elseif (empty($classes)): ?>
            <div class="alert alert--info" role="alert">
                <p>Você não tem aulas atribuídas no momento.</p>
            </div>
        <?php else: ?>
            <section class="trainer-schedule-section">
                <div class="schedule-summary">
                    <div class="summary-card">
                        <span class="summary-number"><?= count($classes) ?></span>
                        <span class="summary-label">Aulas na Semana</span>
                    </div>
                </div>

                <!-- Weekly Calendar Grid -->
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

                        <!-- Classes for each day -->
                        <?php foreach ($classes_by_day as $day_index => $day_classes): ?>
                            <div class="day-column" data-day="<?= $day_index ?>">
                                <?php if (empty($day_classes)): ?>
                                    <div class="no-classes">
                                        <p>Sem aulas</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($day_classes as $class):
                                        $class_id = (int) $class['id'];
                                        $class_name = h($class['name']);
                                        $class_description = h($class['description'] ?? '');
                                        $class_room = h($class['room']);
                                        $class_capacity = (int) $class['capacity'];
                                        $class_duration = (int) $class['duration_min'];
                                        
                                        $timestamp = strtotime($class['scheduled_at']);
                                        $class_time = date('H:i', $timestamp);
                                        $class_date_full = date('d/m/Y', $timestamp);
                                        
                                        // Assign color class
                                        $color_class = $color_classes[$color_index % count($color_classes)];
                                        $color_index++;
                                        
                                        // Fetch member count for this class
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
                                    ?>
                                        <article class="class-block <?= $color_class ?>" id="class-<?= $class_id ?>">
                                            <div class="class-block-header">
                                                <span class="class-time"><?= $class_time ?></span>
                                                <span class="class-duration"><?= $class_duration ?>m</span>
                                            </div>
                                            
                                            <div class="class-block-content">
                                                <h4 class="class-block-title"><?= $class_name ?></h4>
                                                <p class="class-block-room">🏠 <?= $class_room ?></p>
                                                <p class="class-block-capacity">
                                                    <span class="capacity-badge"><?= $member_count ?>/<?= $class_capacity ?> alunos</span>
                                                </p>
                                            </div>

                                            <button 
                                                type="button" 
                                                class="btn btn-small btn-roster"
                                                data-class-id="<?= $class_id ?>"
                                                data-class-name="<?= $class_name ?>"
                                                aria-label="Ver roster de alunos para <?= $class_name ?>"
                                            >
                                                👥 Alunos
                                            </button>

                                            <!-- Roster Container (hidden by default) -->
                                            <div class="roster-container" id="roster-<?= $class_id ?>" style="display: none;">
                                                <div class="roster-loading">
                                                    <span class="spinner"></span>
                                                    <p>Carregando...</p>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<!-- Roster Modal (for larger rosters) -->
<div class="roster-modal" id="roster-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeRosterModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Roster de Alunos</h2>
            <button type="button" class="modal-close" onclick="closeRosterModal()" aria-label="Fechar modal">&times;</button>
        </div>
        <div class="modal-body" id="modal-roster-content">
            <!-- Roster content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
    /**
     * Trainer Schedule AJAX Script
     * Handles fetching and displaying member roster for each class
     */
    document.addEventListener('DOMContentLoaded', function() {
        const rosterButtons = document.querySelectorAll('.btn-roster');

        rosterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const classId = this.dataset.classId;
                const className = this.dataset.className;
                const rosterId = 'roster-' + classId;
                const rosterContainer = document.getElementById(rosterId);

                if (!rosterContainer) return;

                // Check if already loaded
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

                // Show loading state
                rosterContainer.style.display = '';

                // Fetch roster data via AJAX
                fetchRoster(classId, className, rosterId);
            });
        });
    });

    /**
     * Fetch roster data from API endpoint
     */
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

    /**
     * Display roster data in the container
     */
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

        // Build roster HTML
        let rosterHTML = '<div class="roster-list">';
        rosterHTML += `<h4 class="roster-title">${escapeHtml(className)} - ${members.length} aluno${members.length !== 1 ? 's' : ''}</h4>`;
        rosterHTML += '<ul class="roster-members">';

        members.forEach(member => {
            const memberName = escapeHtml(member.name);
            const memberAvatar = escapeHtml(member.avatar || '../assets/images/profile-placeholder.png');
            const memberEmail = escapeHtml(member.email);
            
            rosterHTML += `
                <li class="roster-member">
                    <img src="${memberAvatar}" alt="Foto de ${memberName}" class="member-avatar" onerror="this.src='../assets/images/profile-placeholder.png'">
                    <div class="member-info">
                        <p class="member-name">${memberName}</p>
                        <p class="member-email">${memberEmail}</p>
                    </div>
                </li>
            `;
        });

        rosterHTML += '</ul></div>';
        rosterContainer.innerHTML = rosterHTML;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Close roster modal
     */
    function closeRosterModal() {
        const modal = document.getElementById('roster-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * Close modal when clicking overlay
     */
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeRosterModal();
        }
    });
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>
