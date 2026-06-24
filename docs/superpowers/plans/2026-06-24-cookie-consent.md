# Cookie Consent Lišta Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** GDPR cookie consent lišta se 2 tlačítky a Google Consent Mode v2, připravená na pozdější napojení GA4 vyplněním jednoho env parametru.

**Architecture:** Inline Consent Mode init v `<head>` (default `denied`) běží vždy. GA4 `<script>` se vykreslí jen když je vyplněné `GA_MEASUREMENT_ID`. Volba uživatele se ukládá do cookie `cookie_consent`; vanilla JS v `app.js` zobrazí/skryje lištu a volá `gtag('consent','update')`. Styl přes existující CSS design tokeny.

**Tech Stack:** Symfony 8, Twig, asset-mapper/importmap, vanilla JS, vanilla CSS, Google Consent Mode v2.

## Global Constraints

- **Jazyk UI:** čeština.
- **Žádné nové JS závislosti** — vanilla JS, stejný pattern jako `assets/app.js` (nav, lightbox).
- **CSS jen přes design tokeny** z `assets/styles/app.css` (`--color-*`, `--space-*`, `--text-*`, `--radius-*`).
- **GA tag NESMÍ být napojen** — `GA_MEASUREMENT_ID` zůstává prázdné; aktivaci řeší uživatel později.
- **Cookie:** název `cookie_consent`, hodnoty `all` | `necessary`, platnost 365 dní, `path=/; SameSite=Lax`.
- **Consent Mode default:** `analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization` = `denied`.
- **Route zásad:** `app_privacy`.

---

### Task 1: Env parametr + Twig global

Zpřístupní `GA_MEASUREMENT_ID` v šablonách jako globální Twig proměnnou `ga_measurement_id` (prázdný string default).

**Files:**
- Modify: `.env` (přidat řádek do nové sekce)
- Modify: `config/packages/twig.yaml:2-11` (přidat global)

**Interfaces:**
- Produces: Twig global `ga_measurement_id` (string, prázdný když env nevyplněné) — používá Task 2.

- [ ] **Step 1: Přidat env proměnnou**

Přidat na konec `.env`:

```dotenv
###> google/analytics ###
# GA4 Measurement ID, např. G-XXXXXXXXXX. Prázdné = GA se nenačte.
GA_MEASUREMENT_ID=
###< google/analytics ###
```

- [ ] **Step 2: Přidat Twig global**

V `config/packages/twig.yaml` do bloku `globals:` (za `business:`) přidat:

```yaml
        ga_measurement_id: '%env(GA_MEASUREMENT_ID)%'
```

- [ ] **Step 3: Ověřit že cache projde a global existuje**

Run: `php bin/console cache:clear && php bin/console debug:twig --format=text 2>&1 | grep -i ga_measurement_id`
Expected: výpis obsahuje `ga_measurement_id` (nebo příkaz proběhne bez chyby). Pokud běží přes docker, použij `docker compose exec <php-service> php bin/console ...`.

- [ ] **Step 4: Commit**

```bash
git add .env config/packages/twig.yaml
git commit -m "feat: expose GA_MEASUREMENT_ID as twig global"
```

---

### Task 2: Consent Mode init + podmíněný GA tag v `<head>`

Vloží do `<head>` inicializaci Google Consent Mode v2 (vždy) a GA4 script (jen když je ID vyplněné).

**Files:**
- Modify: `templates/base.html.twig:16-18` (uvnitř `<head>`, před `{% block head_extra %}`)

**Interfaces:**
- Consumes: Twig global `ga_measurement_id` (Task 1).
- Produces: globální `window.gtag` funkce a `dataLayer` — používá Task 4. Čte cookie `cookie_consent` (Task 4 ji zapisuje).

- [ ] **Step 1: Vložit skripty do `<head>`**

V `templates/base.html.twig` hned za řádek `<link href="https://fonts.googleapis.com/css2?...rel="stylesheet">` (a před `{% block head_extra %}`) vložit:

```twig
  {# --- Google Consent Mode v2 (běží vždy, i bez GA) --- #}
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent', 'default', {
      'analytics_storage': 'denied',
      'ad_storage': 'denied',
      'ad_user_data': 'denied',
      'ad_personalization': 'denied',
      'wait_for_update': 500
    });
    if (document.cookie.split('; ').indexOf('cookie_consent=all') !== -1) {
      gtag('consent', 'update', { 'analytics_storage': 'granted' });
    }
  </script>
  {% if ga_measurement_id is not empty %}
    {# --- GA4 – načte se jen s vyplněným measurement ID --- #}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ ga_measurement_id }}"></script>
    <script>
      gtag('js', new Date());
      gtag('config', '{{ ga_measurement_id }}');
    </script>
  {% endif %}
```

- [ ] **Step 2: Ověřit render bez GA**

Načti libovolnou stránku (např. `/`) v prohlížeči nebo přes `curl`. S prázdným `GA_MEASUREMENT_ID`:

Run: `curl -s http://localhost/ | grep -c "googletagmanager.com/gtag"` (uprav port/host dle běžícího serveru)
Expected: `0` — GA script se nevykreslil. Zároveň `curl -s http://localhost/ | grep -c "consent', 'default'"` → `1` (consent init je tam).

- [ ] **Step 3: Commit**

```bash
git add templates/base.html.twig
git commit -m "feat: add Consent Mode v2 init and conditional GA4 tag"
```

---

### Task 3: Markup cookie lišty

Přidá HTML lišty na konec `<body>` v base šabloně. Defaultně skrytá (zobrazí ji JS v Task 4).

**Files:**
- Modify: `templates/base.html.twig` (před `</body>`, za blok `lightbox`)

**Interfaces:**
- Produces: DOM prvky s ID `cookie-banner`, `cookie-accept-all`, `cookie-accept-necessary` — používá Task 4. CSS třída `.cookie-banner` (+ `.is-visible`) — stylováno v Task 5.

- [ ] **Step 1: Vložit markup**

V `templates/base.html.twig` hned před `</body>` (za uzavírací `</div>` lightboxu) vložit:

```twig
  <div class="cookie-banner" id="cookie-banner" role="dialog" aria-live="polite" aria-label="Souhlas s cookies" hidden>
    <div class="cookie-banner__inner">
      <p class="cookie-banner__text">
        Tento web používá nezbytné cookies pro svůj provoz a — s vaším souhlasem —
        analytické cookies služby Google Analytics, které nám pomáhají web zlepšovat.
        Více v <a href="{{ path('app_privacy') }}" class="cookie-banner__link">zásadách ochrany osobních údajů</a>.
      </p>
      <div class="cookie-banner__actions">
        <button type="button" class="btn btn--ghost btn--sm" id="cookie-accept-necessary">Přijmout jen nezbytné</button>
        <button type="button" class="btn btn--primary btn--sm" id="cookie-accept-all">Přijmout vše</button>
      </div>
    </div>
  </div>
```

- [ ] **Step 2: Ověřit render**

Run: `curl -s http://localhost/ | grep -c 'id="cookie-banner"'`
Expected: `1`. Prvek má atribut `hidden` (zatím neviditelný — JS ho odkryje v Task 4).

Pozn.: pokud třída `btn--ghost` v `app.css` neexistuje, Task 5 ji doplní; vizuálně se to projeví až tam.

- [ ] **Step 3: Commit**

```bash
git add templates/base.html.twig
git commit -m "feat: add cookie consent banner markup"
```

---

### Task 4: JS logika lišty

Zobrazí lištu když chybí cookie, na klik uloží volbu, zavolá Consent Mode update a skryje lištu.

**Files:**
- Modify: `assets/app.js` (přidat blok na konec souboru)

**Interfaces:**
- Consumes: DOM ID z Task 3 (`cookie-banner`, `cookie-accept-all`, `cookie-accept-necessary`); `window.gtag` z Task 2.
- Produces: zapisuje cookie `cookie_consent` (`all`|`necessary`), kterou čte Task 2 init při příštím načtení.

- [ ] **Step 1: Přidat kód na konec `assets/app.js`**

```javascript

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
```

- [ ] **Step 2: Ověřit chování v prohlížeči**

1. Otevři web v anonymním okně → lišta je vidět dole.
2. DevTools → Application → Cookies: žádná `cookie_consent`.
3. Klik „Přijmout vše" → lišta zmizí, cookie `cookie_consent=all` existuje.
4. Reload → lišta se nezobrazí.
5. Smaž cookie, reload, klik „Přijmout jen nezbytné" → cookie `cookie_consent=necessary`, lišta zmizí, reload → nezobrazí se.

Expected: všech 5 bodů sedí.

- [ ] **Step 3: Commit**

```bash
git add assets/app.js
git commit -m "feat: cookie banner show/hide and consent update logic"
```

---

### Task 5: Styl lišty

Nastyluje lištu přes design tokeny — fixní dole, responsivní, animované zobrazení.

**Files:**
- Modify: `assets/styles/app.css` (přidat na konec)

**Interfaces:**
- Consumes: třídy `.cookie-banner`, `.cookie-banner__inner/__text/__link/__actions`, `.is-visible` z Task 3; `.btn--ghost` doplnit pokud chybí.

- [ ] **Step 1: Ověřit existující tokeny a `.btn--ghost`**

Run: `grep -nE "\.btn--ghost|--color-surface|--shadow|--radius" assets/styles/app.css | head`
Expected: zjistit, které tokeny/varianty existují. Pokud `.btn--ghost` chybí, přidá se v dalším kroku; pokud `--shadow-*`/`--radius-*` mají jiné názvy, použij existující.

- [ ] **Step 2: Přidat CSS na konec `assets/styles/app.css`**

```css

/* ---- Cookie consent lišta ---- */
.cookie-banner {
  position: fixed;
  left: var(--space-4);
  right: var(--space-4);
  bottom: var(--space-4);
  z-index: 1000;
  background: var(--color-surface, #fff);
  border: 1px solid var(--color-border, #e5e5e5);
  border-radius: var(--radius-lg, 12px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.18);
  padding: var(--space-5);
  opacity: 0;
  transform: translateY(1rem);
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.cookie-banner.is-visible {
  opacity: 1;
  transform: translateY(0);
}
.cookie-banner__inner {
  display: flex;
  align-items: center;
  gap: var(--space-5);
  max-width: var(--container-max, 1200px);
  margin-inline: auto;
  flex-wrap: wrap;
}
.cookie-banner__text {
  flex: 1 1 320px;
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  line-height: 1.5;
}
.cookie-banner__link { color: var(--color-text-primary, inherit); text-decoration: underline; }
.cookie-banner__actions {
  display: flex;
  gap: var(--space-3);
  flex-shrink: 0;
}
@media (max-width: 600px) {
  .cookie-banner__actions { width: 100%; }
  .cookie-banner__actions .btn { flex: 1; }
}
```

Pokud `.btn--ghost` v souboru chybí, přidat ještě:

```css
.btn--ghost {
  background: transparent;
  border: 1px solid var(--color-border, #d4d4d4);
  color: var(--color-text-primary, #1a1a1a);
}
.btn--ghost:hover { background: var(--color-surface-muted, #f5f5f5); }
```

- [ ] **Step 3: Ověřit vizuál**

Smaž cookie `cookie_consent`, reload v prohlížeči (desktop i mobil ~375 px):
Expected: lišta dole s plovoucím okrajem, text vlevo, 2 tlačítka vpravo (na mobilu pod sebou, plná šířka), „Přijmout vše" primární, „jen nezbytné" ghost. Plynulý fade-in.

- [ ] **Step 4: Commit**

```bash
git add assets/styles/app.css
git commit -m "feat: style cookie consent banner"
```

---

### Task 6: Aktualizace zásad ochrany os. údajů

Přepíše sekci „Cookies a sledování" — teď nepravdivě tvrdí, že web nepoužívá analytické cookies.

**Files:**
- Modify: `templates/privacy/index.html.twig:99-101`

**Interfaces:** žádné (statický text).

- [ ] **Step 1: Přepsat sekci**

Nahradit řádky 99–101 (`<h2>Cookies a sledování</h2>` a oba následující `<p>`) za:

```twig
    <h2>Cookies a sledování</h2>
    <p>Web používá <strong>nezbytné technické cookies</strong> pro svůj provoz a bezpečné odeslání formuláře (CSRF token). Tyto cookies nepodléhají souhlasu dle <strong>§ 89 zákona č. 127/2005 Sb.</strong></p>
    <p>S vaším souhlasem web dále používá <strong>analytické cookies služby Google Analytics</strong> (Google Ireland Ltd.), které měří návštěvnost a pomáhají web zlepšovat. Tyto cookies se aktivují <strong>až po udělení souhlasu</strong> v cookie liště. Bez souhlasu se neukládají ani neodesílají žádná analytická data (Google Consent Mode).</p>
    <p>Svůj souhlas můžete kdykoliv odvolat smazáním cookies ve vašem prohlížeči — při další návštěvě se cookie lišta zobrazí znovu a budete moci volbu změnit.</p>
```

- [ ] **Step 2: Ověřit render**

Run: `curl -s http://localhost/zasady-ochrany-osobnich-udaju | grep -c "Google Analytics"`
Expected: `>= 1`. Stránka neobsahuje staré tvrzení „nepoužívá analytické ani marketingové cookies".

- [ ] **Step 3: Commit**

```bash
git add templates/privacy/index.html.twig
git commit -m "docs: update privacy policy cookies section for GA"
```

---

## Poznámky k aktivaci GA (mimo plán)

Až bude uživatel chtít GA zapnout: vyplnit `GA_MEASUREMENT_ID=G-XXXXXXXXXX` v `.env`, `cache:clear`. Žádná změna kódu lišty. Před spuštěním ověřit, že měřicí ID odpovídá reálnému GA4 property.
