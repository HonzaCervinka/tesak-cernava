# Stravování — admin CRUD + dynamický ceník

Datum: 2026-06-26
Branch: feature/admin-rooms-images

## Cíl

Spravovat ceny stravování v adminu a vykreslovat sekci „Stravování" v ceníku (`/cenik`)
dynamicky z DB. Dnes hardcoded.

## Kontext

- Sekce Stravování v [pricing](../../../templates/pricing/index.html.twig) má dva tvary:
  - **Individuální hosté** — tabulka (Typ → Cena): „Snídaně — dítě do 12 let 130 Kč",
    „Snídaně — dospělý 160 Kč" + statický popisek „Snídaně po dohodě. Možnost vlastního
    vaření v kuchyňce.".
  - **Skupiny dětí** — zvýrazněná karta: velká cena „748 Kč /dítě/den", řádek
    „Pondělí–pátek: 2 990 Kč / dítě", 4 odrážky (3× teplé jídlo, 2× svačina, pitný režim,
    dospělý zdarma).
- Vzor: `Massage` (entita + admin CRUD), `Room.features` (JSON list<string>),
  `form-collection` Stimulus controller (add/remove řádků).
- Admin menu: [admin/base.html.twig](../../../templates/admin/base.html.twig) — odkazy
  Pokoje / Rezervace / Masáže; přidáme Stravování.

## Datový model — jedna flexibilní entita `Meal`

- `id` int
- `name` string(180)
- `price` int
- `priceUnit` ?string(60) — např. „/dítě/den"; null → renderuje se jako „Kč"
- `note` ?string(255) — např. „Pondělí–pátek: 2 990 Kč / dítě"
- `highlighted` bool — false = řádek tabulky, true = balíčková karta
- `features` JSON `list<string>` — odrážky karty (prázdné u řádků), jako `Room.features`
- `position` int — řazení

Migrace přes `bin/console doctrine:migrations:diff`.

## Repository

`MealRepository::findAllOrdered(): Meal[]` — `ORDER BY position ASC, id ASC`.

## Admin

- `src/Controller/Admin/MealController.php` — `#[Route('/admin/meals')]`,
  `#[IsGranted('ROLE_ADMIN')]`, akce index / new / edit / delete (vzor MassageController,
  CSRF `delete{id}`).
- `MealType`: `name` (TextType), `price` (IntegerType), `priceUnit` (TextType, required false),
  `note` (TextType, required false), `highlighted` (CheckboxType, required false),
  `position` (IntegerType), `features` (CollectionType entry TextType, allow_add/allow_delete/
  prototype, by_reference false, required false).
- Šablony `admin/meal/{index,new,edit,_form}.html.twig`.
  - `_form` používá `form-collection` Stimulus controller pro dynamické řádky `features`
    (tlačítko „+ přidat" / „× odebrat") — stejně jako masáže u cen.
- Admin menu: přidat odkaz „Stravování" (`app_admin_meals_index`), aktivní dle
  `route starts with 'app_admin_meals'`.

## Veřejná stránka (ceník)

- `PricingController::index` injectne `MealRepository`, předá `meals => findAllOrdered()`.
- Sekce Stravování v šabloně:
  - levý sloupec „Individuální hosté" → tabulka z `meals` kde `not m.highlighted`
    (řádek: `name` → `price` + případně `priceUnit`, jinak „Kč"); pod tabulkou statický
    popisek „Snídaně po dohodě…" zůstává.
  - pravý sloupec „Skupiny dětí" → pro každou `m` kde `m.highlighted` karta: velká cena
    `price` + `priceUnit`, `note` jako success řádek (pokud je), odrážky z `features`.
  - Nadpisy „Individuální hosté" / „Skupiny dětí" zůstanou statické.
- NBSP typografie pro tisíce/jednotku jako jinde v ceníku.

## Data — seed

`app:import-meals` (`src/Command/ImportMealsCommand.php`), idempotentní (skip dle názvu):

| # | name | price | priceUnit | highlighted | note | features |
|---|---|---|---|---|---|---|
| 1 | Snídaně — dítě do 12 let | 130 | — | false | — | — |
| 2 | Snídaně — dospělý | 160 | — | false | — | — |
| 3 | Skupiny dětí | 748 | /dítě/den | true | Pondělí–pátek: 2 990 Kč / dítě | 3× denně teplé jídlo; 2× denně svačina; Pitný režim po celý den; Na 10 dětí 1 dospělý zdarma (při plném obsazení) |

`position` = 1..3.

## Testy

- `tests/Controller/Admin/MealControllerTest.php` — anonymní blokován; vytvoření meal
  (highlighted + features) přes POST, ověřit persist.
- `tests/Controller/PricingControllerTest.php` — přidat ověření, že meal řádek i
  highlighted karta z DB se na `/cenik` vykreslí (název + cena + odrážka).

## Mimo rozsah (YAGNI)

- Vlastní nadpisy sloupců z DB (zůstávají statické).
- Editovatelný popisek „Snídaně po dohodě…" (editorial, statický).
- Stránka stravování mimo ceník (neexistuje).
