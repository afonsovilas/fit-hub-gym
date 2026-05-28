<?php
/**
 * pages/admin/staff.php
 *
 * Painel Administrativo de Gestão de Utilizadores (FitHub).
 * Vista unificada e dinâmica com filtros client-side e formulário reativo por cargo.
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(dirname(__DIR__)));
define('BASE_URL_PATH', '../../');

$page_title  = 'Gestão de Utilizadores';
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

    // Query unificada de alta performance trazendo todos os utilizadores e seus respetivos perfis associados
    $stmtUsers = $db->query("
        SELECT u.id, u.name, u.email, u.phone, u.username, u.role, u.is_active, u.created_at,
               tp.bio, tp.certifications,
               (SELECT GROUP_CONCAT(specialization, ' / ') FROM trainer_specializations WHERE trainer_id = u.id) AS specializations,
               sp.position, sp.department
        FROM users u
        LEFT JOIN trainer_profiles tp ON u.id = tp.user_id
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmtUsers->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
}

// Função auxiliar para tradução de papéis
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
            <h1>Gestão de Utilizadores</h1>
            <p>Administre membros, treinadores, pessoal e administradores da plataforma FitHub</p>
        </div>
    </section>

    <section class="admin-panel">
        <div class="container">
            <?php render_flash(); ?>

            <!-- Separadores de Categoria (Filtro Client-Side instantâneo) -->
            <div class="admin-tabs" role="tablist" aria-label="Categorias de gestão de utilizadores">
                <button type="button" class="tab-button active" data-tab="all" role="tab" id="tab-all" aria-selected="true">👥 Todos</button>
                <button type="button" class="tab-button" data-tab="member" role="tab" id="tab-member" aria-selected="false">👤 Membros</button>
                <button type="button" class="tab-button" data-tab="trainer" role="tab" id="tab-trainer" aria-selected="false">💪 Treinadores</button>
                <button type="button" class="tab-button" data-tab="staff" role="tab" id="tab-staff" aria-selected="false">💼 Staff</button>
                <button type="button" class="tab-button" data-tab="admin" role="tab" id="tab-admin" aria-selected="false">👑 Admins</button>
            </div>

            <?php if (isset($error)): ?>
                <div class="flash flash--error">Erro de base de dados: <?= h($error) ?></div>
            <?php endif; ?>

            <!-- Estilos Premium de Acessibilidade para o Modal Escuro -->
            <style>
            #user-modal label {
                color: #cbd5e1 !important; /* light slate */
                font-weight: 600;
            }
            #user-modal h3, #user-modal h2 {
                color: #38bdf8 !important; /* premium cyan */
            }
            #user-modal input, #user-modal select, #user-modal textarea {
                background-color: #1e293b !important; /* dark slate background */
                border: 1px solid #334155 !important;
                color: #ffffff !important;
                transition: all 0.3s ease;
            }
            #user-modal input:focus, #user-modal select:focus, #user-modal textarea:focus {
                border-color: #38bdf8 !important;
                box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2) !important;
                outline: none;
            }
            #user-modal input::placeholder {
                color: #64748b !important;
            }
            #user-modal select option {
                background-color: #0f172a !important; /* keep select dropdown options dark */
                color: #ffffff !important;
            }
            #user-modal .checkbox-group {
                border: 1px solid #334155 !important;
                border-radius: 8px !important;
                padding: 1.25rem !important;
                background: #1e293b !important;
                margin-top: 10px !important;
            }
            #user-modal .checkbox-group legend {
                background-color: #38bdf8 !important; /* Inverted background color: premium cyan pill */
                color: #0f172a !important; /* Deep dark slate text for absolute high contrast */
                font-weight: bold !important;
                padding: 4px 12px !important;
                border-radius: 999px !important;
                font-size: 0.9em !important;
                margin-left: 10px !important;
            }
            </style>

            <div id="user-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.65); align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);">
                <div style="background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; max-width: 650px; width: 100%; box-shadow: 0 15px 35px rgba(0,0,0,0.6); display: flex; flex-direction: column; overflow: hidden; max-height: 90vh;">
                    
                    <!-- Modal Header -->
                    <div style="padding: 20px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; background: #0b0f19;">
                        <h3 id="form-title" style="margin: 0; color: #38bdf8; font-size: 1.4em; font-weight: 700;">Novo Utilizador</h3>
                        <button type="button" id="btn-close-modal" style="background: none; border: none; color: #94a3b8; font-size: 1.8em; cursor: pointer; line-height: 1; transition: color 0.2s;" onmouseover="this.style.color='#f43f5e'" onmouseout="this.style.color='#94a3b8'">&times;</button>
                    </div>

                    <!-- Modal Body (Formulário) -->
                    <form method="POST" class="staff-form admin-form" id="user-form" action="../../actions/save_user.php" style="padding: 20px; display: flex; flex-direction: column; gap: 15px; overflow-y: auto; margin: 0;">
                        <?= get_csrf_input() ?>
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="user_id" id="form-user-id" value="">

                        <!-- Dados Gerais -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="form-name">Nome Completo *</label>
                                <input type="text" id="form-name" name="name" placeholder="Ex: João Silva" required>
                            </div>
                            <div class="form-group">
                                <label for="form-email">Email *</label>
                                <input type="email" id="form-email" name="email" placeholder="Ex: joao@fithub.pt" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="form-username">Nome de Utilizador *</label>
                                <input type="text" id="form-username" name="username" placeholder="Ex: joao_silva" required>
                                <small class="form-hint" style="color: #94a3b8; font-size: 0.8em; margin-top: 4px; display: block;">Apenas letras, números e sublinhados (_). Sem espaços.</small>
                            </div>
                            <div class="form-group">
                                <label for="form-password">Senha <span id="password-note">(obrigatória para novos)</span> *</label>
                                <input type="password" id="form-password" name="password" placeholder="Mínimo 6 caracteres">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="form-phone">Telefone</label>
                                <input type="tel" id="form-phone" name="phone" placeholder="Ex: 912345678">
                            </div>
                            <div class="form-group">
                                <label for="form-role">Função *</label>
                                <select id="form-role" name="role" required>
                                    <option value="">-- Selecione uma função --</option>
                                    <option value="member">Membro</option>
                                    <option value="trainer">Treinador</option>
                                    <option value="staff">Pessoal (Staff)</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>

                        <div id="trainer-fields-panel" style="display: none; border-top: 1px solid var(--color-border); padding-top: 1.5rem; margin-top: 1rem;">
                            <h3 style="color: var(--color-primary); margin-bottom: 1rem;">Perfil de Treinador</h3>
                            
                            <div class="form-group">
                                <fieldset class="checkbox-group">
                                    <legend>Especializações</legend>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 5px;">
                                        <label class="checkbox-item" for="spec-yoga" style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer;">
                                            <input type="checkbox" id="spec-yoga" name="specializations[]" value="yoga">
                                            <span>Yoga</span>
                                        </label>
                                        <label class="checkbox-item" for="spec-spinning" style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer;">
                                            <input type="checkbox" id="spec-spinning" name="specializations[]" value="spinning">
                                            <span>Spinning</span>
                                        </label>
                                        <label class="checkbox-item" for="spec-hiit" style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer;">
                                            <input type="checkbox" id="spec-hiit" name="specializations[]" value="hiit">
                                            <span>HIIT</span>
                                        </label>
                                        <label class="checkbox-item" for="spec-pilates" style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer;">
                                            <input type="checkbox" id="spec-pilates" name="specializations[]" value="pilates">
                                            <span>Pilates</span>
                                        </label>
                                        <label class="checkbox-item" for="spec-boxing" style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer;">
                                            <input type="checkbox" id="spec-boxing" name="specializations[]" value="boxing">
                                            <span>Boxe</span>
                                        </label>
                                        <label class="checkbox-item" for="spec-personal" style="display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer;">
                                            <input type="checkbox" id="spec-personal" name="specializations[]" value="personal">
                                            <span>Personal Training</span>
                                        </label>
                                    </div>
                                </fieldset>
                            </div>

                            <div class="form-group">
                                <label for="form-certifications">Certificações</label>
                                <textarea id="form-certifications" name="certifications" rows="2" placeholder="Ex: Cédula PROCAFD, Certificação Pilates Clínico..."></textarea>
                            </div>

                            <div class="form-group">
                                <label for="form-bio">Biografia</label>
                                <textarea id="form-bio" name="bio" rows="3" placeholder="Escreva uma breve biografia do treinador..."></textarea>
                            </div>
                        </div>

                        <!-- PAINEL DINÂMICO: Campos de Staff (Oculto por defeito) -->
                        <div id="staff-fields-panel" style="display: none; border-top: 1px solid var(--color-border); padding-top: 1.5rem; margin-top: 1rem;">
                            <h3 style="color: var(--color-primary); margin-bottom: 1rem;">Perfil Operacional (Staff)</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="form-position">Posição *</label>
                                    <select id="form-position" name="position">
                                        <option value="">-- Selecione uma posição --</option>
                                        <option value="front-desk-agent">Front Desk Agent</option>
                                        <option value="facilities-technician">Técnico de Manutenção</option>
                                        <option value="hygiene-assistant">Assistente de Limpeza</option>
                                        <option value="operations-coordinator">Coordenador Operacional</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="form-department">Departamento *</label>
                                    <select id="form-department" name="department">
                                        <option value="">-- Selecione um departamento --</option>
                                        <option value="member-support">Atendimento ao Cliente</option>
                                        <option value="operations">Operações</option>
                                        <option value="facilities">Infraestruturas</option>
                                        <option value="admin">Administração</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Nota informativa sobre restrições de Username -->
                        <div style="border-top: 1px solid #1e293b; padding-top: 12px; margin-top: 10px;">
                            <p style="color: #94a3b8; font-size: 0.8em; margin: 0; line-height: 1.4;">
                                <strong>Regra do Nome de Utilizador:</strong> Apenas pode conter letras, números e sublinhados (_). Espaços introduzidos são automaticamente convertidos em sublinhados.
                            </p>
                        </div>

                        <!-- Ações do Formulário -->
                        <div class="form-actions" style="border-top: 1px solid #1e293b; padding-top: 15px; margin-top: 10px; display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" class="btn btn-secondary" id="btn-cancel-form" style="flex: none; width: auto; padding: 0.5rem 1.5rem; margin: 0;">Cancelar</button>
                            <button type="submit" class="btn btn-primary" style="flex: none; width: auto; padding: 0.5rem 1.5rem; margin: 0;">Guardar Utilizador</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Listagem Geral de Utilizadores -->
            <section class="list-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <h2 style="margin: 0;">Todos os Utilizadores</h2>
                    <button type="button" class="btn btn-primary" id="btn-create-user" style="padding: 0.6rem 1.25rem; font-size: 0.9em; margin: 0; display: none; align-items: center; gap: 6px;">➕ Novo Utilizador</button>
                </div>
                <?php if (empty($users)): ?>
                    <div class="alert alert--info">
                        <p>Nenhum utilizador encontrado na base de dados.</p>
                    </div>
                <?php else: ?>
                    <div class="staff-table-wrapper">
                        <table class="admin-users-table">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email / Telemóvel</th>
                                    <th>Utilizador</th>
                                    <th>Função</th>
                                    <th>Status</th>
                                    <th style="text-align: center;">Ações</th>
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
                                    <tr class="user-row" data-user-id="<?= $user_id ?>" data-user-role="<?= $user['role'] ?>">
                                        <td class="col-name"><?= h($user['name']) ?></td>
                                        <td class="col-email">
                                            <div><?= h($user['email']) ?></div>
                                            <?php if (!empty($user['phone'])): ?>
                                                <small style="color: var(--color-text-muted); font-size: 0.8em;">📞 <?= h($user['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-username"><?= h($user['username'] ?? 'N/D') ?></td>
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
                                        <td class="col-actions" style="text-align: center;">
                                            <!-- Botão de Edição com toda a metadata necessária encapsulada em data attributes -->
                                            <button type="button" class="btn-action btn-edit" 
                                                    data-user-id="<?= $user_id ?>"
                                                    data-name="<?= h($user['name']) ?>"
                                                    data-email="<?= h($user['email']) ?>"
                                                    data-username="<?= h($user['username'] ?? '') ?>"
                                                    data-role="<?= $user['role'] ?>"
                                                    data-phone="<?= h($user['phone'] ?? '') ?>"
                                                    data-bio="<?= h($user['bio'] ?? '') ?>"
                                                    data-certifications="<?= h($user['certifications'] ?? '') ?>"
                                                    data-specializations="<?= h($user['specializations'] ?? '') ?>"
                                                    data-position="<?= h($user['position'] ?? '') ?>"
                                                    data-department="<?= h($user['department'] ?? '') ?>"
                                                    title="Editar utilizador">
                                                ✏️ Editar
                                            </button>
                                            <?php if ($user_id !== (int) $_SESSION['user_id']): ?>
                                                <button type="button" class="btn-action btn-toggle-status" data-user-id="<?= $user_id ?>" data-current-status="<?= $is_active ?>" title="<?= $is_active ? 'Desativar' : 'Ativar' ?> utilizador">
                                                    <?= $is_active ? '🔒 Desativar' : '🔓 Ativar' ?>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-action btn-toggle-status" disabled style="opacity: 0.5; cursor: not-allowed;" title="Não pode desativar a sua própria conta de administrador">
                                                    🔒 Desativar
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
    </section>
</main>

<script src="<?= BASE_URL_PATH ?>js/admin-users.js"></script>

<?php 
require_once BASE_PATH . '/templates/footer.php'; 
?>
