<?php
require_once './config.php';
require_login();

$id = $_GET['id'] ?? null;
$item = ['id' => '', 'date' => date('Y.m.d'), 'cat' => '', 'title' => '', 'body' => ''];
$is_edit = false;

if ($id) {
    $file = NEWS_DIR . basename($id) . '.json';
    $loaded = load_json($file);
    if ($loaded) { $item = $loaded; $is_edit = true; }
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $new_id = $id ?: uniqid();
    $data = [
        'id'    => $new_id,
        'date'  => htmlspecialchars($_POST['date']),
        'cat'   => htmlspecialchars($_POST['cat']),
        'title' => htmlspecialchars($_POST['title']),
        'body'  => $_POST['body'], // HTMLをそのまま保存
    ];
    save_json(NEWS_DIR . $new_id . '.json', $data);
    header('Location: ./news-list.php?msg=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $is_edit ? '記事編集' : '新規作成' ?> — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">News / <?= $is_edit ? '編集' : '新規作成' ?></p>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div style="display:grid; grid-template-columns: 160px 200px 1fr; gap: 16px; margin-bottom: 24px;">
      <div class="form-group" style="margin:0;">
        <label>日付</label>
        <input type="text" name="date" value="<?= htmlspecialchars($item['date']) ?>" placeholder="2025.01.01" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label>カテゴリ</label>
        <input type="text" name="cat" value="<?= htmlspecialchars($item['cat']) ?>" placeholder="お知らせ / Web / 脚本 ...">
      </div>
      <div class="form-group" style="margin:0;">
        <label>タイトル</label>
        <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
      </div>
    </div>

    <div class="form-group">
      <label>本文（HTMLそのまま書けます）</label>
      <textarea name="body"><?= htmlspecialchars($item['body']) ?></textarea>
    </div>

    <div style="display:flex; gap:12px; align-items:center;">
      <button type="submit" class="btn btn-primary">保存する</button>
      <a href="./news-list.php" class="btn btn-secondary">キャンセル</a>
    </div>
  </form>
</div>
</body>
</html>
