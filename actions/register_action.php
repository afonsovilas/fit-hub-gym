<?php
/**
 * actions/register_action.php
 *
 * Processa a criação (registo) de novos utilizadores da plataforma (Membros).
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/utils.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

csrf_protect();

try {
    $db = get_db();

    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password-confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        set_flash('Por favor, preencha todos os campos obrigatórios.', 'error');
        redirect('/pages/register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('O endereço de email introduzido não é válido.', 'error');
        redirect('/pages/register.php');
    }

    if (strlen($password) < 6) {
        set_flash('A palavra-passe deve conter pelo menos 6 caracteres.', 'error');
        redirect('/pages/register.php');
    }

    if ($password !== $password_confirm) {
        set_flash('A confirmação da palavra-passe não coincide.', 'error');
        redirect('/pages/register.php');
    }

    $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        set_flash('Este endereço de email já se encontra registado.', 'error');
        redirect('/pages/register.php');
    }

    // Gerar um username padrão único a partir do nome
    $base_username = strtolower(str_replace(' ', '', $name));
    $base_username = preg_replace('/[^a-z0-9]/', '', $base_username); // manter apenas letras e números
    if (strlen($base_username) === 0) {
        $base_username = 'user';
    }
    $username = $base_username;
    $count = 0;
    
    $stmtCheckUsername = $db->prepare("SELECT id FROM users WHERE username = ?");
    while (true) {
        $stmtCheckUsername->execute([$username]);
        if (!$stmtCheckUsername->fetch()) {
            break;
        }
        $count++;
        $username = $base_username . $count;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $stmtInsert = $db->prepare("
        INSERT INTO users (name, email, username, password_hash, role, phone, is_active)
        VALUES (?, ?, ?, ?, 'member', ?, 1)
    ");
    $stmtInsert->execute([$name, $email, $username, $password_hash, $phone]);

    set_flash('Conta criada com sucesso! Já pode iniciar a sua sessão.', 'success');
    redirect('/pages/login.php');

} catch (Throwable $e) {
    error_log('[DB REGISTER ERROR] ' . $e->getMessage());
    set_flash('Erro interno ao criar conta: ' . $e->getMessage(), 'error');
    redirect('/pages/register.php');
}
