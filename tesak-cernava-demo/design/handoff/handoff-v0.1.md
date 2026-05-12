# Handoff balíček — Tesák-Čerňava
**Verze:** 0.1-discovery  
**Datum:** 7. května 2026  
**Stav:** Barevný systém FINAL · Typografie FINAL · Spacing FINAL · Wireframy CHYBÍ

---

## Co je v tomto balíčku

| Soubor | Obsah | Stav |
|---|---|---|
| `design/tokens/colors.css` | CSS custom properties — celý barevný systém | ✅ FINAL |
| `design/tokens/colors-guide.md` | Průvodce použitím barev + WCAG tabulka | ✅ FINAL |
| `design/tokens/CHANGELOG.md` | Historie změn tokenů | ✅ aktuální |
| `design/handoff/handoff-v0.1.md` | Tento dokument | ✅ |
| `01_BRIEF_PRO_AI_AGENTA.md` | Klientský brief, struktura webu, USP | ✅ zdrojový |
| `05_otazky_pro_klienta.md` | Otevřené otázky — klient musí odpovědět | ⏳ čeká |

---

## Typografie

| Vrstva | Font | Záloha | Načtení |
|---|---|---|---|
| Nadpisy | Fraunces (variable) | Georgia, serif | Google Fonts |
| Tělo | Inter (variable) | system-ui, sans-serif | Google Fonts |

### Stupnice (Major Third ×1.250, základ 16px)

| Token | px | Použití |
|---|---|---|
| `--text-xs` | 10px | Badge, popisky |
| `--text-sm` | 13px | Caption, label |
| `--text-base` | 16px | Tělo textu |
| `--text-md` | 20px | Perex, velký text |
| `--text-lg` | 25px | H3 |
| `--text-xl` | 31px | H2 |
| `--text-2xl` | 39px | H1 desktop |
| `--text-3xl` | 49px | Hero nadpis |

---

## Spacing

Základ 4px: `--space-1` (4px) až `--space-32` (128px)

---

## Grid a breakpointy

```
Mobil  < 640px:   1 sl., margin 16px
Tablet 640–1024px: 8 sl., margin 24px
Desktop > 1024px: 12 sl., max-width 1280px, gutter 24px
```

Breakpointy: `--bp-sm` 640px · `--bp-md` 768px · `--bp-lg` 1024px · `--bp-xl` 1280px

---

## Přechody (Transitions)

```css
/* Tlačítka, linky */
transition: background-color 150ms ease, color 150ms ease, border-color 150ms ease;

/* Karty */
transition: box-shadow 200ms ease, transform 200ms ease;
transform na hover: translateY(-2px);

/* Navigace drawer */
transition: transform 250ms cubic-bezier(0.4, 0, 0.2, 1);

/* Formulářová pole */
transition: border-color 120ms ease, box-shadow 120ms ease;
```

---

## Komponenty — přehled specifikací

| Komponenta | Popis | Stav v tomto balíčku |
|---|---|---|
| Primární tlačítko | 48px výška, bg primary-600, bílý text | ✅ specifikováno |
| Sekundární tlačítko | outline, primary-600 barva | ✅ specifikováno |
| Akcentní tlačítko | 48px, bg secondary-600, wellness | ✅ specifikováno |
| Navigace | sticky, hamburger < 1024px | ✅ specifikováno |
| Hero sekce | full-bleed, overlay, 2× CTA | ✅ specifikováno |
| Room Card | 4:3 foto, cena, CTA | ✅ specifikováno |
| Poptávkový formulář | 10 polí, GDPR, success/error stavy | ✅ detailně specifikováno |
| Sémantické bannery | success/warning/error/info | ✅ specifikováno |
| Ceník tabulka | responzivní, jen 1× na webu | ⚠️ obsah čeká na klienta |
| Google Maps embed | GPS 49.3670216, 17.775705 | ✅ specifikováno |
| Segmentové dlaždice | 4 dlaždice | ✅ popsáno |
| Testimonial blok | placeholder, čeká na klienta | ⏳ |
| Footer | kontakty, IČO, GDPR | ✅ specifikováno |
| Wellness karta | terapeut bio, ceník, rezervace | ⚠️ obsah čeká |
| Activity karta | trasa, vzdálenost, obtížnost | 🔲 zatím nepopsáno |

---

## Kritické datové opravy

```
GPS:    49.3670216, 17.775705  ← VŽDY toto, nikdy Praha (50.08, 14.42)
Tel:    <a href="tel:+420775727306">
Email:  <a href="mailto:tesakcernava@seznam.cz">
IČO:    44348649
IBAN:   CZ06 2010 0000 0022 0284 7380
```

---

## Stránky k implementaci

| Stránka | URL | Priorita | Stav obsahu |
|---|---|---|---|
| Homepage | `/` | P0 | ✅ draft |
| Pro skupiny | `/pro-skupiny/` | P0 — nejvyšší hodnota | ✅ draft |
| Ubytování | `/ubytovani/` | P1 | ✅ draft |
| Ceník | `/cenik/` | P1 | ⚠️ čeká na sjednocení ? |
| Kontakt | `/kontakt/` | P1 | ✅ draft |
| Wellness | `/wellness/` | P1 | ⚠️ čeká na bio terapeuta ? |
| Galerie | `/galerie/` | P2 | ⚠️ čeká na fotografie ? |
| Okolí a aktivity | `/okoli/` | P2 | ✅ draft |

---

## Co v tomto balíčku chybí (verze 0.2)

- [ ] Wireframy (mobile + desktop pro každou stránku)
- [ ] Finální fotografie od klienta
- [ ] Unified ceník — potvrzení od klienta
- [ ] Bio a foto terapeuta
- [ ] Učitelský testimonial
- [ ] Ikonová sada (Lucide / Heroicons — doporučeno)
- [ ] OG preview obrázky (1200×630px pro každou stránku)

---

## QA před nasazením

Kompletní checklist: viz sekci 8 tohoto handoff dokumentu (nebo `handoff-v0.1.md`).

**P0 položky — blokující nasazení:**
1. GPS = 49.3670216, 17.775705 (ne Praha!)
2. Jen JEDEN ceník na celém webu
3. Formulář funguje a doručuje e-mail
4. Telefon je `tel:` odkaz, e-mail je `mailto:` odkaz
5. GDPR souhlas je required pole

---

## Verzovací konvence

```
0.1-discovery   ← tento balíček
0.2-wireframes  ← až budou wireframy
0.3-components  ← až budou komponenty
1.0-review      ← první kompletní verze ke schválení klientem
2.0-launch      ← produkce
```

*Každá změna tokenů: zapsat do `design/tokens/CHANGELOG.md`*
