<?php
require_once './config.php';
require_login();

// カテゴリー読み込み
$categories = load_json(DATA_DIR . 'categories.json') ?: [];

// type: 'news' or 'page'
$type   = $_GET['type'] ?? 'page';
$id     = $_GET['id'] ?? null;
$is_edit = false;

$data_dir = ($type === 'news') ? NEWS_DIR : PAGES_DIR;
$cats = load_json(DATA_DIR . 'categories.json') ?: [];
$item = [
    'id'     => '',
    'title'  => '',
    'blocks' => [],
    // news用
    'date'   => date('Y.m.d'),
    'cat'    => '',
    // page用
    'slug'   => '',
];

if ($id) {
    $loaded = load_json($data_dir . basename($id) . '.json');
    if ($loaded) { $item = $loaded; $is_edit = true; }
}

// 保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $new_id = $id ?: uniqid();
    $blocks = json_decode($_POST['blocks_json'] ?? '[]', true) ?: [];

    $save = [
        'id'     => $new_id,
        'title'     => htmlspecialchars($_POST['title'] ?? ''),
        'thumbnail' => htmlspecialchars($_POST['thumbnail'] ?? ''),
        'blocks' => $blocks,
    ];

    if ($type === 'news') {
        $save['date'] = htmlspecialchars($_POST['date'] ?? date('Y.m.d'));
        $save['cat']  = htmlspecialchars($_POST['cat'] ?? '');
    } else {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['slug'] ?? ''));
        $slug = $slug ?: $new_id;
        $save['slug']          = $slug;
        $save['thumbnail']     = htmlspecialchars($_POST['thumbnail'] ?? '');
        $save['cat']           = htmlspecialchars($_POST['cat'] ?? '');
        $save['show_in_works'] = isset($_POST['show_in_works']) ? true : false;
        // ページPHPファイルを自動生成
        $page_dir = dirname(__DIR__) . '/pages/';
        if (!is_dir($page_dir)) mkdir($page_dir);
        file_put_contents($page_dir . $slug . '.php',
            "<?php\n\$page_data = json_decode(file_get_contents(dirname(__DIR__) . '/data/pages/{$new_id}.json'), true);\ninclude dirname(__DIR__) . '/php/page-template.php';\n"
        );
    }

    save_json($data_dir . $new_id . '.json', $save);
    $redirect = ($type === 'news') ? "./block-editor.php?type=news&id={$new_id}" : "./block-editor.php?type=page&id={$new_id}";
    header('Location: ' . $redirect . '&msg=saved');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ブロックエディタ — 管理画面</title>
<?php include './admin-style.php'; ?>
<style>
/* ブロックエディタ固有スタイル */
.editor-wrap { max-width: 860px; }

.meta-bar {
    display: grid;
    gap: 16px;
    margin-bottom: 28px;
}
.meta-bar.news-meta { grid-template-columns: 140px 160px 1fr; }
.meta-bar.page-meta { grid-template-columns: 1fr 1fr 1fr; }

.title-input {
    width: 100%;
    background: var(--white);
    border: none;
    border-bottom: 2px solid var(--border);
    color: var(--text);
    font-size: 22px;
    font-family: inherit;
    padding: 12px 4px;
    outline: none;
    margin-bottom: 28px;
    transition: border-color 0.2s;
}
.title-input:focus { border-color: var(--accent); }
.title-input::placeholder { color: var(--muted); }

/* ブロックリスト */
.block-list { margin-bottom: 20px; min-height: 40px; }

.block-item {
    background: var(--white);
    border: 1px solid var(--border);
    margin-bottom: 8px;
    transition: border-color 0.2s, box-shadow 0.2s;
    border-radius: 3px;
}
.block-item.dragging {
    opacity: 0.5;
    border-color: var(--accent);
}
.block-item.drag-over {
    border-color: var(--accent);
    box-shadow: 0 0 0 1px var(--accent);
}

.block-header {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    gap: 10px;
    cursor: default;
}
.block-drag-handle {
    cursor: grab;
    color: var(--muted);
    font-size: 16px;
    line-height: 1;
    padding: 4px 2px;
    user-select: none;
}
.block-drag-handle:active { cursor: grabbing; }
.block-type-label {
    font-size: 10px;
    letter-spacing: 0.2em;
    color: var(--accent);
    border: 1px solid var(--accent-lt);
    padding: 2px 8px;
    white-space: nowrap;
    background: var(--accent-lt);
    border-radius: 2px;
}
.block-preview {
    flex: 1;
    font-size: 13px;
    color: var(--muted);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.block-actions { display: flex; gap: 6px; }
.block-actions button {
    background: var(--white);
    border: 1px solid var(--border);
    color: var(--muted);
    font-size: 11px;
    padding: 4px 10px;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 2px;
}
.block-actions button:hover { border-color: var(--accent); color: var(--accent); }
.block-actions button.del:hover { border-color: var(--danger); color: var(--danger); }

/* ブロック編集エリア */
.block-body {
    padding: 0 14px 14px;
    display: none;
}
.block-body.open { display: block; }
.block-body input,
.block-body textarea,
.block-body select {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 10px 12px;
    font-size: 14px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    resize: vertical;
    border-radius: 3px;
}
.block-body input:focus,
.block-body textarea:focus,
.block-body select:focus { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-lt); }
.block-body textarea { min-height: 120px; line-height: 1.8; }
.block-body label { display: block; font-size: 11px; font-weight: 600; color: var(--text-sub); margin-bottom: 6px; margin-top: 12px; }
.block-body label:first-child { margin-top: 0; }

/* 追加ボタン */
.add-block-wrap { position: relative; margin-bottom: 40px; }
.add-block-btn {
    background: var(--white);
    border: 1px dashed var(--border);
    color: var(--muted);
    width: 100%;
    padding: 12px;
    font-size: 12px;
    letter-spacing: 0.2em;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    border-radius: 3px;
}
.add-block-btn:hover { border-color: var(--accent); color: var(--accent); }
.block-menu {
    display: none;
    position: absolute;
    bottom: calc(100% + 4px);
    left: 0;
    background: var(--white);
    border: 1px solid var(--border);
    z-index: 1000;
    min-width: 200px;
    border-radius: 3px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.block-menu.open { display: block; }
.block-menu button {
    display: block;
    width: 100%;
    background: transparent;
    border: none;
    color: var(--text);
    padding: 12px 20px;
    text-align: left;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s;
}
.block-menu button:hover { background: var(--bg); color: var(--accent); }

/* HR プレビュー */
.hr-preview { border: none; border-top: 1px solid var(--border); margin: 8px 0 4px; }

/* 保存ボタンエリア */
.editor-footer { padding-top: 8px; }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title"><?= $type === 'news' ? 'News' : 'Pages' ?> / ブロックエディタ</p>
  <?php if (($_GET['msg'] ?? '') === 'saved'): ?>
  <div class="msg msg-success">保存しました。</div>
  <?php endif; ?>

  <div class="editor-wrap">
    <form method="POST" id="editorForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="blocks_json" id="blocksJson">

      <!-- メタ情報 -->
      <?php if ($type === 'news'): ?>
      <div class="meta-bar news-meta">
        <div class="form-group" style="margin:0">
          <label>日付</label>
          <input type="text" name="date" value="<?= htmlspecialchars($item['date']) ?>" placeholder="2025.01.01">
        </div>
        <div class="form-group" style="margin:0">
          <label>カテゴリ</label>
          <select name="cat">
            <option value="">選択してください</option>
            <?php foreach ($cats as $cat): ?>
            <option value="<?= htmlspecialchars($cat['label']) ?>"
              <?= ($item['cat'] ?? '') === $cat['label'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['label']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0">
          <label>アイキャッチ画像パス（任意）</label>
          <input type="text" name="thumbnail" value="<?= htmlspecialchars($item['thumbnail'] ?? '') ?>" placeholder="/images/uploads/photo.jpg">
        </div>
      </div>
      <?php else: ?>
      <div class="meta-bar page-meta">
        <div class="form-group" style="margin:0">
          <label>アイキャッチ画像パス（任意）</label>
          <input type="text" name="thumbnail" value="<?= htmlspecialchars($item['thumbnail'] ?? '') ?>" placeholder="../images/uploads/photo.jpg">
        </div>
        <div class="form-group" style="margin:0">
          <label>カテゴリー（任意・Worksのタグ表示用）</label>
          <input type="text" name="cat" value="<?= htmlspecialchars($item['cat'] ?? '') ?>" placeholder="脚本 / Web / 3D ...">
        </div>
        <div class="form-group" style="margin:0">
          <label>スラッグ（URL）</label>
          <input type="text" name="slug" value="<?= htmlspecialchars($item['slug'] ?? '') ?>"
                 placeholder="about / works ..." <?= $is_edit ? 'readonly style="color:var(--muted)"' : '' ?>>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:24px;">
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <input type="checkbox" name="show_in_works" value="1"
                 <?= !empty($item['show_in_works']) ? 'checked' : '' ?>
                 style="width:auto;accent-color:var(--accent);">
          <span>トップページのWorksセクションに表示する</span>
        </label>
      </div>
      <?php endif; ?>

      <!-- タイトル -->
      <input type="text" name="title" class="title-input"
             value="<?= htmlspecialchars($item['title']) ?>"
             placeholder="タイトルを入力..." required>

      <!-- ブロックリスト -->
      <div class="block-list" id="blockList"></div>

      <!-- ブロック追加 -->
      <div class="add-block-wrap">
        <button type="button" class="add-block-btn" id="addBlockBtn">＋ ブロックを追加</button>
        <div class="block-menu" id="blockMenu">
          <button type="button" onclick="addBlock('h2')">見出し（H2）</button>
          <button type="button" onclick="addBlock('h3')">見出し（H3）</button>
          <button type="button" onclick="addBlock('text')">テキスト段落</button>
          <button type="button" onclick="addBlock('image')">画像</button>
          <button type="button" onclick="addBlock('hr')">区切り線</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" onclick="prepareSubmit()">保存する</button>
      <?php if ($is_edit): ?>
      <?php if ($type === 'news'): ?>
      <a href="../php/post-single.php?id=<?= $item['id'] ?>" target="_blank"
         class="btn btn-secondary" style="margin-left:12px;">サイトで確認 →</a>
      <?php else: ?>
      <a href="../pages/<?= htmlspecialchars($item['slug'] ?? '') ?>.php" target="_blank"
         class="btn btn-secondary" style="margin-left:12px;">サイトで確認 →</a>
      <?php endif; ?>
      <?php endif; ?>
      <a href="<?= $type === 'news' ? './post-list.php' : './pages-list.php' ?>"
         class="btn btn-secondary" style="margin-left:12px;">キャンセル</a>
    </form>
  </div>
</div>

<script>
// 初期データ
const initialBlocks = <?= json_encode($item['blocks'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
let blocks = initialBlocks.length ? JSON.parse(JSON.stringify(initialBlocks)) : [];
let dragSrcIndex = null;

// ブロック定義
const BLOCK_TYPES = {
  h2:    { label: 'H2 見出し',   fields: [{ key: 'text', type: 'text', placeholder: '見出しテキスト' }] },
  h3:    { label: 'H3 見出し',   fields: [{ key: 'text', type: 'text', placeholder: '見出しテキスト' }] },
  text:  { label: 'テキスト',    fields: [{ key: 'text', type: 'textarea', placeholder: 'テキストを入力...' }] },
  image: { label: '画像',        fields: [
    { key: 'src',  type: 'text', placeholder: '画像パス（例: ../images/photo.jpg）' },
    { key: 'alt',  type: 'text', placeholder: 'alt属性（説明文）' },
  ]},
  hr:    { label: '区切り線',    fields: [] },
};

function preview(block) {
  if (block.type === 'hr') return '<hr class="hr-preview">';
  if (block.type === 'image') return block.src ? `[画像] ${block.src}` : '[画像未設定]';
  return block.text || '（未入力）';
}

function render() {
  const list = document.getElementById('blockList');
  list.innerHTML = '';
  blocks.forEach((block, i) => {
    const def = BLOCK_TYPES[block.type] || {};
    const div = document.createElement('div');
    div.className = 'block-item';
    div.draggable = true;
    div.dataset.index = i;

    // ヘッダー
    let fieldsHtml = def.fields.map(f => {
      const val = block[f.key] || '';
      const el = f.type === 'textarea'
        ? `<textarea data-key="${f.key}" placeholder="${f.placeholder}">${val}</textarea>`
        : `<input type="text" data-key="${f.key}" placeholder="${f.placeholder}" value="${escHtml(val)}">`;
      return `<label>${f.placeholder}</label>${el}`;
    }).join('');

    div.innerHTML = `
      <div class="block-header">
        <span class="block-drag-handle" title="ドラッグで並び替え">⠿</span>
        <span class="block-type-label">${def.label}</span>
        <span class="block-preview">${preview(block)}</span>
        <div class="block-actions">
          <button type="button" onclick="toggleBlock(${i})">編集</button>
          <button type="button" class="del" onclick="removeBlock(${i})">削除</button>
        </div>
      </div>
      <div class="block-body" id="body-${i}">
        ${fieldsHtml}
      </div>`;

    // フィールドのchangeイベント
    div.querySelectorAll('[data-key]').forEach(el => {
      el.addEventListener('input', e => {
        blocks[i][e.target.dataset.key] = e.target.value;
        // プレビュー更新
        div.querySelector('.block-preview').innerHTML = preview(blocks[i]);
      });
    });

    // ドラッグ
    div.addEventListener('dragstart', e => {
      dragSrcIndex = i;
      setTimeout(() => div.classList.add('dragging'), 0);
      e.dataTransfer.effectAllowed = 'move';
    });
    div.addEventListener('dragend', () => div.classList.remove('dragging'));
    div.addEventListener('dragover', e => { e.preventDefault(); div.classList.add('drag-over'); });
    div.addEventListener('dragleave', () => div.classList.remove('drag-over'));
    div.addEventListener('drop', e => {
      e.preventDefault();
      div.classList.remove('drag-over');
      if (dragSrcIndex !== null && dragSrcIndex !== i) {
        const moved = blocks.splice(dragSrcIndex, 1)[0];
        blocks.splice(i, 0, moved);
        render();
      }
      dragSrcIndex = null;
    });

    list.appendChild(div);
  });
}

function toggleBlock(i) {
  const body = document.getElementById('body-' + i);
  body.classList.toggle('open');
}

function addBlock(type) {
  const block = { type };
  const def = BLOCK_TYPES[type];
  def.fields.forEach(f => block[f.key] = '');
  blocks.push(block);
  render();
  // 追加したブロックを自動で開く
  const newIndex = blocks.length - 1;
  setTimeout(() => {
    const body = document.getElementById('body-' + newIndex);
    if (body) body.classList.add('open');
  }, 10);
  document.getElementById('blockMenu').classList.remove('open');
  // スクロール
  setTimeout(() => {
    const list = document.getElementById('blockList');
    list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, 50);
}

function removeBlock(i) {
  if (!confirm('このブロックを削除しますか？')) return;
  blocks.splice(i, 1);
  render();
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function prepareSubmit() {
  document.getElementById('blocksJson').value = JSON.stringify(blocks);
}

// 追加メニューのトグル
document.getElementById('addBlockBtn').addEventListener('click', () => {
  document.getElementById('blockMenu').classList.toggle('open');
});
document.addEventListener('click', e => {
  if (!e.target.closest('.add-block-wrap')) {
    document.getElementById('blockMenu').classList.remove('open');
  }
});

// 初期レンダリング
render();
</script>
</body>
</html>
