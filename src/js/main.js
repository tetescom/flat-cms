
  const hamburger  = document.getElementById('hamburger');
  const globalNav  = document.getElementById('globalNav');
  const navOverlay = document.getElementById('navOverlay');

  function openMenu() {
    hamburger.classList.add('open');
    hamburger.setAttribute('aria-expanded', 'true');
    hamburger.setAttribute('aria-label', 'メニューを閉じる');
    globalNav.classList.add('open');
    navOverlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    hamburger.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    hamburger.setAttribute('aria-label', 'メニューを開く');
    globalNav.classList.remove('open');
    navOverlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  if (hamburger) {
    hamburger.addEventListener('click', () => hamburger.classList.contains('open') ? closeMenu() : openMenu());
    navOverlay.addEventListener('click', closeMenu);
    globalNav.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  }

  // Parallax
  const heroImg = document.querySelector('.hero-photo img');
  window.addEventListener('scroll', () => {
    if (heroImg) heroImg.style.objectPosition = `center calc(50% + ${window.scrollY * 0.4}px)`;
  }, { passive: true });

  // Page top
  const pagetop = document.getElementById('pagetop');
  if (pagetop) {
    window.addEventListener('scroll', () => {
      pagetop.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    pagetop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  // Scroll reveal
  const observer = new IntersectionObserver(entries => {
    entries.forEach((e, i) => {
      if (e.isIntersecting) { setTimeout(() => e.target.classList.add('visible'), i * 80); }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // Form submit
  function handleSubmit(e) {
    e.preventDefault();
    const form   = e.target;
    const btn    = form.querySelector('.btn-submit');
    const span   = btn.querySelector('span');
    const msg    = document.getElementById('formMsg');

    // バリデーション
    for (const field of form.querySelectorAll('[required]')) {
      if (!field.value.trim()) {
        const labelEl = field.closest('.form-group')?.querySelector('label');
        const label = labelEl ? labelEl.childNodes[0].textContent.trim() : field.name;
        showMsg(msg, `${label}は必須です。`, 'error');
        return;
      }
    }

    btn.disabled = true;
    span.textContent = '送信中...';

    const data = new FormData(form);
    // CSRFトークンを追加
    data.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

    fetch('./php/contact.php', { method: 'POST', body: data })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          showMsg(msg, d.message, 'success');
          form.reset();
          span.textContent = '送信する — Send';
        } else {
          showMsg(msg, d.message, 'error');
          span.textContent = '送信する — Send';
        }
        btn.disabled = false;
      })
      .catch(() => {
        showMsg(msg, '通信エラーが発生しました。', 'error');
        span.textContent = '送信する — Send';
        btn.disabled = false;
      });
  }

  function showMsg(el, text, type) {
    if (!el) return;
    el.innerHTML = type === 'success'
      ? text + '<br><span style="font-size:11px;opacity:0.7;">※自動返信メールをお送りしました。迷惑メールフォルダもご確認ください。</span>'
      : text;
    el.className = 'form-msg ' + type;
    el.style.display = 'block';
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
