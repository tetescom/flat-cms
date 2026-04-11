<?php
require_once './config.php';
require_login();

// ゴミ箱へ移動
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    verify_csrf();
    $id   = basename($_POST['delete']);
    $file = NEWS_DIR . $id . '.json';
    if (file_exists($file)) {
        $data = load_json($file);
        $data['_trash_type'] = 'news';
        $data['_trash_date'] = date('Y-m-d H:i:s');
        save_json(TRASH_DIR . $id . '.json', $data);
        unlink($file);
    }
    header('Location: ./post-list.php?msg=trashed');
    exit;
}

// 記事一覧を読み込んで日付順ソート
$files = glob(NEWS_DIR . '*.json');
$items = [];
foreach ($files as $f) {
    $d = load_json($f);
    if ($d) $items[] = $d;
}
usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>投稿一覧 — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Posts</p>
  <?php if ($msg === 'saved'): ?><div class="msg msg-success">保存しました。</div><?php endif; ?>
  <?php if ($msg === 'trashed'): ?><div class="msg msg-success">ゴミ箱に移動しました。</div><?php endif; ?>

  <div class="actions-bar">
    <h2>投稿一覧（<?= count($items) ?>件）</h2>
    <div style="display:flex;gap:8px;">
      <a href="./trash.php" class="btn btn-secondary">🗑 ゴミ箱</a>
      <a href="./block-editor.php?type=news" class="btn btn-primary">+ 新規作成</a>
    </div>
  </div>

  <div class="item-list">
    <?php foreach ($items as $item): ?>
    <div class="item-row">
      <span class="item-date"><?= htmlspecialchars($item['date']) ?></span>
      <span class="item-cat"><?= htmlspecialchars($item['cat']) ?></span>
      <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
      <div class="item-actions">
        <a href="./block-editor.php?type=news&id=<?= $item['id'] ?>">編集</a>
        <form method="POST" style="display:inline;" onsubmit="return confirm('ゴミ箱に移動しますか？')">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="delete" value="<?= htmlspecialchars($item['id']) ?>">
          <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px;">削除</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
    <p style="color:var(--muted);font-size:13px;padding:24px 0;">記事がありません。</p>
    <?php endif; ?>
  </div>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
