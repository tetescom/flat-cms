<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../php/design-colors.php';

$design_file = dirname(__DIR__) . '/data/design.json';
$design = file_exists($design_file) ? json_decode(file_get_contents($design_file), true) ?? [] : [];
$msg = '';

// seo.jsonも読み込む（画像はseo.jsonに保存）
$seo_file = dirname(__DIR__) . '/data/seo.json';
$seo = file_exists($seo_file) ? json_decode(file_get_contents($seo_file), true) ?? [] : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // アクセントカラー
    $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['accent'] ?? '') ? $_POST['accent'] : '#7c5c3a';
    $design['accent'] = $accent;
    save_json($design_file, $design);

    // 画像設定はseo.jsonに保存
    // 生値で保存（表示側で htmlspecialchars。二重エスケープ防止）
    $seo['favicon']    = trim($_POST['favicon'] ?? '');
    $seo['logo_image'] = trim($_POST['logo_image'] ?? '');
    $seo['hero_image'] = trim($_POST['hero_image'] ?? '');
    $seo['hero_sub']   = trim($_POST['hero_sub'] ?? '');
    $seo['hero_title']    = trim($_POST['hero_title'] ?? '');
    $seo['hero_title_em'] = trim($_POST['hero_title_em'] ?? '');
    $seo['hero_catch'] = trim($_POST['hero_catch'] ?? '');
    $seo['hero_desc']  = trim($_POST['hero_desc'] ?? '');
    $seo['og_image']   = trim($_POST['og_image'] ?? '');
    $seo['no_image']   = trim($_POST['no_image'] ?? '');
    save_json($seo_file, $seo);

    // About設定保存
    if (isset($_POST['about_title']) || isset($_POST['about_text']) || isset($_POST['about_mission'])) {
        $about_file = dirname(__DIR__) . '/data/pages/about.json';
        $about = file_exists($about_file) ? json_decode(file_get_contents($about_file), true) ?? [] : [];
        $about['title']     = trim($_POST['about_title'] ?? '');
        $about['mission']   = trim($_POST['about_mission'] ?? '');
        $about['address']   = trim($_POST['about_address'] ?? '');
        $about['hours']     = trim($_POST['about_hours'] ?? '');
        $about['map_embed'] = trim($_POST['about_map'] ?? '');
        $about['blocks']    = [['type' => 'text', 'text' => trim($_POST['about_text'] ?? '')]];
        save_json($about_file, $about);
    }

    // Works並び順保存
    if (isset($_POST['works_order'])) {
        $new_order = array_filter(array_map('trim', explode(',', $_POST['works_order'])));
        $works_order_file = dirname(__DIR__) . '/data/works_order.json';
        save_json($works_order_file, array_values($new_order));
    }

    $msg = '保存しました。';
}

$current_accent = $design['accent'] ?? '#7c5c3a';
$preview_colors = flatcms_generate_colors($current_accent);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>デザイン設定 — Admin</title>
<?php include './admin-style.php'; ?>
<style>
.color-picker-wrap {
  display: flex; align-items: center; gap: 16px; margin-bottom: 8px;
}
.color-picker-wrap input[type="color"] {
  width: 56px; height: 56px; padding: 4px;
  border: 1px solid var(--border); border-radius: 4px;
  cursor: pointer; background: var(--white); flex-shrink: 0;
}
.color-hex-input {
  width: 130px !important;
  font-family: monospace !important;
  font-size: 15px !important;
  letter-spacing: 0.1em !important;
}
.palette-preview {
  display: flex; gap: 8px; margin-top: 20px; flex-wrap: wrap;
}
.swatch {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
}
.swatch-color {
  width: 48px; height: 48px; border-radius: 4px;
  border: 1px solid rgba(0,0,0,0.1);
}
.swatch-label {
  font-size: 9px; color: var(--muted); letter-spacing: 0.05em; text-align: center;
}
.site-preview {
  margin-top: 32px; border: 1px solid var(--border); border-radius: 4px; overflow: hidden;
}
.preview-bar {
  padding: 12px 20px; font-size: 11px; letter-spacing: 0.2em;
  color: #fff; font-weight: 600;
}
.preview-body {
  padding: 24px; display: flex; gap: 16px; flex-wrap: wrap; align-items: center;
}
.preview-btn {
  padding: 10px 24px; font-size: 12px; border: none; cursor: default;
  letter-spacing: 0.1em; border-radius: 2px;
}
.preview-text-sample { font-size: 13px; line-height: 1.8; }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Design</p>

  <?php if ($msg): ?>
  <div class="msg msg-success"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST" id="designForm">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label style="font-weight:600;">アクセントカラー</label>
        <div class="color-picker-wrap">
          <input type="color" id="colorPicker" value="<?= htmlspecialchars($current_accent) ?>" oninput="syncColor(this.value)">
          <input type="text" name="accent" id="colorHex" class="color-hex-input"
                 value="<?= htmlspecialchars($current_accent) ?>"
                 placeholder="#7c5c3a"
                 oninput="syncFromHex(this.value)">
        </div>
        <small style="color:var(--muted);font-size:11px;">アクセントカラー1色から、サイト全体の配色が自動生成されます。</small>
      </div>

      <!-- カラープレビュー -->
      <div class="palette-preview" id="palettePreview">
        <?php foreach ($preview_colors as $key => $hex): ?>
        <div class="swatch">
          <div class="swatch-color" id="swatch-<?= $key ?>" style="background:<?= $hex ?>;"></div>
          <div class="swatch-label"><?= $key ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- サイトプレビュー -->
      <div class="site-preview" id="sitePreview">
        <div class="preview-bar" id="previewBar" style="background:<?= $preview_colors['accent'] ?>;">
          Aroma Coffee &nbsp;—&nbsp; SINCE 2019
        </div>
        <div class="preview-body" id="previewBody" style="background:<?= $preview_colors['bg'] ?>;">
          <button class="preview-btn" id="previewBtn" style="background:<?= $preview_colors['accent'] ?>;color:#fff;">
            お問い合わせ
          </button>
          <button class="preview-btn" id="previewBtnOutline" style="background:transparent;border:1px solid <?= $preview_colors['accent'] ?>;color:<?= $preview_colors['accent'] ?>;">
            View More
          </button>
          <div class="preview-text-sample" id="previewText" style="color:<?= $preview_colors['text'] ?>;">
            一杯のコーヒーが、あなたの時間を<br>
            <span style="color:<?= $preview_colors['accent'] ?>;font-weight:600;">すこし豊かにする。</span>
          </div>
        </div>
      </div>

      <div style="margin-top:28px;">
        <button type="submit" class="btn btn-primary">保存する</button>
      </div>

      <!-- 画像設定 -->
      <div style="margin-top:48px; border-top:1px solid var(--border); padding-top:32px;">
        <p style="font-size:10px;letter-spacing:0.3em;color:var(--accent);text-transform:uppercase;margin-bottom:24px;">画像設定</p>

        <div class="form-group">
          <label style="font-weight:600;">ファビコン <span style="color:var(--muted);font-size:10px;font-weight:400;">（推奨: 32×32px / .ico .png .svg）</span></label>
          <input type="text" name="favicon"
                 value="<?= htmlspecialchars($seo['favicon'] ?? '') ?>"
                 placeholder="/images/uploads/favicon.png">
          <small style="color:var(--muted);font-size:11px;margin-top:5px;display:block;">メディアからパスをコピーして貼り付けてください。</small>
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヘッダーロゴ画像 <span style="color:var(--muted);font-size:10px;font-weight:400;">（未設定の場合はサイトタイトルを表示）</span></label>
          <input type="text" name="logo_image"
                 value="<?= htmlspecialchars($seo['logo_image'] ?? '') ?>"
                 placeholder="/images/uploads/logo.png">
          <small style="color:var(--muted);font-size:11px;margin-top:5px;display:block;">メディアからパスをコピーして貼り付けてください。</small>
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヒーロー背景画像 <span style="color:var(--muted);font-size:10px;font-weight:400;">（未設定の場合はデフォルト画像）</span></label>
          <input type="text" name="hero_image"
                 value="<?= htmlspecialchars($seo['hero_image'] ?? '') ?>"
                 placeholder="/images/uploads/hero.jpg">
          <small style="color:var(--muted);font-size:11px;margin-top:5px;display:block;">メディアからパスをコピーして貼り付けてください。</small>
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヒーロー サブテキスト</label>
          <input type="text" name="hero_sub"
                 value="<?= htmlspecialchars($seo['hero_sub'] ?? '') ?>"
                 placeholder="Specialty Coffee & Food">
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヒーロー タイトル（1行目）</label>
          <input type="text" name="hero_title"
                 value="<?= htmlspecialchars($seo['hero_title'] ?? '') ?>"
                 placeholder="Aroma">
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヒーロー タイトル（2行目・イタリック）</label>
          <input type="text" name="hero_title_em"
                 value="<?= htmlspecialchars($seo['hero_title_em'] ?? '') ?>"
                 placeholder="Coffee">
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヒーロー キャッチコピー</label>
          <input type="text" name="hero_catch"
                 value="<?= htmlspecialchars($seo['hero_catch'] ?? '') ?>"
                 placeholder="Savor Every Sip">
        </div>

        <div class="form-group">
          <label style="font-weight:600;">ヒーロー 説明文 <span style="color:var(--muted);font-size:10px;font-weight:400;">（改行可）</span></label>
          <textarea name="hero_desc" rows="3" style="width:100%;resize:vertical;"><?= htmlspecialchars($seo['hero_desc'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label style="font-weight:600;">OGP画像URL <span style="color:var(--muted);font-size:10px;font-weight:400;">（SNSシェア時のサムネイル・フルURLで入力）</span></label>
          <input type="text" name="og_image"
                 value="<?= htmlspecialchars($seo['og_image'] ?? '') ?>"
                 placeholder="https://example.com/images/ogp.jpg">
        </div>

        <div class="form-group">
          <label style="font-weight:600;">No Image <span style="color:var(--muted);font-size:10px;font-weight:400;">（サムネイル未設定時に表示する画像）</span></label>
          <input type="text" name="no_image"
                 value="<?= htmlspecialchars($seo['no_image'] ?? '/images/no-image.webp') ?>"
                 placeholder="/images/no-image.webp">
          <small style="color:var(--muted);font-size:11px;margin-top:5px;display:block;">メディアからパスをコピーして貼り付けてください。</small>
        </div>
      </div>

      <div style="margin-top:24px;">
        <button type="submit" class="btn btn-primary">保存する</button>
      </div>
    </form>
  </div>

  <!-- About設定 -->
  <?php
  $about_file = dirname(__DIR__) . '/data/pages/about.json';
  $about_data = file_exists($about_file) ? json_decode(file_get_contents($about_file), true) ?? [] : [];
  ?>
  <div class="card" style="margin-top:32px;">
    <p style="font-size:10px;letter-spacing:0.3em;color:var(--accent);text-transform:uppercase;margin-bottom:24px;">About セクション</p>
    <form method="post">
      <?php csrf_field(); ?>
      <div class="form-group">
        <label style="font-weight:600;">見出し</label>
        <input type="text" name="about_title" value="<?= htmlspecialchars($about_data['title'] ?? '') ?>" placeholder="私たちについて">
      </div>
      <div class="form-group">
        <label style="font-weight:600;">本文 <span style="color:var(--muted);font-size:10px;font-weight:400;">（改行可）</span></label>
        <textarea name="about_text" rows="5" style="width:100%;resize:vertical;"><?= htmlspecialchars($about_data['blocks'][0]['text'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label style="font-weight:600;">ミッション文 <span style="color:var(--muted);font-size:10px;font-weight:400;">（改行可・空欄で非表示）</span></label>
        <textarea name="about_mission" rows="3" style="width:100%;resize:vertical;"><?= htmlspecialchars($about_data['mission'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label style="font-weight:600;">住所</label>
        <input type="text" name="about_address" value="<?= htmlspecialchars($about_data['address'] ?? '') ?>" placeholder="岩手県○○市...">
      </div>
      <div class="form-group">
        <label style="font-weight:600;">営業時間 <span style="color:var(--muted);font-size:10px;font-weight:400;">（改行可）</span></label>
        <textarea name="about_hours" rows="3" style="width:100%;resize:vertical;"><?= htmlspecialchars($about_data['hours'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label style="font-weight:600;">Googleマップ埋め込みコード <span style="color:var(--muted);font-size:10px;font-weight:400;">（iframeタグをそのまま貼り付け・空欄で非表示）</span></label>
        <textarea name="about_map" rows="3" style="width:100%;resize:vertical;"><?= htmlspecialchars($about_data['map_embed'] ?? '') ?></textarea>
      </div>
      <div style="margin-top:24px;">
        <button type="submit" class="btn btn-primary">保存する</button>
      </div>
    </form>
  </div>

  <!-- Works並び順 -->
  <?php
  $works_order_file = dirname(__DIR__) . '/data/works_order.json';
  $works_order = file_exists($works_order_file) ? json_decode(file_get_contents($works_order_file), true) ?? [] : [];
  $pages_files = glob(dirname(__DIR__) . '/data/pages/*.json');
  $works_pages = [];
  foreach ($pages_files as $pf) {
    $pd = load_json($pf);
    if ($pd && !empty($pd['show_in_works'])) $works_pages[$pd['id']] = $pd;
  }
  // order通りに並べる
  $sorted_works = [];
  foreach ($works_order as $oid) {
    if (isset($works_pages[$oid])) { $sorted_works[] = $works_pages[$oid]; unset($works_pages[$oid]); }
  }
  foreach ($works_pages as $w) $sorted_works[] = $w;
  ?>
  <div class="card" style="margin-top:24px;">
    <form method="POST" id="worksOrderForm">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="works_order" id="worksOrderInput" value="<?= htmlspecialchars(implode(',', array_column($sorted_works, 'id'))) ?>">

      <p style="font-size:10px;letter-spacing:0.3em;color:var(--accent);text-transform:uppercase;margin-bottom:8px;">Worksの表示順</p>
      <p style="font-size:12px;color:var(--muted);margin-bottom:20px;">ドラッグ＆ドロップで並び替えができます。</p>

      <div id="worksSortList" style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;">
        <?php foreach ($sorted_works as $w): ?>
        <div class="works-sort-item" data-id="<?= htmlspecialchars($w['id']) ?>"
             style="display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--bg);border:1px solid var(--border);border-radius:3px;cursor:grab;user-select:none;">
          <span style="color:var(--muted);font-size:18px;line-height:1;">⠿</span>
          <?php if (!empty($w['thumbnail'])): ?>
          <img src="<?= htmlspecialchars($w['thumbnail']) ?>" style="width:48px;height:36px;object-fit:cover;border:1px solid var(--border);">
          <?php else: ?>
          <div style="width:48px;height:36px;background:var(--border);"></div>
          <?php endif; ?>
          <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($w['title']) ?></span>
          <span style="font-size:11px;color:var(--muted);margin-left:auto;"><?= htmlspecialchars($w['cat'] ?? '') ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (empty($sorted_works)): ?>
        <p style="font-size:13px;color:var(--muted);">「固定ページ」でWorksに表示するをオンにすると、ここに表示されます。</p>
        <?php endif; ?>
      </div>

      <?php if (!empty($sorted_works)): ?>
      <button type="submit" class="btn btn-primary">順番を保存する</button>
      <?php endif; ?>
    </form>
  </div>
</div>

<script>
// PHP側で生成した色データをJSに渡す
const baseAccent = <?= json_encode($current_accent) ?>;

function hexToHsl(hex) {
  hex = hex.replace('#','');
  if (hex.length === 3) hex = hex.split('').map(c=>c+c).join('');
  const r = parseInt(hex.substr(0,2),16)/255;
  const g = parseInt(hex.substr(2,2),16)/255;
  const b = parseInt(hex.substr(4,2),16)/255;
  const max = Math.max(r,g,b), min = Math.min(r,g,b);
  let h, s, l = (max+min)/2;
  const d = max - min;
  if (d === 0) { h = s = 0; }
  else {
    s = d / (1 - Math.abs(2*l-1));
    switch(max) {
      case r: h = ((g-b)/d % 6); break;
      case g: h = (b-r)/d + 2; break;
      default: h = (r-g)/d + 4;
    }
    h = Math.round(h * 60);
    if (h < 0) h += 360;
  }
  return [h, Math.round(s*100), Math.round(l*100)];
}

function hslToHex(h, s, l) {
  s /= 100; l /= 100;
  const c = (1 - Math.abs(2*l-1)) * s;
  const x = c * (1 - Math.abs((h/60)%2-1));
  const m = l - c/2;
  let r,g,b;
  if (h < 60)      [r,g,b]=[c,x,0];
  else if (h < 120)[r,g,b]=[x,c,0];
  else if (h < 180)[r,g,b]=[0,c,x];
  else if (h < 240)[r,g,b]=[0,x,c];
  else if (h < 300)[r,g,b]=[x,0,c];
  else             [r,g,b]=[c,0,x];
  const toHex = n => Math.round((n+m)*255).toString(16).padStart(2,'0');
  return '#' + toHex(r) + toHex(g) + toHex(b);
}

function clamp(v, min, max) { return Math.min(max, Math.max(min, v)); }

function generateColors(hex) {
  const [h, s, l] = hexToHsl(hex);
  return {
    'accent':     hex,
    'accent2':    hslToHex(h, clamp(s-10,0,100), clamp(l+15,0,100)),
    'accent-lt':  hslToHex(h, clamp(s-30,0,100), clamp(l+42,0,100)),
    'border':     hslToHex(h, clamp(s-40,0,100), clamp(l+48,0,100)),
    'bg':         hslToHex(h, clamp(s-55,0,100), clamp(l+54,0,100)),
    'bg2':        hslToHex(h, clamp(s-50,0,100), clamp(l+50,0,100)),
    'text':       hslToHex(h, clamp(s+5,0,100),  clamp(l-55,0,100)),
    'text-sub':   hslToHex(h, clamp(s-10,0,100), clamp(l-30,0,100)),
    'text-muted': hslToHex(h, clamp(s-20,0,100), clamp(l-10,0,100)),
  };
}

function updatePreview(hex) {
  if (!/^#[0-9a-fA-F]{6}$/.test(hex)) return;
  const colors = generateColors(hex);

  // スウォッチ更新
  Object.entries(colors).forEach(([key, color]) => {
    const el = document.getElementById('swatch-' + key);
    if (el) el.style.background = color;
  });

  // サイトプレビュー更新
  document.getElementById('previewBar').style.background = colors['accent'];
  document.getElementById('previewBody').style.background = colors['bg'];
  document.getElementById('previewBtn').style.background = colors['accent'];
  document.getElementById('previewBtnOutline').style.borderColor = colors['accent'];
  document.getElementById('previewBtnOutline').style.color = colors['accent'];
  document.getElementById('previewText').style.color = colors['text'];
  document.getElementById('previewText').querySelector('span').style.color = colors['accent'];
}

function syncColor(val) {
  document.getElementById('colorHex').value = val;
  updatePreview(val);
}

function syncFromHex(val) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    document.getElementById('colorPicker').value = val;
    updatePreview(val);
  }
}
// Works並び順ドラッグ&ドロップ
(function() {
  const list = document.getElementById('worksSortList');
  const input = document.getElementById('worksOrderInput');
  if (!list) return;

  let dragSrc = null;

  function updateInput() {
    const ids = [...list.querySelectorAll('.works-sort-item')].map(el => el.dataset.id);
    input.value = ids.join(',');
  }

  list.querySelectorAll('.works-sort-item').forEach(item => {
    item.addEventListener('dragstart', e => {
      dragSrc = item;
      setTimeout(() => item.style.opacity = '0.4', 0);
      e.dataTransfer.effectAllowed = 'move';
    });
    item.addEventListener('dragend', () => { item.style.opacity = '1'; });
    item.addEventListener('dragover', e => {
      e.preventDefault();
      item.style.borderColor = 'var(--accent)';
    });
    item.addEventListener('dragleave', () => { item.style.borderColor = 'var(--border)'; });
    item.addEventListener('drop', e => {
      e.preventDefault();
      item.style.borderColor = 'var(--border)';
      if (dragSrc && dragSrc !== item) {
        const allItems = [...list.querySelectorAll('.works-sort-item')];
        const srcIdx = allItems.indexOf(dragSrc);
        const tgtIdx = allItems.indexOf(item);
        if (srcIdx < tgtIdx) item.after(dragSrc);
        else item.before(dragSrc);
        updateInput();
      }
    });
    item.setAttribute('draggable', true);
  });
})();
</script>
</body>
</html>
