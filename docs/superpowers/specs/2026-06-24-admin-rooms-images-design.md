# Admin: správa pokojů + znovupoužitelný image subsystém

Datum: 2026-06-24
Stav: schváleno k plánování

## Cíl

Přidat do adminu (`/admin/*`) správu pokojů. Statické pokoje na veřejné stránce
`/ubytování` se migrují do databáze a stránka se renderuje z DB. Součástí je
znovupoužitelný systém pro upload a zpracování obrázků (resize + převod na `.webp`),
navržený tak, aby šel později použít i na dalších místech (vybavení, letecké
pohledy, galerie).

## Rozhodnutí (z brainstormingu)

- **Veřejná stránka DB-driven** — 8 statických pokojů se migruje do DB, admin je
  plně spravuje, `/ubytování` se vykresluje z DB.
- **Image backend: GD + Intervention Image v3** — GD se přidá do Dockerfile,
  služba postavená na Intervention Image v3.
- **Generická Image entita** — jeden znovupoužitelný tvar, teď napojený na Room
  přes nullable FK; pro další místa se přidá další FK / join-table bez změny tvaru.

## Architektura

```
Admin (/admin/*)  ──► Room CRUD ──► Room + Image entity ──► MySQL
   │ UX Dropzone upload          │
   ▼                             ▼
ImageProcessor service ──► resize + .webp ──► public/uploads/rooms/<slug>/
                                              │
Veřejná /ubytování ◄── render z DB ◄──────────┘
```

Tři logické kusy:
- **(A) Image subsystém** — Image entita + ImageProcessor služba + upload widget.
- **(B) Room CRUD + veřejné renderování** z DB.
- **(C) Admin shell** — vlastní layout + sidebar menu.

## Komponenty

### Room entita

Pole odvozená z věrnosti stávající karty pokoje.

| Pole | Typ | Poznámka |
|---|---|---|
| `id` | int | |
| `name` | string | "Dvoulůžkový pokoj" |
| `slug` | string, unique | = `data-gallery` klíč ("double-shared"), identita/URL |
| `description` | text, nullable | dlouhý popis (zatím nepoužit na kartě, pro budoucí detail) |
| `features` | json `list<string>` | bullety ("2 lůžka…", "Sdílená koupelna…") |
| `price` | int, nullable | CZK, kvůli řazení (800, 2100) |
| `priceFrom` | bool | prefix "od" |
| `priceUnit` | string | "/ noc (2+ noci)", "/ osoba / noc" |
| `position` | int | pořadí karet |
| `images` | OneToMany → Image | galerie, řazená dle `position` |
| `createdAt` / `updatedAt` | datetime | |

- Počet fotek ("11 fotek", "+7") je **odvozený** z `images`, neukládá se.
- Rezervace link je statický (`app_contact`), neukládá se.
- Zobrazení ceny: `(priceFrom ? "od " : ""){price} Kč {priceUnit}`.

### Image entita (generická)

Standalone záznam souboru, znovupoužitelný. Teď FK na Room (nullable → lze
odpojit/přesunout později).

| Pole | Typ | Poznámka |
|---|---|---|
| `id` | int | |
| `filename` | string | cesta k full webp variantě |
| `thumbnail` | string | cesta k thumb webp variantě |
| `originalName` | string | původní název souboru |
| `alt` | string, nullable | alt text |
| `width` / `height` | int | rozměry full varianty |
| `size` | int | velikost full varianty v bytech |
| `position` | int | pořadí v galerii |
| `isMain` | bool | cover karty |
| `room` | ManyToOne → Room, nullable | |
| `createdAt` | datetime | |

Generičnost = stejný tvar všude; pro další místa se přidá nullable FK nebo
join-table, bez změny tvaru entity.

### ImageProcessor služba

- **Dockerfile**: přidat GD (`install-php-extensions gd`) → rebuild image.
- **Composer**: `intervention/image` v3.
- **Služba `ImageProcessor`**: vstup `UploadedFile`:
  1. ošetří EXIF orientaci,
  2. vygeneruje 2 varianty webp — **full** (max 1600px šířka) + **thumb** (max 600px),
  3. uloží do `public/uploads/rooms/<slug>/`,
  4. vrátí naplněnou `Image` entitu (filename, thumbnail, rozměry, velikost, originalName).
- Originál se nedrží (jen webp varianty).
- Zavést `ImageStorageInterface` (cesta + zápis souboru) ať je služba testovatelná
  a znovupoužitelná mimo Room kontext.

### Upload + Symfony UX

- Admin dostane **vlastní JS entrypoint** který nabootuje Stimulus (appka teď
  Stimulus nebootuje — prázdný `bootstrap.js`) a zaregistruje UX Dropzone controller.
- `composer require symfony/ux-dropzone`.
- Form field = drag&drop, multi-file. Upload zpracuje controller → `ImageProcessor`
  → persist Image, napojí na Room, nastaví `position`.

### Admin shell

- Nový `templates/admin/base.html.twig`, **nezávislý** na public base — vlastní
  sidebar + obsahová oblast, styl konzistentní s login stránkou.
- Sidebar menu; první (zatím jediná) položka **Pokoje** → `/admin/rooms`.
- `/admin` dashboard zobrazí menu / rozcestník.
- `RoomController` (pod `^/admin`, `ROLE_ADMIN`):
  - `index` — seznam pokojů,
  - `new` — vytvořit,
  - `edit` — upravit + správa galerie (upload, smazat foto, drag-reorder, set cover),
  - `delete`.
- Symfony Form + session CSRF (jako login, ne stateless).

### Veřejná /ubytování z DB

- V [templates/accommodation/index.html.twig](../../../templates/accommodation/index.html.twig)
  se sekce „Pokoje a apartmány" přepíše na `{% for room in rooms %}`; počty fotek
  a thumbnaily z `room.images`.
- Zbytek stránky (O penzionu, vybavení, zahrada, praktické info) zůstává statický.
- `AccommodationController` načte pokoje z repository řazené dle `position`.

### Seed existujících pokojů

- Importní příkaz `app:import-rooms`: založí 8 pokojů + nakopíruje stávající
  `assets/images/rooms/*` do `public/uploads/` a vytvoří Image řádky.
- Jednorázový, idempotentní (znovuspuštění nevytvoří duplikáty).
- Zachová stávající fotky po přepnutí veřejné stránky na DB.

### Úložiště

- Uploady → `public/uploads/` (servíruje Caddy přímo, mimo asset-mapper).
- `.gitignore` pro nahrané soubory.

## Migrace

Doctrine migrace pro `room` a `image` tabulky se generuje výhradně přes
`doctrine:migrations:diff` (nikdy ručně), spouští se v containeru
(`docker compose exec -T php php bin/console …`).

## Testování

- Unit test `ImageProcessor` — vstup testovací obrázek → ověří 2 webp varianty,
  rozměry, EXIF orientaci (přes `ImageStorageInterface` s fake storage).
- Funkční test RoomController — CRUD pod `ROLE_ADMIN`, redirect anonyma na login.
- Funkční test veřejné `/ubytování` — renderuje pokoje z DB.

## Mimo rozsah (zatím)

- Napojení image subsystému na facilities / views / gallery (jen připraveno tvarem).
- Detail pokoje jako samostatná stránka (`description` pole je připravené).
- Rezervační logika.
```
