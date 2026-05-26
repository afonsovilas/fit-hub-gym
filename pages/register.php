<?php
/**
 * pages/register.php
 *
 * Página de registo de novos membros no FitHub.
 */

declare(strict_types=1);

define('BASE_PATH',     dirname(__DIR__));
define('BASE_URL_PATH', '../');

$page_title  = 'Registar-se';
$body_class  = 'register-page';

require_once BASE_PATH . '/templates/header.php';
require_once BASE_PATH . '/templates/navbar.php';
require_once BASE_PATH . '/src/csrf.php';

if (isset($_SESSION['user_id'])) {
    redirect('/pages/profile.php');
}
?>

<main style="display: flex; align-items: center; justify-content: center; min-height: 80vh; padding: 40px 20px; background: #090d16;">
    <div style="max-width: 500px; width: 100%; padding: 30px; border-radius: 12px; background: #0f172a; border: 1px solid #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        
        <h2 style="text-align: center; color: #38bdf8; margin-top: 0; margin-bottom: 8px; font-size: 1.8em; font-weight: bold;">Criar Conta no FitHub</h2>
        <p style="text-align: center; color: #94a3b8; margin-bottom: 24px; font-size: 0.95em;">Registe-se em poucos segundos e comece a treinar</p>
        
        <?php render_flash(); ?>
        
        <form action="../actions/register_action.php" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
            <?= get_csrf_input() ?>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="name" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Nome Completo:</label>
                <input type="text" id="name" name="name" required placeholder="O seu nome" 
                       style="width: 100%; padding: 11px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="email" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Endereço de Email:</label>
                <input type="email" id="email" name="email" required placeholder="exemplo@fithub.pt" 
                       style="width: 100%; padding: 11px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="phone" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Contacto Telefónico (opcional):</label>
                <input type="tel" id="phone" name="phone" placeholder="+351 9XX XXX XXX" 
                       style="width: 100%; padding: 11px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="password" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Palavra-passe (mín. 6 caracteres):</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" 
                       style="width: 100%; padding: 11px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="password-confirm" style="font-weight: 600; color: #e2e8f0; font-size: 0.95em;">Confirmar Palavra-passe:</label>
                <input type="password" id="password-confirm" name="password-confirm" required placeholder="••••••••" 
                       style="width: 100%; padding: 11px; border-radius: 6px; background: #1e293b; border: 1px solid #334155; color: #fff; font-size: 1em; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='#38bdf8'" onblur="this.style.borderColor='#334155'">
            </div>
            
            <button type="submit" class="btn btn-primary" 
                    style="width: 100%; padding: 13px; font-size: 1.05em; font-weight: bold; border-radius: 6px; cursor: pointer; border: none; margin-top: 10px; transition: background 0.2s;">
                Criar Conta
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 24px; color: #94a3b8; font-size: 0.95em;">
            Já tem uma conta ativa? <a href="login.php" style="color: #38bdf8; text-decoration: none; font-weight: 600; transition: color 0.2s;" onmouseover="this.style.color='#0ea5e9'" onmouseout="this.style.color='#38bdf8'">Inicie sessão aqui</a>
        </div>
    </div>
</main>

<?php
require_once BASE_PATH . '/templates/footer.php';
?>
