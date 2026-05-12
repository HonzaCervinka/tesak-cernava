# Changelog — Barevné tokeny

## [0.1] — 2026-05-07

### Přidáno
- Primitivní stupnice `primary-50` až `primary-900` (lesní zeleň, kotva #22603C)
- Primitivní stupnice `secondary-50` až `secondary-900` (jantarové dřevo, kotva #7D4C0A)
- Primitivní stupnice `neutral-0` až `neutral-900` (teplé šedé, krémová základna)
- Sémantické tokeny: `success`, `warning`, `error`, `info` (bg / border / text)
- Surface tokeny: `surface-page`, `surface-card`, `surface-muted`, `surface-primary`, `surface-primary-light`, `surface-secondary`, `surface-overlay`
- Text tokeny: `text-primary`, `text-secondary`, `text-muted`, `text-disabled`, `text-placeholder`, `text-on-dark`, `text-on-dark-muted`, `text-link`, `text-link-hover`, `text-link-visited`
- Border tokeny: `border-default`, `border-strong`, `border-focus`, `border-primary`
- Button tokeny: `btn-primary-*`, `btn-secondary-*`, `btn-accent-*`

### Ověřeno
- Všechny kombinace text/pozadí splňují WCAG AA (≥ 4.5:1 pro normální text)
- Kontrastní poměry zdokumentovány v `colors-guide.md`
- Zakázané kombinace (dekorativní barvy jako text) explicitně označeny v `colors.css`
