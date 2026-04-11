<?php
// ブロック配列を受け取ってHTMLを出力する関数
function render_blocks(array $blocks): string {
    $html = '';
    foreach ($blocks as $block) {
        switch ($block['type'] ?? '') {
            case 'h2':
                $html .= '<h2>' . htmlspecialchars($block['text'] ?? '') . '</h2>' . "\n";
                break;
            case 'h3':
                $html .= '<h3>' . htmlspecialchars($block['text'] ?? '') . '</h3>' . "\n";
                break;
            case 'text':
                // 改行を<br>に変換
                $text = htmlspecialchars($block['text'] ?? '');
                $text = nl2br($text);
                $html .= '<p>' . $text . '</p>' . "\n";
                break;
            case 'image':
                $src = htmlspecialchars($block['src'] ?? '');
                $alt = htmlspecialchars($block['alt'] ?? '');
                if ($src) {
                    $html .= '<figure><img src="' . $src . '" alt="' . $alt . '" loading="lazy"></figure>' . "\n";
                }
                break;
            case 'hr':
                $html .= '<hr>' . "\n";
                break;
        }
    }
    return $html;
}
?>
