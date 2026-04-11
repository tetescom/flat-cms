<?php
$page_title = 'ブログ';
$cats = json_decode(file_get_contents(dirname(__DIR__) . '/data/categories.json'), true) ?? [];
include __DIR__ . '/header.php';
$files = glob(dirname(__DIR__) . '/data/news/*.json');
$items = [];
foreach ($files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d && ($d['cat'] ?? '') === 'ブログ') $items[] = $d;
}
usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));
?>
<div class="breadcrumb"><a href="/">HOME</a><span>›</span>ブログ</div>
<div class="page-hero">
  <p class="section-label">Blog</p>
  <h1 class="section-title">ブログ</h1>
</div>
<?php if (empty($items)): ?>
  <div class="blog-empty" style="padding: 80px 60px;">記事がまだありません。</div>
<?php else: ?>
<section style="padding-top: 60px;">
<div class="blog-grid blog-grid-4">
  <?php foreach ($items as $item):
    $cat_slug_b = '';
    foreach ($cats as $c) { if ($c['label'] === $item['cat']) { $cat_slug_b = $c['slug']; break; } }
  ?>
  <a href="/news/<?= $item['id'] ?>" class="blog-card reveal">
    <?php if (!empty($item['thumbnail'])): ?>
      <img class="blog-thumb" src="<?= htmlspecialchars($item['thumbnail']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
    <?php else: ?>
      <img class="blog-thumb" src="<?= htmlspecialchars($seo['no_image'] ?? '/images/uploads/no-image.webp') ?>" alt="No Image" loading="lazy">
    <?php endif; ?>
    <div class="blog-card-body">
      <div class="blog-meta">
        <span class="blog-date"><?= htmlspecialchars($item['date']) ?></span>
        <span class="blog-cat"><?= htmlspecialchars($item['cat']) ?></span>
      </div>
      <p class="blog-title"><?= htmlspecialchars($item['title']) ?></p>
    </div>
  </a>
  <?php endforeach; ?>
</div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
