<?php
// ===================================================
// 設定ファイル - インストール後に自動生成されます
// ===================================================

$config_file = __DIR__ . '/config.json';

// 未インストールの場合はインストーラーへ
if (!file_exists($config_file)) {
    if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
        header('Location: ./install.php');
        exit;
    }
    return;
}

$config = json_decode(file_get_contents($config_file), true);

define('ADMIN_USER', $config['admin_user'] ?? 'admin');
define('ADMIN_PASS', $config['admin_pass'] ?? '');
define('SMTP_HOST',  $config['smtp_host']  ?? '');
define('SMTP_USER',  $config['smtp_user']  ?? '');
define('SMTP_PASS',  $config['smtp_pass']  ?? '');
define('SMTP_PORT',  $config['smtp_port']  ?? 587);

define('DATA_DIR',   dirname(__DIR__) . '/data/');
define('NEWS_DIR',   DATA_DIR . 'news/');
define('PAGES_DIR',  DATA_DIR . 'pages/');
define('TRASH_DIR',  DATA_DIR . 'trash/');
if (!is_dir(TRASH_DIR)) mkdir(TRASH_DIR, 0755, true);

session_start();

if (!function_exists('mb_strtohiragana')) {
    function mb_strtohiragana($str) {
        return mb_convert_kana($str, 'c', 'UTF-8');
    }
}

function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ./index.php');
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('不正なリクエストです。');
    }
}

function save_json($path, $data) {
    file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function load_json($path) {
    if (!file_exists($path)) return null;
    return json_decode(file_get_contents($path), true);
}

// ログイン試行回数チェック
function check_login_attempts() {
    $limit_file = dirname(__DIR__) . '/data/.login_attempts';
    $attempts = [];
    if (file_exists($limit_file)) {
        $attempts = json_decode(file_get_contents($limit_file), true) ?? [];
    }
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();

    // 15分以上前の記録を削除
    $attempts[$ip] = array_filter($attempts[$ip] ?? [], fn($t) => $now - $t < 900);

    if (count($attempts[$ip]) >= 5) {
        return false; // ロック中
    }
    return true;
}

function record_login_attempt() {
    $limit_file = dirname(__DIR__) . '/data/.login_attempts';
    $attempts = [];
    if (file_exists($limit_file)) {
        $attempts = json_decode(file_get_contents($limit_file), true) ?? [];
    }
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();
    $attempts[$ip] = array_filter($attempts[$ip] ?? [], fn($t) => $now - $t < 900);
    $attempts[$ip][] = $now;
    file_put_contents($limit_file, json_encode($attempts));
}

function clear_login_attempts() {
    $limit_file = dirname(__DIR__) . '/data/.login_attempts';
    $attempts = [];
    if (file_exists($limit_file)) {
        $attempts = json_decode(file_get_contents($limit_file), true) ?? [];
    }
    $ip = $_SERVER['REMOTE_ADDR'];
    unset($attempts[$ip]);
    file_put_contents($limit_file, json_encode($attempts));
}
