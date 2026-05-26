<?php
/**
 * actions/logout_action.php
 *
 * Termina a sessão do utilizador ativo, limpando cookies e destruindo dados da sessão.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/utils.php';

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

session_start();
set_flash('Sessão terminada. Volte sempre!', 'info');

redirect('/index.php');
