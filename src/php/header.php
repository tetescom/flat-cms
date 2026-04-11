<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// SEO設定を読み込む
$seo_file = __DIR__ . '/../data/seo.json';
$seo = file_exists($seo_file) ? json_decode(file_get_contents($seo_file), true) ?? [] : [];
$base_path = '/';
$site_title = $seo['site_title'] ?? 'Aroma Coffee';
$separator  = $seo['title_separator'] ?? ' — ';
$page_title = isset($page_title) ? $page_title . $separator . $site_title : $site_title;

// ナビデータを読み込む
$nav_file = __DIR__ . '/../data/nav.json';
$nav_items = file_exists($nav_file)
    ? json_decode(file_get_contents($nav_file), true) ?? []
    : [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<?php if (!empty($seo['description'])): ?>
<meta name="description" content="<?= htmlspecialchars($seo['description']) ?>">
<?php endif; ?>
<?php if (!empty($seo['keywords'])): ?>
<meta name="keywords" content="<?= htmlspecialchars($seo['keywords']) ?>">
<?php endif; ?>
<?php
// ページ個別のOGP画像があればそちらを優先
$og_image = $og_image ?? $seo['og_image'] ?? '';
$og_desc  = $og_desc  ?? $seo['description'] ?? '';
$og_type  = $og_type  ?? 'website';
$og_url   = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<?php if ($og_image): ?>
<meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
<?php endif; ?>
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
<meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($og_desc) ?>">
<meta property="og:type" content="<?= htmlspecialchars($og_type) ?>">
<meta property="og:url" content="<?= htmlspecialchars($og_url) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($og_desc) ?>">
<?php if ($og_image): ?>
<meta name="twitter:image" content="<?= htmlspecialchars($og_image) ?>">
<?php endif; ?>
<?php if (!empty($seo['console_verification'])): ?>
<meta name="google-site-verification" content="<?= htmlspecialchars($seo['console_verification']) ?>">
<?php endif; ?>
<?php if (!empty($seo['analytics_id'])): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($seo['analytics_id']) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= htmlspecialchars($seo['analytics_id']) ?>');
</script>
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400&family=Noto+Serif+JP:wght@200;400&family=Noto+Sans+JP:wght@300;400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base_path ?>data/style.css">
<?php
// デザイン設定からカラーを動的生成
require_once __DIR__ . '/design-colors.php';
$design_file = __DIR__ . '/../data/design.json';
$design = file_exists($design_file) ? json_decode(file_get_contents($design_file), true) ?? [] : [];
$accent_hex = $design['accent'] ?? '#7c5c3a';
$colors = flatcms_generate_colors($accent_hex);
?>
<style>
:root {
  --accent:     <?= $colors['accent'] ?>;
  --accent2:    <?= $colors['accent2'] ?>;
  --accent-lt:  <?= $colors['accent-lt'] ?>;
  --border:     <?= $colors['border'] ?>;
  --bg:         <?= $colors['bg'] ?>;
  --bg2:        <?= $colors['bg2'] ?>;
  --text:       <?= $colors['text'] ?>;
  --text-sub:   <?= $colors['text-sub'] ?>;
  --text-muted: <?= $colors['text-muted'] ?>;
}
</style>
  <link rel="icon" href="<?= !empty($seo['favicon']) ? $base_path . ltrim(htmlspecialchars($seo['favicon']), '/') : $base_path . 'favicon.ico' ?>">
</head>
<body>

<header>
  <div class="logo">
    <a href="<?= $base_path ?>" class="nav-logo">
      <?php if (!empty($seo['logo_image'])): ?>
      <img src="<?= $base_path ?><?= ltrim(htmlspecialchars($seo['logo_image']), '/') ?>" alt="<?= htmlspecialchars($seo['site_title'] ?? '') ?>" style="height:32px;width:auto;vertical-align:middle;">
      <?php else: ?>
      <?= htmlspecialchars($seo['site_title'] ?? 'サイト名') ?>
      <?php endif; ?>
    </a>
  </div>
  <button class="hamburger" id="hamburger" aria-label="メニューを開く" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
  <nav id="globalNav">
    <ul class="nav-links" id="navLinks">
      <?php
      $current_url = strtok($_SERVER['REQUEST_URI'], '?');
      foreach ($nav_items as $item):
        $url = str_replace('{base}', $base_path, $item['url']);
        $item_path = strtok($url, '?');
        $is_anchor  = strpos($url, '#') !== false && strpos($url, '#') === strpos($url, $base_path) + strlen($base_path);
        $is_current = !$is_anchor && (($current_url === $item_path) || ($item_path !== '/' && strpos($current_url, $item_path) === 0));
      ?>
      <li><a href="<?= htmlspecialchars($url) ?>" <?= $is_current ? 'class="current"' : '' ?>><?= htmlspecialchars($item['label']) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </nav>
  <div class="nav-overlay" id="navOverlay"></div>
</header>
