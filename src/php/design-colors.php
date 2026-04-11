<?php
/**
 * アクセントカラー1色からサイト全体のカラーパレットを自動生成
 */
function flatcms_generate_colors(string $hex): array {
    // HEX → RGB
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;

    // RGB → HSL
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    $d = $max - $min;

    if ($d == 0) {
        $h = $s = 0;
    } else {
        $s = $d / (1 - abs(2 * $l - 1));
        switch ($max) {
            case $r: $h = fmod(($g - $b) / $d, 6); break;
            case $g: $h = ($b - $r) / $d + 2; break;
            default: $h = ($r - $g) / $d + 4; break;
        }
        $h = round($h * 60);
        if ($h < 0) $h += 360;
    }
    $s = round($s * 100);
    $l = round($l * 100);

    // HSL → HEX変換ヘルパー
    $hsl2hex = function(int $h, int $s, int $l): string {
        $s /= 100; $l /= 100;
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;
        if ($h < 60)       [$r,$g,$b] = [$c, $x, 0];
        elseif ($h < 120)  [$r,$g,$b] = [$x, $c, 0];
        elseif ($h < 180)  [$r,$g,$b] = [0, $c, $x];
        elseif ($h < 240)  [$r,$g,$b] = [0, $x, $c];
        elseif ($h < 300)  [$r,$g,$b] = [$x, 0, $c];
        else               [$r,$g,$b] = [$c, 0, $x];
        return sprintf('#%02x%02x%02x',
            round(($r + $m) * 255),
            round(($g + $m) * 255),
            round(($b + $m) * 255)
        );
    };

    // アクセントカラーから派生色を生成
    return [
        'accent'     => '#' . $hex,
        'accent2'    => $hsl2hex($h, max(0, $s - 10), min(100, $l + 15)), // 少し明るく
        'accent-lt'  => $hsl2hex($h, max(0, $s - 30), min(100, $l + 42)), // かなり薄く
        'border'     => $hsl2hex($h, max(0, $s - 40), min(100, $l + 48)), // さらに薄く
        'bg'         => $hsl2hex($h, max(0, $s - 55), min(100, $l + 54)), // ほぼ白
        'bg2'        => $hsl2hex($h, max(0, $s - 50), min(100, $l + 50)), // 少し色味
        'text'       => $hsl2hex($h, min(100, $s + 5),  max(0, $l - 55)), // かなり暗く
        'text-sub'   => $hsl2hex($h, max(0, $s - 10),  max(0, $l - 30)),
        'text-muted' => $hsl2hex($h, max(0, $s - 20),  max(0, $l - 10)),
    ];
}
