<?php
require_once './config.php';

$hiragana = ['あ','い','う','え','お','か','き','く','け','こ',
             'さ','し','す','せ','そ','た','ち','つ','て','と',
             'な','に','ぬ','ね','の','は','ひ','ふ','へ','ほ',
             'ま','み','む','め','も','や','ゆ','よ','ら','り',
             'る','れ','ろ','わ','を'];
$chars = [];
for ($i = 0; $i < 4; $i++) $chars[] = $hiragana[array_rand($hiragana)];
$_SESSION['captcha_text'] = implode('', $chars);

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
$svg = "<svg xmlns='http://www.w3.org/2000/svg' width='180' height='60' style='background:#f0f0f1;border:1px solid #dcdcde;display:block;'><defs><filter id='wv'><feTurbulence type='turbulence' baseFrequency='0.018' numOctaves='2' result='t'/><feDisplacementMap in='SourceGraphic' in2='t' scale='5' xChannelSelector='R' yChannelSelector='G'/></filter></defs>{$lines}{$dots}<g filter='url(#wv)'>{$items}</g></svg>";

header('Content-Type: application/json');
echo json_encode(['svg' => $svg]);
