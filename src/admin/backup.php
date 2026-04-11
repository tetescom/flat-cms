<?php
require_once './config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $zip_name = 'backup_' . date('Ymd_His') . '.zip';
    $zip_path = sys_get_temp_dir() . '/' . $zip_name;

    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE) !== true) {
        die('ZIPの作成に失敗しました。');
    }

    // dataフォルダ以下を全部追加
    $data_dir = dirname(__DIR__) . '/data/';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($data_dir));
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        $relative = 'data/' . substr($file->getPathname(), strlen($data_dir));
        $zip->addFile($file->getPathname(), $relative);
    }
    $zip->close();

    // ダウンロード
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_name . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    unlink($zip_path);
    exit;
}

// 設定バックアップファイル一覧（uploads以外）
$data_dir = dirname(__DIR__) . '/data/';
$counts = [
    '投稿' => count(glob($data_dir . 'news/*.json')),
    '固定ページ' => count(glob($data_dir . 'pages/*.json')),
    'カテゴリー' => file_exists($data_dir . 'categories.json') ? 1 : 0,
    'ナビ' => file_exists($data_dir . 'nav.json') ? 1 : 0,
    'SEO設定' => file_exists($data_dir . 'seo.json') ? 1 : 0,
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>設定バックアップ — 管理画面</title>
<?php include './admin-style.php'; ?>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Backup</p>

  <div class="card" style="max-width:500px;">
    <p style="font-size:13px;color:var(--muted);line-height:2;margin-bottom:24px;">
      サイトのデータ（投稿・固定ページ・設定）をZIPファイルでダウンロードします。<br>
      定期的に設定バックアップを取ることをおすすめします。
    </p>

    <table style="width:100%;margin-bottom:28px;border-collapse:collapse;">
      <?php foreach ($counts as $label => $count): ?>
      <tr style="border-bottom:1px solid #222;">
        <td style="padding:10px 0;font-size:12px;color:var(--muted);letter-spacing:0.1em;"><?= $label ?></td>
        <td style="padding:10px 0;font-size:13px;text-align:right;color:var(--accent);"><?= $count ?>件</td>
      </tr>
      <?php endforeach; ?>
    </table>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <button type="submit" class="btn btn-primary">設定バックアップをダウンロード</button>
    </form>
  </div>
</div>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
