<?php
header('Content-Type: application/json');

// PHP 8.2以下用のポリフィル
if (!function_exists('mb_str_pad')) {
    function mb_str_pad(string $string, int $length, string $pad_string = ' ', int $pad_type = STR_PAD_RIGHT, ?string $encoding = null): string {
        $enc = $encoding ?? mb_internal_encoding();
        $str_len = mb_strlen($string, $enc);
        if ($str_len >= $length) return $string;
        $pad_len = mb_strlen($pad_string, $enc);
        $repeat = (int)ceil(($length - $str_len) / $pad_len);
        $padding = mb_substr(str_repeat($pad_string, $repeat), 0, $length - $str_len, $enc);
        return match ($pad_type) {
            STR_PAD_LEFT => $padding . $string,
            STR_PAD_BOTH => $padding . $string . $padding,
            default      => $string . $padding,
        };
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// CSRF検証
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => '不正なリクエストです。']);
    exit;
}

// 設定を読み込み
$config_file = dirname(__DIR__) . '/data/config.json';
$config = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) ?? [] : [];
$seo_file = dirname(__DIR__) . '/data/seo.json';
$seo = file_exists($seo_file) ? json_decode(file_get_contents($seo_file), true) ?? [] : [];

$to           = $seo['contact_email'] ?? '';
$smtp_host    = $config['smtp_host']  ?? '';
$smtp_user    = $config['smtp_user']  ?? '';
$smtp_pass    = $config['smtp_pass']  ?? '';
$smtp_port    = (int)($config['smtp_port'] ?? 587);
$site_title   = $seo['site_title']    ?? '';

if (empty($to) || empty($smtp_host) || empty($smtp_user) || empty($smtp_pass)) {
    echo json_encode(['success' => false, 'message' => 'SMTP設定が完了していません。管理画面のSEO設定から設定してください。']);
    exit;
}

// フォーム設定読み込み
$form_file = dirname(__DIR__) . '/data/form.json';
$form_fields = file_exists($form_file) ? json_decode(file_get_contents($form_file), true) ?? [] : [];

// デフォルト
if (empty($form_fields)) {
    $form_fields = [
        ['id'=>'name','label'=>'お名前','type'=>'text','visible'=>true,'required'=>true],
        ['id'=>'email','label'=>'メールアドレス','type'=>'email','visible'=>true,'required'=>true],
        ['id'=>'type','label'=>'ご依頼内容','type'=>'select','visible'=>true,'required'=>true],
        ['id'=>'message','label'=>'メッセージ','type'=>'textarea','visible'=>true,'required'=>true],
    ];
}

// 入力値取得・バリデーション
$errors = [];
$post_data = [];
$name  = '';
$email = '';

foreach ($form_fields as $field) {
    if (!$field['visible']) continue;
    $id  = $field['id'];
    $val = trim($_POST[$id] ?? '');
    $post_data[$id] = $val;

    if ($id === 'name')  $name  = $val;
    if ($id === 'email') $email = $val;

    if ($field['required'] && empty($val)) {
        $errors[] = htmlspecialchars($field['label']) . 'は必須です。';
    }
    if ($field['type'] === 'email' && !empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'メールアドレスの形式が正しくありません。';
    }
    if ($field['type'] === 'email2') {
        if ($val !== ($post_data['email'] ?? '')) {
            $errors[] = 'メールアドレス（確認）が一致しません。';
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode('<br>', $errors)]);
    exit;
}

// SMTPで送信
$subject = "[{$site_title}] お問い合わせ：{$name}様";
$body  = "以下のお問い合わせが届きました。\r\n";
$body .= str_repeat('-', 40) . "\r\n";
foreach ($form_fields as $field) {
    if (!$field['visible']) continue;
    $id  = $field['id'];
    if ($id === 'email2') continue;
    $val = $post_data[$id] ?? '';
    if (empty($val)) continue;
    $body .= mb_str_pad(htmlspecialchars($field['label']), 10, '　') . ": {$val}\r\n";
}
$body .= str_repeat('-', 40) . "\r\n";
$body .= "\r\n※このメールは {$site_title} のお問い合わせフォームから自動送信されました。\r\n";

// SMTP手動実装（PHPMailer不要）
function smtp_send($host, $port, $user, $pass, $from, $to, $subject, $body) {
    $socket = fsockopen($host, $port, $errno, $errstr, 10);
    if (!$socket) return ['ok' => false, 'err' => "接続失敗: {$errstr}"];

    $read = fgets($socket, 1024);

    // EHLO
    fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    $ehlo = '';
    while ($line = fgets($socket, 1024)) {
        $ehlo .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }

    // STARTTLS
    fputs($socket, "STARTTLS\r\n");
    fgets($socket, 1024);

    // TLSハンドシェイク
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    // 再EHLO
    fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
    while ($line = fgets($socket, 1024)) {
        if (substr($line, 3, 1) === ' ') break;
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket, 1024);
    fputs($socket, base64_encode($user) . "\r\n");
    fgets($socket, 1024);
    fputs($socket, base64_encode($pass) . "\r\n");
    $auth = fgets($socket, 1024);
    if (substr($auth, 0, 3) !== '235') {
        fclose($socket);
        return ['ok' => false, 'err' => '認証失敗: ' . trim($auth)];
    }

    // MAIL FROM
    fputs($socket, "MAIL FROM:<{$from}>\r\n");
    fgets($socket, 1024);

    // RCPT TO
    fputs($socket, "RCPT TO:<{$to}>\r\n");
    fgets($socket, 1024);

    // DATA
    fputs($socket, "DATA\r\n");
    fgets($socket, 1024);

    $headers  = "From: {$site_title} <{$from}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Reply-To: {$name} <{$email}>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    fputs($socket, $headers . "\r\n" . chunk_split(base64_encode($body)) . "\r\n.\r\n");
    $result = fgets($socket, 1024);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return substr($result, 0, 3) === '250'
        ? ['ok' => true]
        : ['ok' => false, 'err' => '送信失敗: ' . trim($result)];
}

global $site_title, $name;
$result = smtp_send($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_user, $to, $subject, $body);

if ($result['ok']) {
    // 自動返信メール（送信者へ）
    $reply_subject = "[{$site_title}] お問い合わせを受け付けました";
    $reply_body  = "{$name} 様\n\n";
    $reply_body .= "この度はお問い合わせいただきありがとうございます。\n";
    $reply_body .= "以下の内容でお問い合わせを受け付けました。\n";
    $reply_body .= "内容を確認の上、担当者よりご連絡いたします。\n\n";
    $reply_body .= str_repeat('-', 40) . "\n";
    foreach ($form_fields as $field) {
        if (!$field['visible']) continue;
        $fid = $field['id'];
        if ($fid === 'email2') continue;
        $val = $post_data[$fid] ?? '';
        if (empty($val)) continue;
        $reply_body .= mb_str_pad(htmlspecialchars($field['label']), 10, '　') . ": {$val}\n";
    }
    $reply_body .= str_repeat('-', 40) . "\n\n";
    $reply_body .= "{$site_title}\n";

    smtp_send($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_user, $email, $reply_subject, $reply_body);

    echo json_encode(['success' => true, 'message' => 'お問い合わせを受け付けました。ありがとうございます。<br>自動返信メールをお送りしましたのでご確認ください。<br><small style="opacity:0.7;">※迷惑メールフォルダに入っている場合があります。</small>']);
} else {
    echo json_encode(['success' => false, 'message' => '送信に失敗しました。(' . ($result['err'] ?? '') . ')']);
}
