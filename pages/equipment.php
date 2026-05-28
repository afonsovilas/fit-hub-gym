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
require_once BASE_PATH . '/src/csrf.php';
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

                <div class="equipment-controls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <!-- Filtro por Estado -->
                    <div class="equipment-filter" style="margin: 0;">
                        <form method="GET" class="filter-form" style="margin: 0; padding: 0;">
                            <div class="filter-group" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <label for="filter-status" style="font-weight: 600; margin: 0;">Filtrar por Estado:</label>
                                <select id="filter-status" name="status" onchange="filterEquipment(this.value)" style="padding: 0.5rem 1rem; border-radius: 8px;">
                                    <option value="">Todos</option>
                                    <option value="available">Disponível</option>
                                    <option value="in_use">Em Uso</option>
                                    <option value="maintenance">Manutenção</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- Botão Adicionar (Apenas Admin) -->
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <div class="admin-toolbar" style="margin: 0; display: flex; align-items: center;">
                            <button type="button" class="btn btn-primary" id="btn-create-equipment" style="margin: 0; padding: 0.6rem 1.5rem; font-weight: 600; font-size: 0.95em;">➕ Adicionar Equipamento</button>
                        </div>
                    <?php endif; ?>
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

                            <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <div class="equipment-actions" style="display: flex; gap: 8px; border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 12px; justify-content: flex-end;">
                                    <button type="button" class="btn-action btn-edit" 
                                            data-id="<?= $item_id ?>"
                                            data-name="<?= $item_name ?>"
                                            data-category="<?= $item_category ?>"
                                            data-status="<?= $item_status ?>"
                                            data-description="<?= $item_description ?>"
                                            style="padding: 6px 12px; font-size: 0.85em; border-radius: 4px; cursor: pointer; border: 1px solid #334155; background: #1e293b; color: #cbd5e1; font-weight: 600;"
                                            title="Editar equipamento">
                                        ✏️ Editar
                                    </button>
                                    <form method="POST" action="../actions/delete_equipment.php" class="delete-equipment-form" data-equipment-name="<?= $item_name ?>" style="margin: 0; padding: 0; display: inline;">
                                        <?= get_csrf_input() ?>
                                        <input type="hidden" name="id" value="<?= $item_id ?>">
                                        <button type="submit" class="btn-action btn-delete" style="padding: 6px 12px; font-size: 0.85em; border-radius: 4px; cursor: pointer; border: 1px solid #f43f5e; background: rgba(244, 63, 94, 0.1); color: #f43f5e; font-weight: 600;" title="Remover equipamento">
                                            ❌ Remover
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
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

<!-- Modal de Equipamento Unificado (Oculto por defeito) -->
<?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
    <style>
    #equipment-modal label, #delete-equipment-confirm-modal label {
        color: #cbd5e1 !important;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: block;
    }
    #equipment-modal h3, #equipment-modal h2, #delete-equipment-confirm-modal h3 {
        color: #38bdf8 !important;
    }
    #equipment-modal input, #equipment-modal select, #equipment-modal textarea {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #ffffff !important;
        transition: all 0.3s ease;
        width: 100%;
    }
    #equipment-modal input:focus, #equipment-modal select:focus, #equipment-modal textarea:focus {
        border-color: #38bdf8 !important;
        box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
        outline: none;
    }
    #equipment-modal input::placeholder {
        color: #64748b !important;
    }
    #equipment-modal select option {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }
    </style>

    <div id="equipment-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 550px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;">
            
            <!-- Modal Header -->
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
                <h3 id="form-title" style="margin: 0; color: #38bdf8; font-size: 1.4em; font-weight: 700;">Novo Equipamento</h3>
                <button type="button" id="btn-close-modal" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>

            <!-- Modal Body (Formulário) -->
            <form method="POST" class="staff-form admin-form" id="equipment-form" action="../actions/save_equipment.php" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; overflow-y: auto; margin: 0;">
                <?= get_csrf_input() ?>
                <input type="hidden" name="equipment_id" id="form-equipment-id" value="">

                <div class="form-group">
                    <label for="form-name">Nome do Equipamento *</label>
                    <input type="text" id="form-name" name="name" placeholder="Ex: Passadeira Matrix C" required>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label for="form-category">Categoria *</label>
                        <select id="form-category" name="category" required>
                            <option value="">-- Categoria --</option>
                            <option value="Cardio">Cardio</option>
                            <option value="Strength">Força (Strength)</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="form-status">Estado *</label>
                        <select id="form-status" name="status" required>
                            <option value="available">Disponível</option>
                            <option value="in_use">Em Uso</option>
                            <option value="maintenance">Manutenção</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="form-description">Descrição / Notas</label>
                    <textarea id="form-description" name="description" rows="3" placeholder="Notas sobre o estado do equipamento..."></textarea>
                </div>

                <div class="form-actions" style="border-top: 1px solid #1e293b; padding-top: 15px; margin-top: 10px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-secondary" id="btn-cancel-form" style="flex: none; width: auto; padding: 0.5rem 1.5rem; margin: 0;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="flex: none; width: auto; padding: 0.5rem 1.5rem; margin: 0;">Guardar Equipamento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmação de Remoção de Equipamento (Delete Equipment Custom Modal) -->
    <div id="delete-equipment-confirm-modal" class="modal" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 450px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
            <!-- Header -->
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
                <h3 style="margin: 0; color: #cf5a2b; font-size: 1.25em; font-weight: 700;">Confirmar Remoção</h3>
                <button type="button" id="delete-equipment-close" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
            </div>
            <!-- Body -->
            <div style="padding: 20px; color: #cbd5e1; font-size: 0.95rem; line-height: 1.5; background: #0f172a;">
                <p id="delete-equipment-message" style="margin: 0;">Deseja realmente remover este equipamento?</p>
            </div>
            <!-- Footer -->
            <div style="padding: 15px 20px; border-top: 1px solid #1e293b; display: flex; justify-content: flex-end; gap: 10px; background: #0b0f19;">
                <button type="button" id="delete-equipment-cancel" class="btn btn-secondary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px;">Cancelar</button>
                <button type="button" id="delete-equipment-proceed" class="btn btn-primary" style="padding: 0.5rem 1.25rem; font-size: 0.9em; margin: 0; border-radius: 6px; background-color: #cf5a2b; color: white; font-weight: bold; border: none; cursor: pointer;">Confirmar</button>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL_PATH ?>js/admin-equipment.js"></script>
<?php endif; ?>
