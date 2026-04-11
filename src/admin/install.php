<?php
$config_file = __DIR__ . '/config.json';

if (file_exists($config_file)) {
    header('Location: ./index.php');
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title  = trim($_POST['site_title'] ?? '');
    $admin_user  = trim($_POST['admin_user'] ?? '');
    $admin_pass  = trim($_POST['admin_pass'] ?? '');
    $admin_pass2 = trim($_POST['admin_pass2'] ?? '');
    $smtp_host   = trim($_POST['smtp_host'] ?? '');
    $smtp_user   = trim($_POST['smtp_user'] ?? '');
    $smtp_pass   = trim($_POST['smtp_pass'] ?? '');
    $smtp_port   = (int)($_POST['smtp_port'] ?? 587);
    // base_path の正規化（先頭・末尾に / を付与）
    $base_path_val = trim($_POST['base_path'] ?? '/');
    if ($base_path_val === '' || $base_path_val === '/') {
        $base_path_val = '/';
    } else {
        if (!str_starts_with($base_path_val, '/')) $base_path_val = '/' . $base_path_val;
        if (!str_ends_with($base_path_val, '/'))   $base_path_val .= '/';
    }

    if (empty($site_title))       $err = 'サイトタイトルを入力してください。';
    elseif (empty($admin_user))   $err = 'ユーザー名を入力してください。';
    elseif (empty($admin_pass))   $err = 'パスワードを入力してください。';
    elseif (strlen($admin_pass) < 8) $err = 'パスワードは8文字以上にしてください。';
    elseif ($admin_pass !== $admin_pass2) $err = 'パスワードが一致しません。';
    else {
        // config.json 書き込み
        $config = [
            'admin_user'   => $admin_user,
            'admin_pass'   => password_hash($admin_pass, PASSWORD_BCRYPT),
            'smtp_host'    => $smtp_host,
            'smtp_user'    => $smtp_user,
            'smtp_pass'    => $smtp_pass,
            'smtp_port'    => $smtp_port,
            'base_path'    => $base_path_val,
            'installed_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents($config_file, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // .htaccess を base_path に合わせて更新
        $htaccess_file = dirname(__DIR__) . '/.htaccess';
        if (file_exists($htaccess_file)) {
            $ht = file_get_contents($htaccess_file);
            $ht = preg_replace('/ErrorDocument 404 \S+/', 'ErrorDocument 404 ' . $base_path_val . '404.php', $ht);
            $ht = preg_replace('/\nRewriteBase \S+/', '', $ht);
            $ht = preg_replace('/RewriteEngine On/', 'RewriteEngine On' . "\nRewriteBase " . $base_path_val, $ht);
            file_put_contents($htaccess_file, $ht);
        }

        // seo.json にサイトタイトルを書き込み
        $seo_file = dirname(__DIR__) . '/data/seo.json';
        $seo = file_exists($seo_file) ? json_decode(file_get_contents($seo_file), true) ?? [] : [];
        $seo['site_title']     = $site_title;
        $seo['copyright']      = '© ' . date('Y') . ' ' . $site_title . '. All rights reserved.';
        if (!empty($smtp_user)) $seo['contact_email'] = $smtp_user;
        file_put_contents($seo_file, json_encode($seo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));


        // SMTPが設定されていればインストール完了メールを送信
        if (!empty($smtp_host) && !empty($smtp_user) && !empty($smtp_pass)) {
            $site_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI']));
            $admin_url = $site_url . '/admin/';
            $subject = '【' . $site_title . '】Flat CMS インストール完了';
            $body = implode("\r\n", [
                $site_title . ' のインストールが完了しました。',
                '',
                '■ サイトURL',
                $site_url,
                '',
                '■ 管理画面URL',
                $admin_url,
                '',
                '■ ユーザー名',
                $admin_user,
                '',
                'このメールは自動送信されています。',
                'Powered by Flat CMS',
            ]);

            // PHPMailer不使用・mb_send_mail で送信
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
            }

            // SMTPソケット直接送信（簡易実装）
            _flatcms_send_mail($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_user, $subject, $body);
        }

        header('Location: ./index.php?installed=1');
        exit;
    }
}

function _flatcms_send_mail($host, $port, $user, $pass, $to, $subject, $body) {
    try {
        $socket = fsockopen(($port == 465 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 10);
        if (!$socket) return false;

        $read = function() use ($socket) { return fgets($socket, 512); };
        $send = function($cmd) use ($socket) { fputs($socket, $cmd . "\r\n"); };

        $read(); // 220 greeting
        $send('EHLO ' . gethostname());
        while ($line = $read()) { if (substr($line, 3, 1) == ' ') break; }

        if ($port == 587) {
            $send('STARTTLS');
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send('EHLO ' . gethostname());
            while ($line = $read()) { if (substr($line, 3, 1) == ' ') break; }
        }

        $send('AUTH LOGIN');
        $read();
        $send(base64_encode($user));
        $read();
        $send(base64_encode($pass));
        $read();

        $send('MAIL FROM:<' . $user . '>');
        $read();
        $send('RCPT TO:<' . $to . '>');
        $read();
        $send('DATA');
        $read();

        $headers = implode("\r\n", [
            'From: Flat CMS <' . $user . '>',
            'To: ' . $to,
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ]);
        $send($headers . "\r\n\r\n" . base64_encode($body) . "\r\n.");
        $read();

        $send('QUIT');
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>インストール — Flat CMS</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --bg: #f0f0f1; --white: #ffffff; --text: #1d2327;
    --accent: #2271b1; --accent2: #135e96; --accent-lt: #d0e4f5;
    --muted: #646970; --border: #dcdcde; --danger: #d63638;
  }
  body { background: var(--bg); color: var(--text); font-family: -apple-system, 'Helvetica Neue', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
  .install-box { width: 100%; max-width: 520px; background: var(--white); border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 48px; border-radius: 3px; }
  .install-logo { font-size: 11px; letter-spacing: 0.35em; color: var(--accent); margin-bottom: 12px; text-transform: uppercase; }
  .install-title { font-size: 22px; font-weight: 600; margin-bottom: 8px; color: var(--text); }
  .install-desc { font-size: 12px; color: var(--muted); margin-bottom: 40px; line-height: 1.8; }
  .section-label { font-size: 10px; letter-spacing: 0.3em; color: var(--muted); margin: 32px 0 16px; text-transform: uppercase; border-bottom: 1px solid var(--border); padding-bottom: 8px; }
  .form-group { margin-bottom: 20px; }
  .form-group label { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
  .required { font-size: 10px; background: var(--danger); color: #fff; padding: 1px 5px; border-radius: 2px; letter-spacing: 0.05em; font-weight: 400; }
  .optional { font-size: 10px; background: var(--border); color: var(--muted); padding: 1px 5px; border-radius: 2px; letter-spacing: 0.05em; font-weight: 400; }
  .form-group input { width: 100%; background: var(--white); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; font-size: 14px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; border-radius: 3px; }
  .form-group input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-lt); }
  .form-group small { font-size: 11px; color: var(--muted); margin-top: 5px; display: block; }
  .btn { width: 100%; background: var(--accent); color: #fff; border: 1px solid var(--accent2); padding: 14px; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 32px; transition: background 0.2s; border-radius: 3px; }
  .btn:hover { background: var(--accent2); }
  .err { background: #fcf0f1; border-left: 3px solid var(--danger); padding: 12px 16px; font-size: 13px; color: var(--danger); margin-bottom: 24px; border-radius: 0 3px 3px 0; }
</style>
</head>
<body>
<div class="install-box">
  <p class="install-logo">Flat CMS</p>
  <h1 class="install-title">初期設定</h1>
  <p class="install-desc">サイト情報と管理者アカウントを設定します。<br>このページはインストール後は表示されません。</p>

  <?php if ($err): ?><div class="err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="POST">
    <p class="section-label">サイト情報</p>
    <div class="form-group">
      <label>サイトタイトル <span class="required">必須</span></label>
      <input type="text" name="site_title" value="<?= htmlspecialchars($_POST['site_title'] ?? '') ?>" placeholder="例：Aroma Coffee" required>
    </div>
    <div class="form-group">
      <label>インストールパス <span class="optional">任意</span></label>
      <input type="text" name="base_path" value="<?= htmlspecialchars($_POST['base_path'] ?? '/') ?>" placeholder="/" style="max-width:200px;">
      <small>ドメインルートにインストールする場合は / のままでOK。サブディレクトリの場合は例：/mysite/</small>
    </div>

    <p class="section-label">管理者アカウント</p>
    <div class="form-group">
      <label>ユーザー名 <span class="required">必須</span></label>
      <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? '') ?>" placeholder="admin" required>
    </div>
    <div class="form-group">
      <label>パスワード <span class="required">必須</span></label>
      <input type="password" name="admin_pass" placeholder="8文字以上" required>
      <small>英数字・記号を組み合わせた強いパスワードを設定してください。</small>
    </div>
    <div class="form-group">
      <label>パスワード（確認） <span class="required">必須</span></label>
      <input type="password" name="admin_pass2" placeholder="もう一度入力" required>
    </div>

    <p class="section-label">メール送信設定（SMTP）</p>
    <div class="form-group">
      <label>SMTPホスト <span class="optional">任意</span></label>
      <input type="text" name="smtp_host" value="<?= htmlspecialchars($_POST['smtp_host'] ?? '') ?>" placeholder="mail.example.com">
    </div>
    <div class="form-group">
      <label>SMTPユーザー名（メールアドレス） <span class="optional">任意</span></label>
      <input type="email" name="smtp_user" value="<?= htmlspecialchars($_POST['smtp_user'] ?? '') ?>" placeholder="info@example.com">
      <small>SMTPを設定するとインストール完了メールが届きます。</small>
    </div>
    <div class="form-group">
      <label>SMTPパスワード <span class="optional">任意</span></label>
      <input type="password" name="smtp_pass" placeholder="メールのパスワード">
    </div>
    <div class="form-group">
      <label>SMTPポート <span class="optional">任意</span></label>
      <input type="text" name="smtp_port" value="<?= htmlspecialchars($_POST['smtp_port'] ?? '587') ?>" placeholder="587" style="max-width:100px;">
    </div>

    <button type="submit" class="btn">インストールする</button>
  </form>
</div>
</body>
</html>
