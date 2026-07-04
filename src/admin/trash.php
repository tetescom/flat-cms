<?php
require_once './config.php';
require_login();

// 復元処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    verify_csrf();
    $id   = basename($_POST['restore']);
    $file = TRASH_DIR . $id . '.json';
    if (file_exists($file)) {
        $data = load_json($file);
        $type = $data['_trash_type'] ?? 'news';
        unset($data['_trash_type'], $data['_trash_date']);
        $dest = ($type === 'page') ? PAGES_DIR : NEWS_DIR;
        save_json($dest . $id . '.json', $data);
        unlink($file);
        // 固定ページは公開用PHPを復元時に再生成する（削除時に消しているため）
        if ($type === 'page') {
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($data['slug'] ?? '')) ?: $id;
            $page_dir = dirname(__DIR__) . '/pages/';
            if (!is_dir($page_dir)) mkdir($page_dir);
            file_put_contents($page_dir . $slug . '.php',
                "<?php\n\$__pf = dirname(__DIR__) . '/data/pages/{$id}.json';\n\$page_data = is_file(\$__pf) ? json_decode(file_get_contents(\$__pf), true) : null;\ninclude dirname(__DIR__) . '/php/page-template.php';\n"
            );
        }
        $back = ($type === 'page') ? 'pages-list.php' : 'post-list.php';
        header('Location: ./' . $back . '?msg=restored');
        exit;
    }
}

// 完全削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purge'])) {
    verify_csrf();
    $id   = basename($_POST['purge']);
    $file = TRASH_DIR . $id . '.json';
    if (file_exists($file)) {
        // 念のため、残っている公開用PHPがあれば除去
        $data = load_json($file);
        if (($data['_trash_type'] ?? '') === 'page') {
            $slug = $data['slug'] ?? '';
            if ($slug !== '') {
                $php = dirname(__DIR__) . '/pages/' . basename($slug) . '.php';
                if (file_exists($php)) unlink($php);
            }
        }
        unlink($file);
    }
    header('Location: ./trash.php?msg=purged');
    exit;
}

// ゴミ箱一覧
$files = glob(TRASH_DIR . '*.json');
$items = [];
foreach ($files as $f) {
    $d = load_json($f);
    if ($d) $items[] = $d;
}
usort($items, fn($a, $b) => strcmp($b['_trash_date'] ?? '', $a['_trash_date'] ?? ''));

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ゴミ箱 — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Trash</p>
  <?php if ($msg === 'purged'): ?><div class="msg msg-success">完全に削除しました。</div><?php endif; ?>
  <?php if ($msg === 'restored'): ?><div class="msg msg-success">復元しました。</div><?php endif; ?>

  <div class="actions-bar">
    <h2>ゴミ箱（<?= count($items) ?>件）</h2>
  </div>

  <div class="item-list">
    <?php foreach ($items as $item): ?>
    <div class="item-row">
      <span class="item-cat" style="min-width:60px;"><?= $item['_trash_type'] === 'page' ? '固定ページ' : 'お知らせ' ?></span>
      <span class="item-title"><?= htmlspecialchars($item['title'] ?? '（無題）') ?></span>
      <span style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($item['_trash_date'] ?? '') ?></span>
      <div class="item-actions">
        <form method="POST" style="display:inline;">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="restore" value="<?= htmlspecialchars($item['id']) ?>">
          <button type="submit" class="btn btn-secondary" style="font-size:11px;padding:4px 10px;">復元</button>
        </form>
        <form method="POST" style="display:inline;" onsubmit="return confirm('完全に削除します。元に戻せません。よろしいですか？')">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="purge" value="<?= htmlspecialchars($item['id']) ?>">
          <button type="submit" class="btn btn-danger" style="font-size:11px;padding:4px 10px;">完全削除</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
    <p style="color:var(--muted);font-size:13px;padding:24px 0;">ゴミ箱は空です。</p>
    <?php endif; ?>
  </div>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
