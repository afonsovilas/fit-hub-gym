<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

start_session();

/**
 * Usar SEMPRE ao imprimir dados vindos da base de dados ou do utilizador.
 *
 * Alias curto: h() — para usar diretamente no HTML: <?= h($user['name']) ?>
 *
 * @param mixed $data  O valor a escapar (será convertido para string).
 * @return string      O valor seguro para inserir em HTML.
 */
function sanitize_output(mixed $data): string
{
    return htmlspecialchars((string) $data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


function h(mixed $data): string
{
    return sanitize_output($data);
}

/**
 * Define uma flash message que persiste até ser lida UMA VEZ.
 * Ideal para mostrar alertas após redirects (Post-Redirect-Get pattern).
 *
 * @param string $message  O texto da mensagem.
 * @param string $type     Tipo visual: 'success' | 'error' | 'warning' | 'info'
 */
function set_flash(string $message, string $type = 'info'): void
{
    $_SESSION['_flash'] = [
        'message' => $message,
        'type'    => $type,
    ];
}

/**
 * Obtém e apaga a flash message da sessão.
 * Retorna null se não existir nenhuma mensagem pendente.
 *
 * @return array{message: string, type: string}|null
 */
function get_flash(): ?array
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }

    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    return $flash;
}

function render_flash(): void
{
    $flash = get_flash();
    if ($flash === null) {
        return;
    }

    $msg = json_encode($flash['message'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $type = json_encode($flash['type']);

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast($msg, $type);
            }
        });
    </script>";
}

/**
 * Redireciona para um URL e termina o script imediatamente.
 *
 * SEGURANÇA: Valida que o URL é interno (começa com '/') ou é um caminho
 * relativo, prevenindo Open Redirect para domínios externos.
 *
 * @param string $url  URL de destino (ex: '/pages/dashboard.php').
 * @return never       Esta função nunca retorna.
 */
function redirect(string $url): never
{

    if (preg_match('#^(https?:)?//#i', $url)) {
        $url = '/';
    }

    header('Location: ' . $url);
    exit;
}
