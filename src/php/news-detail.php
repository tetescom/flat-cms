<?php
$base_path = '/';
include __DIR__ . '/header.php';
?>
<div class="detail-wrap">
  <div class="detail-meta">
    <span class="detail-date"><?= htmlspecialchars($article['date']) ?></span>
    <span class="detail-cat"><?= htmlspecialchars($article['cat']) ?></span>
  </div>
  <h1 class="detail-title"><?= htmlspecialchars($article['title']) ?></h1>
  <?php if (!empty($article['thumbnail'])): ?>
  <div class="detail-thumbnail">
    <img src="<?= htmlspecialchars($article['thumbnail']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" style="width:100%;height:auto;display:block;margin-bottom:40px;">
  </div>
  <?php endif; ?>
  <div class="detail-body">
    <?php
    require_once __DIR__ . '/render-blocks.php';
    if (!empty($article['blocks'])) {
        echo render_blocks($article['blocks']);
    } else {
        echo $article['body'] ?? '';
    }
    ?>
  </div>
  <div class="detail-nav">
    <?php if ($prev): ?>
      <a href="./news-detail.php?id=<?= $prev['id'] ?>" class="nav-prev"><?= htmlspecialchars($prev['title']) ?></a>
    <?php else: ?><span></span><?php endif; ?>
    <a href="./news.php" class="nav-back">一覧へ戻る</a>
    <?php if ($next): ?>
      <a href="./news-detail.php?id=<?= $next['id'] ?>" class="nav-next"><?= htmlspecialchars($next['title']) ?></a>
    <?php else: ?><span></span><?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
