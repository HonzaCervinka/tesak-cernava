# Masáže — admin CRUD + dynamický ceník

Datum: 2026-06-26
Branch: feature/admin-rooms-images

## Cíl

Spravovat masáže v adminu (název + libovolný počet cen, každá za určitou délku) a
vykreslovat je dynamicky z DB na dvou veřejných místech, kde jsou dnes hardcoded:
ceník (`/cenik`, sekce „Wellness a masáže") a wellness stránka (`/wellness`).

## Kontext

- Masáže jsou dnes hardcoded **dvakrát**, identicky: [pricing](../../../templates/pricing/index.html.twig)
  a [wellness](../../../templates/wellness/index.html.twig). 11 položek.
- Markup položky: `wellness-item` > `wellness-item__name` (+ volitelný `<small>` note) >
  `wellness-item__prices` > N× `wellness-item__price` = `<strong>{cena} Kč</strong><span>{délka} min</span>`.
- Vzor pro entitu i admin: `Room` → `Image` (OneToMany, cascade, orphanRemoval),
  `RoomController` (index/new/edit/delete + CSRF), `RoomType` (CollectionType `features`).
- Admin menu: [admin/base.html.twig](../../../templates/admin/base.html.twig) má dnes jediný odkaz „Pokoje".
- Žádný JS pro přidávání/mazání řádků CollectionType zatím neexistuje → doplníme.

## Datový model

### Massage
- `id` int
- `name` string(180)
- `note` ?string(255) — volitelná poznámka (dnešní `<small>`, např. „(předehřátí před masáží)")
- `position` int — řazení ve výpisu
- `prices` OneToMany → MassagePrice, `cascade: [persist, remove]`, `orphanRemoval: true`,
  `OrderBy(['minutes' => 'ASC'])`
- pomocné: `addPrice()`, `removePrice()`

### MassagePrice
- `id` int
- `minutes` int — délka (30, 60…), vykreslí se jako „{minutes} min"
- `price` int — cena v Kč
- `position` int — pořadí (rezerva; primárně řadíme dle minutes)
- `massage` ManyToOne → Massage, `JoinColumn(onDelete: 'CASCADE')`

Migrace přes `bin/console doctrine:migrations:diff` (NE ručně).

## Repository

`MassageRepository::findAllOrdered(): Massage[]` — `ORDER BY position ASC, id ASC`.

## Admin

### Controller — `src/Controller/Admin/MassageController.php`
- `#[Route('/admin/massages')]`, `#[IsGranted('ROLE_ADMIN')]`.
- `index` (GET) — výpis masáží (název, počet cen, akce).
- `new` (GET|POST), `edit` (GET|POST) — formulář `MassageType`, flush, flash, redirect na index.
- `delete` (POST) — CSRF `delete{id}`, remove, flush.

### Formuláře
- `MassageType`: `name` (TextType), `note` (TextType, required false), `position` (IntegerType),
  `prices` (CollectionType s `entry_type: MassagePriceType`, `allow_add`, `allow_delete`,
  `prototype`, `by_reference: false`).
- `MassagePriceType`: `price` (IntegerType, „Cena (Kč)"), `minutes` (IntegerType, „Délka (min)").

### Stimulus — `assets/controllers/form_collection_controller.js`
Znovupoužitelný controller pro CollectionType: tlačítko „+ přidat cenu" naklonuje
`data-prototype` (nahradí `__name__` indexem) a vloží řádek; každý řádek má „× odebrat",
které řádek smaže z DOM. Targets: `container`; values: `prototype`, `index`.

### Šablony
- `admin/massage/index.html.twig` — tabulka (Pořadí, Název, Počet cen, akce upravit/smazat).
- `admin/massage/new.html.twig`, `edit.html.twig` — extends admin base, include `_form`.
- `admin/massage/_form.html.twig` — pole name/note/position + sekce cen s `form_collection`
  controllerem (container, prototype, add tlačítko); každý existující i nový řádek má odebrat.
- Admin menu: do [admin/base.html.twig](../../../templates/admin/base.html.twig) přidat odkaz
  „Masáže" (`app_admin_massages_index`), aktivní stav dle `route starts with 'app_admin_massages'`.

## Veřejné stránky

- `PricingController::index` a `WellnessController::index` injectnou `MassageRepository`
  a předají `massages => $repo->findAllOrdered()`.
- V obou šablonách nahradit hardcoded `wellness-item` bloky smyčkou:
  ```twig
  {% for m in massages %}
    <div class="wellness-item">
      <span class="wellness-item__name">{{ m.name }}{% if m.note %} <small style="font-weight:400;color:var(--color-text-muted)">{{ m.note }}</small>{% endif %}</span>
      <div class="wellness-item__prices">
        {% for p in m.prices %}
          <div class="wellness-item__price"><strong>{{ p.price|number_format(0, ',', ' ') }} Kč</strong><span>{{ p.minutes }} min</span></div>
        {% endfor %}
      </div>
    </div>
  {% endfor %}
  ```
  (NBSP pro tisíce/jednotku jako jinde v ceníku.)
- Mimo rozsah: stravování a balíčky v ceníku zůstávají hardcoded.

## Data — seed

Nový command `app:import-massages` (`src/Command/ImportMassagesCommand.php`), idempotentní
(skip dle názvu, jako `app:import-rooms`), naplní 11 masáží + jejich ceny z dnešní hardcoded
tabulky:

| # | Název | Ceny (Kč / min) |
|---|---|---|
| 1 | Klasická masáž | 400/30, 800/60 |
| 2 | Zábal z nahřátých obilných zrn | 250/20, 450/40 | (note: „předehřátí před masáží")
| 3 | Horké kameny | 700/30, 1400/60 |
| 4 | Baňkování | 300/20 |
| 5 | Reflexní masáž plosky nohou | 600/30, 1200/60 |
| 6 | Masáže dětí a těhotných žen | 400/30, 800/60 |
| 7 | Sportovní masáž | 500/30, 1000/60 |
| 8 | Akupresurní odblokování krční páteře | 600/30, 1200/60 |
| 9 | Měkké stabilizační techniky | 400/30 |
| 10 | Energetická masáž Reiki | 600/30, 1200/60 |
| 11 | Jógovo-rehabilitační cvičení | 400/30 |

`position` = pořadí v tabulce (1..11).

## Testy

- `tests/Entity/MassageTest.php` — addPrice/removePrice udržují vazbu.
- `tests/Controller/Admin/MassageControllerTest.php` — vytvoření masáže se 2 cenami přes POST,
  ověřit persist; smazání.
- `tests/Controller/PricingControllerTest.php` + `WellnessControllerTest.php` (nový) — masáž
  z DB se vykreslí (název + cena) na obou stránkách.

## Mimo rozsah (YAGNI)

- Drag-drop řazení masáží (stačí číselné `position`).
- Aktivní/skrytá masáž (řeší smazání).
- Fotky/ikony u masáží.
- Dynamizace stravování a balíčků v ceníku.
