<?php
require_once './config.php';
require_login();

$seo_file = DATA_DIR . 'seo.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    // 生値で保存する（表示側で htmlspecialchars する。二重エスケープ防止）
    $data = [
        'site_title'           => $_POST['site_title'] ?? '',
        'title_separator'      => $_POST['title_separator'] ?? ' — ',
        'description'          => $_POST['description'] ?? '',
        'keywords'             => $_POST['keywords'] ?? '',
        'contact_email'        => trim($_POST['contact_email'] ?? ''),
        'copyright'            => trim($_POST['copyright'] ?? ''),
        'site_url'             => rtrim($_POST['site_url'] ?? '', '/'),
        'analytics_id'         => preg_replace('/[^A-Z0-9\-]/', '', strtoupper($_POST['analytics_id'] ?? '')),
        'console_verification' => $_POST['console_verification'] ?? '',
    ];
    save_json($seo_file, $data);
    header('Location: ./seo.php?msg=saved');
    exit;
}

$seo = load_json($seo_file) ?: [];
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SEO設定 — 管理画面</title>
<?php include './admin-style.php'; ?>
<style>
  .seo-section { margin-bottom: 40px; }
  .seo-section-title {
    font-size: 10px; letter-spacing: 0.25em; color: var(--accent);
    text-transform: uppercase; margin-bottom: 20px;
    padding-bottom: 10px; border-bottom: 1px solid rgba(200,169,110,0.2);
  }
  .preview-bar {
    background: #111; border: 1px solid #333;
    padding: 16px 20px; margin-top: 12px; font-size: 12px;
  }
  .preview-bar .preview-title { color: #8ab4f8; font-size: 18px; margin-bottom: 4px; }
  .preview-bar .preview-url   { color: #aaa; font-size: 12px; margin-bottom: 4px; }
  .preview-bar .preview-desc  { color: #bbb; font-size: 13px; line-height: 1.5; }
  .char-count { font-size: 11px; color: var(--muted); text-align: right; margin-top: 4px; }
  .char-count.warn { color: #e07070; }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">SEO / Analytics</p>
  <?php if ($msg === 'saved'): ?><div class="msg msg-success">保存しました。</div><?php endif; ?>

  <form method="POST" style="max-width: 760px;">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <!-- 基本設定 -->
    <div class="seo-section">
      <p class="seo-section-title">基本設定</p>
      <div class="form-group">
        <label>サイトタイトル</label>
        <input type="text" name="site_title" id="siteTitle"
               value="<?= htmlspecialchars($seo['site_title'] ?? '') ?>"
               placeholder="Flat CMS">
      </div>
      <div class="form-group">
        <label>タイトル区切り文字（ページタイトル ＋ 区切り ＋ サイトタイトル）</label>
        <input type="text" name="title_separator"
               value="<?= htmlspecialchars($seo['title_separator'] ?? ' — ') ?>"
               style="max-width: 120px;">
      </div>
<div class="form-group">
        <label>コピーライト</label>
        <input type="text" name="copyright"
               value="<?= htmlspecialchars($seo['copyright'] ?? '') ?>"
               placeholder="© 2025 Your Company. All rights reserved.">
      </div>
      <div class="form-group">
        <label>お問い合わせ送信先メールアドレス</label>
        <input type="email" name="contact_email"
               value="<?= htmlspecialchars($seo['contact_email'] ?? '') ?>"
               placeholder="info@example.com">
      </div>

    </div>

    <!-- メタ情報 -->
    <div class="seo-section">
      <p class="seo-section-title">メタ情報</p>
      <div class="form-group">
        <label>メタディスクリプション <span style="color:var(--muted);font-size:10px;">（推奨: 120〜160文字）</span></label>
        <textarea name="description" id="metaDesc" rows="3"
                  oninput="countChars(this, 'descCount', 120, 160)"><?= htmlspecialchars($seo['description'] ?? '') ?></textarea>
        <div class="char-count" id="descCount"></div>
      </div>
      <div class="form-group">
        <label>メタキーワード <span style="color:var(--muted);font-size:10px;">（カンマ区切り）</span></label>
        <input type="text" name="keywords"
               value="<?= htmlspecialchars($seo['keywords'] ?? '') ?>"
               placeholder="脚本制作, サイト制作, 3Dモデリング">
      </div>
      <div class="form-group">
        <label>サイトURL <span style="color:var(--muted);font-size:10px;">（サイトマップ・OGP用）</span></label>
        <input type="text" name="site_url"
               value="<?= htmlspecialchars($seo['site_url'] ?? '') ?>"
               placeholder="https://example.com">
      </div>
<!-- 検索プレビュー -->
      <div class="preview-bar">
        <p style="font-size:10px;letter-spacing:0.15em;color:var(--muted);margin-bottom:10px;">GOOGLE 検索プレビュー</p>
        <div class="preview-title" id="previewTitle"><?= htmlspecialchars($seo['site_title'] ?? 'Flat CMS') ?></div>
        <div class="preview-url">https://your-domain.com/</div>
        <div class="preview-desc" id="previewDesc"><?= htmlspecialchars($seo['description'] ?? '') ?></div>
      </div>
    </div>

    <!-- Google Analytics -->
    <div class="seo-section">
      <p class="seo-section-title">Google Analytics</p>
      <div class="form-group">
        <label>測定ID <span style="color:var(--muted);font-size:10px;">（例: G-XXXXXXXXXX）</span></label>
        <input type="text" name="analytics_id"
               value="<?= htmlspecialchars($seo['analytics_id'] ?? '') ?>"
               placeholder="G-XXXXXXXXXX"
               style="max-width: 240px;">
      </div>
      <?php if (!empty($seo['analytics_id'])): ?>
      <p style="font-size:12px;color:#88cc88;">✓ Analytics タグが出力されています</p>
      <?php endif; ?>
    </div>

    <!-- Search Console -->
    <div class="seo-section">
      <p class="seo-section-title">Google Search Console</p>
      <div class="form-group">
        <label>サイト認証コード <span style="color:var(--muted);font-size:10px;">（HTMLタグ方式のcontent属性の値）</span></label>
        <input type="text" name="console_verification"
               value="<?= htmlspecialchars($seo['console_verification'] ?? '') ?>"
               placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
        <p style="font-size:11px;color:var(--muted);margin-top:8px;">
          Search Console → プロパティを追加 → HTMLタグ →
          <code style="color:var(--accent)">&lt;meta name="google-site-verification" content="<strong>ここの値</strong>"&gt;</code>
        </p>
      </div>
      <?php if (!empty($seo['console_verification'])): ?>
      <p style="font-size:12px;color:#88cc88;">✓ 認証タグが出力されています</p>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">保存する</button>
  </form>
</div>

<script>
// 文字数カウント
function countChars(el, countId, min, max) {
  const len = el.value.length;
  const el2 = document.getElementById(countId);
  el2.textContent = len + '文字';
  el2.className = 'char-count' + (len < min || len > max ? ' warn' : '');
  // プレビュー更新
  if (el.name === 'description') {
    document.getElementById('previewDesc').textContent = el.value;
  }
}

// 初期カウント
const desc = document.getElementById('metaDesc');
if (desc) countChars(desc, 'descCount', 120, 160);

// タイトルプレビュー
document.getElementById('siteTitle').addEventListener('input', e => {
  document.getElementById('previewTitle').textContent = e.target.value;
});
</script>
<div class="powered-by">Powered by Flat CMS</div>
</body>
</html>
