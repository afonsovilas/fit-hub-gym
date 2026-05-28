<?php

$_utils_path = defined('BASE_PATH')
    ? BASE_PATH . '/src/utils.php'
    : __DIR__ . '/../src/utils.php';

require_once $_utils_path;
unset($_utils_path);

$page_title ??= 'FitHub';
$body_class ??= '';

if (isset($_SESSION['user_id'])) {
    $body_class = trim($body_class . ' logged-in');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FitHub — Plataforma de gestão de ginásio moderna e intuitiva.">
    <title><?= h($page_title) ?> — FitHub</title>

    <?php
    $css_path = defined('BASE_URL_PATH') ? BASE_URL_PATH . 'css/style.css' : 'css/style.css';
    ?>
    <link rel="stylesheet" href="<?= h($css_path) ?>">
</head>
<body class="<?= h($body_class) ?>">
