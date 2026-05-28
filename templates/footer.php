<?php

$extra_js ??= [];
$base = defined('BASE_URL_PATH') ? BASE_URL_PATH : '';
?>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>FitHub</h4>
                    <p>Plataforma de gestão de ginásio moderna e intuitiva.</p>
                </div>
                <div class="footer-section">
                    <h4>Ligações Rápidas</h4>
                    <ul>
                        <li><a href="<?= h($base) ?>index.php">Início</a></li>
                        <li><a href="<?= h($base) ?>pages/schedules.php">Aulas</a></li>
                        <li><a href="<?= h($base) ?>pages/trainers.php">Treinadores</a></li>
                        <li><a href="<?= h($base) ?>pages/equipment.php">Equipamentos</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contacto</h4>
                    <p>Email: info@fithub.pt</p>
                    <p>Telefone: +351 939 939 939</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> FitHub. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="<?= h($base) ?>js/toast.js"></script>
    <script src="<?= h($base) ?>js/navbar-scroll.js"></script>

    <?php foreach ($extra_js as $js_file): ?>
        <script src="<?= h($base . $js_file) ?>"></script>
    <?php endforeach; ?>

</body>
</html>
