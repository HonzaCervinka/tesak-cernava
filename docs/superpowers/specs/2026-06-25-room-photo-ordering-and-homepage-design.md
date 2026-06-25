# Drag-drop pořadí fotek pokoje + homepage pokoje z DB

Datum: 2026-06-25
Branch: feature/admin-rooms-images

## Cíl

1. **Admin — pořadí fotek pokoje pouze drag-and-dropem.** První fotka v pořadí je
   automaticky titulní (cover). V administraci je vidět, která je titulní.
2. **Homepage — pokoje z DB.** Každý pokoj má v adminu checkbox „Zobrazit na hlavní
   stránce". Homepage vykreslí všechny zaškrtnuté pokoje (dle `position`) a u každého
   jeho titulní fotku. Celá karta je data-driven (název, features, cena, foto).
3. (Hotovo mimo tento spec) Admin uživatel `admin@admin.cz` / `admin` / `ROLE_ADMIN`.

## Kontext

- `Image` entita už má pole `position` (int) a `isMain` (bool).
- `Room::getMainImage()` vrací fotku s `isMain`, jinak `images.first()`. Kolekce
  `images` je `OrderBy(['position' => 'ASC'])` → `first()` = nejnižší position.
- Admin galerie ([templates/admin/room/edit.html.twig](../../../templates/admin/room/edit.html.twig))
  má dnes tlačítko „Hlavní" (`app_admin_rooms_images_main`) — to se ruší. Pořadí dnes
  nelze měnit.
- Accommodation page ([templates/accommodation/index.html.twig](../../../templates/accommodation/index.html.twig))
  už vykresluje pokoje z DB přes `room.mainImage`, `room.features`, `room.priceLabel`.
  Homepage tento vzor převezme.
- Homepage ([templates/home/index.html.twig](../../../templates/home/index.html.twig))
  má 3 hardcoded karty `<article class="room-card">` s vlastními CSS třídami
  (`room-card__image`, `room-card__title`, `room-card__price`).
- Stimulus + AssetMapper importmap. Admin stránky už Stimulus používají (UX Dropzone).
  Nové controllery v `assets/controllers/` se registrují automaticky.

## Návrh

### A) Pořadí fotek drag-and-dropem (admin)

**Frontend**
- Přidat `sortablejs` do `importmap.php` (`bin/console importmap:require sortablejs`).
- Nový Stimulus controller `assets/controllers/gallery_sort_controller.js`:
  - Inicializuje SortableJS nad gridem fotek.
  - Na `onEnd` posbírá pořadí `data-image-id` z položek a pošle `fetch` POST na
    `app_admin_rooms_images_reorder` s `{ order: [id, …], _token }` jako JSON.
  - Po úspěchu přesune badge „★ titulní" na první kartu (bez reloadu); při chybě
    udělá `location.reload()` jako fallback.
- `edit.html.twig`: grid fotek dostane `stimulus_controller('gallery-sort')` + cílový
  atribut, každá karta `data-image-id` a target. Tlačítko „Hlavní" + jeho `<form>` se
  odstraní. Badge „★ titulní" se zobrazí na položce s `image.isMain` (= první).

**Backend** — `src/Controller/Admin/RoomController.php`
- Nová akce `reorder(Request, Room, EntityManagerInterface)`:
  route `POST /admin/rooms/{id}/images/reorder`, name `app_admin_rooms_images_reorder`.
  - CSRF token `reorder<roomId>`.
  - Načte `order` (pole intů) z JSON body.
  - Projde fotky pokoje; každé nastaví `position` dle indexu v `order`. Fotky, jejichž
    ID v `order` chybí, jdou na konec (zachovat relativní pořadí).
  - `isMain = true` pro fotku na pozici 0, `false` pro ostatní.
  - `flush()`, vrátí `JsonResponse(['ok' => true, 'mainId' => <id>])`.
- Akce `setMainImage()` + route `app_admin_rooms_images_main` se **odstraní**.
- `deleteImage()`: po `removeImage` přečíslovat zbývající fotky `position` 0..n−1 (dle
  stávajícího pořadí) a první označit `isMain=true`, ostatní `false`. Zamezí dírám
  v pozicích a osiřelému `isMain`.
- `uploadImages()`: beze změny (přidává na konec, position = count).

### B) Pokoje na homepage z DB

**Schema** — `src/Entity/Room.php`
- Nové pole `#[ORM\Column] private bool $showOnHomepage = false;` + `isShowOnHomepage()`
  / `setShowOnHomepage(bool)`.
- Migrace přes `bin/console doctrine:migrations:diff` (NE ručně). Sloupec
  `show_on_homepage TINYINT(1) NOT NULL DEFAULT 0`.

**Data** — po migraci nastavit předvybrané pokoje (dnešní hardcoded homepage):
`UPDATE room SET show_on_homepage = 1 WHERE id IN (2, 8, 9);`
(2 = family-large „Rodinný pokoj", 9 = double-shared „Dvoulůžkový pokoj",
8 = apartment-2bedroom „Apartmán 2 ložnice".)

**Form** — `src/Form/RoomType.php`
- Přidat `showOnHomepage` jako `CheckboxType` (`required: false`, label „Zobrazit na
  hlavní stránce").

**Repository** — `src/Repository/RoomRepository.php`
- `findForHomepage(): array` → `WHERE show_on_homepage = true ORDER BY position ASC`.

**Controller** — `src/Controller/HomeController.php`
- Injectnout `RoomRepository`, předat `rooms => $rooms->findForHomepage()` do template.

**Template** — `templates/home/index.html.twig`
- 3 hardcoded `<article>` nahradit `{% for room in rooms %}` smyčkou se zachováním
  homepage CSS tříd:
  - foto: `room.mainImage` → `<img src="{{ main.thumbnail }}">`, fallback alt `room.name`.
  - `room-card__title` = `room.name`.
  - `room-card__features` smyčka přes `room.features` (s checkmark SVG).
  - `room-card__pricing` = `room.priceLabel` + `room.priceUnit` (jen pokud `priceLabel`).
  - CTA `app_contact`, beze změny.
  - Pole `label` (kategorie) a `discount` se vypouští — v DB nejsou, konzistentní
    s accommodation page.

## Datový model

Žádná změna `Image`. `Room` + 1 bool sloupec přes `migrations:diff`. Cover fotka se
nadále odvozuje z `isMain` / první position — beze změny `getMainImage()`.

## Testy

- `tests/Controller/Admin/RoomImageTest.php`: nový test reorder endpointu — nahrát ≥2
  fotky, POST nové pořadí, ověřit `position` a že `isMain` je na nové první fotce.
  Ověřit, že odstraněná route `app_admin_rooms_images_main` už neexistuje (nebo smazat
  její test, pokud existuje).
- `tests/Controller/HomeControllerTest.php`: pokoj s `showOnHomepage=true` se na
  homepage vykreslí (název v response), pokoj s `false` ne.
- Případně `tests/Entity/RoomTest.php`: getter/setter `showOnHomepage`.

## Mimo rozsah (YAGNI)

- Řazení pořadí jinak než drag-dropem (žádná number-input pole).
- Pole `label`/`discount` v DB (homepage je nebude mít).
- Změna pořadí pokojů na homepage z UI (řídí se `Room.position`, spravováno jinde).
