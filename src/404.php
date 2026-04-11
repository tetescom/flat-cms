<?php
http_response_code(404);
$page_title = 'ページが見つかりません';
include __DIR__ . '/php/header.php';
?>
<style>
  .notfound-wrap {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120px 24px;
    text-align: center;
  }
  .notfound-code {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(100px, 20vw, 200px);
    color: transparent;
    -webkit-text-stroke: 1px rgba(200,169,110,0.3);
    line-height: 1;
    margin-bottom: 24px;
    letter-spacing: 0.05em;
  }
  .notfound-title {
    font-size: 13px;
    letter-spacing: 0.3em;
    color: var(--text-muted);
    text-transform: uppercase;
    margin-bottom: 16px;
  }
  .notfound-text {
    font-size: 14px;
    color: var(--text-muted);
    line-height: 2;
    margin-bottom: 48px;
  }
  .notfound-btn {
    display: inline-block;
    padding: 14px 40px;
    border: 1px solid rgba(200,169,110,0.5);
    color: var(--accent);
    text-decoration: none;
    font-size: 11px;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    transition: all 0.3s;
  }
  .notfound-btn:hover {
    background: var(--accent);
    color: var(--white);
  }
</style>

<div class="notfound-wrap">
  <div class="notfound-code">404</div>
  <p class="notfound-title">Page Not Found</p>
  <p class="notfound-text">お探しのページは見つかりませんでした。<br>移動または削除された可能性があります。</p>
  <a href="<?= $base_path ?>" class="notfound-btn">トップへ戻る</a>
</div>

<?php include __DIR__ . '/php/footer.php'; ?>
