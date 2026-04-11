<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
  :root {
    --bg:      #f0f0f1;
    --white:   #ffffff;
    --text:    #1d2327;
    --text-sub:#50575e;
    --muted:   #646970;
    --accent:  #2271b1;
    --accent2: #135e96;
    --accent-lt:#d0e4f5;
    --sidebar-bg:   #1d2327;
    --sidebar-text: #a7aaad;
    --sidebar-hover:#2c3338;
    --sidebar-active:#2271b1;
    --border:  #dcdcde;
    --danger:  #d63638;
  }
  body { background: var(--bg); color: var(--text); font-family: -apple-system, 'Helvetica Neue', sans-serif; min-height: 100vh; font-size: 13px; }

  /* サイドバー */
  .sidebar { width: 220px; background: var(--sidebar-bg); padding: 0; flex-shrink: 0; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; overflow-y: auto; }
  .sidebar-logo { font-size: 12px; letter-spacing: 0.25em; color: #fff; padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); font-weight: 600; }
  .sidebar-nav { padding: 8px 0; flex: 1; }
  .sidebar-nav a { display: block; padding: 10px 24px; font-size: 12px; letter-spacing: 0.05em; color: var(--sidebar-text); text-decoration: none; transition: background 0.15s, color 0.15s; }
  .sidebar-nav a:hover { background: var(--sidebar-hover); color: #fff; }
  .sidebar-nav a.active { background: var(--sidebar-hover); color: #fff; border-left: 3px solid var(--accent); padding-left: 21px; }
  .sidebar-logout { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.07); }
  .sidebar-logout a { font-size: 11px; letter-spacing: 0.05em; color: var(--sidebar-text); text-decoration: none; transition: color 0.2s; }
  .sidebar-logout a:hover { color: #fff; }

  /* メインコンテンツ */
  .main { flex: 1; padding: 32px 40px; overflow-y: auto; margin-left: 220px; }
  .page-title { font-size: 15px; font-weight: 600; color: var(--text); margin-bottom: 20px; line-height: 1.3; }

  /* カード */
  .card { background: var(--white); border: 1px solid var(--border); padding: 24px; margin-bottom: 20px; border-radius: 3px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }

  /* フォーム */
  .form-group { margin-bottom: 20px; }
  .form-group label { display: block; font-size: 12px; font-weight: 600; color: var(--text); margin-bottom: 6px; }
  .form-group input[type="text"],
  .form-group input[type="date"],
  .form-group input[type="email"],
  .form-group input[type="url"],
  .form-group input[type="password"],
  .form-group select,
  .form-group textarea {
    width: 100%; background: var(--white); border: 1px solid var(--border);
    color: var(--text); padding: 10px 14px; font-size: 14px; outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
    font-family: inherit; resize: vertical; border-radius: 3px;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 2px var(--accent-lt); }
  .form-group textarea { min-height: 320px; line-height: 1.8; }

  /* ボタン */
  .btn { padding: 8px 20px; font-size: 13px; font-weight: 600; letter-spacing: 0; cursor: pointer; transition: all 0.15s; border-radius: 3px; }
  .btn-primary { background: var(--accent); color: #fff; border: 1px solid var(--accent2); }
  .btn-primary:hover { background: var(--accent2); border-color: #0a4b78; }
  .btn-danger { background: transparent; border: 1px solid var(--danger); color: var(--danger); }
  .btn-danger:hover { background: var(--danger); color: #fff; }
  .btn-secondary { background: var(--white); border: 1px solid var(--border); color: var(--muted); }
  .btn-secondary:hover { border-color: var(--accent); color: var(--accent); }

  /* リスト */
  .item-row { display: flex; align-items: center; gap: 16px; padding: 12px 0; border-bottom: 1px solid var(--border); }
  .item-row:first-child { border-top: 1px solid var(--border); }
  .item-date { font-size: 12px; color: var(--muted); width: 100px; flex-shrink: 0; }
  .item-cat { font-size: 10px; letter-spacing: 0.1em; color: var(--accent); border: 1px solid var(--accent-lt); padding: 2px 8px; flex-shrink: 0; border-radius: 2px; background: var(--accent-lt); }
  .item-title { flex: 1; font-size: 13px; color: var(--text); }
  .item-actions { display: flex; gap: 8px; flex-shrink: 0; }
  .item-actions a { font-size: 11px; color: var(--accent); text-decoration: none; padding: 4px 10px; border: 1px solid var(--border); transition: all 0.15s; border-radius: 2px; background: var(--white); }
  .item-actions a:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
  .item-actions a.del { color: var(--danger); border-color: #f0c0c0; }
  .item-actions a.del:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

  /* メッセージ */
  .msg { padding: 12px 16px; margin-bottom: 20px; font-size: 13px; border-radius: 3px; }
  .msg-success { background: #edfaef; border: 1px solid #68de7c; color: #1a7a2a; }
  .msg-error { background: #fcf0f1; border: 1px solid #f86368; color: #d63638; }

  .actions-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  .actions-bar h2 { font-size: 14px; font-weight: 600; color: var(--text); }

  /* Powered by */
  .powered-by {
    position: fixed; bottom: 12px; right: 16px;
    font-size: 10px; letter-spacing: 0.1em;
    color: var(--muted); z-index: 9999;
    pointer-events: none; user-select: none;
  }
</style>
