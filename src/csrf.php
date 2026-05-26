<?php

declare(strict_types=1);

const CSRF_SESSION_KEY = '_csrf_token';

const CSRF_FIELD_NAME  = 'csrf_token';


/**
 * Gera (ou reutiliza) um token CSRF seguro para a sessão atual.
 *
 * - Usa random_bytes(32) → 256 bits de entropia criptográfica.
 * - Gera um novo token apenas se não existir um na sessão.
 *   (Um token por sessão é suficiente e mais simples para SPAs/AJAX.)
 *
 * @return string  Token hexadecimal de 64 caracteres.
 * @throws RuntimeException Se não for possível gerar bytes aleatórios.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_SESSION_KEY];
}


/**
 * Verifica se o token enviado pelo cliente corresponde ao da sessão.
 *
 * Usa hash_equals() para comparação em tempo constante,
 * prevenindo ataques de timing (timing attacks).
 *
 * @param string|null $submitted_token  Token recebido via POST ou cabeçalho AJAX.
 * @return bool  true se o token for válido, false caso contrário.
 */
function validate_csrf_token(?string $submitted_token): bool
{
    if (empty($_SESSION[CSRF_SESSION_KEY]) || empty($submitted_token)) {
        return false;
    }

    return hash_equals($_SESSION[CSRF_SESSION_KEY], $submitted_token);
}

/**
 * Valida o token CSRF e termina o request imediatamente se for inválido.
 * Atalho conveniente para usar no início dos scripts de action (POST).
 *
 * Exemplo:
 *   require_once __DIR__ . '/../src/csrf.php';
 *   csrf_protect();
 *
 * @param string|null $token  Token a validar. Por defeito lê $_POST['csrf_token'].
 * @return void
 */
function csrf_protect(?string $token = null): void
{
    $token ??= $_POST[CSRF_FIELD_NAME] ?? null;

    if (!validate_csrf_token($token)) {
        http_response_code(403);
        // Em produção, logar a tentativa suspeita.
        error_log('[CSRF] Token inválido. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die('Pedido inválido: token CSRF ausente ou incorreto.');
    }
}

/**
 * Retorna o HTML do campo hidden para inserir em formulários.
 *
 * Gera o token se ainda não existir na sessão.
 * O valor é escapado com htmlspecialchars por segurança extra.
 *
 * @return string  HTML: <input type="hidden" name="csrf_token" value="...">
 */
function get_csrf_input(): string
{
    $token = generate_csrf_token();

    return '<input type="hidden" name="' . CSRF_FIELD_NAME . '" value="'
        . htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Retorna o token CSRF atual (sem o HTML do input).
 * Útil para pedidos AJAX onde o token é enviado num header HTTP:
 *
 *   fetch('/api/endpoint.php', {
 *     method: 'POST',
 *     headers: { 'X-CSRF-Token': '<?= get_csrf_token() ?>' },
 *     body: JSON.stringify(data)
 *   });
 * @return string
 */
function get_csrf_token(): string
{
    return generate_csrf_token();
}
