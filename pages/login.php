<?php
/**
 * pages/login.php
 *
 * Página de login/autenticação dos utilizadores do FitHub.
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Entrar';
$body_class  = 'login-page';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';

if (isset($_SESSION['user_id'])) {
    $redirect_to = trim($_GET['redirect_to'] ?? '');
    if ($redirect_to !== '' && strpos($redirect_to, '/') === 0 && strpos($redirect_to, '//') !== 0) {
        redirect($redirect_to);
    } else {
        redirect('/pages/profile.php');
    }
}
?>

<main style="display: flex; align-items: center; justify-content: center; min-height: 70vh; padding: 40px 20px; background: #090d16;">
    <div style="max-width: 450px; width: 100%; padding: 30px; border-radius: 12px; background: #0f172a; border: 1px solid #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        
        <h2 style="text-align: center; color: #38bdf8; margin-top: 0; margin-bottom: 8px; font-size: 1.8em; font-weight: bold;">Entrar no FitHub</h2>
        <p style="text-align: center; color: #94a3b8; margin-bottom: 24px; font-size: 0.95em;">Insira as suas credenciais para aceder ao seu painel</p>
        
        <?php render_flash(); ?>
        
        <form action="../actions/login_action.php" method="POST" style="display: flex; flex-direction: column; gap: 18px;">
            <?= get_csrf_input() ?>
            <?php
            $redirect_to = $_GET['redirect_to'] ?? '';
            if ($redirect_to !== '' && strpos($redirect_to, '/') === 0 && strpos($redirect_to, '//') !== 0):
            ?>
                <input type="hidden" name="redirect_to" value="<?= h($redirect_to) ?>">
            <?php endif; ?>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="email" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Endereço de Email:</label>
                <input type="email" id="email" name="email" required placeholder="exemplo@fithub.pt" 
                       style="width: 100%; padding: 12px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="password" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Palavra-passe:</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" 
                       style="width: 100%; padding: 12px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>
            
            <button type="submit" class="btn btn-primary" 
                    style="width: 100%; padding: 14px; font-size: 1.05em; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; margin-top: 10px; transition: background 0.2s;">
                Iniciar Sessão
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 24px; color: #94a3b8; font-size: 0.95em;">
            Ainda não tem conta? <a href="register.php" style="color: #38bdf8; text-decoration: none; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='#0ea5e9'" onmouseout="this.style.color='#38bdf8'">Registe-se aqui</a>
        </div>
        
        <div style="margin-top: 24px; padding: 14px; background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.15); border-radius: 8px; font-size: 0.85em; color: #38bdf8; line-height: 1.5;">
            <strong>💡 Dica de Teste (Contas Piloto):</strong><br>
            • <strong>Membro:</strong> <code>aluno@fithub.pt</code><br>
            • <strong>Treinador:</strong> <code>joao@fithub.pt</code><br>
            • <strong>Administrador:</strong> <code>admin@fithub.pt</code><br>
            • <strong>Palavra-passe de todas:</strong> <code>123456</code>
        </div>
    </div>
</main>

<?php
require_once BASE_PATH . '/templates/footer.php';
?>
