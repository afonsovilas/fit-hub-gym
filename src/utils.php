<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Lisbon');

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

/**
 * Valida se um determinado horário está em intervalos de 15 minutos
 * e dentro das horas de abertura/fecho do ginásio no respetivo dia.
 *
 * @param string $datetime_str  Data e hora a validar (ex: Y-m-d H:i ou Y-m-d\TH:i).
 * @return array{success: bool, message?: string}
 */
function validate_schedule_time(string $datetime_str, int $duration_min = 0): array
{
    $timestamp = strtotime($datetime_str);
    if ($timestamp === false) {
        return ['success' => false, 'message' => 'Data e hora inválidas.'];
    }
    
    // Validar intervalos de 15 minutos
    $minute = (int) date('i', $timestamp);
    if ($minute % 15 !== 0) {
        return [
            'success' => false, 
            'message' => 'O horário deve ser agendado em intervalos de 15 minutos (ex: :00, :15, :30, :45).'
        ];
    }
    
    // Determinar se é feriado em Portugal
    $day_month = date('m-d', $timestamp);
    $year = (int) date('Y', $timestamp);
    
    // Feriados Nacionais Fixos
    $fixed_holidays = [
        '01-01', 
        '04-25', 
        '05-01', 
        '06-10',
        '08-15', 
        '10-05', 
        '11-01', 
        '12-01', 
        '12-08', 
        '12-25', 
    ];
    
    $is_holiday = in_array($day_month, $fixed_holidays);
    if (!$is_holiday) {
        // Feriados Nacionais Móveis (Páscoa, Sexta-feira Santa, Corpo de Deus)
        $easter_time = easter_date($year);
        $easter_dt = new DateTime();
        $easter_dt->setTimestamp($easter_time);
        $easter_dt->setTime(0, 0, 0);
        
        $good_friday = clone $easter_dt;
        $good_friday->modify('-2 days');
        
        $corpus_christi = clone $easter_dt;
        $corpus_christi->modify('+60 days');
        
        $check_date = date('Y-m-d', $timestamp);
        if ($check_date === $easter_dt->format('Y-m-d') || 
            $check_date === $good_friday->format('Y-m-d') || 
            $check_date === $corpus_christi->format('Y-m-d')) {
            $is_holiday = true;
        }
    }
    
    // Obter limites de horário com base no dia
    $day_of_week = (int) date('w', $timestamp); // 0 = Domingo, 6 = Sábado, 1-5 = Segunda a Sexta
    $time_str = date('H:i', $timestamp);
    
    if ($day_of_week === 0) {
        return [
            'success' => false,
            'message' => 'O FitHub encontra-se encerrado aos Domingos. Por favor, selecione outro dia (Segunda a Sábado).'
        ];
    }

    if ($is_holiday) {
        $open = '09:00';
        $close = '20:00';
        $day_name = 'Feriado';
    } elseif ($day_of_week === 6) {
        $open = '07:00';
        $close = '22:00';
        $day_name = 'Sábado';
    } else {
        $open = '06:00';
        $close = '23:00';
        $day_name = 'Segunda a Sexta';
    }
    
    if ($time_str < $open || $time_str > $close) {
        return [
            'success' => false, 
            'message' => "O horário de funcionamento ao $day_name é das $open às $close. A hora de início ($time_str) não pode ser anterior à de abertura nem posterior à de fecho."
        ];
    }

    // Validar se a sessão termina antes do fecho do ginásio
    if ($duration_min > 0) {
        $end_timestamp = $timestamp + ($duration_min * 60);
        $date_str = date('Y-m-d', $timestamp);
        $close_timestamp = strtotime("$date_str $close");
        if ($end_timestamp > $close_timestamp) {
            $max_start_time = date('H:i', $close_timestamp - ($duration_min * 60));
            return [
                'success' => false,
                'message' => "Para uma sessão de $duration_min minutos, o horário de início não pode ser posterior a $max_start_time (fecho do ginásio às $close)."
            ];
        }
    }
    
    return ['success' => true];
}
