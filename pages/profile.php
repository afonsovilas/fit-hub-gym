<?php
/**
 * pages/profile.php
 *
 * Página de perfil do utilizador logado.
 * Atua como um controlador que despacha para o layout específico do role do utilizador.
 */

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('BASE_URL_PATH')) {
    define('BASE_URL_PATH', '../');
}

require_once BASE_PATH . '/src/utils.php';
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

    // Dispatch based on user role
    if ($user['role'] === 'member') {
        require_once BASE_PATH . '/templates/profile/profile_member.php';
    } elseif ($user['role'] === 'trainer') {
        require_once BASE_PATH . '/templates/profile/profile_trainer.php';
    } elseif ($user['role'] === 'admin') {
        require_once BASE_PATH . '/templates/profile/profile_admin.php';
    } else {
        throw new Exception("Tipo de utilizador inválido.");
    }

} catch (Throwable $e) {
    $error = $e->getMessage();
    
    // In case of error before including templates, render base error page
    $page_title  = 'Meu Perfil';
    $body_class  = 'profile-page';
    $active_page = 'profile';

    require_once BASE_PATH . '/templates/header.php';
    require_once BASE_PATH . '/templates/navbar.php';
    ?>
    <main>
        <section class="page-header">
            <div class="container">
                <h1>Meu Perfil</h1>
                <p>Ocorreu um erro ao carregar o seu perfil.</p>
            </div>
        </section>
        <div class="container">
            <?php render_flash(); ?>
            <div class="flash flash--error">Erro na base de dados: <?= h($error) ?></div>
        </div>
    </main>
    <?php
    require_once BASE_PATH . '/templates/footer.php';
}
?>
