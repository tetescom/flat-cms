<?php
require_once './config.php';
require_login();

$sns_file = DATA_DIR . 'sns.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'x'         => htmlspecialchars(trim($_POST['x'] ?? '')),
        'instagram' => htmlspecialchars(trim($_POST['instagram'] ?? '')),
        'youtube'   => htmlspecialchars(trim($_POST['youtube'] ?? '')),
        'line'      => htmlspecialchars(trim($_POST['line'] ?? '')),
        'facebook'  => htmlspecialchars(trim($_POST['facebook'] ?? '')),
        'tiktok'    => htmlspecialchars(trim($_POST['tiktok'] ?? '')),
    ];
    save_json($sns_file, $data);
    header('Location: ./sns.php?msg=saved');
    exit;
}

$sns = load_json($sns_file) ?: [];
$msg = $_GET['msg'] ?? '';

$fields = [
    'x'         => ['label' => 'X（旧Twitter）', 'placeholder' => 'https://x.com/yourname'],
    'instagram' => ['label' => 'Instagram',       'placeholder' => 'https://instagram.com/yourname'],
    'youtube'   => ['label' => 'YouTube',          'placeholder' => 'https://youtube.com/@yourname'],
    'line'      => ['label' => 'LINE（公式アカウント）', 'placeholder' => 'https://line.me/ti/p/xxxxxxxxxx'],
    'facebook'  => ['label' => 'Facebook',         'placeholder' => 'https://facebook.com/yourname'],
    'tiktok'    => ['label' => 'TikTok',           'placeholder' => 'https://tiktok.com/@yourname'],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SNS設定 — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">SNS</p>
  <?php if ($msg === 'saved'): ?><div class="msg msg-success">保存しました。</div><?php endif; ?>

  <form method="POST" style="max-width:600px;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <p style="font-size:12px;color:var(--muted);margin-bottom:24px;line-height:2;">
      URLを入力したSNSだけフッターとContactセクションに表示されます。<br>
      空欄のものは表示されません。
    </p>
    <?php foreach ($fields as $key => $f): ?>
    <div class="form-group">
      <label><?= $f['label'] ?></label>
      <input type="url" name="<?= $key ?>"
             value="<?= htmlspecialchars($sns[$key] ?? '') ?>"
             placeholder="<?= $f['placeholder'] ?>">
    </div>
    <?php endforeach; ?>
    <div style="margin-top:28px;">
      <button type="submit" class="btn btn-primary">保存する</button>
    </div>
  </form>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
