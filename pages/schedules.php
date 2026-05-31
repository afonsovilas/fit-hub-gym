<?php
/**
 * pages/schedules.php
 *
 * Página dinâmica de horários e filtros de aulas de grupo.
 * Ligada à base de dados SQLite (FitHub).
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Horários de Aulas';
$body_class  = 'schedules-page';
$active_page = 'schedule';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';
require_once BASE_PATH . '/database/connection.php';

try {
    $db = get_db();
    
    // Obter ID do utilizador logado da sessão, ou 0 se não autenticado
    $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    
    $stmtCheckBooked = $user_id > 0 
        ? $db->prepare("SELECT 1 FROM bookings WHERE user_id = ? AND class_id = ?") 
        : null;

    $stmtCheckWaitlist = $user_id > 0 
        ? $db->prepare("SELECT 1 FROM class_waitlists WHERE user_id = ? AND class_id = ?") 
        : null;

    $filter_type        = $_GET['class-type'] ?? '';
    $filter_trainer     = $_GET['trainer'] ?? '';
    $filter_day         = $_GET['day'] ?? '';
    $filter_time        = $_GET['time'] ?? '';
    $filter_time_frame  = $_GET['time-frame'] ?? 'upcoming';

    $stmtTrainers = $db->query("SELECT id, name FROM users WHERE role = 'trainer' AND is_active = 1 ORDER BY name ASC");
    $trainersList = $stmtTrainers->fetchAll();

    $query = "
        SELECT c.*, u.name AS instructor_name 
        FROM classes c
        JOIN users u ON c.instructor_id = u.id
        WHERE u.is_active = 1
    ";
    $params = [];

    if ($filter_type !== '') {
        $query .= " AND LOWER(c.name) LIKE ?";
        $params[] = '%' . strtolower($filter_type) . '%';
    }
    if ($filter_trainer !== '') {
        $query .= " AND c.instructor_id = ?";
        $params[] = (int) $filter_trainer;
    }

    if ($filter_day !== '') {
        $day_map = [
            'monday'    => '1',
            'tuesday'   => '2',
            'wednesday' => '3',
            'thursday'  => '4',
            'friday'    => '5',
            'saturday'  => '6'
        ];
        if (isset($day_map[$filter_day])) {
            $query .= " AND strftime('%w', c.scheduled_at) = ?";
            $params[] = $day_map[$filter_day];
        }
    }

    if ($filter_time !== '') {
        if ($filter_time === 'morning') {
            $query .= " AND strftime('%H', c.scheduled_at) BETWEEN '06' AND '11'";
        } elseif ($filter_time === 'afternoon') {
            $query .= " AND strftime('%H', c.scheduled_at) BETWEEN '12' AND '17'";
        } elseif ($filter_time === 'evening') {
            $query .= " AND strftime('%H', c.scheduled_at) BETWEEN '18' AND '23'";
        }
    }

    if ($filter_time_frame === 'upcoming') {
        $query .= " AND c.scheduled_at >= ?";
        $params[] = date('Y-m-d H:i');
    } elseif ($filter_time_frame === 'past') {
        $query .= " AND c.scheduled_at < ?";
        $params[] = date('Y-m-d H:i');
    }

    $query .= " ORDER BY c.scheduled_at ASC";

    $stmtClasses = $db->prepare($query);
    $stmtClasses->execute($params);
    $classes = $stmtClasses->fetchAll();

    $stmtCount = $db->prepare("SELECT COUNT(*) AS total FROM bookings WHERE class_id = ?");
    $stmtReviews = $db->prepare("
        SELECT r.*, u.name AS user_name 
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.class_id = ?
        ORDER BY r.created_at DESC
    ");

} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<main>
    <section class="page-header page-header--schedules">
        <div class="container">
            <h1>Horários de Aulas</h1>
            <p>Encontre a aula perfeita para si no FitHub</p>
        </div>
    </section>

    <section class="filters-section">
        <div class="container">
            <h2>Filtros</h2>
            <form class="filters-form" method="GET" action="schedules.php">
                <div class="filter-group">
                    <label for="class-type">Tipo de Aula:</label>
                    <select id="class-type" name="class-type">
                        <option value="">Todos</option>
                        <option value="yoga" <?= $filter_type === 'yoga' ? 'selected' : '' ?>>Yoga</option>
                        <option value="spinning" <?= $filter_type === 'spinning' ? 'selected' : '' ?>>Spinning</option>
                        <option value="hiit" <?= $filter_type === 'hiit' ? 'selected' : '' ?>>HIIT</option>
                        <option value="pilates" <?= $filter_type === 'pilates' ? 'selected' : '' ?>>Pilates</option>
                        <option value="zumba" <?= $filter_type === 'zumba' ? 'selected' : '' ?>>Zumba</option>
                        <option value="boxe" <?= $filter_type === 'boxe' ? 'selected' : '' ?>>Boxe</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="trainer">Treinador:</label>
                    <select id="trainer" name="trainer">
                        <option value="">Qualquer Treinador</option>
                        <?php foreach ($trainersList as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $filter_trainer == $t['id'] ? 'selected' : '' ?>>
                                <?= h($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="day">Dia:</label>
                    <select id="day" name="day">
                        <option value="">Todos os Dias</option>
                        <option value="monday" <?= $filter_day === 'monday' ? 'selected' : '' ?>>Segunda</option>
                        <option value="tuesday" <?= $filter_day === 'tuesday' ? 'selected' : '' ?>>Terça</option>
                        <option value="wednesday" <?= $filter_day === 'wednesday' ? 'selected' : '' ?>>Quarta</option>
                        <option value="thursday" <?= $filter_day === 'thursday' ? 'selected' : '' ?>>Quinta</option>
                        <option value="friday" <?= $filter_day === 'friday' ? 'selected' : '' ?>>Sexta</option>
                        <option value="saturday" <?= $filter_day === 'saturday' ? 'selected' : '' ?>>Sábado</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="time">Horário:</label>
                    <select id="time" name="time">
                        <option value="">Qualquer Horário</option>
                        <option value="morning" <?= $filter_time === 'morning' ? 'selected' : '' ?>>Manhã (6:00 - 12:00)</option>
                        <option value="afternoon" <?= $filter_time === 'afternoon' ? 'selected' : '' ?>>Tarde (12:00 - 18:00)</option>
                        <option value="evening" <?= $filter_time === 'evening' ? 'selected' : '' ?>>Noite (18:00 - 23:00)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="time-frame">Período:</label>
                    <select id="time-frame" name="time-frame">
                        <option value="upcoming" <?= $filter_time_frame === 'upcoming' ? 'selected' : '' ?>>Próximas Aulas</option>
                        <option value="past" <?= $filter_time_frame === 'past' ? 'selected' : '' ?>>Aulas Passadas</option>
                        <option value="all" <?= $filter_time_frame === 'all' ? 'selected' : '' ?>>Todas as Aulas</option>
                    </select>
                </div>

                <div class="filter-actions" style="display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="btn btn-secondary" style="margin: 0; white-space: nowrap;">Aplicar Filtros</button>
                    <?php if ($filter_type || $filter_trainer || $filter_day || $filter_time || $filter_time_frame !== 'upcoming'): ?>
                        <a href="schedules.php" class="btn" style="background:#475569; color:#fff; text-decoration:none; margin: 0; white-space: nowrap;">Limpar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </section>

    <section class="schedule-section">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 15px;">
                <h2 style="margin: 0;">Aulas Disponíveis</h2>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <button type="button" class="btn btn-primary" id="btn-create-class" style="margin: 0; padding: 0.6rem 1.5rem; font-weight: 600;">➕ Criar Nova Aula</button>
                <?php endif; ?>
            </div>

            <?php render_flash(); ?>
            
            <?php if (isset($error)): ?>
                <div class="flash flash--error">Erro na base de dados: <?= h($error) ?></div>
            <?php elseif (empty($classes)): ?>
                <p style="color:#94a3b8; text-align:center; padding:40px;">Nenhuma aula encontrada com os filtros selecionados.</p>
            <?php else: ?>
                <div class="schedule-grid">
                    <?php foreach ($classes as $class): 
                        $stmtCount->execute([$class['id']]);
                        $inscritos = $stmtCount->fetch()['total'];
                        $vagas_restantes = $class['capacity'] - $inscritos;
                        $cheia = $vagas_restantes <= 0;

                        $is_booked = false;
                        if ($user_id > 0 && $stmtCheckBooked) {
                            $stmtCheckBooked->execute([$user_id, $class['id']]);
                            $is_booked = (bool) $stmtCheckBooked->fetch();
                        }

                        $is_waitlisted = false;
                        if ($user_id > 0 && $stmtCheckWaitlist) {
                            $stmtCheckWaitlist->execute([$user_id, $class['id']]);
                            $is_waitlisted = (bool) $stmtCheckWaitlist->fetch();
                        }

                        $is_own_class = ($user_id > 0 && (int) $class['instructor_id'] === $user_id);

                        $timestamp = strtotime($class['scheduled_at']);
                        $is_past = $timestamp < time();
                        $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                        $dia_extenso = $dias_semana[date('w', $timestamp)];
                        $hora_inicio = date('H:i', $timestamp);
                        $hora_fim = date('H:i', $timestamp + ($class['duration_min'] * 60));
                        $stmtReviews->execute([$class['id']]);
                        $class_reviews = $stmtReviews->fetchAll();

                        $total_rating = 0;
                        $review_count = count($class_reviews);
                        foreach ($class_reviews as $cr) {
                            $total_rating += $cr['rating'];
                        }
                        $avg_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;
                    ?>
                        <article class="class-card">
                            <div class="class-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <div>
                                    <h3><?= h($class['name']) ?></h3>
                                </div>
                                <span class="class-type" style="text-transform: capitalize;"><?= h(strtolower(str_replace(' Express', '', str_replace(' Matinal', '', str_replace(' Intenso', '', str_replace(' Clássico', '', $class['name'])))))) ?></span>
                            </div>
                            <div class="class-info">
                                <p><strong>Treinador:</strong> <?= h($class['instructor_name']) ?></p>
                                <p><strong>Dia:</strong> <?= $dia_extenso ?>, <?= date('d/m', $timestamp) ?></p>
                                <p><strong>Horário:</strong> <?= $hora_inicio ?> - <?= $hora_fim ?> (<?= $class['duration_min'] ?> min)</p>
                                <p><strong>Local:</strong> <?= h($class['room']) ?></p>
                                <p><strong>Vagas:</strong> 
                                    <?php if ($cheia): ?>
                                        <span class="full">0/<?= $class['capacity'] ?> (Cheia)</span>
                                    <?php else: ?>
                                        <span class="available"><?= $vagas_restantes ?>/<?= $class['capacity'] ?> livres</span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($avg_rating > 0): ?>
                                    <p><strong>Avaliação:</strong> 
                                        <span style="color: #f59e0b; font-weight: bold;">★ <?= $avg_rating ?>/5</span> 
                                        <span style="color: #94a3b8; font-size: 0.9em;">(<?= $review_count ?> <?= $review_count === 1 ? 'avaliação' : 'avaliações' ?>)</span>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <p class="class-description"><?= h($class['description']) ?></p>
                            
                            <?php if (!empty($class_reviews)): ?>
                                <div class="class-reviews-section" style="margin-top: 15px; border-top: 1px dashed var(--color-border); padding-top: 12px; width: 100%;">
                                    <h5 style="margin: 0 0 8px 0; font-size: 0.9em; color: #38bdf8; font-weight: 700;">Avaliações dos Membros:</h5>
                                    <div style="display: flex; flex-direction: column; gap: 8px; max-height: 150px; overflow-y: auto; padding-right: 5px;">
                                        <?php foreach ($class_reviews as $cr): ?>
                                            <div style="background: rgba(255, 255, 255, 0.03); padding: 8px; border-radius: 6px; font-size: 0.85em; border: 1px solid rgba(255, 255, 255, 0.05);">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                                    <span style="font-weight: 600; color: #cbd5e1;"><?= h($cr['user_name']) ?></span>
                                                    <span style="color: #f59e0b;">
                                                        <?= str_repeat('★', $cr['rating']) ?><?= str_repeat('☆', 5 - $cr['rating']) ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($cr['comment'])): ?>
                                                    <p style="margin: 0; color: #94a3b8; font-style: italic; overflow-wrap: break-word; word-break: break-word;">"<?= h($cr['comment']) ?>"</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="class-actions" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: auto; padding-top: 15px;">
                                <?php if ($is_past): ?>
                                    <span style="background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); font-weight: 600; padding: 6px 12px; border-radius: 4px; font-size: 0.85em; display: inline-block;">✓ Aula Concluída</span>
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                        <form method="POST" action="../actions/delete_class.php" class="delete-class-form" data-class-name="<?= h($class['name']) ?>" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                            <button type="submit" class="btn-action btn-delete" style="padding: 6px 12px; font-size: 0.85em; border-radius: 4px; cursor: pointer; border: 1px solid #f43f5e; background: rgba(244, 63, 94, 0.1); color: #f43f5e; font-weight: 600;" title="Remover aula">
                                                ❌ Remover
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                        <button type="button" class="btn-action btn-edit" 
                                                data-id="<?= $class['id'] ?>"
                                                data-name="<?= h($class['name']) ?>"
                                                data-description="<?= h($class['description'] ?? '') ?>"
                                                data-instructor-id="<?= $class['instructor_id'] ?>"
                                                data-capacity="<?= $class['capacity'] ?>"
                                                data-scheduled-at="<?= date('Y-m-d\TH:i', strtotime($class['scheduled_at'])) ?>"
                                                data-duration-min="<?= $class['duration_min'] ?>"
                                                data-room="<?= h($class['room']) ?>"
                                                style="padding: 6px 12px; font-size: 0.85em; border-radius: 4px; cursor: pointer; border: 1px solid #334155; background: #1e293b; color: #cbd5e1; font-weight: 600;"
                                                title="Editar aula">
                                            Editar
                                        </button>
                                        <form method="POST" action="../actions/delete_class.php" class="delete-class-form" data-class-name="<?= h($class['name']) ?>" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                            <button type="submit" class="btn-action btn-delete" style="padding: 6px 12px; font-size: 0.85em; border-radius: 4px; cursor: pointer; border: 1px solid #f43f5e; background: rgba(244, 63, 94, 0.1); color: #f43f5e; font-weight: 600;" title="Remover aula">
                                                ❌ Remover
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <?php if ($is_own_class): ?>
                                            <button type="button" class="btn btn-secondary btn-small" disabled style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-weight: bold;">Minha Aula</button>
                                        <?php elseif ($is_booked): ?>
                                            <form method="POST" action="../actions/cancel_booking.php" style="margin: 0; padding: 0; display: inline;">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                                <input type="hidden" name="redirect_to" value="/pages/schedules.php">
                                                <button type="submit" class="btn btn-secondary btn-small" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Cancelar Inscrição</button>
                                            </form>
                                        <?php elseif ($cheia): ?>
                                            <?php if ($user_id > 0): ?>
                                                <?php if ($is_waitlisted): ?>
                                                    <form method="POST" action="../actions/cancel_waitlist.php" style="margin: 0; padding: 0; display: inline;">
                                                        <?= get_csrf_input() ?>
                                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                                        <input type="hidden" name="redirect_to" value="/pages/schedules.php">
                                                        <button type="submit" class="btn btn-secondary btn-small" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3);">Sair da Lista de Espera</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="../actions/join_waitlist.php" style="margin: 0; padding: 0; display: inline;">
                                                        <?= get_csrf_input() ?>
                                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                                        <button type="submit" class="btn btn-secondary btn-small" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);">Lista de Espera</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-secondary btn-small" style="text-decoration: none; display: inline-block; text-align: center; line-height: 1.8; background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3);" onclick="showToast('Inicie sessão para entrar na lista de espera!', 'error');">Lista de Espera</a>
                                            <?php endif; ?>
                                        <?php elseif ($user_id > 0): ?>
                                            <form method="POST" action="../actions/book_class.php" style="margin: 0; padding: 0; display: inline;">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                                <button type="submit" class="btn btn-primary btn-small">Inscrever-se</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-primary btn-small" style="text-decoration: none; display: inline-block; text-align: center; line-height: 1.8;" onclick="showToast('Inicie sessão para se inscrever nas aulas!', 'error');">Inscrever-se</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
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

<!-- Modal de Catálogo de Aulas Unificado (Oculto por defeito) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <style>
    #class-modal label, #delete-class-confirm-modal label {
        color: #cbd5e1 !important; /* light slate */
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: block;
    }
    #class-modal h3, #class-modal h2, #delete-class-confirm-modal h3 {
        color: #38bdf8 !important; /* premium cyan */
    }
    #class-modal input, #class-modal select, #class-modal textarea {
        background-color: #1e293b !important; /* dark slate background */
        border: 1px solid #334155 !important;
        color: #ffffff !important;
        transition: all 0.3s ease;
        width: 100%;
    }
    #class-modal input:focus, #class-modal select:focus, #class-modal textarea:focus {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
        outline: none;
    }
    #class-modal input::placeholder {
        color: #64748b !important;
    }
    #class-modal select option {
        background-color: #0f172a !important; /* keep select dropdown options dark */
        color: #ffffff !important;
    }
    </style>

    <div id="class-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 580px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;">
            
            <!-- Modal Header -->
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
                <h3 id="form-title" style="margin: 0; color: #38bdf8; font-size: 1.4em; font-weight: 700;">Nova Aula de Grupo</h3>
                <button type="button" id="btn-close-modal" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>

            <!-- Modal Body (Formulário) -->
            <form method="POST" class="staff-form admin-form" id="class-form" action="../actions/save_class.php" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; overflow-y: auto; margin: 0;">
                <?= get_csrf_input() ?>
                <input type="hidden" name="class_id" id="form-class-id" value="">

                <div class="form-row" style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="form-name">Nome da Aula *</label>
                        <input type="text" id="form-name" name="name" placeholder="Ex: Yoga Matinal, Spinning Intenso, HIIT Express..." required>
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="form-instructor">Treinador Atribuído *</label>
                        <select id="form-instructor" name="instructor_id" required>
                            <option value="">-- Selecionar Treinador --</option>
                            <?php foreach ($trainersList as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= h($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="form-capacity">Vagas (Capacidade) *</label>
                        <input type="number" id="form-capacity" name="capacity" min="1" value="20" required>
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 0;">
                        <div>
                            <label for="form-scheduled-date">Data *</label>
                            <input type="date" id="form-scheduled-date" min="<?= date('Y-m-d') ?>" required style="width: 100%;">
                        </div>
                        <div>
                            <label for="form-scheduled-time">Horário *</label>
                            <select id="form-scheduled-time" required style="width: 100%;">
                                <option value="">-- Selecione data --</option>
                            </select>
                        </div>
                        <input type="hidden" id="form-scheduled-at" name="scheduled_at">
                    </div>
                    <div class="form-group">
                        <label for="form-duration">Duração (Minutos) *</label>
                        <input type="number" id="form-duration" name="duration_min" min="1" value="60" required>
                    </div>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="form-room">Sala / Localização *</label>
                        <select id="form-room" name="room" required>
                            <option value="Sala 1">Sala 1</option>
                            <option value="Sala 2">Sala 2</option>
                            <option value="Sala 3">Sala 3</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="form-description">Descrição da Aula</label>
                    <textarea id="form-description" name="description" rows="3" placeholder="Escreva uma breve descrição da aula..."></textarea>
                </div>

                <!-- Ações do Formulário -->
                <div class="form-actions" style="border-top: 1px solid #1e293b; padding-top: 15px; margin-top: 10px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-form" style="flex: none; width: auto; padding: 0.5rem 1.5rem; margin: 0;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex: none; width: auto; padding: 0.5rem 1.5rem; margin: 0;">Guardar no Catálogo</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmação de Remoção de Aula (Delete Class Custom Modal) -->
    <div id="delete-class-confirm-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 450px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <!-- Header -->
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
                <h3 style="margin: 0; color: #cf5a2b; font-size: 1.25em; font-weight: 700;">Confirmar Remoção</h3>
                <button type="button" id="delete-class-close" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>
            <!-- Body -->
            <div style="padding: 20px; color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; background: #0f172a;">
                <p id="delete-class-message" style="margin: 0;">Deseja realmente remover esta aula? Isto também removerá todas as inscrições associadas.</p>
            </div>
            <!-- Footer -->
            <div style="padding: 15px 20px; border-top: 1px solid #1e293b; display: flex; justify-content: flex-end; gap: 10px; background: #0b0f19;">
                <button type="button" id="delete-class-cancel" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px;">Cancelar</button>
                <button type="button" id="delete-class-proceed" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px; background-color: #cf5a2b; color: white; font-weight: bold; border: none; cursor: pointer;">Confirmar</button>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL_PATH ?>js/admin-schedules.js"></script>
<?php endif; ?>
