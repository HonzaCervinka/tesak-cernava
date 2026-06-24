# Cookie consent lišta — design

**Datum:** 2026-06-24
**Web:** Tesák-Čerňava (Symfony 8, asset-mapper/importmap, vanilla JS, vanilla CSS s design tokeny)

## Cíl

GDPR/§ 89 z. č. 127/2005 Sb. compliant cookie lišta. Web bude později používat
Google Analytics 4 (reklama ne). Lišta se staví teď, GA se napojí později vyplněním
jednoho env parametru — bez další změny lišty.

## Rozhodnutí

- **2 tlačítka:** „Přijmout vše" / „Přijmout jen nezbytné". Druhé = plnohodnotné
  odmítnutí analytiky (nezbytné cookies souhlas nevyžadují, § 89). Obě tlačítka
  vizuálně rovnocenná (GDPR požaduje stejně snadné odmítnutí jako přijetí).
- **Google Consent Mode v2, default `denied`.** Stav se řeší od první sekundy,
  i když GA ještě není napojené.
- **Volba uložená v cookie** `cookie_consent` (hodnota `all` | `necessary`),
  platnost 1 rok, `SameSite=Lax`, `path=/`. Ne localStorage — přežije, je
  auditovatelné, čte se i bez JS později.
- **GA tag se vloží jen když je vyplněné `GA_MEASUREMENT_ID`.** Teď prázdné →
  nenačte se nic. Vyplnění ID = jediný krok pro aktivaci GA.

## Komponenty

### 1. Env parametr
- `.env`: `GA_MEASUREMENT_ID=` (prázdné).
- Zpřístupnit v Twigu přes `config/services.yaml` →
  `twig.globals` nebo parametr `app.ga_measurement_id` (default prázdný řetězec).

### 2. Consent Mode init (inline `<head>` skript v base.html.twig)
- Definuje `dataLayer`, `gtag()`.
- `gtag('consent','default', { analytics_storage:'denied', ad_storage:'denied',
  ad_user_data:'denied', ad_personalization:'denied', wait_for_update:500 })`.
- Pokud cookie `cookie_consent=all` → hned `gtag('consent','update',{analytics_storage:'granted'})`.
- Běží **vždy**, nezávisle na GA ID.

### 3. GA4 tag (base.html.twig, podmíněně)
- `{% if ga_id %}` → vloží `https://www.googletagmanager.com/gtag/js?id={{ ga_id }}`
  + `gtag('js', new Date()); gtag('config', ga_id)`.
- Bez ID se nevykreslí nic.

### 4. Banner (base.html.twig)
- Fixní lišta dole, `role="dialog"`, `aria-live`, skryté přístupné popisky.
- Text: stručně „používáme cookies… analytika Google" + odkaz na zásady
  (`path('app_privacy')` nebo existující route privacy stránky).
- 2 `<button>`: `#cookie-accept-all`, `#cookie-accept-necessary`.
- Default skrytý; JS ho zobrazí jen když cookie `cookie_consent` chybí.

### 5. JS logika (assets/app.js — vanilla, stejný pattern jako nav/lightbox)
- Helper `getCookie`/`setCookie`.
- Při loadu: pokud `cookie_consent` chybí → zobraz banner.
- Klik „Přijmout vše": `setCookie('cookie_consent','all',365)`,
  `gtag('consent','update',{analytics_storage:'granted', ad_storage:'denied'})`,
  skryj banner.
- Klik „Přijmout jen nezbytné": `setCookie('cookie_consent','necessary',365)`,
  consent zůstává denied, skryj banner.
- `gtag` volání obalit guardem (`window.gtag && ...`) — když GA není, no-op.

### 6. CSS (assets/styles/app.css)
- `.cookie-banner` přes design tokeny (`--color-*`, `--space-*`, `--text-sm`,
  `--radius`, stíny). Responsivní: na mobilu tlačítka pod sebe.
- Z-index nad obsah, pod nav drawer/lightbox.

### 7. Privacy stránka (templates/privacy/index.html.twig)
- Přepsat sekci „Cookies a sledování": web používá analytické cookies Google
  Analytics jen po souhlasu; popsat volbu a možnost odvolání (smazat cookie /
  znovu-zobrazení lišty — viz níže).

## Odvolání souhlasu (YAGNI check)
Minimálně: zmínit v zásadách, že souhlas lze odvolat smazáním cookies v prohlížeči.
Volitelně později: odkaz „Nastavení cookies" co smaže `cookie_consent` a zobrazí
banner znovu. **Mimo scope teď** — přidat jen pokud uživatel chce.

## Mimo scope
- Reálné napojení GA (vyplnění ID) — uživatel řeší později.
- Granulární kategorie / modal s přepínači — nepotřeba, jen analytics.
- Google Fonts hostované z googleapis.com (třetí strana) vs. tvrzení v zásadách
  „fonty lokálně" — nesouvisí s lištou, řešit samostatně.

## Testování
- Bez `GA_MEASUREMENT_ID`: banner se zobrazí, kliky uloží cookie, žádný GA request.
- Po kliku „vše": cookie `all`, reload → banner skrytý.
- Po kliku „nezbytné": cookie `necessary`, reload → banner skrytý, consent denied.
- Po vyplnění ID (manuální test): GA request jen po souhlasu „vše".
