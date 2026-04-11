<?php
require_once './config.php';
require_login();

$form_file = DATA_DIR . 'form.json';

// デフォルトフィールド定義
$default_fields = [
    ['id' => 'name',     'label' => 'お名前',           'type' => 'text',     'visible' => true,  'required' => true,  'options' => []],
    ['id' => 'company',  'label' => '会社名・団体名',   'type' => 'text',     'visible' => true,  'required' => false, 'options' => []],
    ['id' => 'email',    'label' => 'メールアドレス',   'type' => 'email',    'visible' => true,  'required' => true,  'options' => []],
    ['id' => 'email2',   'label' => 'メールアドレス（確認）', 'type' => 'email2', 'visible' => false, 'required' => false, 'options' => []],
    ['id' => 'tel',      'label' => '電話番号',         'type' => 'tel',      'visible' => false, 'required' => false, 'options' => []],
    ['id' => 'type',     'label' => 'ご依頼内容',       'type' => 'select',   'visible' => true,  'required' => true,  'options' => ['脚本制作', 'サイト制作', '3Dモデリング', 'その他']],
    ['id' => 'radio1',   'label' => 'ご予算',           'type' => 'radio',    'visible' => false, 'required' => false, 'options' => ['〜10万円', '10〜30万円', '30万円以上', '未定']],
    ['id' => 'message',  'label' => 'メッセージ',       'type' => 'textarea', 'visible' => true,  'required' => true,  'options' => []],
    ['id' => 'privacy',  'label' => 'プライバシーポリシーに同意する', 'type' => 'checkbox', 'visible' => false, 'required' => false, 'options' => []],
];

// 保存処理
$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $fields = json_decode($_POST['fields'] ?? '[]', true);
        if ($fields !== null) {
            save_json($form_file, $fields);
            $msg = '保存しました。';
        } else {
            $err = '保存に失敗しました。';
        }
    }
}

// フィールド読み込み
$fields = file_exists($form_file) ? (json_decode(file_get_contents($form_file), true) ?: $default_fields) : $default_fields;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>フォームビルダー — 管理画面</title>
<?php include './admin-style.php'; ?>
<style>
.field-list { display: flex; flex-direction: column; gap: 8px; max-width: 640px; }
.field-item {
    background: var(--white);
    border: 1px solid var(--border);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: default;
    transition: border-color 0.2s;
    user-select: none;
}
.field-item.dragging { opacity: 0.4; }
.field-item.drag-over { border-color: var(--accent); }
.drag-handle { color: var(--muted); font-size: 16px; cursor: grab; padding: 0 4px; flex-shrink: 0; }
.drag-handle:active { cursor: grabbing; }
.field-name { flex: 1; font-size: 13px; color: var(--text); }
.field-type-badge {
    font-size: 9px; letter-spacing: 0.1em;
    border: 1px solid var(--border); color: var(--muted);
    padding: 2px 7px; min-width: 72px; text-align: center;
    flex-shrink: 0;
}
.toggle-wrap { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-sub); flex-shrink: 0; }
.toggle {
    width: 36px; height: 20px;
    background: var(--border); border-radius: 10px;
    position: relative; cursor: pointer;
    transition: background 0.2s; flex-shrink: 0;
}
.toggle.on { background: var(--accent); }
.toggle::after {
    content: ''; position: absolute;
    width: 14px; height: 14px; background: #fff;
    border-radius: 50%; top: 3px; left: 3px;
    transition: transform 0.2s;
}
.toggle.on::after { transform: translateX(16px); }
.req-badge {
    font-size: 9px; letter-spacing: 0.1em;
    padding: 2px 7px; cursor: pointer;
    border: 1px solid var(--border); color: var(--muted);
    transition: all 0.2s; flex-shrink: 0;
}
.req-badge.on { border-color: var(--accent); color: var(--accent); }
</style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="main">
  <p class="page-title">Form Builder</p>
  <?php if ($msg): ?><div class="msg msg-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg msg-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <p style="font-size:12px;color:var(--muted);margin-bottom:24px;">⠿ をドラッグして順番を入れ替えられます。表示トグルで表示/非表示、必須ボタンで必須/任意を切り替えます。</p>

  <form method="POST" id="formBuilderForm">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="fields" id="fieldsData" value="">

    <div class="field-list" id="fieldList">
      <?php foreach ($fields as $field): ?>
      <div class="field-item"
           data-id="<?= htmlspecialchars($field['id']) ?>"
           data-type="<?= htmlspecialchars($field['type']) ?>"
           data-label="<?= htmlspecialchars($field['label']) ?>"
           data-visible="<?= $field['visible'] ? '1' : '0' ?>"
           data-required="<?= $field['required'] ? '1' : '0' ?>"
           data-options="<?= htmlspecialchars(json_encode($field['options'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
           draggable="true">
        <span class="drag-handle">⠿</span>
        <span class="field-name"><?= htmlspecialchars($field['label']) ?></span>
        <span class="field-type-badge"><?= strtoupper(htmlspecialchars($field['type'])) ?></span>
        <div class="toggle-wrap">
          <span>表示</span>
          <div class="toggle <?= $field['visible'] ? 'on' : '' ?>" onclick="toggleVisible(this)"></div>
        </div>
        <span class="req-badge <?= $field['required'] ? 'on' : '' ?>" onclick="this.classList.toggle('on')">必須</span>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="margin-top:24px;max-width:640px;">
      <button type="submit" class="btn btn-primary" onclick="saveFields()">保存する</button>
    </div>
  </form>
</div>

<script>
const list = document.getElementById('fieldList');
let dragged = null;

list.querySelectorAll('.field-item').forEach(addDragEvents);

function addDragEvents(item) {
  item.addEventListener('dragstart', () => {
    dragged = item;
    setTimeout(() => item.classList.add('dragging'), 0);
  });
  item.addEventListener('dragend', () => {
    item.classList.remove('dragging');
    list.querySelectorAll('.field-item').forEach(i => i.classList.remove('drag-over'));
  });
  item.addEventListener('dragover', e => {
    e.preventDefault();
    list.querySelectorAll('.field-item').forEach(i => i.classList.remove('drag-over'));
    item.classList.add('drag-over');
    const mid = item.getBoundingClientRect().top + item.getBoundingClientRect().height / 2;
    if (e.clientY < mid) list.insertBefore(dragged, item);
    else list.insertBefore(dragged, item.nextSibling);
  });
}

function toggleVisible(toggle) {
  toggle.classList.toggle('on');
}

function saveFields() {
  const items = list.querySelectorAll('.field-item');
  const fields = [];
  items.forEach(item => {
    fields.push({
      id:       item.dataset.id,
      label:    item.dataset.label,
      type:     item.dataset.type,
      visible:  item.querySelector('.toggle').classList.contains('on'),
      required: item.querySelector('.req-badge').classList.contains('on'),
      options:  JSON.parse(item.dataset.options || '[]')
    });
  });
  document.getElementById('fieldsData').value = JSON.stringify(fields);
}
</script>
</body>
</html>
