# Průvodce barevným systémem — Tesák-Čerňava

## Paleta v kostce

| Barva | Hex | Účel |
|---|---|---|
| primary-600 | `#22603C` | Hlavní brand barva — loga, nadpisy, CTA |
| primary-500 | `#2F7A4E` | Aktivní prvky, hover stavy |
| primary-50 | `#EFF7F2` | Světlé sekce, pozadí karet |
| secondary-600 | `#7D4C0A` | Wellness CTA, zvýraznění, akcentové tagy |
| secondary-300 | `#ECA848` | Dekorativní ikony, dělítka (ne text!) |
| neutral-800 | `#2C2420` | Tělo textu |
| neutral-600 | `#5A5049` | Sekundární text, popisky |
| neutral-50 | `#FAF8F5` | Pozadí stránky |
| neutral-100 | `#F2EFE9` | Alternativní pozadí sekcí (zebra vzor) |

---

## Pravidla WCAG AA — co smí stát na čem

### ✅ Ověřené kombinace pro normální text

| Popředí | Pozadí | Poměr |
|---|---|---|
| `#2C2420` neutral-800 | `#FFFFFF` bílá | **15.2:1** |
| `#2C2420` neutral-800 | `#FAF8F5` neutral-50 | **14.4:1** |
| `#2C2420` neutral-800 | `#F2EFE9` neutral-100 | **12.9:1** |
| `#5A5049` neutral-600 | `#FFFFFF` bílá | **7.8:1** |
| `#5A5049` neutral-600 | `#F2EFE9` neutral-100 | **6.8:1** |
| `#756A62` neutral-500 | `#FFFFFF` bílá | **5.25:1** |
| `#22603C` primary-600 | `#FFFFFF` bílá | **6.9:1** |
| `#22603C` primary-600 | `#FAF8F5` neutral-50 | **6.5:1** |
| `#2F7A4E` primary-500 | `#FFFFFF` bílá | **5.2:1** |
| `#7D4C0A` secondary-600 | `#FFFFFF` bílá | **7.2:1** |
| `#A8650F` secondary-500 | `#FFFFFF` bílá | **4.6:1** |
| `#FFFFFF` bílá | `#22603C` primary-600 | **6.9:1** |
| `#FFFFFF` bílá | `#7D4C0A` secondary-600 | **7.2:1** |
| `#FAF8F5` neutral-50 | `#22603C` primary-600 | **6.5:1** |

### ⚠ Pouze velký text (≥ 18px nebo ≥ 14px tučný) nebo UI ikony

| Popředí | Pozadí | Poměr |
|---|---|---|
| `#4A9468` primary-400 | `#FFFFFF` bílá | **3.7:1** |
| `#D38320` secondary-400 | `#FFFFFF` bílá | **2.9:1** — pouze dekor |

### ❌ Zakázané kombinace

- secondary-300 `#ECA848` nebo světlejší — **nikdy jako text**
- primary-300 `#70B48C` nebo světlejší — **nikdy jako text**
- neutral-400 `#A89C93` — pouze placeholder/disabled, ne čitelný text

---

## Jak používat tokeny v kódu

```css
/* Tělo stránky */
body {
  background: var(--color-surface-page);
  color: var(--color-text-primary);
}

/* Karta */
.card {
  background: var(--color-surface-card);
  border: 1px solid var(--color-border-default);
}

/* Alternativní sekce (každá druhá) */
.section--muted {
  background: var(--color-surface-muted);
}

/* Primární CTA tlačítko */
.btn--primary {
  background: var(--color-btn-primary-bg);
  color: var(--color-btn-primary-text);
}
.btn--primary:hover {
  background: var(--color-btn-primary-bg-hover);
}

/* Wellness / akcentní tlačítko */
.btn--accent {
  background: var(--color-btn-accent-bg);
  color: var(--color-btn-accent-text);
}

/* Focus ring (přístupnost klávesnicí) */
:focus-visible {
  outline: 2px solid var(--color-border-focus);
  outline-offset: 2px;
}

/* Odkaz */
a {
  color: var(--color-text-link);
}
a:hover {
  color: var(--color-text-link-hover);
}

/* Tmavá hero sekce */
.hero--dark {
  background: var(--color-surface-primary);
  color: var(--color-text-on-dark);
}
```

---

## Sémantické barvy — hlášky a stavy

```css
/* Úspěch — potvrzení rezervace */
.alert--success {
  background: var(--color-success-bg);
  border-left: 3px solid var(--color-success-border);
  color: var(--color-success-text);
}

/* Varování — kapacita omezena */
.alert--warning {
  background: var(--color-warning-bg);
  border-left: 3px solid var(--color-warning-border);
  color: var(--color-warning-text);
}

/* Chyba — neplatný formulář */
.alert--error {
  background: var(--color-error-bg);
  border-left: 3px solid var(--color-error-border);
  color: var(--color-error-text);
}

/* Info — doplňkové sdělení */
.alert--info {
  background: var(--color-info-bg);
  border-left: 3px solid var(--color-info-border);
  color: var(--color-info-text);
}
```

---

## Designová filozofie barev

**Primární zeleň** (`#22603C`) odráží bukový les Hostýnských vrchů — je tmavá, zemitá, ne klinická ani výrazně moderní. Asociuje přírodu, důvěru, klid.

**Jantarový akcent** (`#7D4C0A`) evokuje teplé dřevo, plamen krbu a med — používáme ho střídmě pro wellness CTA a zvýraznění, ne jako dominantní barvu.

**Neutrály** jsou záměrně teplé (mírný krémový nádech) — vyhýbáme se studeně šedým, které by potlačily útulnou atmosféru penzionu.

**Nikdy nepoužívat:** zářivé nebo neonové odstíny, studené modré nebo fialové jako primární barvy — narušily by charakter značky.
