<?php


define('BASE_PATH',     __DIR__);
define('BASE_URL_PATH', '');

$page_title  = 'Início';
$body_class  = 'home-page';
$active_page = 'home';

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/navbar.php';
?>

<main>

    <!-- ── Hero ──────────────────────────────────────────────────────────── -->
    <section class="hero-large">
        <div class="hero-image-wrapper">
            <img src="assets/images/home_banner.jpg" alt="FitHub - Ginásio Moderno">
            <div class="hero-overlay">
                <div class="hero-text">
                    <h1>FitHub</h1>
                    <p class="hero-subtitle">O seu espaço de transformação e bem-estar</p>
                </div>
            </div>
        </div>
    </section>

    <?php render_flash(); ?>

    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Bem-vindo ao FitHub</h2>
                    <p>O FitHub é um ginásio moderno e completo, equipado com os melhores aparelhos de treino e uma equipa de treinadores certificados. Oferecemos um ambiente acolhedor e profissional para alcançar os seus objetivos de fitness.</p>
                    <p>Temos à sua disposição aulas variadas, treinadores especializados e um espaço pensado para o seu conforto e progresso.</p>
                    <a href="pages/schedules.php" class="btn btn-primary btn-large">Explorar Aulas</a>
                </div>
                <div class="about-stats">
                    <div class="stat-box">
                        <h3>150+</h3>
                        <p>Membros</p>
                    </div>
                    <div class="stat-box">
                        <h3>20+</h3>
                        <p>Aulas</p>
                    </div>
                    <div class="stat-box">
                        <h3>15+</h3>
                        <p>Treinadores</p>
                    </div>
                    <div class="stat-box">
                        <h3>50+</h3>
                        <p>Equipamentos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Acesso Rápido ─────────────────────────────────────────────────── -->
    <section class="quick-access">
        <div class="container">
            <div class="quick-access-header">
                <h2>Acesso Rápido</h2>
                <a href="pages/register.php" class="btn btn-signup">Inscrever-se no FitHub</a>
            </div>
            <div class="quick-grid">
                <a href="pages/schedules.php" class="quick-card">
                    <div class="quick-icon">📅</div>
                    <h3>Aulas em Grupo</h3>
                    <p>Consulte o horário e inscreva-se</p>
                </a>
                <a href="pages/trainers.php" class="quick-card">
                    <div class="quick-icon">👤</div>
                    <h3>Treinadores</h3>
                    <p>Conheça os nossos especialistas</p>
                </a>
                <a href="actions/access_nutrition.php" class="quick-card">
                    <div class="quick-icon">🍎</div>
                    <h3>Planos de Nutrição</h3>
                    <p>Solicite o seu plano personalizado</p>
                </a>
            </div>
        </div>
    </section>

    <section class="opening-hours">
        <div class="container">
            <div class="hours-content">
                <div class="hours-header">
                    <h2>Horário de Funcionamento</h2>
                    <p>Visite-nos nos horários abaixo</p>
                </div>
                <div class="hours-grid">
                    <div class="hours-card">
                        <h3>Segunda a Sexta</h3>
                        <p class="time">06:00 - 23:00</p>
                        <p class="description">Acesso completo a todas as instalações</p>
                    </div>
                    <div class="hours-card">
                        <h3>Sábado</h3>
                        <p class="time">07:00 - 22:00</p>
                        <p class="description">Aulas e treino disponíveis</p>
                    </div>
                    <div class="hours-card">
                        <h3>Domingo</h3>
                        <p class="time">08:00 - 21:00</p>
                        <p class="description">Treino livre e aulas especiais</p>
                    </div>
                    <div class="hours-card holiday">
                        <h3>Feriados</h3>
                        <p class="time">09:00 - 20:00</p>
                        <p class="description">Horário reduzido</p>
                    </div>
                </div>
                <div class="contact-info">
                    <p><strong>Telefone:</strong> +351 939 939 939</p>
                    <p><strong>Email:</strong> info@fithub.pt</p>
                </div>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
