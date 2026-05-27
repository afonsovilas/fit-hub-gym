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

if (!isset($_SESSION['user_id'])) {
    set_flash('Inicie sessão para aceder à área de gestão.', 'error');
    redirect('/pages/login.php');
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    set_flash('Acesso restrito. Apenas administradores podem aceder à área de gestão.', 'error');
    redirect('/pages/profile.php');
}

try {
    $db = get_db();

    // Fetch all trainers
    $stmtTrainers = $db->query("
        SELECT u.id, u.name, u.email, u.phone, u.is_active, tp.bio, tp.certifications,
               (SELECT GROUP_CONCAT(specialization, ' / ') FROM trainer_specializations WHERE trainer_id = u.id) AS specializations
        FROM users u
        LEFT JOIN trainer_profiles tp ON u.id = tp.user_id
        WHERE u.role = 'trainer'
        ORDER BY u.name ASC
    ");
    $trainers = $stmtTrainers->fetchAll();

    // Fetch all staff
    $stmtStaff = $db->query("
        SELECT u.id, u.name, u.email, u.phone, u.is_active, sp.position, sp.department
        FROM users u
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id
        WHERE u.role = 'staff'
        ORDER BY u.name ASC
    ");
    $staffList = $stmtStaff->fetchAll();

    // Fetch all users for management
    $stmtUsers = $db->prepare("
        SELECT id, name, email, username, role, is_active, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    $stmtUsers->execute();
    $users = $stmtUsers->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Helper function to translate role
function translate_role(string $role): string {
    $roles = [
        'member'  => 'Membro',
        'trainer' => 'Treinador',
        'admin'   => 'Administrador',
        'staff'   => 'Pessoal',
    ];
    return $roles[$role] ?? $role;
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
                <button type="button" class="tab-button active" data-tab="users" role="tab" id="tab-users" aria-controls="users" aria-selected="true">👥 Utilizadores</button>
                <button type="button" class="tab-button" data-tab="trainers" role="tab" id="tab-trainers" aria-controls="trainers" aria-selected="false">Treinadores</button>
                <button type="button" class="tab-button" data-tab="staff" role="tab" id="tab-staff" aria-controls="staff" aria-selected="false">Staff</button>
            </div>

            <?php if (isset($error)): ?>
                <div class="flash flash--error">Erro de base de dados: <?= h($error) ?></div>
            <?php endif; ?>

            <!-- Users Tab -->
            <div id="users" class="tab-content active" role="tabpanel" aria-labelledby="tab-users">
                <section class="form-section">
                    <h2>Novo Utilizador</h2>
                    <form method="POST" class="staff-form">
                        <?= get_csrf_input() ?>
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="user_id" value="">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="user-name">Nome Completo *</label>
                                <input type="text" id="user-name" name="name" placeholder="Ex: João Silva" required>
                            </div>
                            <div class="form-group">
                                <label for="user-email">Email *</label>
                                <input type="email" id="user-email" name="email" placeholder="Ex: joao@fithub.pt" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="user-username">Nome de Utilizador *</label>
                                <input type="text" id="user-username" name="username" placeholder="Ex: joao_silva" required>
                            </div>
                            <div class="form-group">
                                <label for="user-password">Senha <span id="password-note">(obrigatória para novos)</span> *</label>
                                <input type="password" id="user-password" name="password" placeholder="Deixar em branco para não alterar (edições)">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="user-role">Função *</label>
                            <select id="user-role" name="role" required>
                                <option value="">-- Selecione uma função --</option>
                                <option value="member">Membro</option>
                                <option value="trainer">Treinador</option>
                                <option value="staff">Pessoal</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Guardar Utilizador</button>
                            <button type="reset" class="btn btn-secondary">Limpar</button>
                        </div>
                    </form>
                </section>

                <section class="list-section">
                    <h2>Todos os Utilizadores</h2>
                    <?php if (empty($users)): ?>
                        <div class="alert alert--info">
                            <p>Nenhum utilizador encontrado.</p>
                        </div>
                    <?php else: ?>
                        <div class="staff-table-wrapper">
                            <table class="admin-users-table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Utilizador</th>
                                        <th>Função</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user):
                                        $user_id = (int) $user['id'];
                                        $is_active = (int) $user['is_active'];
                                        $status_class = $is_active ? 'active' : 'inactive';
                                        $status_text = $is_active ? 'Ativo' : 'Inativo';
                                        $role_text = translate_role($user['role']);
                                    ?>
                                        <tr class="user-row" data-user-id="<?= $user_id ?>">
                                            <td class="col-name"><?= h($user['name']) ?></td>
                                            <td class="col-email"><?= h($user['email']) ?></td>
                                            <td class="col-username"><?= h($user['username']) ?></td>
                                            <td class="col-role">
                                                <span class="role-badge role-<?= strtolower($user['role']) ?>">
                                                    <?= $role_text ?>
                                                </span>
                                            </td>
                                            <td class="col-status">
                                                <span class="status-badge status-<?= $status_class ?>" id="status-<?= $user_id ?>">
                                                    <?= $status_text ?>
                                                </span>
                                            </td>
                                            <td class="col-actions">
                                                <button type="button" class="btn-action btn-edit" data-user-id="<?= $user_id ?>" title="Editar utilizador">
                                                    ✏️ Editar
                                                </button>
                                                <button type="button" class="btn-action btn-toggle-status" data-user-id="<?= $user_id ?>" data-current-status="<?= $is_active ?>" title="<?= $is_active ? 'Desativar' : 'Ativar' ?> utilizador">
                                                    <?= $is_active ? '🔒 Desativar' : '🔓 Ativar' ?>
                                                </button>
                                                <?php if ($user['role'] !== 'admin'): ?>
                                                    <button type="button" class="btn-action btn-make-admin" data-user-id="<?= $user_id ?>" title="Promover a administrador">
                                                        👑 Admin
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Trainers Tab -->
            <div id="trainers" class="tab-content" role="tabpanel" aria-labelledby="tab-trainers">
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
                                            <button type="button" class="btn-icon btn-edit" title="Editar" 
                                                    data-id="<?= $t['id'] ?>"
                                                    data-name="<?= h($t['name']) ?>"
                                                    data-email="<?= h($t['email']) ?>"
                                                    data-phone="<?= h($t['phone'] ?? '') ?>"
                                                    data-bio="<?= h($t['bio'] ?? '') ?>"
                                                    data-certifications="<?= h($t['certifications'] ?? '') ?>"
                                                    data-specializations="<?= h($t['specializations'] ?? '') ?>"
                                                    onclick="openEditTrainerModal(this)">
                                                ✏️
                                            </button>
                                            
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
                                            <button type="button" class="btn-icon btn-edit" title="Editar" 
                                                    data-id="<?= $s['id'] ?>"
                                                    data-name="<?= h($s['name']) ?>"
                                                    data-email="<?= h($s['email']) ?>"
                                                    data-phone="<?= h($s['phone'] ?? '') ?>"
                                                    data-position="<?= h($s['position'] ?? '') ?>"
                                                    data-department="<?= h($s['department'] ?? '') ?>"
                                                    onclick="openEditStaffModal(this)">
                                                ✏️
                                            </button>
                                            
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

    <div id="edit-trainer-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 20px;">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 600px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.5); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;">
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #38bdf8; font-size: 1.4em;">Editar Treinador</h3>
                <button type="button" onclick="closeTrainerModal()" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form method="POST" action="../../actions/update_user.php" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; overflow-y: auto;">
                <?= get_csrf_input() ?>
                <input type="hidden" name="user_id" id="edit-trainer-id">
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-trainer-name-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Nome Completo:</label>
                    <input type="text" id="edit-trainer-name-input" name="name" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-trainer-email-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Email:</label>
                    <input type="email" id="edit-trainer-email-input" name="email" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-trainer-phone-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Telefone:</label>
                    <input type="tel" id="edit-trainer-phone-input" name="phone" style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <fieldset class="checkbox-group" style="border: 1px solid #334155; border-radius: 6px; padding: 12px; background: #1e293b;">
                        <legend style="color: #e2e8f0; font-weight: bold; padding: 0 6px;">Especializações:</legend>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 5px;">
                            <label class="checkbox-item" for="edit-spec-yoga" style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" id="edit-spec-yoga" name="specializations[]" value="yoga">
                                <span>Yoga</span>
                            </label>
                            <label class="checkbox-item" for="edit-spec-spinning" style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" id="edit-spec-spinning" name="specializations[]" value="spinning">
                                <span>Spinning</span>
                            </label>
                            <label class="checkbox-item" for="edit-spec-hiit" style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" id="edit-spec-hiit" name="specializations[]" value="hiit">
                                <span>HIIT</span>
                            </label>
                            <label class="checkbox-item" for="edit-spec-pilates" style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" id="edit-spec-pilates" name="specializations[]" value="pilates">
                                <span>Pilates</span>
                            </label>
                            <label class="checkbox-item" for="edit-spec-boxing" style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" id="edit-spec-boxing" name="specializations[]" value="boxing">
                                <span>Boxe</span>
                            </label>
                            <label class="checkbox-item" for="edit-spec-personal" style="display: flex; align-items: center; gap: 8px; color: #e2e8f0; cursor: pointer;">
                                <input type="checkbox" id="edit-spec-personal" name="specializations[]" value="personal">
                                <span>Personal Training</span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-trainer-certifications-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Certificações:</label>
                    <textarea id="edit-trainer-certifications-input" name="certifications" rows="2" style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;"></textarea>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-trainer-bio-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Biografia:</label>
                    <textarea id="edit-trainer-bio-input" name="bio" rows="3" style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;"></textarea>
                </div>

                <div style="padding: 15px 0 0; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #1e293b; margin-top: 10px;">
                    <button type="button" onclick="closeTrainerModal()" class="btn btn-secondary btn-small">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-small">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-staff-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 20px;">
        <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 500px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.5); display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #38bdf8; font-size: 1.4em;">Editar Funcionário Staff</h3>
                <button type="button" onclick="closeStaffModal()" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1;">&times;</button>
            </div>
            <form method="POST" action="../../actions/update_user.php" style="padding: 20px; display: flex; flex-direction: column; gap: 15px;">
                <?= get_csrf_input() ?>
                <input type="hidden" name="user_id" id="edit-staff-id">
                
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-staff-name-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Nome Completo:</label>
                    <input type="text" id="edit-staff-name-input" name="name" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-staff-email-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Email:</label>
                    <input type="email" id="edit-staff-email-input" name="email" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-staff-phone-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Telefone:</label>
                    <input type="tel" id="edit-staff-phone-input" name="phone" style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-staff-position-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Posição:</label>
                    <select id="edit-staff-position-input" name="position" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; cursor: pointer;">
                        <option value="front-desk-agent">Front Desk Agent</option>
                        <option value="facilities-technician">Técnico de Manutenção</option>
                        <option value="hygiene-assistant">Assistente de Limpeza</option>
                        <option value="operations-coordinator">Coordenador Operacional</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label for="edit-staff-department-input" style="font-weight: 600; color: #e2e8f0; font-size: 0.9em; text-align: left;">Departamento:</label>
                    <select id="edit-staff-department-input" name="department" required style="width: 100%; padding: 10px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; cursor: pointer;">
                        <option value="member-support">Atendimento ao Cliente</option>
                        <option value="operations">Operações</option>
                        <option value="facilities">Infraestruturas</option>
                        <option value="admin">Administração</option>
                    </select>
                </div>

                <div style="padding: 15px 0 0; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #1e293b; margin-top: 10px;">
                    <button type="button" onclick="closeStaffModal()" class="btn btn-secondary btn-small">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-small">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
function openEditTrainerModal(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    const email = button.dataset.email;
    const phone = button.dataset.phone;
    const bio = button.dataset.bio;
    const certifications = button.dataset.certifications;
    const specializations = button.dataset.specializations.split(' / ').map(s => s.trim().toLowerCase());

    document.getElementById('edit-trainer-id').value = id;
    document.getElementById('edit-trainer-name-input').value = name;
    document.getElementById('edit-trainer-email-input').value = email;
    document.getElementById('edit-trainer-phone-input').value = phone;
    document.getElementById('edit-trainer-bio-input').value = bio;
    document.getElementById('edit-trainer-certifications-input').value = certifications;

    const checkboxes = document.querySelectorAll('#edit-trainer-modal input[type="checkbox"]');
    checkboxes.forEach(cb => {
        cb.checked = specializations.includes(cb.value.toLowerCase());
    });

    const modal = document.getElementById('edit-trainer-modal');
    modal.style.display = 'flex';
}

function closeTrainerModal() {
    document.getElementById('edit-trainer-modal').style.display = 'none';
}

function openEditStaffModal(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    const email = button.dataset.email;
    const phone = button.dataset.phone;
    const position = button.dataset.position;
    const department = button.dataset.department;

    document.getElementById('edit-staff-id').value = id;
    document.getElementById('edit-staff-name-input').value = name;
    document.getElementById('edit-staff-email-input').value = email;
    document.getElementById('edit-staff-phone-input').value = phone;
    document.getElementById('edit-staff-position-input').value = position;
    document.getElementById('edit-staff-department-input').value = department;

    const modal = document.getElementById('edit-staff-modal');
    modal.style.display = 'flex';
}

function closeStaffModal() {
    document.getElementById('edit-staff-modal').style.display = 'none';
}

window.addEventListener('click', function(event) {
    const trainerModal = document.getElementById('edit-trainer-modal');
    const staffModal = document.getElementById('edit-staff-modal');
    if (event.target === trainerModal) {
        closeTrainerModal();
    }
    if (event.target === staffModal) {
        closeStaffModal();
    }
});

// Tab switching functionality
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const tabName = this.dataset.tab;
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });
        
        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');
    });
});
</script>

<script src="<?= BASE_URL_PATH ?>js/admin-users.js"></script>

<?php 
$extra_js = ['js/admin-staff.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
