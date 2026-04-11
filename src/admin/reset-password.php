<?php
$config_file = __DIR__ . '/config.json';
if (!file_exists($config_file)) {
    header('Location: ./install.php');
    exit;
}
$config = json_decode(file_get_contents($config_file), true);

require_once './config.php';

$err = '';
$msg = '';
$step = $_GET['step'] ?? 'request';
$token = $_GET['token'] ?? '';

if ($step === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email !== ($config['smtp_user'] ?? '')) {
        $err = 'メールアドレスが登録されていません。';
    } else {
        $reset_token = bin2hex(random_bytes(32));
        $config['reset_token'] = $reset_token;
        $config['reset_expires'] = time() + 3600;
        file_put_contents($config_file, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // リセットリンク送信
        $site_url = 'https://' . $_SERVER['HTTP_HOST'];
        $reset_url = $site_url . '/admin/reset-password.php?step=reset&token=' . $reset_token;
        $subject = '[Flat CMS] パスワードリセット';
        $body  = "パスワードリセットのリクエストを受け付けました。\r\n\r\n";
        $body .= "以下のリンクから1時間以内にパスワードを再設定してください。\r\n\r\n";
        $body .= $reset_url . "\r\n\r\n";
        $body .= "このメールに覚えがない場合は無視してください。\r\n";

        // SMTP送信
        include_once './config.php';
        $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
        if ($socket) {
            fgets($socket, 1024);
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            while ($line = fgets($socket, 1024)) { if (substr($line, 3, 1) === ' ') break; }
            fputs($socket, "STARTTLS\r\n"); fgets($socket, 1024);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            while ($line = fgets($socket, 1024)) { if (substr($line, 3, 1) === ' ') break; }
            fputs($socket, "AUTH LOGIN\r\n"); fgets($socket, 1024);
            fputs($socket, base64_encode(SMTP_USER) . "\r\n"); fgets($socket, 1024);
            fputs($socket, base64_encode(SMTP_PASS) . "\r\n");
            $auth = fgets($socket, 1024);
            if (substr($auth, 0, 3) === '235') {
                fputs($socket, "MAIL FROM:<" . SMTP_USER . ">\r\n"); fgets($socket, 1024);
                fputs($socket, "RCPT TO:<{$email}>\r\n"); fgets($socket, 1024);
                fputs($socket, "DATA\r\n"); fgets($socket, 1024);
                $headers  = "From: Flat CMS <" . SMTP_USER . ">\r\n";
                $headers .= "To: <{$email}>\r\n";
                $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $headers .= "Content-Transfer-Encoding: base64\r\n";
                fputs($socket, $headers . "\r\n" . chunk_split(base64_encode($body)) . "\r\n.\r\n");
                fgets($socket, 1024);
                $msg = 'リセットリンクをメールで送信しました。';
            } else {
                $err = 'メール送信に失敗しました。SMTP設定を確認してください。';
            }
            fputs($socket, "QUIT\r\n");
            fclose($socket);
        } else {
            $err = 'SMTPサーバーに接続できませんでした。';
        }
    }
}

if ($step === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm  = $_POST['confirm_pass'] ?? '';
    $token    = $_POST['token'] ?? '';

    if ($token !== ($config['reset_token'] ?? '') || time() > ($config['reset_expires'] ?? 0)) {
        $err = 'リセットリンクが無効か期限切れです。';
    } elseif (strlen($new_pass) < 8) {
        $err = 'パスワードは8文字以上にしてください。';
    } elseif ($new_pass !== $confirm) {
        $err = 'パスワードが一致しません。';
    } else {
        $config['admin_pass'] = password_hash($new_pass, PASSWORD_BCRYPT);
        unset($config['reset_token'], $config['reset_expires']);
        file_put_contents($config_file, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header('Location: ./index.php?reset=1');
        exit;
    }
}

// トークン検証
if ($step === 'reset' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($token !== ($config['reset_token'] ?? '') || time() > ($config['reset_expires'] ?? 0)) {
        $err = 'リセットリンクが無効か期限切れです。';
        $step = 'expired';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>パスワードリセット — Flat CMS</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0a0a0a; color: #f2ede8; font-family: 'Helvetica Neue', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
  .box { width: 100%; max-width: 440px; }
  .logo { font-size: 11px; letter-spacing: 0.4em; color: #c8a96e; margin-bottom: 12px; }
  h1 { font-size: 20px; letter-spacing: 0.1em; margin-bottom: 32px; }
  .form-group { margin-bottom: 20px; }
  label { display: block; font-size: 11px; letter-spacing: 0.15em; color: #555; margin-bottom: 8px; }
  input { width: 100%; background: #111; border: 1px solid #222; color: #f2ede8; padding: 12px 16px; font-size: 13px; outline: none; transition: border-color 0.2s; }
  input:focus { border-color: #c8a96e; }
  .btn { width: 100%; background: #c8a96e; color: #0a0a0a; border: none; padding: 14px; font-size: 12px; letter-spacing: 0.2em; cursor: pointer; margin-top: 8px; }
  .err { border-left: 3px solid #9f6a6a; background: rgba(159,106,106,0.08); padding: 12px 16px; font-size: 13px; color: #cc8888; margin-bottom: 24px; }
  .msg { border-left: 3px solid #6a9f6a; background: rgba(106,159,106,0.08); padding: 12px 16px; font-size: 13px; color: #88cc88; margin-bottom: 24px; }
  .back { display: block; margin-top: 20px; font-size: 11px; letter-spacing: 0.15em; color: #555; text-decoration: none; text-align: center; }
  .back:hover { color: #c8a96e; }
</style>
</head>
<body>
<div class="box">
  <p class="logo">FLAT CMS</p>

  <?php if ($step === 'request'): ?>
  <h1>パスワードリセット</h1>
  <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if ($msg): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if (!$msg): ?>
  <form method="POST">
    <div class="form-group">
      <label>登録メールアドレス（SMTPユーザー名）</label>
      <input type="email" name="email" required placeholder="info@example.com">
    </div>
    <button type="submit" class="btn">リセットリンクを送信</button>
  </form>
  <?php endif; ?>

  <?php elseif ($step === 'reset'): ?>
  <h1>新しいパスワードを設定</h1>
  <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="form-group">
      <label>新しいパスワード（8文字以上）</label>
      <input type="password" name="new_pass" required>
    </div>
    <div class="form-group">
      <label>新しいパスワード（確認）</label>
      <input type="password" name="confirm_pass" required>
    </div>
    <button type="submit" class="btn">パスワードを変更する</button>
  </form>

  <?php else: ?>
  <h1>リンクが無効です</h1>
  <div class="err">リセットリンクが無効か期限切れです。もう一度お試しください。</div>
  <?php endif; ?>

  <a href="./index.php" class="back">← ログイン画面へ戻る</a>
</div>
</body>
</html>
