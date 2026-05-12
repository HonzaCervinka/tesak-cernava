/* Tesák-Čerňava — main.js */

/* ---- Navigace - hamburger ---- */
const hamburger = document.getElementById('nav-hamburger');
const drawer    = document.getElementById('nav-drawer');
const overlay   = drawer?.querySelector('.nav__overlay');
const panelClose = document.getElementById('nav-close');

function openDrawer() {
  if (!drawer) return;
  drawer.classList.add('open');
  hamburger.setAttribute('aria-expanded', 'true');
  document.body.style.overflow = 'hidden';
  panelClose?.focus();
}

function closeDrawer() {
  if (!drawer) return;
  drawer.classList.remove('open');
  hamburger?.setAttribute('aria-expanded', 'false');
  document.body.style.overflow = '';
  hamburger?.focus();
}

hamburger?.addEventListener('click', openDrawer);
panelClose?.addEventListener('click', closeDrawer);
overlay?.addEventListener('click', closeDrawer);
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && drawer?.classList.contains('open')) closeDrawer();
});

/* ---- Aktivní odkaz v navigaci ---- */
const currentFile = location.pathname.split('/').pop() || 'index.html';
document.querySelectorAll('.nav__link, .nav__panel-link').forEach(link => {
  const href = link.getAttribute('href') || '';
  const isHome = (currentFile === '' || currentFile === 'index.html') && (href === 'index.html' || href === './');
  if (href === currentFile || isHome) link.classList.add('active');
});

/* ---- Hero scroll hint ---- */
const scrollHint = document.querySelector('.hero__scroll');
if (scrollHint) {
  const fadeHint = () => {
    scrollHint.style.opacity = window.scrollY > 80 ? '0' : '1';
  };
  window.addEventListener('scroll', fadeHint, { passive: true });
}

/* ---- Formulář ---- */
const form = document.getElementById('inquiry-form');
if (form) {
  /* Dnešní datum jako minimum */
  const today = new Date().toISOString().split('T')[0];
  const dateFrom = form.querySelector('[name="date_from"]');
  const dateTo   = form.querySelector('[name="date_to"]');
  if (dateFrom) dateFrom.min = today;
  if (dateTo)   dateTo.min   = today;

  /* Odjezd min = příjezd */
  dateFrom?.addEventListener('change', () => {
    if (!dateTo) return;
    dateTo.min = dateFrom.value;
    if (dateTo.value && dateTo.value < dateFrom.value) dateTo.value = dateFrom.value;
  });

  /* Počítadlo znaků */
  const textarea = form.querySelector('[name="message"]');
  const counter  = document.getElementById('msg-counter');
  textarea?.addEventListener('input', () => {
    if (counter) counter.textContent = `${textarea.value.length} / 1000`;
  });

  /* Validace a odeslání */
  form.addEventListener('submit', e => {
    e.preventDefault();
    let valid = true;

    form.querySelectorAll('[required]').forEach(field => {
      const errId = field.getAttribute('aria-describedby');
      const errEl = errId ? document.getElementById(errId) : null;
      const empty = field.type === 'checkbox' ? !field.checked : !field.value.trim();

      if (empty) {
        field.classList.add('error');
        if (errEl) errEl.hidden = false;
        valid = false;
      } else {
        field.classList.remove('error');
        if (errEl) errEl.hidden = true;
      }
    });

    if (!valid) {
      const firstError = form.querySelector('.error');
      firstError?.focus();
      return;
    }

    /* Skrýt formulář, zobrazit potvrzení */
    const container = document.getElementById('form-container');
    const success   = document.getElementById('form-success');
    if (container) container.hidden = true;
    if (success)   { success.hidden = false; success.focus(); }
  });

  /* Real-time validace při blur */
  form.querySelectorAll('[required]').forEach(field => {
    field.addEventListener('blur', () => {
      const errId = field.getAttribute('aria-describedby');
      const errEl = errId ? document.getElementById(errId) : null;
      const empty = field.type === 'checkbox' ? !field.checked : !field.value.trim();
      field.classList.toggle('error', empty && field !== document.activeElement);
      if (errEl) errEl.hidden = !empty;
    });
  });
}

/* ---- Galerie lightbox ---- */
const lightbox = document.getElementById('lightbox');
const lbImg    = document.getElementById('lb-img');

document.querySelectorAll('.gallery-item').forEach(item => {
  item.addEventListener('click', () => {
    const src = item.querySelector('img')?.src;
    const alt = item.querySelector('img')?.alt || '';
    if (lightbox && lbImg && src) {
      lbImg.src = src;
      lbImg.alt = alt;
      lightbox.classList.add('open');
      document.body.style.overflow = 'hidden';
      lightbox.querySelector('.lightbox__close')?.focus();
    }
  });
  item.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); item.click(); }
  });
});

function closeLightbox() {
  if (!lightbox) return;
  lightbox.classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('lb-close')?.addEventListener('click', closeLightbox);
lightbox?.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && lightbox?.classList.contains('open')) closeLightbox();
});
