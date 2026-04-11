<?php
require_once './config.php';
require_login();

$nav_file = DATA_DIR . 'nav.json';

// 保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $labels = $_POST['label'] ?? [];
    $urls   = $_POST['url']   ?? [];
    $items  = [];
    foreach ($labels as $i => $label) {
        $label = trim($label);
        $url   = trim($urls[$i] ?? '');
        if ($label !== '' && $url !== '') {
            $items[] = ['label' => $label, 'url' => $url];
        }
    }
    save_json($nav_file, $items);
    header('Location: ./nav.php?msg=saved');
    exit;
}

$nav = load_json($nav_file) ?: [];
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ナビゲーション管理 — 管理画面</title>
<?php include './admin-style.php'; ?>
<style>
.nav-list { margin-bottom: 20px; }
.nav-row {
    display: grid;
    grid-template-columns: 24px 180px 1fr 36px;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}
.nav-row:first-child { border-top: 1px solid var(--border); }
.nav-row input {
    background: #111;
    border: 1px solid #333;
    color: var(--white);
    padding: 10px 12px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.3s;
    width: 100%;
}
.nav-row input:focus { border-color: var(--accent); }
.drag-handle { color: #444; cursor: grab; text-align: center; font-size: 16px; user-select: none; }
.drag-handle:active { cursor: grabbing; }
.del-btn {
    background: transparent;
    border: 1px solid #442222;
    color: #664444;
    width: 32px; height: 32px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
    display: flex; align-items: center; justify-content: center;
}
.del-btn:hover { border-color: #884444; color: #cc6666; }
.hint { font-size: 11px; color: var(--muted); margin-top: 8px; line-height: 1.8; }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Navigation</p>
  <?php if ($msg === 'saved'): ?><div class="msg msg-success">保存しました。</div><?php endif; ?>

  <form method="POST" id="navForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="actions-bar">
      <h2>ナビゲーション項目</h2>
      <button type="button" class="btn btn-secondary" onclick="addRow()">＋ 追加</button>
    </div>

    <div class="nav-list" id="navList">
      <?php foreach ($nav as $item): ?>
      <div class="nav-row" draggable="true">
        <span class="drag-handle">⠿</span>
        <input type="text" name="label[]" value="<?= htmlspecialchars($item['label']) ?>" placeholder="リンク名（例: About）">
        <input type="text" name="url[]"   value="<?= htmlspecialchars($item['url']) ?>"   placeholder="URL（例: {base}index.php#about）">
        <button type="button" class="del-btn" onclick="this.closest('.nav-row').remove()">×</button>
      </div>
      <?php endforeach; ?>
    </div>

    <p class="hint">
      URLの <code style="color:var(--accent)">{base}</code> はページの階層に応じて自動で <code>./</code> や <code>../</code> に置き換わります。<br>
      例: <code>{base}index.php#about</code> → トップページのAboutセクション<br>
      例: <code>{base}php/post-list.php</code> → ニュース一覧
    </p>

    <div style="margin-top: 28px;">
      <button type="submit" class="btn btn-primary">保存する</button>
    </div>
  </form>
</div>

<script>
function addRow() {
  const row = document.createElement('div');
  row.className = 'nav-row';
  row.draggable = true;
  row.innerHTML = `
    <span class="drag-handle">⠿</span>
    <input type="text" name="label[]" placeholder="リンク名（例: About）">
    <input type="text" name="url[]"   placeholder="URL（例: {base}index.php#about）">
    <button type="button" class="del-btn" onclick="this.closest('.nav-row').remove()">×</button>`;
  document.getElementById('navList').appendChild(row);
  setupDrag(row);
  row.querySelector('input').focus();
}

// ドラッグ&ドロップ
let dragSrc = null;
function setupDrag(el) {
  el.addEventListener('dragstart', () => {
    dragSrc = el;
    setTimeout(() => el.style.opacity = '0.4', 0);
  });
  el.addEventListener('dragend', () => el.style.opacity = '');
  el.addEventListener('dragover', e => { e.preventDefault(); el.style.borderTop = '2px solid var(--accent)'; });
  el.addEventListener('dragleave', () => el.style.borderTop = '');
  el.addEventListener('drop', e => {
    e.preventDefault();
    el.style.borderTop = '';
    if (dragSrc && dragSrc !== el) {
      const list = document.getElementById('navList');
      const rows = [...list.children];
      const srcIdx = rows.indexOf(dragSrc);
      const tgtIdx = rows.indexOf(el);
      if (srcIdx < tgtIdx) el.after(dragSrc);
      else el.before(dragSrc);
    }
    dragSrc = null;
  });
}
document.querySelectorAll('.nav-row').forEach(setupDrag);
</script>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
