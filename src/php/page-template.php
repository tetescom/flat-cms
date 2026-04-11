<?php
require_once __DIR__ . '/render-blocks.php';
$page_title = $page_data['title'] ?? '';
$og_image   = $page_data['thumbnail'] ?? '';
$og_desc    = !empty($page_data['blocks']) ? strip_tags($page_data['blocks'][0]['text'] ?? '') : '';
include __DIR__ . '/header.php';
?>
<div class="breadcrumb">
  <a href="/">HOME</a><span>›</span>
  <?= htmlspecialchars($page_data['title']) ?>
</div>
<div class="detail-wrap">
  <p class="detail-label"><?= htmlspecialchars($page_data['cat'] ?? 'Page') ?></p>
  <h1 class="detail-title"><?= htmlspecialchars($page_data['title']) ?></h1>
  <?php if (!empty($page_data['show_in_works'])): ?>
  <div class="detail-thumbnail">
    <img src="<?= !empty($page_data['thumbnail']) ? htmlspecialchars($page_data['thumbnail']) : htmlspecialchars($seo['no_image'] ?? '/images/uploads/no-image.webp') ?>" alt="<?= htmlspecialchars($page_data['title']) ?>">
  </div>
  <?php endif; ?>
  <div class="detail-body">
    <?php
    if (!empty($page_data['blocks'])) {
        echo render_blocks($page_data['blocks']);
    } else {
        echo $page_data['body'] ?? '';
    }
    ?>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
