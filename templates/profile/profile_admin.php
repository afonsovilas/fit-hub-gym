<?php
/**
 * templates/profile/profile_admin.php
 *
 * Página de perfil com visualizações e abas exclusivas para Administradores (Admins).
 */

declare(strict_types=1);

// Ensure user is admin
if ($user['role'] !== 'admin') {
    redirect('/pages/profile.php');
}

$page_title = 'Meu Perfil';
$body_class = 'profile-page';
$active_page = 'profile';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
?>

<main>
    <section class="page-header">
        <div class="container">
            <h1>Meu Perfil</h1>
            <p>Gerencie suas informações de administrador do sistema</p>
        </div>
    </section>

    <div class="container">
        <?php render_flash(); ?>

        <div class="profile-wrapper">
            <?php require BASE_PATH . '/templates/profile/shared_sidebar.php'; ?>

            <section class="profile-main">
                <!-- Tab: Dados Pessoais (Admin only has personal data tab) -->
                <?php require BASE_PATH . '/templates/profile/shared_personal_data.php'; ?>
            </section>
        </div>
    </div>
</main>

<?php 
require_once BASE_PATH . '/templates/profile/shared_modals.php';
$extra_js = ['js/profile.js'];
require_once BASE_PATH . '/templates/footer.php'; 
?>
