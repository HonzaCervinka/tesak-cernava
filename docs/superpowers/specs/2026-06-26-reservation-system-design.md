# Rezervační systém — návrh

Datum: 2026-06-26
Větev: feature/admin-rooms-images (rezervace navazují)

## Cíl

Jednoduchý ruční rezervační systém pro ubytování. Admin vidí kalendář
obsazenosti pokojů (timeline) a ručně zadává/upravuje rezervace.
Žádná automatizace, žádné veřejné zadávání, žádné platby. Důraz na
jednoduché a hezké ovládání i z telefonu.

## Klíčová rozhodnutí

- **Model obsazení:** celý pokoj na termín. Rezervace zabere pokoj na
  rozsah dní; v daný den je pokoj volný, nebo obsazený. Žádné sčítání
  osob po lůžkách.
- **Hlavní pohled:** timeline — řádky = pokoje, sloupce = dny, proužky =
  rezervace.
- **Překryv:** pouze varování (soft), uložení se nezablokuje.
- **Stavy rezervace:** žádné (každá zadaná = platí).
- **Stack:** server-rendered Twig + CSS grid + Stimulus, žádná nová JS
  závislost. Sedí na existující admin (Symfony 8, Doctrine ORM 3,
  Stimulus, Turbo, sortablejs).

## Datový model

### Room (úprava)

Přidat pole:

- `capacity: int` — default `0`. Max hostů. Pouze informativní pro
  varování při `guests > capacity`.

Přidat `OneToMany $reservations` (mappedBy `room`).

### Reservation (nová entita)

| pole | typ | poznámka |
|---|---|---|
| `id` | int | PK |
| `room` | ManyToOne → Room | povinné, `onDelete: RESTRICT` (pokoj s rezervacemi nelze smazat) |
| `guestName` | string(180) | povinné, NotBlank |
| `arrival` | date_immutable | povinné, NotNull |
| `departure` | date_immutable | povinné, NotNull, musí být `> arrival` |
| `guests` | int nullable | počet osob, Positive nebo null |
| `phone` | string(40) nullable | |
| `email` | string(180) nullable | Email pokud vyplněno |
| `note` | text nullable | volný text |
| `createdAt` | datetime_immutable | PrePersist, jako u Room |
| `updatedAt` | datetime_immutable | PreUpdate, jako u Room |

### Logika překryvu (half-open intervaly)

Dvě rezervace na stejném pokoji kolidují, právě když:

```
a.arrival < b.departure  AND  b.arrival < a.departure
```

Odjezdový den je volný → back-to-back rezervace (odjezd jednoho =
příjezd druhého) **nekolidují**. Tato logika je čistá funkce, testuje
se bez DB (metoda `overlaps()` na entitě nebo helper).

## Routy a controller

`App\Controller\Admin\ReservationController`, prefix
`/admin/reservations`, `#[IsGranted('ROLE_ADMIN')]`. Do
`templates/admin/base.html.twig` přidat odkaz „Rezervace" v sidebaru
(aktivní stav `route starts with 'app_admin_reservations'`).

| route | metoda | účel |
|---|---|---|
| `app_admin_reservations_index` | GET | timeline; query `?from=YYYY-MM-DD` |
| `app_admin_reservations_new` | GET/POST | nová; query `?room=&arrival=` předvyplní |
| `app_admin_reservations_edit` | GET/POST | úprava `{id}` |
| `app_admin_reservations_delete` | POST | smazat `{id}`, CSRF token |

### Index

- `from` default = dnešek zarovnaný na pondělí. Okno default = 4 týdny
  (28 dní). Navigace prev/dnes/další mění `?from`.
- Načte všechny pokoje (`RoomRepository::findAllOrdered()`) a rezervace
  v zobrazeném okně (`ReservationRepository::findInRange(from, to)`).
- Controller (nebo lehký view-model) spočítá pro každou rezervaci
  offset dne od `from` a délku ve dnech (clamp na okno) → předá šabloně.

### Soft validace v controlleru (new i edit, po isValid)

- Překryv: `findOverlapping(room, arrival, departure, exceptId)`. Při
  nálezu `addFlash('warning', 'Překrývá se s: Novák 3.–5. 6.')` ale
  uloží se.
- `guests > room.capacity` (a capacity > 0) → `addFlash('warning', …)`,
  uloží se.

## Formulář — ReservationType

Pole: room (`EntityType`, label = název pokoje), guestName, arrival,
departure (`DateType`, widget single_text → nativní mobilní picker),
guests (`IntegerType`), phone, email, note (`TextareaType`).

Validace (constraints na entitě):

- guestName NotBlank; arrival/departure NotNull.
- `departure > arrival` — Expression/Callback constraint.
- email Email (jen pokud vyplněno); guests Positive nebo null.
- Překryv a `guests > capacity` se **nevaliduje** (neblokuje) → pouze
  flash warning v controlleru.

## Timeline UI

### Layout (CSS grid, server-rendered Twig)

- Sticky levý sloupec = názvy pokojů. Sticky horní řádek = dny (datum +
  zkratka dne; víkend a dnešek jemně zvýrazněné pozadím).
- Tělo = grid `pokoje × dny`. Rezervace = proužek `grid-column: span N`
  na řádku svého pokoje, offset na arrival.
- Rezervace přesahující okno: zaoblení jen na jedné straně (náznak
  „pokračuje").
- Barvy: proužky v zelené paletě adminu (`#2f5d45`).

### Interakce — Stimulus `reservation_timeline_controller`

- Tap na prázdnou buňku → navigace `new?room=X&arrival=Y`.
- Tap na proužek → `edit/{id}`.
- Tlačítka prev / dnes / další (Turbo navigace, mění `?from`).
- Žádný hover-only UX.

### Mobil

- Tělo timeline v `overflow-x: auto`; levý sloupec pokojů sticky.
- Šířka dne fixní (~48px), touch terče min. 44px na výšku.
- Navigační hlavička sticky nahoře.

### CSS

Nový flash typ `warning` (žlutá) do admin `base.html.twig` stylů
(`.flash--warning`). Timeline styly inline v admin base nebo v dílčí
šabloně dle existující konvence.

## Repository

`ReservationRepository`:

- `findInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array`
  — rezervace zasahující do okna (`arrival < to AND departure > from`).
- `findOverlapping(Room $room, $arrival, $departure, ?int $exceptId): array`
- `findAllForRoom(Room $room): array` (pomocné, dle potřeby).

## Testy (PHPUnit)

Dle existujících `tests/Controller/Admin/*` a `tests/Entity/*`.

- `tests/Entity/ReservationTest.php` — čistý unit překryvové logiky
  (`overlaps()`): překryv, back-to-back nekoliduje, obsažení, mimo.
- `tests/Controller/Admin/ReservationControllerTest.php`:
  - index 200 + guard ROLE_ADMIN (anonym → redirect/403).
  - vytvoření rezervace (POST) → persist + redirect.
  - edit existující.
  - delete s platným CSRF → smazáno.
  - překryv → flash `warning`, ale rezervace uložena.
  - `departure <= arrival` → validační chyba, neuloženo.

## Migrace

Přes `doctrine:migrations:diff` v Dockeru
(`docker compose exec php bin/console doctrine:migrations:diff`).
Nikdy ručně. Pak `migrations:migrate`.

## Mimo rozsah (YAGNI)

- Veřejné/online rezervace, e-maily hostům, platby.
- Stavy rezervace (předběžná/potvrzená).
- Rezervace po lůžkách / částečné obsazení.
- Tvrdé blokování překryvu.
- Reporty, export.
```
