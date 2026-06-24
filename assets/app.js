import './styles/app.css';

/* ---- Navigace – hamburger ---- */
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

/* ---- Hero scroll hint ---- */
const scrollHint = document.querySelector('.hero__scroll');
if (scrollHint) {
  window.addEventListener('scroll', () => {
    scrollHint.style.opacity = window.scrollY > 80 ? '0' : '1';
  }, { passive: true });
}

/* ---- Sdílená logika formuláře ---- */
function initForm(formId, containerId, successId) {
  const form = document.getElementById(formId);
  if (!form) return;

  const today = new Date().toISOString().split('T')[0];
  const dateFrom  = form.querySelector('[name="date_from"]');
  const dateTo    = form.querySelector('[name="date_to"]');
  const dateField = form.querySelector('[name="date"]');
  if (dateFrom)  dateFrom.min  = today;
  if (dateTo)    dateTo.min    = today;
  if (dateField) dateField.min = today;

  dateFrom?.addEventListener('change', () => {
    if (!dateTo) return;
    dateTo.min = dateFrom.value;
    if (dateTo.value && dateTo.value < dateFrom.value) dateTo.value = dateFrom.value;
  });

  const textarea = form.querySelector('[name="message"]');
  const counter  = document.getElementById('msg-counter');
  textarea?.addEventListener('input', () => {
    if (counter) counter.textContent = `${textarea.value.length} / 1000`;
  });

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
      form.querySelector('.error')?.focus();
      return;
    }

    const container = document.getElementById(containerId);
    const success   = document.getElementById(successId);
    if (container) container.hidden = true;
    if (success)   { success.hidden = false; success.focus(); }
  });

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

initForm('inquiry-form', 'form-container', 'form-success');
initForm('wellness-form', 'wellness-form-container', 'wellness-success');

/* ---- Galerie lightbox ---- */
const lightbox  = document.getElementById('lightbox');
const lbImg     = document.getElementById('lb-img');
const lbCounter = document.getElementById('lb-counter');
const lbPrev    = document.getElementById('lb-prev');
const lbNext    = document.getElementById('lb-next');

let lbItems = [];
let lbIndex = 0;

function showLbSlide(idx) {
  lbIndex = idx;
  const el  = lbItems[idx];
  const img = el.tagName === 'IMG' ? el : el.querySelector('img');
  if (img && lbImg) { lbImg.src = img.src; lbImg.alt = img.alt || ''; }
  if (lbCounter) lbCounter.textContent = lbItems.length > 1 ? `${idx + 1} / ${lbItems.length}` : '';
  if (lbPrev) lbPrev.hidden = idx === 0;
  if (lbNext) lbNext.hidden = idx === lbItems.length - 1;
}

function openLightbox(items, idx) {
  lbItems = items;
  showLbSlide(idx);
  lightbox.classList.add('open');
  document.body.style.overflow = 'hidden';
  document.getElementById('lb-close')?.focus();
}

function closeLightbox() {
  if (!lightbox) return;
  lightbox.classList.remove('open');
  document.body.style.overflow = '';
}

const galleries = {};
document.querySelectorAll('[data-gallery]').forEach(el => {
  const key = el.dataset.gallery;
  if (!galleries[key]) galleries[key] = [];
  galleries[key].push(el);
});

document.querySelectorAll('.gallery-item, .room-card__main, .room-card__thumb').forEach(el => {
  if (el.getAttribute('tabindex') === '-1') return;
  el.addEventListener('click', () => {
    const key = el.dataset.gallery;
    if (key && galleries[key]) {
      openLightbox(galleries[key], Math.max(0, galleries[key].indexOf(el)));
    } else {
      openLightbox([el], 0);
    }
  });
  el.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
  });
});

lbPrev?.addEventListener('click', () => { if (lbIndex > 0) showLbSlide(lbIndex - 1); });
lbNext?.addEventListener('click', () => { if (lbIndex < lbItems.length - 1) showLbSlide(lbIndex + 1); });
document.getElementById('lb-close')?.addEventListener('click', closeLightbox);
lightbox?.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });
document.addEventListener('keydown', e => {
  if (!lightbox?.classList.contains('open')) return;
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft'  && lbIndex > 0)                   { e.preventDefault(); showLbSlide(lbIndex - 1); }
  if (e.key === 'ArrowRight' && lbIndex < lbItems.length - 1)  { e.preventDefault(); showLbSlide(lbIndex + 1); }
});

/* ---- Cookie consent lišta ---- */
(function () {
  const banner = document.getElementById('cookie-banner');
  if (!banner) return;

  const COOKIE_NAME = 'cookie_consent';

  function getConsent() {
    const match = document.cookie.split('; ').find(c => c.startsWith(COOKIE_NAME + '='));
    return match ? match.split('=')[1] : null;
  }

  function setConsent(value) {
    const maxAge = 60 * 60 * 24 * 365; // 1 rok
    document.cookie = `${COOKIE_NAME}=${value}; max-age=${maxAge}; path=/; SameSite=Lax`;
  }

  function hideBanner() {
    banner.hidden = true;
    banner.classList.remove('is-visible');
  }

  function showBanner() {
    banner.hidden = false;
    requestAnimationFrame(() => banner.classList.add('is-visible'));
  }

  if (!getConsent()) showBanner();

  document.getElementById('cookie-accept-all')?.addEventListener('click', () => {
    setConsent('all');
    if (window.gtag) gtag('consent', 'update', { analytics_storage: 'granted' });
    hideBanner();
  });

  document.getElementById('cookie-accept-necessary')?.addEventListener('click', () => {
    setConsent('necessary');
    if (window.gtag) gtag('consent', 'update', { analytics_storage: 'denied' });
    hideBanner();
  });
})();
