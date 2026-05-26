<?php
/**
 * pages/admin/staff.php
 *
 * Página de Gestão administrativa de Treinadores e Staff.
 * Integrada com a base de dados SQLite (FitHub).
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(dirname(__DIR__)));
define('BASE_URL_PATH', '../../');

$page_title  = 'Gestão de Funcionários';
$body_class  = 'admin-page';
$active_page = 'admin';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';
require_once BASE_PATH . '/database/connection.php';

try {
    $db = get_db();


    $stmtTrainers = $db->query("
        SELECT u.id, u.name, u.email, u.phone, u.is_active, tp.bio, tp.certifications,
               (SELECT GROUP_CONCAT(specialization, ' / ') FROM trainer_specializations WHERE trainer_id = u.id) AS specializations
        FROM users u
        LEFT JOIN trainer_profiles tp ON u.id = tp.user_id
        WHERE u.role = 'trainer'
        ORDER BY u.name ASC
    ");
    $trainers = $stmtTrainers->fetchAll();

    $stmtStaff = $db->query("
        SELECT u.id, u.name, u.email, u.phone, u.is_active, sp.position, sp.department
        FROM users u
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id
        WHERE u.role = 'staff'
        ORDER BY u.name ASC
    ");
    $staffList = $stmtStaff->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<main>
    <section class="page-header page-header--admin">
        <div class="container">
            <h1>Gestão de Funcionários</h1>
            <p>Administre treinadores e staff do ginásio FitHub</p>
        </div>
    </section>

    <section class="admin-panel">
        <div class="container">
            <?php render_flash(); ?>

            <div class="admin-tabs" role="tablist" aria-label="Categorias de gestão de equipa">
                <button type="button" class="tab-button active" data-tab="trainers" role="tab" id="tab-trainers" aria-controls="trainers" aria-selected="true">Treinadores</button>
                <button type="button" class="tab-button" data-tab="staff" role="tab" id="tab-staff" aria-controls="staff" aria-selected="false">Staff</button>
            </div>

            <?php if (isset($error)): ?>
                <div class="flash flash--error">Erro de base de dados: <?= h($error) ?></div>
            <?php endif; ?>

            <div id="trainers" class="tab-content active" role="tabpanel" aria-labelledby="tab-trainers">
                <section class="form-section">
                    <h2>Adicionar Novo Treinador</h2>
                    <form class="staff-form" method="POST" action="../../actions/add_trainer.php">
                        <?= get_csrf_input() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="trainer-name">Nome Completo:</label>
                                <input type="text" id="trainer-name" name="trainer-name" required>
                            </div>
                            <div class="form-group">
                                <label for="trainer-email">Email:</label>
                                <input type="email" id="trainer-email" name="trainer-email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="trainer-phone">Telefone:</label>
                                <input type="tel" id="trainer-phone" name="trainer-phone">
                            </div>
                            <div class="form-group">
                                <fieldset class="checkbox-group">
                                    <legend>Especializações:</legend>
                                    <label class="checkbox-item" for="spec-yoga">
                                        <input type="checkbox" id="spec-yoga" name="trainer-specialization[]" value="yoga">
                                        <span>Yoga</span>
                                    </label>
                                    <label class="checkbox-item" for="spec-spinning">
                                        <input type="checkbox" id="spec-spinning" name="trainer-specialization[]" value="spinning">
                                        <span>Spinning</span>
                                    </label>
                                    <label class="checkbox-item" for="spec-hiit">
                                        <input type="checkbox" id="spec-hiit" name="trainer-specialization[]" value="hiit">
                                        <span>HIIT</span>
                                    </label>
                                    <label class="checkbox-item" for="spec-pilates">
                                        <input type="checkbox" id="spec-pilates" name="trainer-specialization[]" value="pilates">
                                        <span>Pilates</span>
                                    </label>
                                    <label class="checkbox-item" for="spec-boxing">
                                        <input type="checkbox" id="spec-boxing" name="trainer-specialization[]" value="boxing">
                                        <span>Boxe</span>
                                    </label>
                                    <label class="checkbox-item" for="spec-personal">
                                        <input type="checkbox" id="spec-personal" name="trainer-specialization[]" value="personal">
                                        <span>Personal Training</span>
                                    </label>
                                </fieldset>
                                <small class="form-hint">Selecione uma ou mais especializações.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="trainer-certifications">Certificações:</label>
                            <textarea id="trainer-certifications" name="trainer-certifications" rows="3" placeholder="Descreva as certificações..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="trainer-bio">Biografia:</label>
                            <textarea id="trainer-bio" name="trainer-bio" rows="4" placeholder="Escreva a biografia do treinador..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Adicionar Treinador</button>
                            <button type="reset" class="btn btn-secondary">Limpar</button>
                        </div>
                    </form>
                </section>

                <section class="list-section">
                    <h2>Treinadores Registados (Base de Dados)</h2>
                    <div class="staff-table-wrapper">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Especializações</th>
                                    <th scope="col">Telefone</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="actions-col">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainers as $t): ?>
                                    <tr>
                                        <td><strong><?= h($t['name']) ?></strong></td>
                                        <td><?= h($t['email']) ?></td>
                                        <td style="text-transform: capitalize; color:#38bdf8;"><?= h($t['specializations'] ?? 'Nenhuma') ?></td>
                                        <td><?= h($t['phone'] ?? 'N/D') ?></td>
                                        <td>
                                            <?php if ($t['is_active']): ?>
                                                <span class="status active">Ativo</span>
                                            <?php else: ?>
                                                <span class="status inactive">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions" style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                            <button type="button" class="btn-icon btn-edit" title="Editar" onclick="alert('Editar treinador <?= h($t['name']) ?> (ID: <?= $t['id'] ?>)')">✏️</button>
                                            
                                            <form method="POST" action="../../actions/toggle_user_status.php" onsubmit="return confirm('Tem a certeza que deseja alterar o estado de <?= h($t['name']) ?>?');" style="margin: 0; padding: 0; display: inline;">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="user_id" value="<?= $t['id'] ?>">
                                                <button type="submit" class="btn-icon btn-delete" title="<?= $t['is_active'] ? 'Desativar' : 'Ativar' ?>" style="background: none; border: none; cursor: pointer; padding: 0;">
                                                    <?= $t['is_active'] ? '🗑️' : '✔️' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div id="staff" class="tab-content" role="tabpanel" aria-labelledby="tab-staff">
                <section class="form-section">
                    <h2>Adicionar Novo Membro do Staff</h2>
                    <form class="staff-form" method="POST" action="../../actions/add_staff.php">
                        <?= get_csrf_input() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-name">Nome Completo:</label>
                                <input type="text" id="staff-name" name="staff-name" required>
                            </div>
                            <div class="form-group">
                                <label for="staff-email">Email:</label>
                                <input type="email" id="staff-email" name="staff-email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="staff-phone">Telefone:</label>
                                <input type="tel" id="staff-phone" name="staff-phone">
                            </div>
                            <div class="form-group">
                                <label for="staff-position">Posição:</label>
                                <select id="staff-position" name="staff-position" required>
                                    <option value="">Selecione</option>
                                    <option value="front-desk-agent">Front Desk Agent</option>
                                    <option value="facilities-technician">Técnico de Manutenção</option>
                                    <option value="hygiene-assistant">Assistente de Limpeza</option>
                                    <option value="operations-coordinator">Coordenador Operacional</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="staff-department">Departamento:</label>
                            <select id="staff-department" name="staff-department" required>
                                <option value="">Selecione</option>
                                <option value="member-support">Atendimento ao Cliente</option>
                                <option value="operations">Operações</option>
                                <option value="facilities">Infraestruturas</option>
                                <option value="admin">Administração</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Adicionar Staff</button>
                            <button type="reset" class="btn btn-secondary">Limpar</button>
                        </div>
                    </form>
                </section>

                <section class="list-section">
                    <h2>Staff Registado (Base de Dados)</h2>
                    <div class="staff-table-wrapper">
                        <table class="staff-table">
                            <thead>
                                <tr>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Posição</th>
                                    <th scope="col">Departamento</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="actions-col">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffList as $s): 
                                    $pos_map = [
                                        'front-desk-agent' => 'Front Desk Agent',
                                        'facilities-technician' => 'Técnico de Manutenção',
                                        'hygiene-assistant' => 'Assistente de Limpeza',
                                        'operations-coordinator' => 'Coordenador Operacional'
                                    ];
                                    $dept_map = [
                                        'member-support' => 'Atendimento ao Cliente',
                                        'operations' => 'Operações',
                                        'facilities' => 'Infraestruturas',
                                        'admin' => 'Administração'
                                    ];
                                    $pos_name = $pos_map[$s['position']] ?? $s['position'];
                                    $dept_name = $dept_map[$s['department']] ?? $s['department'];
                                ?>
                                    <tr>
                                        <td><strong><?= h($s['name']) ?></strong></td>
                                        <td><?= h($s['email']) ?></td>
                                        <td><?= h($pos_name) ?></td>
                                        <td><?= h($dept_name) ?></td>
                                        <td>
                                            <?php if ($s['is_active']): ?>
                                                <span class="status active">Ativo</span>
                                            <?php else: ?>
                                                <span class="status inactive">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions" style="display: flex; gap: 8px; align-items: center; justify-content: center;">
                                            <button type="button" class="btn-icon btn-edit" title="Editar" onclick="alert('Editar staff <?= h($s['name']) ?> (ID: <?= $s['id'] ?>)')">✏️</button>
                                            
                                            <form method="POST" action="../../actions/toggle_user_status.php" onsubmit="return confirm('Tem a certeza que deseja alterar o estado de <?= h($s['name']) ?>?');" style="margin: 0; padding: 0; display: inline;">
                                                <?= get_csrf_input() ?>
                                                <input type="hidden" name="user_id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn-icon btn-delete" title="<?= $s['is_active'] ? 'Desativar' : 'Ativar' ?>" style="background: none; border: none; cursor: pointer; padding: 0;">
                                                    <?= $s['is_active'] ? '🗑️' : '✔️' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>

<?php 
$extra_js = ['js/admin-staff.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
