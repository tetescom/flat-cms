<?php
require_once './config.php';
require_login();

// 削除処理
if (isset($_GET['delete'])) {
    verify_csrf();
    $file = NEWS_DIR . basename($_GET['delete']) . '.json';
    if (file_exists($file)) unlink($file);
    header('Location: ./news-list.php?msg=deleted');
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
<title>お知らせ一覧 — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">News</p>
  <?php if ($msg === 'saved'): ?><div class="msg msg-success">保存しました。</div><?php endif; ?>
  <?php if ($msg === 'deleted'): ?><div class="msg msg-success">削除しました。</div><?php endif; ?>

  <div class="actions-bar">
    <h2>お知らせ一覧（<?= count($items) ?>件）</h2>
    <a href="./block-editor.php?type=news" class="btn btn-primary">+ 新規作成</a>
  </div>

  <div class="item-list">
    <?php foreach ($items as $item): ?>
    <div class="item-row">
      <span class="item-date"><?= htmlspecialchars($item['date']) ?></span>
      <span class="item-cat"><?= htmlspecialchars($item['cat']) ?></span>
      <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
      <div class="item-actions">
        <a href="./block-editor.php?type=news&id=<?= $item['id'] ?>">編集</a>
        <a href="./news-list.php?delete=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>"
           class="del" onclick="return confirm('削除しますか？')">削除</a>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
    <p style="color:var(--muted);font-size:13px;padding:24px 0;">記事がありません。</p>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
