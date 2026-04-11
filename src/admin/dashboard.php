<?php
require_once './config.php';
require_login();

// 件数カウント
$news_count = count(glob(NEWS_DIR . '*.json'));
$pages_count = count(glob(PAGES_DIR . '*.json'));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Dashboard</p>
  <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
    <div class="card" style="text-align:center;">
      <p style="font-size:36px; color:var(--accent); margin-bottom:8px;"><?= $news_count ?></p>
      <p style="font-size:11px; letter-spacing:0.2em; color:var(--muted);">お知らせ</p>
    </div>
    <div class="card" style="text-align:center;">
      <p style="font-size:36px; color:var(--accent); margin-bottom:8px;"><?= $pages_count ?></p>
      <p style="font-size:11px; letter-spacing:0.2em; color:var(--muted);">固定ページ</p>
    </div>
    <div class="card" style="text-align:center;">
      <a href="../" target="_blank" style="font-size:11px; letter-spacing:0.2em; color:var(--muted); text-decoration:none;">サイトを見る →</a>
    </div>
  </div>
  <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
      <p style="font-size:11px;letter-spacing:0.2em;color:var(--muted);margin-bottom:20px;">お知らせ</p>
      <a href="./block-editor.php?type=news" class="btn btn-primary">新規作成</a>
      <a href="./post-list.php" class="btn btn-secondary" style="margin-left:12px;">一覧</a>
    </div>
    <div class="card">
      <p style="font-size:11px;letter-spacing:0.2em;color:var(--muted);margin-bottom:20px;">固定ページ</p>
      <a href="./block-editor.php?type=page" class="btn btn-primary">新規作成</a>
      <a href="./pages-list.php" class="btn btn-secondary" style="margin-left:12px;">一覧</a>
    </div>
  </div>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
