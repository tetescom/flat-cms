<?php
require_once './config.php';
require_login();

$upload_dir = dirname(__DIR__) . '/images/uploads/';
$upload_url = '../images/uploads/';

$msg = '';
$err = '';

// アップロード処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    verify_csrf();
    $file = $_FILES['file'];
    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    // 拡張子チェック
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // MIMEタイプをサーバー側で再検証（クライアント送信値は信用しない）
    $finfo     = new finfo(FILEINFO_MIME_TYPE);
    $real_mime = $finfo->file($file['tmp_name']);

    // SVGは特別扱い（finfоがtext/xmlなどを返すことがある）
    if ($ext === 'svg') $real_mime = 'image/svg+xml';

    if (!in_array($ext, $allowed_ext)) {
        $err = '許可されていない拡張子です（jpg/png/gif/webp/svgのみ）。';
    } elseif (!in_array($real_mime, $allowed_mime)) {
        $err = 'ファイルの中身が画像ではありません。';
    } elseif ($file['size'] > 10 * 1024 * 1024) {
        $err = 'ファイルサイズが大きすぎます（上限10MB）。';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $err = 'アップロードエラーが発生しました。';
    } else {
        $basename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));

        // SVG以外はWebPに変換
        if ($ext !== 'svg' && function_exists('imagewebp')) {
            $filename = $basename . '_' . time() . '.webp';
            $dest = $upload_dir . $filename;

            // 元画像を読み込み
            $img = null;
            if ($real_mime === 'image/jpeg') $img = imagecreatefromjpeg($file['tmp_name']);
            elseif ($real_mime === 'image/png') {
                $img = imagecreatefrompng($file['tmp_name']);
                // 透過を保持
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
            elseif ($real_mime === 'image/gif') $img = imagecreatefromgif($file['tmp_name']);
            elseif ($real_mime === 'image/webp') $img = imagecreatefromwebp($file['tmp_name']);

            if ($img) {
                imagewebp($img, $dest, 82); // 品質82（軽さと画質のバランス）
                imagedestroy($img);
                $msg = 'アップロードしました（WebP変換済み）：' . $filename;
            } else {
                // 変換失敗時はそのまま保存
                $filename = $basename . '_' . time() . '.' . $ext;
                $dest = $upload_dir . $filename;
                move_uploaded_file($file['tmp_name'], $dest);
                $msg = 'アップロードしました：' . $filename;
            }
        } else {
            // SVGはそのまま保存
            $filename = $basename . '_' . time() . '.' . $ext;
            $dest = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $msg = 'アップロードしました：' . $filename;
            } else {
                $err = 'アップロードに失敗しました。フォルダの書き込み権限を確認してください。';
            }
        }
    }
}

// 削除処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    verify_csrf();
    $target = $upload_dir . basename($_POST['delete']);
    if (file_exists($target)) unlink($target);
    header('Location: ./media.php?msg=deleted');
    exit;
}
if (($_GET['msg'] ?? '') === 'deleted') $msg = '削除しました。';

// 画像一覧を取得（新しい順）
$exts  = ['jpg','jpeg','png','gif','webp','svg'];
$files = [];
foreach ($exts as $ext) {
    $files = array_merge($files, glob($upload_dir . '*.' . $ext));
    $files = array_merge($files, glob($upload_dir . '*.' . strtoupper($ext)));
}
usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>メディア — 管理画面</title>
<?php include './admin-style.php'; ?>
<style>
.upload-area {
    border: 2px dashed #333;
    padding: 32px;
    text-align: center;
    margin-bottom: 32px;
    transition: border-color 0.3s;
    cursor: pointer;
    position: relative;
}
.upload-area:hover, .upload-area.dragover { border-color: var(--accent); }
.upload-area input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-area-text { font-size: 13px; color: var(--muted); pointer-events: none; }
.upload-area-text span { color: var(--accent); }
.upload-area-preview { margin-top: 16px; display: none; }
.upload-area-preview img { max-height: 80px; border: 1px solid #333; }

.upload-btn {
    margin-top: 16px;
    display: none;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 16px;
}
.media-item {
    background: #111;
    border: 1px solid #222;
    overflow: hidden;
    transition: border-color 0.2s;
    position: relative;
}
.media-item:hover { border-color: rgba(200,169,110,0.4); }
.media-thumb {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    display: block;
    background: #1a1a1a;
}
.media-item-body { padding: 10px; }
.media-filename {
    font-size: 10px; color: var(--muted); letter-spacing: 0.05em;
    overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
    margin-bottom: 8px;
}
.media-actions { display: flex; gap: 6px; }
.media-actions button, .media-actions a {
    flex: 1; background: transparent; border: 1px solid #333;
    color: #666; font-size: 10px; padding: 5px 4px; cursor: pointer;
    text-align: center; text-decoration: none; letter-spacing: 0.05em;
    transition: all 0.2s;
}
.media-actions button:hover { border-color: var(--accent); color: var(--accent); }
.media-actions a.del:hover { border-color: #884444; color: #cc6666; }
.copy-done { color: var(--accent) !important; border-color: var(--accent) !important; }

.media-empty { color: var(--muted); font-size: 13px; padding: 40px 0; text-align: center; }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Media</p>
  <?php if ($msg): ?><div class="msg msg-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <!-- アップロードエリア -->
  <form method="POST" enctype="multipart/form-data" id="uploadForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="upload-area" id="uploadArea">
      <input type="file" name="file" id="fileInput" accept="image/*">
      <div class="upload-area-text">
        <p style="font-size:28px;margin-bottom:8px;color:#333;">↑</p>
        <p>クリックまたはドラッグ＆ドロップで画像をアップロード</p>
        <p style="font-size:11px;margin-top:6px;">jpg / png / gif / webp / svg（最大10MB）</p>
      </div>
      <div class="upload-area-preview" id="previewArea">
        <img id="previewImg" src="" alt="">
        <p id="previewName" style="font-size:11px;color:var(--muted);margin-top:6px;"></p>
      </div>
    </div>
    <div class="upload-btn" id="uploadBtn">
      <button type="submit" class="btn btn-primary">アップロードする</button>
      <button type="button" class="btn btn-secondary" style="margin-left:12px;" onclick="resetUpload()">キャンセル</button>
    </div>
  </form>

  <!-- メディア一覧 -->
  <div class="actions-bar" style="margin-top:8px;">
    <h2>アップロード済み（<?= count($files) ?>件）</h2>
  </div>

  <?php if (empty($files)): ?>
    <div class="media-empty">画像がまだありません。</div>
  <?php else: ?>
  <div class="media-grid">
    <?php foreach ($files as $f):
      $fname = basename($f);
      $furl  = $upload_url . $fname;
      $ext   = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
      $is_svg = $ext === 'svg';
    ?>
    <div class="media-item">
      <?php if ($is_svg): ?>
        <div style="width:100%;aspect-ratio:1;background:#1a1a1a;display:flex;align-items:center;justify-content:center;">
          <img src="<?= htmlspecialchars($furl) ?>" style="max-width:80%;max-height:80%;">
        </div>
      <?php else: ?>
        <img class="media-thumb" src="<?= htmlspecialchars($furl) ?>" alt="<?= htmlspecialchars($fname) ?>" loading="lazy">
      <?php endif; ?>
      <div class="media-item-body">
        <p class="media-filename" title="<?= htmlspecialchars($fname) ?>"><?= htmlspecialchars($fname) ?></p>
        <div class="media-actions">
          <button type="button" onclick="copyPath('<?= htmlspecialchars($furl) ?>', this)">パスをコピー</button>
          <form method="POST" style="flex:1;" onsubmit="return confirm('削除しますか？')">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="delete" value="<?= htmlspecialchars($fname) ?>">
            <button type="submit" class="del" style="width:100%;">削除</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
// パスをコピー
function copyPath(path, btn) {
  navigator.clipboard.writeText(path).then(() => {
    btn.textContent = 'コピー完了!';
    btn.classList.add('copy-done');
    setTimeout(() => {
      btn.textContent = 'パスをコピー';
      btn.classList.remove('copy-done');
    }, 2000);
  });
}

// ファイル選択プレビュー
document.getElementById('fileInput').addEventListener('change', function() {
  if (this.files[0]) showPreview(this.files[0]);
});

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('previewImg').src = e.target.result;
    document.getElementById('previewName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + 'KB)';
    document.getElementById('previewArea').style.display = 'block';
    document.getElementById('uploadBtn').style.display = 'block';
    document.querySelector('.upload-area-text').style.display = 'none';
  };
  reader.readAsDataURL(file);
}

function resetUpload() {
  document.getElementById('fileInput').value = '';
  document.getElementById('previewArea').style.display = 'none';
  document.getElementById('uploadBtn').style.display = 'none';
  document.querySelector('.upload-area-text').style.display = 'block';
}

// ドラッグ&ドロップ
const area = document.getElementById('uploadArea');
area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
area.addEventListener('dragleave', () => area.classList.remove('dragover'));
area.addEventListener('drop', e => {
  e.preventDefault();
  area.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file && file.type.startsWith('image/')) {
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('fileInput').files = dt.files;
    showPreview(file);
  }
});
</script>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
