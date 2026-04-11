<?php
$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']) : '';

// カテゴリー情報を取得
$cats = json_decode(file_get_contents(dirname(__DIR__) . '/data/categories.json'), true) ?? [];
$cat_label = '';
foreach ($cats as $c) {
    if ($c['slug'] === $slug) { $cat_label = $c['label']; break; }
}
if (!$cat_label) { header('Location: /news'); exit; }

// カテゴリーに一致する記事を取得
$files = glob(dirname(__DIR__) . '/data/news/*.json');
$items = [];
foreach ($files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d && ($d['cat'] ?? '') === $cat_label) $items[] = $d;
}
usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));

$page_title = $cat_label;
include __DIR__ . '/header.php';
?>

<div class="breadcrumb"><a href="/">HOME</a><span>›</span><?= htmlspecialchars($cat_label ?? 'カテゴリー') ?></div>
<div class="page-hero">
  <p class="section-label">Category</p>
  <h1 class="section-title"><?= htmlspecialchars($cat_label) ?></h1>
</div>

<div class="news-page-list">
  <a href="/news" class="back-link">← すべてのニュース</a>

  <?php if (empty($items)): ?>
    <p style="color:var(--text-muted); font-size:14px;">この カテゴリーの記事はまだありません。</p>
  <?php else: ?>
    <?php foreach ($items as $item): ?>
    <a href="/news/<?= $item['id'] ?>" class="news-page-item reveal">
      <span class="news-page-date"><?= htmlspecialchars($item['date']) ?></span>
      <span class="news-page-cat"><?= htmlspecialchars($item['cat']) ?></span>
      <span class="news-page-title"><?= htmlspecialchars($item['title']) ?><span class="news-arrow">→</span></span>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
