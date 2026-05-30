<?php

if (!isset($active_page)) {
    $current_script = basename($_SERVER['PHP_SELF'], '.php');
    $active_page = match($current_script) {
        'index'        => 'home',
        'schedules'    => 'schedule',
        'equipment'    => 'equipment',
        'trainers'     => 'trainers',
        'trainer_schedule' => 'trainer',
        'admin-staff'  => 'admin',
        'dashboard'    => 'dashboard',
        'profile'      => 'profile',
        default        => ''
    };
}

$nav_class = fn(string $id): string => $active_page === $id ? ' active' : '';

$base = defined('BASE_URL_PATH') ? BASE_URL_PATH : '';
?>
<header class="header">
    <nav class="navbar" aria-label="Navegação principal">
        <div class="navbar-container">
            <button type="button" class="menu-toggle" aria-label="Abrir menu" title="Menu">☰</button>

            <div class="navbar-logo">
                <a href="<?= h($base) ?>index.php">
                    <span class="site-brand">FitHub</span>
                </a>
            </div>

            <ul class="navbar-menu">
                <li>
                    <a href="<?= h($base) ?>index.php"
                       class="nav-link<?= $nav_class('home') ?>">Início</a>
                </li>
                <li>
                    <a href="<?= h($base) ?>pages/schedules.php"
                       class="nav-link<?= $nav_class('schedule') ?>">Aulas</a>
                </li>
                <li>
                    <a href="<?= h($base) ?>pages/equipment.php"
                       class="nav-link<?= $nav_class('equipment') ?>">Equipamentos</a>
                </li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>
                        <a href="<?= h($base) ?>pages/profile.php"
                           class="nav-link<?= $nav_class('profile') ?>">Perfil</a>
                    </li>

                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'trainer'): ?>
                        <li>
                            <a href="<?= h($base) ?>pages/trainer_schedule.php"
                               class="nav-link<?= $nav_class('trainer') ?>">Meu Horário</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li>
                            <a href="<?= h($base) ?>pages/admin/users.php"
                               class="nav-link<?= $nav_class('admin') ?>">Gestão</a>
                        </li>
                    <?php endif; ?>

                    <li>
                        <a href="<?= h($base) ?>actions/logout_action.php"
                           class="nav-link">Sair</a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="<?= h($base) ?>pages/trainers.php"
                           class="nav-link<?= $nav_class('trainers') ?>">Treinadores</a>
                    </li>
                    <li>
                        <a href="<?= h($base) ?>pages/login.php"
                           class="nav-link">Entrar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>
