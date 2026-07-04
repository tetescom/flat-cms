<?php
/**
 * BUG-03 移行スクリプト（1回だけ実行）
 *
 * 旧仕様では「保存時 htmlspecialchars ＋ 表示時 htmlspecialchars」で二重エスケープしていた。
 * 保存側のエスケープを撤去したため、既に `&amp;` 等に変換済みの既存データを
 * 1段だけデコードして生値へ戻す。
 *
 * 使い方（CLI）:  php migrate-unescape.php [dataディレクトリ]
 *   例: php migrate-unescape.php ./src/data
 *   Docker: docker compose exec -T php81 php /var/www/html/../migrate-unescape.php /var/www/html/data
 *
 * ※ 二重実行しないこと（生値の `&amp;` を実体の & に潰してしまう）。
 * ※ body / blocks は元々エスケープしていないため対象外。
 */

$dataDir = rtrim($argv[1] ?? (__DIR__ . '/src/data'), '/\\');
if (!is_dir($dataDir)) {
    fwrite(STDERR, "data ディレクトリが見つかりません: {$dataDir}\n");
    exit(1);
}

$dec = fn($v) => is_string($v) ? html_entity_decode($v, ENT_QUOTES | ENT_HTML401, 'UTF-8') : $v;
$changed = 0;

// 対象ファイルとフィールド
$targets = [
    "{$dataDir}/seo.json" => ['site_title','title_separator','description','keywords',
        'contact_email','copyright','site_url','console_verification',
        'favicon','logo_image','hero_image','hero_sub','hero_title','hero_title_em',
        'hero_catch','hero_desc','og_image','no_image'],
    "{$dataDir}/sns.json" => ['x','instagram','youtube','line','facebook','tiktok'],
];

// news / pages は全ファイルの title / cat（about は mission/address/hours も）
foreach (glob("{$dataDir}/news/*.json") as $f)  $targets[$f] = ['title','cat'];
foreach (glob("{$dataDir}/pages/*.json") as $f) $targets[$f] = ['title','cat','mission','address','hours'];

foreach ($targets as $file => $fields) {
    if (!is_file($file)) continue;
    $json = json_decode(file_get_contents($file), true);
    if (!is_array($json)) { echo "SKIP(壊れJSON): {$file}\n"; continue; }

    $before = $json;
    foreach ($fields as $k) {
        if (isset($json[$k])) $json[$k] = $dec($json[$k]);
    }
    if ($json !== $before) {
        file_put_contents($file, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo "decoded: " . basename(dirname($file)) . '/' . basename($file) . "\n";
        $changed++;
    }
}

echo "完了: {$changed} ファイルを更新しました。\n";
