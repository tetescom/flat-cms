<?php
$page_title = '最新情報';
$cats = json_decode(file_get_contents(dirname(__DIR__) . '/data/categories.json'), true) ?? [];
include __DIR__ . '/header.php';
?>
<div class="breadcrumb"><a href="<?= $base_path ?>">HOME</a><span>›</span>最新情報</div>
<div class="page-hero">
  <p class="section-label">News</p>
  <h1 class="section-title">最新情報</h1>
</div>
<div class="cat-nav">
  <a href="<?= $base_path ?>news" class="active">すべて</a>
  <?php foreach ($cats as $cat): ?>
  <a href="<?= $base_path ?>category/<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['label']) ?></a>
  <?php endforeach; ?>
</div>
<?php
$files = glob(dirname(__DIR__) . '/data/news/*.json');
$per_page = 10;
$current_page = max(1, (int)($_GET['p'] ?? 1));
$items = [];
foreach ($files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d) $items[] = $d;
}
usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));
$total = count($items);
$total_pages = max(1, ceil($total / $per_page));
$current_page = min($current_page, $total_pages);
$items = array_slice($items, ($current_page - 1) * $per_page, $per_page);
$cat_slugs = [];
foreach ($cats as $c) $cat_slugs[$c['label']] = $c['slug'];
?>
<div class="news-page-list">
<?php foreach ($items as $item):
  $cat_slug = $cat_slugs[$item['cat']] ?? '';
?>
  <a href="<?= $base_path ?>news/<?= $item['id'] ?>" class="news-page-item reveal">
    <span class="news-page-date"><?= htmlspecialchars($item['date']) ?></span>
    <?php if ($cat_slug): ?>
    <span class="news-page-cat" onclick="event.preventDefault();location.href='<?= $base_path ?>category/<?= htmlspecialchars($cat_slug) ?>'"><?= htmlspecialchars($item['cat']) ?></span>
    <?php else: ?>
    <span class="news-page-cat"><?= htmlspecialchars($item['cat']) ?></span>
    <?php endif; ?>
    <span class="news-page-title"><?= htmlspecialchars($item['title']) ?><span class="news-arrow">→</span></span>
  </a>
<?php endforeach; ?>
<?php if (empty($items)): ?>
  <p style="padding:60px 0;color:var(--text-muted);font-size:13px;">投稿はまだありません。</p>
<?php endif; ?>
</div>
<?php if ($total_pages > 1): ?>
<div class="pagination">
  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
  <a href="?p=<?= $i ?>" class="<?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
