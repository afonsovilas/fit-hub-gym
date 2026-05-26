<?php
/**
 * pages/equipment.php
 *
 * Página de disponibilidade de equipamentos da zona de treino livre.
 * Exibe o estado atual de todos os equipamentos (Disponível, Em Uso, Manutenção).
 * Ligada à base de dados SQLite (FitHub).
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Equipamentos';
$body_class  = 'equipment-page';
$active_page = 'equipment';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/database/connection.php';

$equipment = [];
$error = null;

try {
    $db = get_db();

    // Fetch all equipment ordered by category and name
    $stmtEquipment = $db->prepare("
        SELECT id, name, category, status, description, created_at
        FROM equipment
        ORDER BY category ASC, name ASC
    ");
    $stmtEquipment->execute();
    $equipment = $stmtEquipment->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
    error_log('[EQUIPMENT PAGE ERROR] ' . $error);
}
?>

<main>
    <!-- Page Header -->
    <section class="page-header page-header--equipment">
        <div class="container">
            <h1>Equipamentos Disponíveis</h1>
            <p class="page-subtitle">Consulte o estado atual de todos os equipamentos da zona de treino livre</p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert--error" role="alert">
                <p>Erro ao carregar os equipamentos: <?= h($error) ?></p>
            </div>
        <?php elseif (empty($equipment)): ?>
            <div class="alert alert--info" role="alert">
                <p>Nenhum equipamento registado no momento.</p>
            </div>
        <?php else: ?>
            <section class="equipment-section">
                <!-- Status Summary -->
                <div class="equipment-stats">
                    <?php
                        $stats = ['available' => 0, 'in_use' => 0, 'maintenance' => 0];
                        foreach ($equipment as $item) {
                            $status = strtolower($item['status']);
                            if (isset($stats[$status])) {
                                $stats[$status]++;
                            }
                        }
                    ?>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['available'] ?></span>
                        <span class="stat-label">Disponível</span>
                        <span class="stat-indicator available"></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['in_use'] ?></span>
                        <span class="stat-label">Em Uso</span>
                        <span class="stat-indicator in-use"></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= $stats['maintenance'] ?></span>
                        <span class="stat-label">Manutenção</span>
                        <span class="stat-indicator maintenance"></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= count($equipment) ?></span>
                        <span class="stat-label">Total</span>
                        <span class="stat-indicator total"></span>
                    </div>
                </div>

                <!-- Filter Options (Optional) -->
                <div class="equipment-filter">
                    <form method="GET" class="filter-form">
                        <div class="filter-group">
                            <label for="filter-status">Filtrar por Estado:</label>
                            <select id="filter-status" name="status" onchange="filterEquipment(this.value)">
                                <option value="">Todos</option>
                                <option value="available">Disponível</option>
                                <option value="in_use">Em Uso</option>
                                <option value="maintenance">Manutenção</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Equipment Grid -->
                <div class="equipment-grid">
                    <?php foreach ($equipment as $item):
                        $item_id = (int) $item['id'];
                        $item_name = h($item['name']);
                        $item_category = h($item['category']);
                        $item_status = strtolower($item['status']);
                        $item_description = h($item['description'] ?? '');
                        
                        // Translate status for display
                        $status_labels = [
                            'available' => 'Disponível',
                            'in_use' => 'Em Uso',
                            'maintenance' => 'Manutenção'
                        ];
                        $status_display = $status_labels[$item_status] ?? h($item['status']);
                    ?>
                        <article class="equipment-card equipment-card--<?= $item_status ?>" data-status="<?= $item_status ?>">
                            <div class="equipment-header">
                                <h3 class="equipment-name"><?= $item_name ?></h3>
                                <span class="status-badge status-<?= $item_status ?>">
                                    <?= $status_display ?>
                                </span>
                            </div>

                            <div class="equipment-category">
                                <span class="category-label"><?= $item_category ?></span>
                            </div>

                            <?php if (!empty($item_description)): ?>
                                <div class="equipment-description">
                                    <p><?= $item_description ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="equipment-status-indicator">
                                <span class="status-dot status-dot--<?= $item_status ?>"></span>
                                <span class="status-text">
                                    <?php
                                        $status_messages = [
                                            'available' => 'Pronto para usar',
                                            'in_use' => 'Atualmente em uso',
                                            'maintenance' => 'Em manutenção'
                                        ];
                                        echo $status_messages[$item_status] ?? $status_display;
                                    ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="equipment-legend">
                    <h3>Legenda de Estados:</h3>
                    <ul class="legend-list">
                        <li>
                            <span class="legend-dot legend-dot--available"></span>
                            <strong>Disponível:</strong> Equipamento pronto para utilizar
                        </li>
                        <li>
                            <span class="legend-dot legend-dot--in-use"></span>
                            <strong>Em Uso:</strong> Equipamento está atualmente a ser utilizado
                        </li>
                        <li>
                            <span class="legend-dot legend-dot--maintenance"></span>
                            <strong>Manutenção:</strong> Equipamento está fora de serviço para reparações ou limpeza
                        </li>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<script>
    /**
     * Simple client-side filter function
     * Note: This is optional and can be enhanced to use PHP filtering
     */
    function filterEquipment(status) {
        const cards = document.querySelectorAll('.equipment-card');
        cards.forEach(card => {
            if (status === '' || card.dataset.status === status) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Update URL without page reload (optional)
        const url = new URL(window.location);
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        window.history.replaceState({}, '', url);
    }

    // Apply filter on page load if status parameter exists
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const status = params.get('status');
        if (status) {
            const filterSelect = document.getElementById('filter-status');
            if (filterSelect) {
                filterSelect.value = status;
                filterEquipment(status);
            }
        }
    });
</script>

<?php require_once BASE_PATH . '/templates/footer.php'; ?>
