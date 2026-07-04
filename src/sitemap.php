<?php
header('Content-Type: application/xml; charset=utf-8');

// サイトURLは seo.json の site_url を最優先。無ければ実際のスキーム＋ホスト＋base_path。
$seo_file = __DIR__ . '/data/seo.json';
$seo = file_exists($seo_file) ? json_decode(file_get_contents($seo_file), true) ?? [] : [];
$cfg_file = __DIR__ . '/admin/config.json';
$base_path = file_exists($cfg_file) ? (json_decode(file_get_contents($cfg_file), true)['base_path'] ?? '/') : '/';

if (!empty($seo['site_url'])) {
    $base_url = rtrim($seo['site_url'], '/');
} else {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base_url = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . rtrim($base_path, '/');
}

// 固定ページ一覧
$pages = [];
$page_files = glob(__DIR__ . '/data/pages/*.json');
foreach ($page_files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d && !empty($d['slug'])) $pages[] = $d;
}

// 投稿一覧
$posts = [];
$post_files = glob(__DIR__ . '/data/news/*.json');
foreach ($post_files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if ($d) $posts[] = $d;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <!-- トップ -->
  <url>
    <loc><?= $base_url ?>/</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>

  <!-- 投稿一覧 -->
  <url>
    <loc><?= $base_url ?>/news</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <!-- ブログ一覧 -->
  <url>
    <loc><?= $base_url ?>/blog</loc>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>

  <!-- 投稿詳細 -->
  <?php foreach ($posts as $post): ?>
  <url>
    <loc><?= $base_url ?>/news/<?= htmlspecialchars($post['id']) ?></loc>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
  <?php endforeach; ?>

  <!-- 固定ページ -->
  <?php foreach ($pages as $page): ?>
  <url>
    <loc><?= $base_url ?>/pages/<?= htmlspecialchars($page['slug']) ?>.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  <?php endforeach; ?>

</urlset>
