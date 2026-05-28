<?php
/**
 * actions/access_nutrition.php
 *
 * Redirects the user to the nutrition tab of their profile if logged in,
 * otherwise sets a flash message and redirects to the login page with a return URL.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/utils.php';

if (isset($_SESSION['user_id'])) {
    redirect('/pages/profile.php#nutricao');
} else {
    set_flash('Faça Login para pedir o seu Plano!', 'error');
    redirect('/pages/login.php?redirect_to=/pages/profile.php%23nutricao');
}
