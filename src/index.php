<?php
$base_path = './';
include __DIR__ . '/php/header.php';
$about_file = __DIR__ . '/data/pages/about.json';
$about = file_exists($about_file) ? json_decode(file_get_contents($about_file), true) ?? [] : [];
?>

<!-- HERO -->
<section id="hero">
  <div class="hero-bg"></div>
  <div class="hero-photo" <?php if (!empty($seo['hero_image'])): ?>style="background-image:url('<?= htmlspecialchars(flatcms_asset($seo['hero_image'])) ?>')"<?php endif; ?>></div>
  <div class="hero-line"></div>
  <div class="hero-counter">AROMA COFFEE — SINCE 2019</div>
  <div class="hero-content">
    <p class="hero-sub"><?= htmlspecialchars($seo['hero_sub'] ?? 'Specialty Coffee & Food') ?></p>
    <h1 class="hero-title"><?= htmlspecialchars($seo['hero_title'] ?? 'Aroma') ?><?php if (!empty($seo['hero_title_em'])): ?><br><em><?= htmlspecialchars($seo['hero_title_em']) ?></em><?php endif; ?></h1>
    <p class="hero-title-en"><?= htmlspecialchars($seo['hero_catch'] ?? '') ?></p>
    <p class="hero-desc">
      <?= nl2br(htmlspecialchars($seo['hero_desc'] ?? '')) ?>
    </p>
  </div>
  <div class="scroll-hint">Scroll</div>
  <?php
  $sns_hero = json_decode(file_get_contents(__DIR__ . '/data/sns.json'), true) ?? [];
  include __DIR__ . '/php/sns-icons.php';
  $sns_hero_icons = $sns_icons;
  $active_hero_sns = array_filter($sns_hero, fn($v) => !empty($v));
  if (!empty($active_hero_sns)):
  ?>
  <div class="hero-sns">
    <?php foreach ($active_hero_sns as $key => $url): if (isset($sns_hero_icons[$key])): ?>
    <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener" class="hero-sns-link">
      <?= $sns_hero_icons[$key] ?>
    </a>
    <?php endif; endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- ABOUT -->
<section id="about">
  <div class="about-left reveal">
    <p class="section-label">About</p>
    <h2 class="section-title"><?= htmlspecialchars($about['title'] ?? '私たちについて') ?></h2>
    <div class="divider"></div>
    <p class="about-text">
      <?= nl2br(htmlspecialchars($about['blocks'][0]['text'] ?? '')) ?>
    </p>
    <?php if (!empty($about['mission'])): ?>
    <div class="mission-box">
      <p class="mission-text">
        <?= nl2br(htmlspecialchars($about['mission'])) ?>
      </p>
    </div>
    <?php endif; ?>
  </div>
  <div class="about-right reveal">
    <p class="section-label">Access</p>
    <h2 class="section-title">アクセス</h2>
    <div class="access-info">
      <?php if (!empty($about['address'])): ?>
      <div class="access-item">
        <p class="access-label">住所</p>
        <p class="access-value"><?= nl2br(htmlspecialchars($about['address'])) ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($about['hours'])): ?>
      <div class="access-item">
        <p class="access-label">営業時間</p>
        <p class="access-value"><?= nl2br(htmlspecialchars($about['hours'])) ?></p>
      </div>
      <?php endif; ?>
    </div>
    <?php if (!empty($about['map_embed'])): ?>
    <div class="access-map">
      <?= $about['map_embed'] ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- MENU（Worksセクション） -->
<section id="works">
  <p class="section-label reveal">Menu</p>
  <h2 class="section-title reveal">おすすめメニュー</h2>
  <?php
  $work_files = glob(__DIR__ . '/data/pages/*.json');
  $works_map = [];
  foreach ($work_files as $wf) {
    $wd = json_decode(file_get_contents($wf), true);
    if ($wd && !empty($wd['show_in_works'])) $works_map[$wd['id']] = $wd;
  }
  $works_order_file = __DIR__ . '/data/works_order.json';
  $works_order = file_exists($works_order_file) ? json_decode(file_get_contents($works_order_file), true) ?? [] : [];
  // order通りに並べる、orderにないものは末尾に追加
  $works = [];
  foreach ($works_order as $oid) {
    if (isset($works_map[$oid])) { $works[] = $works_map[$oid]; unset($works_map[$oid]); }
  }
  foreach ($works_map as $w) $works[] = $w;
  ?>
  <div class="works-grid">
    <?php if (empty($works)): ?>
    <p style="color:var(--text-muted);font-size:13px;">固定ページで「Worksに表示する」をオンにすると、ここに表示されます。</p>
    <?php else: ?>
    <?php foreach ($works as $i => $work):
      $thumb = htmlspecialchars($work['thumbnail'] ?? '');
      $slug  = htmlspecialchars($work['slug'] ?? '');
      $tag   = htmlspecialchars($work['cat'] ?? '');
    ?>
    <a href="./pages/<?= $slug ?>.php" class="work-card reveal" style="text-decoration:none;color:inherit;">
      <?php $thumb_url = htmlspecialchars(flatcms_asset($work['thumbnail'] ?? '')); ?>
      <div class="work-bg" <?php if($thumb_url) echo 'style="background-image:url(' . $thumb_url . ');background-size:cover;background-position:center;"'; ?>></div>
      <div class="work-inner">
        <?php if ($tag): ?><p class="work-tag"><?= $tag ?></p><?php endif; ?>
        <h3 class="work-title"><?= htmlspecialchars($work['title']) ?></h3>
      </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- NEWS -->
<section id="news">
  <p class="section-label reveal">News</p>
  <h2 class="section-title reveal">お知らせ</h2>
  <?php
  $files = glob(__DIR__ . '/data/news/*.json');
  $all_news = []; $all_blog = [];
  foreach ($files as $f) {
    $d = json_decode(file_get_contents($f), true);
    if (!$d) continue;
    if (($d['cat'] ?? '') === 'ブログ') $all_blog[] = $d;
    else $all_news[] = $d;
  }
  usort($all_news, fn($a, $b) => strcmp($b['date'], $a['date']));
  usort($all_blog, fn($a, $b) => strcmp($b['date'], $a['date']));
  $recent_news = array_slice($all_news, 0, 4);
  $recent_blog = array_slice($all_blog, 0, 4);
  ?>
  <div class="news-list">
    <?php foreach ($recent_news as $item): ?>
    <a href="<?= $base_path ?>news/<?= $item['id'] ?>" class="news-item reveal" style="text-decoration:none;color:inherit;">
      <span class="news-date"><?= htmlspecialchars($item['date']) ?></span>
      <span class="news-cat"><?= htmlspecialchars($item['cat']) ?></span>
      <span class="news-title"><?= htmlspecialchars($item['title']) ?></span>
    </a>
    <?php endforeach; ?>
    <?php if (empty($recent_news)): ?>
    <p style="color:var(--text-muted);font-size:13px;padding:24px 0;">お知らせはまだありません。</p>
    <?php endif; ?>
  </div>
  <div style="text-align:right; margin-top:40px;">
    <a href="<?= $base_path ?>news" class="btn-submit" style="display:inline-block;text-decoration:none;"><span>View All News</span></a>
  </div>
</section>

<!-- BLOG -->
<?php if (!empty($recent_blog)): ?>
<section id="blog-top">
  <p class="section-label reveal">Blog</p>
  <h2 class="section-title reveal">ブログ</h2>
  <div class="blog-grid blog-grid-4">
    <?php foreach ($recent_blog as $item): ?>
    <a href="<?= $base_path ?>news/<?= $item['id'] ?>" class="blog-card reveal">
      <?php if (!empty($item['thumbnail'])): ?>
        <img class="blog-thumb" src="<?= htmlspecialchars(flatcms_asset($item['thumbnail'])) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
      <?php else: ?>
        <img class="blog-thumb" src="<?= htmlspecialchars(flatcms_asset($seo['no_image'] ?? '/images/uploads/no-image.webp')) ?>" alt="No Image" loading="lazy">
      <?php endif; ?>
      <div class="blog-card-body">
        <div class="blog-meta">
          <span class="blog-date"><?= htmlspecialchars($item['date']) ?></span>
        </div>
        <p class="blog-title"><?= htmlspecialchars($item['title']) ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <div style="text-align:right; margin-top:40px;">
    <a href="<?= $base_path ?>blog" class="btn-submit" style="display:inline-block;text-decoration:none;"><span>View All Blog</span></a>
  </div>
</section>
<?php endif; ?>

<!-- CONTACT -->
<?php
$sns_file = __DIR__ . '/data/sns.json';
$sns_contact = file_exists($sns_file) ? json_decode(file_get_contents($sns_file), true) ?? [] : [];
?>
<section id="contact">
  <div class="contact-left reveal">
    <p class="section-label">Contact</p>
    <h2 class="section-title">お問い合わせ</h2>
    <div class="divider"></div>
    <p style="font-size:14px; color:var(--text-sub); line-height:2.2; margin-bottom:40px;">
      ご予約・ご質問・取材のご依頼など<br>
      お気軽にお問い合わせください。
    </p>
    <div class="contact-info">
      <div class="contact-info-item">
        <label>Shop Name</label>
        <p>Aroma Coffee（アロマコーヒー）</p>
      </div>
      <div class="contact-info-item">
        <label>Hours</label>
        <p>平日 9:00〜19:00 / 土日祝 10:00〜18:00</p>
      </div>
      <div class="contact-info-item">
        <label>Location</label>
        <p>〒100-0001 東京都千代田区（架空の住所）</p>
      </div>
    </div>
  </div>
  <div class="contact-right reveal">
    <form id="contactForm" onsubmit="handleSubmit(event)" novalidate>
<?php
$form_file = __DIR__ . '/data/form.json';
$form_fields = file_exists($form_file) ? (json_decode(file_get_contents($form_file), true) ?: []) : [];
if (empty($form_fields)) {
    $form_fields = [
        ['id'=>'name',    'label'=>'お名前',         'type'=>'text',     'visible'=>true,  'required'=>true,  'options'=>[]],
        ['id'=>'email',   'label'=>'メールアドレス', 'type'=>'email',    'visible'=>true,  'required'=>true,  'options'=>[]],
        ['id'=>'type',    'label'=>'お問い合わせ種別','type'=>'select',   'visible'=>true,  'required'=>true,  'options'=>['ご予約','商品について','取材・メディア','その他']],
        ['id'=>'message', 'label'=>'メッセージ',     'type'=>'textarea', 'visible'=>true,  'required'=>true,  'options'=>[]],
    ];
}
foreach ($form_fields as $field) {
    if (empty($field['visible'])) continue;
    $fid   = htmlspecialchars($field['id']);
    $flab  = htmlspecialchars($field['label']);
    $ftype = $field['type'];
    $freq  = !empty($field['required']);
    $req   = $freq ? ' required' : '';
    $badge = $freq ? ' <span class="required-badge">必須</span>' : '';
    echo '<div class="form-group">';
    echo '<label>' . $flab . $badge . '</label>';
    switch ($ftype) {
        case 'text': case 'tel':
            echo '<input type="' . $ftype . '" name="' . $fid . '"' . $req . '>';
            break;
        case 'email':
            echo '<input type="email" name="' . $fid . '"' . $req . '>';
            break;
        case 'textarea':
            echo '<textarea name="' . $fid . '"' . $req . '></textarea>';
            break;
        case 'select':
            echo '<select name="' . $fid . '"' . $req . '>';
            echo '<option value="">選択してください</option>';
            foreach ($field['options'] as $opt) {
                $o = htmlspecialchars($opt);
                echo '<option value="' . $o . '">' . $o . '</option>';
            }
            echo '</select>';
            break;
    }
    echo '</div>';
}
?>
      <input type="hidden" name="_subject" value="Aroma Coffee お問い合わせ">
      <button type="submit" class="btn-submit" id="submitBtn"><span>送信する — Send</span></button>
      <div id="formMsg" class="form-msg"></div>
    </form>
  </div>
</section>

<button class="pagetop" id="pagetop" aria-label="ページトップへ"></button>

<?php include __DIR__ . '/php/footer.php'; ?>
