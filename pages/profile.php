<?php
/**
 * pages/profile.php
 *
 * Página de perfil do utilizador logado.
 * Integrada com a base de dados SQLite (FitHub).
 * 
*/

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Meu Perfil';
$body_class  = 'profile-page';
$active_page = 'profile';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';
require_once BASE_PATH . '/database/connection.php';

if (!isset($_SESSION['user_id'])) {
    set_flash('Inicie sessão para aceder ao seu perfil.', 'error');
    redirect('/pages/login.php');
}

try {
    $db = get_db();

    $user_id = $_SESSION['user_id'];

    $stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch();

    if (!$user) {
        throw new Exception("Utilizador não encontrado na base de dados.");
    }

    $stmtMyClasses = $db->prepare("
        SELECT c.*, u.name AS instructor_name 
        FROM bookings b
        JOIN classes c ON b.class_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE b.user_id = ?
        ORDER BY c.scheduled_at ASC
    ");
    $stmtMyClasses->execute([$user_id]);
    $myClasses = $stmtMyClasses->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Meu Perfil</h1>
            <p>Gerencie suas informações pessoais e consulte suas marcações de aulas</p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>

        <?php if (isset($error)): ?>
            <div class="flash flash--error">Erro na base de dados: <?= h($error) ?></div>
        <?php else: ?>
            <div class="profile-wrapper">
                
                <aside class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-header">
                            <img src="<?= h($user['avatar'] ?? '../assets/images/profile-placeholder.png') ?>" 
                                 alt="Foto de perfil de <?= h($user['name']) ?>" 
                                 class="profile-photo"
                                 onerror="this.src='../assets/images/profile-placeholder.png';">
                            <div class="profile-info">
                                <h2 class="profile-name"><?= h($user['name']) ?></h2>
                                <p class="profile-username">@<?= h(strtolower(str_replace(' ', '', $user['name']))) ?></p>
                            </div>
                        </div>
                        <div class="profile-details">
                            <p class="profile-detail">
                                <span class="detail-label">Email:</span>
                                <span><?= h($user['email']) ?></span>
                            </p>
                            <p class="profile-detail">
                                <span class="detail-label">Membro desde:</span>
                                <span><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                            </p>
                            <p class="profile-detail">
                                <span class="detail-label">Função:</span>
                                <span class="badge badge-<?= h($user['role']) ?>"><?= h($user['role']) ?></span>
                            </p>
                            <p class="profile-detail">
                                <span class="detail-label">Status:</span>
                                <span class="status-badge status-active"><?= $user['is_active'] ? 'Ativo' : 'Inativo' ?></span>
                            </p>
                        </div>
                    </div>
                </aside>

                <section class="profile-main">
                    <div class="profile-section">
                        <div class="profile-section-header">
                            <h3>Editar Perfil (Base de Dados)</h3>
                            <button type="button" class="btn btn-primary btn-small" id="edit-profile-btn">Editar</button>
                        </div>
                        <form class="profile-form" id="profile-form" method="POST" action="../actions/update_profile.php">
                            <?= get_csrf_input() ?>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full-name">Nome Completo:</label>
                                    <input type="text" id="full-name" name="full-name" value="<?= h($user['name']) ?>" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="username">Nome de Utilizador (estático):</label>
                                    <input type="text" id="username" name="username" value="<?= h(strtolower(str_replace(' ', '', $user['name']))) ?>" disabled required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" id="email" name="email" value="<?= h($user['email']) ?>" disabled required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Telefone:</label>
                                    <input type="tel" id="phone" name="phone" value="<?= h($user['phone'] ?? '+351 900 000 000') ?>" disabled>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">Nova Senha (deixe em branco para não alterar):</label>
                                <input type="password" id="password" name="password" placeholder="••••••••" disabled>
                            </div>

                            <div class="form-group">
                                <label for="password-confirm">Confirmar Senha:</label>
                                <input type="password" id="password-confirm" name="password-confirm" placeholder="••••••••" disabled>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="save-btn" style="display: none;">Guardar Alterações</button>
                                <button type="reset" class="btn btn-secondary btn-small" id="cancel-btn" style="display: none;">Cancelar</button>
                            </div>
                        </form>
                    </div>

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
                                        <form method="POST" action="../actions/cancel_booking.php" onsubmit="return confirm('Deseja realmente cancelar a sua inscrição na aula <?= h($c['name']) ?>?');" style="margin: 0; padding: 0; display: inline;">
                                            <?= get_csrf_input() ?>
                                            <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-small btn-cancel" style="cursor: pointer;">Cancelar Inscrição</button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php 
$extra_js = ['js/profile.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
