<?php
require_once './config.php';
require_login();

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current  = $_POST['current_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm  = $_POST['confirm_pass'] ?? '';

    $config_file = dirname(__DIR__) . '/data/config.json';
    $config = json_decode(file_get_contents($config_file), true);

    if (!password_verify($current, $config['admin_pass'])) {
        $err = '現在のパスワードが正しくありません。';
    } elseif (strlen($new_pass) < 8) {
        $err = '新しいパスワードは8文字以上にしてください。';
    } elseif ($new_pass !== $confirm) {
        $err = 'パスワードが一致しません。';
    } else {
        $config['admin_pass'] = password_hash($new_pass, PASSWORD_BCRYPT);
        file_put_contents($config_file, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $msg = 'パスワードを変更しました。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>パスワード変更 — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Password</p>
  <?php if ($msg): ?><div class="msg msg-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="POST" style="max-width:400px;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group">
      <label>現在のパスワード</label>
      <input type="password" name="current_pass" required>
    </div>
    <div class="form-group">
      <label>新しいパスワード <span style="color:var(--muted);font-size:10px;">（8文字以上）</span></label>
      <input type="password" name="new_pass" required>
    </div>
    <div class="form-group">
      <label>新しいパスワード（確認）</label>
      <input type="password" name="confirm_pass" required>
    </div>
    <div style="margin-top:28px;">
      <button type="submit" class="btn btn-primary">変更する</button>
    </div>
  </form>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
