<?php
require_once './config.php';
require_login();

$cat_file = DATA_DIR . 'categories.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $labels = $_POST['label'] ?? [];
    $slugs  = $_POST['slug']  ?? [];
    $items  = [];
    foreach ($labels as $i => $label) {
        $label = trim($label);
        $slug  = trim($slugs[$i] ?? '');
        $slug  = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        if ($label !== '' && $slug !== '') {
            $items[] = ['slug' => $slug, 'label' => $label];
        }
    }
    save_json($cat_file, $items);
    header('Location: ./categories.php?msg=saved');
    exit;
}

$cats = load_json($cat_file) ?: [];
$msg  = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>カテゴリー管理 — 管理画面</title>
<?php include './admin-style.php'; ?>
<style>
.cat-list { margin-bottom: 20px; }
.cat-row {
    display: grid;
    grid-template-columns: 24px 180px 1fr 36px;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}
.cat-row:first-child { border-top: 1px solid var(--border); }
.cat-row input {
    background: #111; border: 1px solid #333; color: var(--white);
    padding: 10px 12px; font-size: 13px; outline: none;
    transition: border-color 0.3s; width: 100%;
}
.cat-row input:focus { border-color: var(--accent); }
.drag-handle { color: #444; cursor: grab; text-align: center; font-size: 16px; user-select: none; }
.drag-handle:active { cursor: grabbing; }
.del-btn {
    background: transparent; border: 1px solid #442222; color: #664444;
    width: 32px; height: 32px; cursor: pointer; font-size: 14px;
    transition: all 0.2s; display: flex; align-items: center; justify-content: center;
}
.del-btn:hover { border-color: #884444; color: #cc6666; }
.hint { font-size: 11px; color: var(--muted); margin-top: 8px; line-height: 1.8; }
.col-labels { display: grid; grid-template-columns: 24px 180px 1fr 36px; gap: 12px; padding-bottom: 8px; margin-bottom: 4px; }
.col-labels span { font-size: 10px; letter-spacing: 0.15em; color: var(--muted); }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Categories</p>
  <?php if ($msg === 'saved'): ?><div class="msg msg-success">保存しました。</div><?php endif; ?>

  <form method="POST" id="catForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="actions-bar">
      <h2>カテゴリー一覧</h2>
      <button type="button" class="btn btn-secondary" onclick="addRow()">＋ 追加</button>
    </div>

    <div class="col-labels">
      <span></span>
      <span>表示名</span>
      <span>スラッグ（URL用・英数字）</span>
      <span></span>
    </div>

    <div class="cat-list" id="catList">
      <?php foreach ($cats as $cat): ?>
      <div class="cat-row" draggable="true">
        <span class="drag-handle">⠿</span>
        <input type="text" name="label[]" value="<?= htmlspecialchars($cat['label']) ?>" placeholder="お知らせ">
        <input type="text" name="slug[]"  value="<?= htmlspecialchars($cat['slug']) ?>"  placeholder="info">
        <button type="button" class="del-btn" onclick="this.closest('.cat-row').remove()">×</button>
      </div>
      <?php endforeach; ?>
    </div>

    <p class="hint">スラッグはURLに使われます（例: <code style="color:var(--accent)">info</code> → <code>category.php?slug=info</code>）</p>

    <div style="margin-top: 28px;">
      <button type="submit" class="btn btn-primary">保存する</button>
    </div>
  </form>
</div>

<script>
function addRow() {
  const row = document.createElement('div');
  row.className = 'cat-row';
  row.draggable = true;
  row.innerHTML = `
    <span class="drag-handle">⠿</span>
    <input type="text" name="label[]" placeholder="お知らせ">
    <input type="text" name="slug[]"  placeholder="info">
    <button type="button" class="del-btn" onclick="this.closest('.cat-row').remove()">×</button>`;
  document.getElementById('catList').appendChild(row);
  setupDrag(row);
  row.querySelector('input').focus();
}

let dragSrc = null;
function setupDrag(el) {
  el.addEventListener('dragstart', () => { dragSrc = el; setTimeout(() => el.style.opacity = '0.4', 0); });
  el.addEventListener('dragend',   () => el.style.opacity = '');
  el.addEventListener('dragover',  e => { e.preventDefault(); el.style.borderTop = '2px solid var(--accent)'; });
  el.addEventListener('dragleave', () => el.style.borderTop = '');
  el.addEventListener('drop', e => {
    e.preventDefault(); el.style.borderTop = '';
    if (dragSrc && dragSrc !== el) {
      const list = document.getElementById('catList');
      const rows = [...list.children];
      rows.indexOf(dragSrc) < rows.indexOf(el) ? el.after(dragSrc) : el.before(dragSrc);
    }
    dragSrc = null;
  });
}
document.querySelectorAll('.cat-row').forEach(setupDrag);
</script>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
