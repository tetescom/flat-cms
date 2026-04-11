<?php
session_start();

// ひらがなリスト
$hiragana = ['あ','い','う','え','お','か','き','く','け','こ',
             'さ','し','す','せ','そ','た','ち','つ','て','と',
             'な','に','ぬ','ね','の','は','ひ','ふ','へ','ほ',
             'ま','み','む','め','も','や','ゆ','よ','ら','り',
             'る','れ','ろ','わ','を'];

// 4文字ランダムに選ぶ
$chars = [];
for ($i = 0; $i < 4; $i++) {
    $chars[] = $hiragana[array_rand($hiragana)];
}
$text = implode('', $chars);
$_SESSION['captcha_text'] = $text;

// 各文字のランダム属性
$items = '';
$x = 18;
foreach ($chars as $char) {
    $angle   = rand(-18, 18);
    $size    = rand(22, 28);
    $y       = rand(38, 48);
    // アクセントカラー系のゴールド〜ホワイト
    $colors  = ['#c8a96e','#d4b97e','#e8d5a8','#f2ede8','#b8996e','#ddc88e'];
    $color   = $colors[array_rand($colors)];
    $items  .= "<text x=\"{$x}\" y=\"{$y}\" font-size=\"{$size}\" fill=\"{$color}\" "
             . "transform=\"rotate({$angle},{$x},{$y})\" "
             . "font-family='Noto Sans JP, sans-serif' font-weight='400'>"
             . htmlspecialchars($char) . "</text>\n";
    $x += rand(34, 40);
}

// ノイズ線
$lines = '';
for ($i = 0; $i < 5; $i++) {
    $x1 = rand(0, 180); $y1 = rand(0, 60);
    $x2 = rand(0, 180); $y2 = rand(0, 60);
    $op = round(rand(15, 40) / 100, 2);
    $lines .= "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" "
            . "stroke=\"#c8a96e\" stroke-width=\"1\" stroke-opacity=\"{$op}\"/>\n";
}

// ノイズドット
$dots = '';
for ($i = 0; $i < 30; $i++) {
    $cx = rand(0, 180); $cy = rand(0, 60);
    $op = round(rand(10, 40) / 100, 2);
    $dots .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"1.5\" fill=\"#c8a96e\" fill-opacity=\"{$op}\"/>\n";
}

// 波形フィルター（SVGのfeTurbulence）
$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="180" height="60"
     style="background:#0d0d0d; border:1px solid #2a2a2a;">
  <defs>
    <filter id="wave">
      <feTurbulence type="turbulence" baseFrequency="0.018" numOctaves="2"
                    result="turbulence" seed="{$_SESSION['captcha_text']}"/>
      <feDisplacementMap in="SourceGraphic" in2="turbulence"
                         scale="5" xChannelSelector="R" yChannelSelector="G"/>
    </filter>
    <style>
      @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400&display=swap');
    </style>
  </defs>
  {$lines}
  {$dots}
  <g filter="url(#wave)">
    {$items}
  </g>
</svg>
SVG;

header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo $svg;
