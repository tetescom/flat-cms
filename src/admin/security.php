<?php
require_once './config.php';
require_login();

$config_file = __DIR__ . '/config.json';
$config = json_decode(file_get_contents($config_file), true) ?? [];

$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'change_admin_url') {
        $new_slug = preg_replace('/[^a-zA-Z0-9_\-]/', '', trim($_POST['admin_slug'] ?? ''));
        if (strlen($new_slug) < 4) {
            $err = '管理画面URLは4文字以上の英数字にしてください。';
        } elseif ($new_slug === 'admin') {
            $err = '「admin」は使用できません。別の名前にしてください。';
        } else {
            $config['admin_slug'] = $new_slug;
            file_put_contents($config_file, json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // .htaccessにリダイレクトルールを追加
            $htaccess = file_get_contents(dirname(__DIR__) . '/.htaccess');
            // 既存のadmin slugルールを削除
            $htaccess = preg_replace('/# Admin URL.*?(?=\n\n|\n#|$)/s', '', $htaccess);
            $htaccess .= "\n# Admin URL\nRewriteRule ^{$new_slug}/?$ /admin/ [R=301,L]\nRewriteRule ^{$new_slug}/(.*)$ /admin/$1 [R=301,L]\n";
            file_put_contents(dirname(__DIR__) . '/.htaccess', $htaccess);

            $msg = "管理画面URLを変更しました。新しいURL: /{$new_slug}/";
        }
    }
}

$current_slug = $config['admin_slug'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>セキュリティ — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Security</p>
  <?php if ($msg): ?><div class="msg msg-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- 管理画面URL変更 -->
  <div class="card" style="max-width:500px;margin-bottom:32px;">
    <p style="font-size:12px;letter-spacing:0.15em;color:var(--accent);margin-bottom:16px;">管理画面URL</p>
    <p style="font-size:13px;color:var(--muted);line-height:2;margin-bottom:24px;">
      現在: <span style="color:var(--accent);">/<?= htmlspecialchars($current_slug) ?>/</span><br>
      「admin」以外のURLに変更することで、不正アクセスのリスクを減らせます。<br>
      変更後は新しいURLからアクセスしてください。
    </p>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="action" value="change_admin_url">
      <div class="form-group">
        <label>新しい管理画面URL（英数字・ハイフン・アンダースコアのみ）</label>
        <div style="display:flex;align-items:center;gap:8px;">
          <span style="color:var(--muted);font-size:13px;">https://your-domain.com/</span>
          <input type="text" name="admin_slug" placeholder="my-cms-2025" style="flex:1;">
          <span style="color:var(--muted);font-size:13px;">/</span>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" onclick="return confirm('URLを変更します。新しいURLをメモしてから変更してください。')">変更する</button>
    </form>
  </div>

  <!-- ログイン試行状況 -->
  <div class="card" style="max-width:500px;">
    <p style="font-size:12px;letter-spacing:0.15em;color:var(--accent);margin-bottom:16px;">ログイン保護</p>
    <p style="font-size:13px;color:var(--muted);line-height:2;">
      同一IPからのログイン失敗が5回を超えると、15分間ロックされます。<br>
      ブルートフォース攻撃から管理画面を保護します。
    </p>
  </div>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
