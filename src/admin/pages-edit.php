<?php
require_once './config.php';
require_login();

$id = $_GET['id'] ?? null;
$item = ['id' => '', 'title' => '', 'slug' => '', 'body' => ''];
$is_edit = false;

if ($id) {
    $file = PAGES_DIR . basename($id) . '.json';
    $loaded = load_json($file);
    if ($loaded) { $item = $loaded; $is_edit = true; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $new_id = $id ?: uniqid();
    // slugはURLに使えるように英数字とハイフンのみ
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['slug']));
    $slug = $slug ?: $new_id;
    $data = [
        'id'    => $new_id,
        'title' => $_POST['title'] ?? '',
        'slug'  => $slug,
        'body'  => $_POST['body'],
    ];
    save_json(PAGES_DIR . $new_id . '.json', $data);

    // ページのPHPファイルを自動生成
    $page_dir = dirname(__DIR__) . '/pages/';
    if (!is_dir($page_dir)) mkdir($page_dir);
    $page_php = <<<PHP
<?php
\$__pf = dirname(__DIR__) . '/data/pages/{$new_id}.json';
\$page_data = is_file(\$__pf) ? json_decode(file_get_contents(\$__pf), true) : null;
include dirname(__DIR__) . '/php/page-template.php';
PHP;
    file_put_contents($page_dir . $slug . '.php', $page_php);

    header('Location: ./pages-list.php?msg=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $is_edit ? 'ページ編集' : '新規ページ作成' ?> — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Pages / <?= $is_edit ? '編集' : '新規作成' ?></p>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px;">
      <div class="form-group" style="margin:0;">
        <label>ページタイトル</label>
        <input type="text" name="title" value="<?= htmlspecialchars($item['title']) ?>" required>
      </div>
      <div class="form-group" style="margin:0;">
        <label>スラッグ（URLになります: pages/〇〇.php）</label>
        <input type="text" name="slug" value="<?= htmlspecialchars($item['slug']) ?>"
               placeholder="about / works / profile ..." <?= $is_edit ? 'readonly style="color:var(--muted)"' : '' ?>>
      </div>
    </div>

    <div class="form-group">
      <label>本文（HTMLそのまま書けます）</label>
      <textarea name="body"><?= htmlspecialchars($item['body']) ?></textarea>
    </div>

    <div style="display:flex; gap:12px;">
      <button type="submit" class="btn btn-primary">保存する</button>
      <a href="./pages-list.php" class="btn btn-secondary">キャンセル</a>
    </div>
  </form>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
