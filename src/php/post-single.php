<?php
require_once __DIR__ . '/render-blocks.php';

$id = isset($_GET['id']) ? basename($_GET['id']) : '';
$data_dir = dirname(__DIR__) . '/data/news/';
$file = $data_dir . $id . '.json';

if (!$id || !file_exists($file)) {
    header('Location: /news');
    exit;
}
$article = json_decode(file_get_contents($file), true);

// 前後の記事を取得
$files = glob($data_dir . '*.json');
$items = [];
foreach ($files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d) $items[] = $d;
}
usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));
$index = array_search($id, array_column($items, 'id'));
$prev = ($index < count($items) - 1) ? $items[$index + 1] : null;
$next = ($index > 0) ? $items[$index - 1] : null;

$page_title = $article['title'];
$og_image   = $article['thumbnail'] ?? '';
$og_desc    = !empty($article['blocks']) ? strip_tags($article['blocks'][0]['text'] ?? '') : '';
$og_type    = 'article';
include __DIR__ . '/header.php';

$cats_data = json_decode(file_get_contents(dirname(__DIR__) . '/data/categories.json'), true) ?? [];
$cat_slug = '';
foreach ($cats_data as $c) { if ($c['label'] === $article['cat']) { $cat_slug = $c['slug']; break; } }

// パンくずの親リンク（ブログカテゴリならブログ、それ以外はお知らせ）
$is_blog = ($article['cat'] ?? '') === 'ブログ';
$parent_label = $is_blog ? 'ブログ' : 'お知らせ';
$parent_url   = $is_blog ? '/blog' : '/news';
?>

<div class="breadcrumb">
  <a href="/">HOME</a><span>›</span>
  <a href="<?= $parent_url ?>"><?= $parent_label ?></a><span>›</span>
  <?= htmlspecialchars($article['title']) ?>
</div>

<div class="detail-wrap">
  <div class="detail-meta">
    <span class="detail-date"><?= htmlspecialchars($article['date']) ?></span>
    <?php if ($cat_slug): ?>
    <a href="/category/<?= htmlspecialchars($cat_slug) ?>" class="detail-cat" style="text-decoration:none;"><?= htmlspecialchars($article['cat']) ?></a>
    <?php else: ?>
    <span class="detail-cat"><?= htmlspecialchars($article['cat']) ?></span>
    <?php endif; ?>
  </div>
  <h1 class="detail-title"><?= htmlspecialchars($article['title']) ?></h1>
  <?php if ($is_blog): ?>
  <div class="detail-thumbnail">
    <img src="<?= !empty($article['thumbnail']) ? htmlspecialchars($article['thumbnail']) : htmlspecialchars($seo['no_image'] ?? '/images/uploads/no-image.webp') ?>" alt="<?= htmlspecialchars($article['title']) ?>">
  </div>
  <?php endif; ?>
  <div class="detail-body">
    <?php
    if (!empty($article['blocks'])) {
        echo render_blocks($article['blocks']);
    } else {
        echo $article['body'] ?? '';
    }
    ?>
  </div>
  <div class="detail-nav">
    <?php if ($prev): ?>
      <a href="/news/<?= $prev['id'] ?>" class="nav-prev"><?= htmlspecialchars($prev['title']) ?></a>
    <?php else: ?><span></span><?php endif; ?>
    <a href="<?= $parent_url ?>" class="nav-back">一覧へ戻る</a>
    <?php if ($next): ?>
      <a href="/news/<?= $next['id'] ?>" class="nav-next"><?= htmlspecialchars($next['title']) ?></a>
    <?php else: ?><span></span><?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
