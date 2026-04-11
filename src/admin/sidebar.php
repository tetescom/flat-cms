<?php
$_seo_file = dirname(__DIR__) . '/data/seo.json';
$_seo = file_exists($_seo_file) ? json_decode(file_get_contents($_seo_file), true) ?? [] : [];
$_site_title = $_seo['site_title'] ?? 'Flat CMS';
?>
<div class="sidebar">
  <a href="../" target="_blank" class="sidebar-logo" style="text-decoration:none;"><?= htmlspecialchars($_site_title) ?><br><span style="font-size:9px;opacity:0.5;letter-spacing:0.15em;">ADMIN</span></a>
  <nav class="sidebar-nav">
    <a href="./dashboard.php" <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
    <a href="./post-list.php" <?= in_array(basename($_SERVER['PHP_SELF']), ['post-list.php','post-edit.php']) ? 'class="active"' : '' ?>>投稿</a>
    <a href="./pages-list.php" <?= in_array(basename($_SERVER['PHP_SELF']), ['pages-list.php','pages-edit.php']) ? 'class="active"' : '' ?>>固定ページ</a>
    <a href="./trash.php" <?= basename($_SERVER['PHP_SELF']) === 'trash.php' ? 'class="active"' : '' ?>>ゴミ箱</a>
    <a href="./categories.php" <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'class="active"' : '' ?>>カテゴリー</a>
    <a href="./media.php" <?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'class="active"' : '' ?>>メディア</a>
    <a href="./nav.php" <?= basename($_SERVER['PHP_SELF']) === 'nav.php' ? 'class="active"' : '' ?>>ナビゲーション</a>
    <a href="./sns.php" <?= basename($_SERVER['PHP_SELF']) === 'sns.php' ? 'class="active"' : '' ?>>SNS</a>
    <a href="./form-builder.php" <?= basename($_SERVER['PHP_SELF']) === 'form-builder.php' ? 'class="active"' : '' ?>>フォーム設定</a>
    <a href="./seo.php" <?= basename($_SERVER['PHP_SELF']) === 'seo.php' ? 'class="active"' : '' ?>>SEO / Analytics</a>
    <a href="./design.php" <?= basename($_SERVER['PHP_SELF']) === 'design.php' ? 'class="active"' : '' ?>>デザイン設定</a>
    <a href="./security.php" <?= basename($_SERVER['PHP_SELF']) === 'security.php' ? 'class="active"' : '' ?>>セキュリティ</a>
    <a href="./backup.php" <?= basename($_SERVER['PHP_SELF']) === 'backup.php' ? 'class="active"' : '' ?>>設定バックアップ</a>
  </nav>
  <div class="sidebar-logout">
    <a href="./password.php" style="display:block;font-size:10px;letter-spacing:0.15em;color:var(--muted);text-decoration:none;padding:8px 28px;transition:color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--muted)'">パスワード変更</a>
    <a href="./logout.php">ログアウト</a>
  </div>
</div>
