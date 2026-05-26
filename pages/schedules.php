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
    
    $user_id = 9;
    $stmtCheckBooked = $db->prepare("SELECT 1 FROM bookings WHERE user_id = ? AND class_id = ?");

    $filter_type    = $_GET['class-type'] ?? '';
    $filter_trainer = $_GET['trainer'] ?? '';
    $filter_day     = $_GET['day'] ?? '';
    $filter_time    = $_GET['time'] ?? '';

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
            'sunday'    => '0',
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

    $query .= " ORDER BY c.scheduled_at ASC";

    $stmtClasses = $db->prepare($query);
    $stmtClasses->execute($params);
    $classes = $stmtClasses->fetchAll();

    $stmtCount = $db->prepare("SELECT COUNT(*) AS total FROM bookings WHERE class_id = ?");

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
                        <option value="sunday" <?= $filter_day === 'sunday' ? 'selected' : '' ?>>Domingo</option>
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

                <button type="submit" class="btn btn-secondary">Aplicar Filtros</button>
                <?php if ($filter_type || $filter_trainer || $filter_day || $filter_time): ?>
                    <a href="schedules.php" class="btn btn-small" style="margin-left:10px; background:#475569; color:#fff; text-decoration:none; padding:10px 15px; border-radius:4px; font-weight:bold;">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <!-- Lista Dinâmica de Aulas -->
    <section class="schedule-section">
        <div class="container">
            <h2>Aulas Disponíveis</h2>

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

                        $stmtCheckBooked->execute([$user_id, $class['id']]);
                        $is_booked = (bool) $stmtCheckBooked->fetch();

                        $timestamp = strtotime($class['scheduled_at']);
                        $dias_semana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
                        $dia_extenso = $dias_semana[date('w', $timestamp)];
                        $hora_inicio = date('H:i', $timestamp);
                        $hora_fim = date('H:i', $timestamp + ($class['duration_min'] * 60));
                    ?>
                        <article class="class-card" style="<?= $is_booked ? 'border-color: #10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.15);' : '' ?>">
                            <div class="class-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <div style="display: flex; flex-direction: column;">
                                    <h3><?= h($class['name']) ?></h3>
                                    <?php if ($is_booked): ?>
                                        <span style="color:#10b981; font-weight:bold; font-size:0.85em; margin-top:2px;">✓ Já está Inscrito</span>
                                    <?php endif; ?>
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
                            </div>
                            <p class="class-description"><?= h($class['description']) ?></p>
                            <div class="class-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php if ($is_booked): ?>
                                    <form method="POST" action="../actions/cancel_booking.php" style="margin: 0; padding: 0; display: inline;">
                                        <?= get_csrf_input() ?>
                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                        <input type="hidden" name="redirect_to" value="/pages/schedules.php">
                                        <button type="submit" class="btn btn-secondary btn-small" style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3);">Cancelar Inscrição</button>
                                    </form>
                                <?php elseif ($cheia): ?>
                                    <button type="button" class="btn btn-primary btn-small" disabled>Aula Cheia</button>
                                <?php else: ?>
                                    <form method="POST" action="../actions/book_class.php" style="margin: 0; padding: 0; display: inline;">
                                        <?= get_csrf_input() ?>
                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-small">Inscrever-se</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary btn-small" onclick="alert('Detalhes da aula: <?= h($class['description']) ?>\nLocal: Sala <?= h($class['room']) ?>')">Ver Detalhes</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>
