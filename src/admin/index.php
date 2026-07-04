<?php
require_once './config.php';

// CAPTCHA生成（GETのときだけ）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
$hiragana = ['あ','い','う','え','お','か','き','く','け','こ',
             'さ','し','す','せ','そ','た','ち','つ','て','と',
             'な','に','ぬ','ね','の','は','ひ','ふ','へ','ほ',
             'ま','み','む','め','も','や','ゆ','よ','ら','り',
             'る','れ','ろ','わ','を'];
$chars = [];
for ($i = 0; $i < 4; $i++) $chars[] = $hiragana[array_rand($hiragana)];
$captcha_text = implode('', $chars);
$_SESSION['captcha_text'] = $captcha_text;

$items = ''; $x = 18;
foreach ($chars as $char) {
    $angle  = rand(-18, 18);
    $size   = rand(22, 28);
    $y      = rand(38, 48);
    $colors = ['#1d2327','#2271b1','#135e96','#50575e','#1a4a6e','#2c5f8a'];
    $color  = $colors[array_rand($colors)];
    $items .= "<text x='{$x}' y='{$y}' font-size='{$size}' fill='{$color}' "
            . "transform='rotate({$angle},{$x},{$y})' "
            . "font-family='Noto Sans JP,sans-serif'>"
            . htmlspecialchars($char) . "</text>";
    $x += rand(34, 40);
}
$lines = ''; $dots = '';
for ($i = 0; $i < 5; $i++) {
    $op = round(rand(15,35)/100,2);
    $lines .= "<line x1='".rand(0,180)."' y1='".rand(0,60)."' x2='".rand(0,180)."' y2='".rand(0,60)."' stroke='#2271b1' stroke-width='1' stroke-opacity='{$op}'/>";
}
for ($i = 0; $i < 30; $i++) {
    $op = round(rand(10,35)/100,2);
    $dots .= "<circle cx='".rand(0,180)."' cy='".rand(0,60)."' r='1.5' fill='#2271b1' fill-opacity='{$op}'/>";
}
$captcha_svg = "<svg xmlns='http://www.w3.org/2000/svg' width='180' height='60' style='background:#f0f0f1;border:1px solid #dcdcde;display:block;'><defs><filter id='wv'><feTurbulence type='turbulence' baseFrequency='0.018' numOctaves='2' result='t'/><feDisplacementMap in='SourceGraphic' in2='t' scale='5' xChannelSelector='R' yChannelSelector='G'/></filter></defs>{$lines}{$dots}<g filter='url(#wv)'>{$items}</g></svg>";
} else {
    $captcha_svg = ''; // POST時はAjaxで更新
} // end if GET/POST

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_login_attempts()) {
        // 同一IPで5回失敗すると15分ロック（config.php の関数群）
        $error = 'ログイン試行回数の上限に達しました。約15分後に再度お試しください。';
    } else {
        $captcha_ok = isset($_POST['captcha'], $_SESSION['captcha_text'])
            && mb_convert_kana(trim($_POST['captcha']), 'c', 'UTF-8') === $_SESSION['captcha_text'];
        unset($_SESSION['captcha_text']);
        // エラー後のCAPTCHA再生成はAjaxで行うのでここでは何もしない

        if (!$captcha_ok) {
            $error = '画像認証が正しくありません。';
        } elseif (($_POST['username'] ?? '') === ADMIN_USER && password_verify($_POST['password'] ?? '', ADMIN_PASS)) {
            clear_login_attempts();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: ./dashboard.php');
            exit;
        } else {
            record_login_attempt();
            $error = 'ユーザー名またはパスワードが違います。';
        }
    }
}

if (is_logged_in()) {
    header('Location: ./dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理画面 — ログイン</title>
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
  :root {
    --bg: #f0f0f1; --white: #ffffff; --text: #1d2327;
    --muted: #646970; --accent: #2271b1; --accent2: #135e96;
    --border: #dcdcde;
  }
  body { background: var(--bg); color: var(--text); font-family: -apple-system, 'Helvetica Neue', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
  .login-box { width: 360px; background: var(--white); border: 1px solid var(--border); padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
  .login-logo { font-size: 13px; letter-spacing: 0.3em; color: var(--accent); margin-bottom: 8px; text-align: center; font-weight: 600; }
  .login-title { font-size: 11px; letter-spacing: 0.2em; color: var(--muted); text-align: center; margin-bottom: 32px; }
  .form-group { margin-bottom: 18px; }
  .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
  .form-group input { width: 100%; background: var(--white); border: 1px solid var(--border); color: var(--text); padding: 10px 14px; font-size: 14px; outline: none; transition: border-color 0.15s, box-shadow 0.15s; border-radius: 3px; }
  .form-group input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(34,113,177,0.15); }
  .btn { width: 100%; padding: 10px; background: var(--accent); border: 1px solid var(--accent2); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 8px; transition: background 0.15s; border-radius: 3px; }
  .btn:hover { background: var(--accent2); }
  .error { font-size: 12px; color: #d63638; text-align: center; margin-bottom: 20px; background: #fcf0f1; border: 1px solid #f86368; padding: 10px; border-radius: 3px; }
</style>
</head>
<body>
<div class="login-box">
  <p class="login-logo">Flat CMS</p>
  <p class="login-title">Admin Login</p>
  <?php if ($error): ?>
  <p class="error"><?= htmlspecialchars($error) ?></p>
  <script>document.addEventListener('DOMContentLoaded', function(){ refreshCaptcha(); });</script>
  <?php endif; ?>
  <form method="POST" autocomplete="on">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" autocomplete="username" required autofocus>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" autocomplete="current-password" required>
    </div>
    <div class="form-group">
      <label>画像認証 — 表示されたひらがなを入力</label>
      <div style="margin-bottom:10px;">
        <div id="captchaWrap" style="cursor:pointer;" title="クリックで更新" onclick="refreshCaptcha()">
          <?= $captcha_svg ?>
        </div>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <script>document.addEventListener('DOMContentLoaded', refreshCaptcha);</script>
        <?php endif; ?>
        <span style="font-size:10px;color:#555;margin-top:4px;display:block;">クリックすると更新されます</span>
      </div>
      <input type="text" name="captcha" placeholder="ひらがなで入力" required autocomplete="off">
    </div>
    <button type="submit" class="btn">Login →</button>
  </form>
    <a href="./reset-password.php" style="display:block;text-align:center;margin-top:20px;font-size:11px;letter-spacing:0.15em;color:#555;text-decoration:none;" onmouseover="this.style.color='#c8a96e'" onmouseout="this.style.color='#555'">パスワードを忘れた方はこちら</a>
</div>
<script>
function refreshCaptcha() {
  fetch('./captcha-ajax.php?' + Date.now())
    .then(r => r.json())
    .then(d => {
      document.getElementById('captchaWrap').innerHTML = d.svg;
    });
}
</script>
</body>
</html>
