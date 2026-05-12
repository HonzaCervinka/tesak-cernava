# BRIEF PRO MODERNIZACI WEBU – PENZION TESÁK-ČERŇAVA

> **Účel dokumentu:** Tento soubor obsahuje kompletní analýzu stávajícího webu `tesak-cernava.cz` a slouží jako podklad pro AI agenta, který bude tvořit modernizovanou verzi stránek. Texty je možné použít 1:1, ale je nutné opravit chyby uvedené v sekci "Co opravit".

**Zdrojový web:** https://www.tesak-cernava.cz/  
**Datum analýzy:** 7. května 2026  
**Platforma:** Webnode 2 (drag & drop builder)  
**Rok zveřejnění obsahu:** 2022 (s aktualizacemi)

---

## 1. ZÁKLADNÍ INFORMACE O SUBJEKTU

- **Název zařízení:** Penzion Tesák-Čerňava (též "Chata Tesák-Čerňava")
- **Provozovatel:** Pavel Dobiáš
- **Adresa:** Chvalčov 253, 768 72 Chvalčov (okres Kroměříž, Zlínský kraj)
- **IČO:** 44348649
- **DIČ:** 7306026541
- **Telefon:** +420 775 727 306
- **Email:** tesakcernava@seznam.cz
- **Číslo účtu:** 220847380/2010
- **IBAN:** CZ06 2010 0000 0022 0284 7380
- **BIC/SWIFT:** FIOBCZPPXXX
- **Skutečná GPS (z mapy):** 49.3670216, 17.775705
- **Nadmořská výška:** 690 m n. m.
- **Kapacita:** 51 lůžek + až 8 přistýlek (na webu uvedeno též "52 lůžek včetně přistýlek")

---

## 2. POZICOVÁNÍ A CÍLOVÉ SKUPINY

### Hlavní zaměření
Horský penzion v přírodní rezervaci Tesák (Hostýnské vrchy) vhodný pro skupinové i rodinné pobyty.

### Cílové skupiny (v pořadí dle důrazu na webu)
1. **Školy v přírodě** – nejvýraznější zákazník (samostatné cenové balíčky)
2. **Dětské tábory a soustředění** (sportovní, pěvecká, lyžařská)
3. **Rodinné pobyty** s dětmi
4. **Oslavy a setkání** (rodinné, narozeniny)
5. **Firemní akce** a teambuildingy
6. **Sportovní kluby** – cyklisté, lyžaři
7. **Turisté** – pěší turistika v Hostýnských vrších
8. **Pejskaři** (psi povoleni za poplatek)

### Doplňková služba
**Wellness a masáže** – provozované zkušeným terapeutem s praxí z Mariánských, Františkových Lázní a Karlových Varů. Lze využít při ubytování i samostatně.

---

## 3. UNIKÁTNÍ PRODEJNÍ ARGUMENTY (USP)

- **Lokalita:** Přírodní rezervace v parku Hostýnské vrchy (chráněná ptačí oblast, evropsky významná lokalita)
- **Křižovatka turistických tras** Hostýnských vrchů
- **Bukové lesy** s léčivými účinky
- **Blízkost rozhleden:** Kelčský Javorník (1,5 h), Maruška (2 h), Sochová (1 h), Svatý Hostýn (4 h)
- **Domácí strava** z čerstvých surovin (důraz na nepoužívání průmyslových náhražek)
- **Velký krb** s pohodlnou sedačkou ve společenské místnosti
- **Zahrada** s dětským hřištěm, trampolínou, posezením u ohniště
- **Voliéra se zvířaty** (králík, morčata, andulky, občas kocour Garfield)
- **Zrekonstruováno v roce 2012** (retro objekt s moderními úpravami)
- **Bohaté zkušenosti** s pobyty dětí různých věkových kategorií

---

## 4. STRUKTURA WEBU (STÁVAJÍCÍ)

```
├── Úvod (homepage)        /
├── Tesák (o ubytování)    /ubytovani/
├── Masáže (wellness)      /wellness/
├── Galerie                /galerie/
├── Ceník                  /cenik/
└── Kontakt                /kontakt/
```

### Obsah jednotlivých stránek

#### 4.1 Úvod (homepage)
- Hero text: "Naše zařízení je ideální pro ŠKOLY V PŘÍRODĚ, rodinné pobyty, soustředění, oslavy, setkání, firemní akce, tábory, cyklisty, sportovní kluby"
- Popis lokality Tesák (přírodní rezervace, Hostýnské vrchy)
- Křižovatka turistických tras
- **Detailní výpis pokojů a cen** (DUPLICITA s ceníkem – ceny se liší!)
- Sekce "Co u nás najdete" (4 dlaždice s odkazy)
- Sekce "Naše kuchyně" (3 fotky kuchyně/jídelny)
- Sekce "Útulné pokoje a skvělé služby" (5 fotek)
- Sekce "Prozkoumejte okolí"

#### 4.2 Tesák / Ubytování
- Hero obrázek
- Sekce "U nás jako doma"
- Popis chaty a rekonstrukce (2012)
- Popis lokality
- Fotogalerie (loaded přes JS)

#### 4.3 Masáže / Wellness
- Hero obrázek
- Popis terapeuta (zkušenosti z lázní)
- Ceník 10 typů masáží/terapií

#### 4.4 Galerie
- Pouze nadpis "Naše okolí a jak to vypadá u nás"
- Fotogalerie načítaná přes JavaScript (nelze stáhnout přes web fetch – nutný ruční export přes Webnode admin nebo screenshot)

#### 4.5 Ceník
- **Druhý ceník**, jiný než ten na úvodu (viz "Co opravit")
- Stravování pro skupiny dětí
- Doplňkové info (check-in/out, psi)

#### 4.6 Kontakt
- Telefon, email, adresa
- Vložená Google mapa
- Fakturační údaje (IČO, DIČ, bankovní spojení)

---

## 5. KOMPLETNÍ TEXTY KE PŘEVZETÍ DO NOVÉHO WEBU

### 5.1 Hlavní slogany / hero texty

> **Naše zařízení je ideální pro ŠKOLY V PŘÍRODĚ, rodinné pobyty, soustředění, oslavy, setkání, firemní akce, tábory, cyklisty, sportovní kluby**

> **U nás jako doma**

> **Těšíme se na Vaši návštěvu**

> **Vítejte na Tesáku**

### 5.2 O penzionu a lokalitě

> Tesák je přírodní rezervace v lokalitě Rajnochovice v okrese Kroměříž. Rezervace leží uvnitř přírodního parku Hostýnské vrchy, který zároveň podléhá ochraně jako ptačí oblast a evropsky významná lokalita.

> Na kopci Tesák na Vás dýchne klid, příroda, bukové lesy a výhledy do kopcovité krajiny. Jsme křižovatkou a srdcem hostýnských turisticky značených cest. Můžete se hodiny anebo celé dny procházet a nechat na sebe působit blahodárné účinky lesa. Od nás to máte například na rozhlednu Kelčský Javorník 1,5 h, rozhlednu Maruška 2 h, Sochovou 1 h a na Svatý Hostýn 4 h.

> Chata Tesák-Čerňava se nachází na kopci Tesák nad městem Chvalčov nedaleko Bystřice pod Hostýnem.

> Tento Retro rekreační objekt kompletně zrekonstruovaný v roce 2012, který na vás dýchne klidem s možností zasnít se při pohledu do velkého krbu z pohodlné sedačky.

> Chata stojí na kopci Tesák v nadmořské výšce 690 m n. m. Obklopená z jedné strany parkovištěm a z druhé strany oplocenou prostornou zahradou s dětským hřištěm, trampolínou, posezením u ohniště a voliérou se zvířátky (králík, morčata, andulky). V bezprostředním okolí navazují bukové lesy, kde se můžete hodiny anebo celé dny procházet a nechat na sebe působit blahodárné účinky lesa.

### 5.3 Co u nás najdete (4 hlavní benefity)

**Ubytování**  
Celková kapacita našeho zařízení je 52 lůžek včetně přistýlek v útulných pokojích s koupelnami se sprchovými kouty a toaletou.

**Výborná kuchyně**  
Domácí strava z čerstvých potravin, ne z náhražkových průmyslových produktů. Můžete si ale vařit i sami.

**Volně přístupná voliéra**  
Pro milovníky zvířat máme připravenou volně přístupnou voliéru s andulkami, králíčkem a morčaty, občas přijde i Garfield.

**Krásná příroda, chráněné oblasti**  
Jsme křižovatkou a srdcem hostýnských turisticky značených cest.

### 5.4 Sekce kuchyně

> Naše kuchyně je pro Vás k dispozici, může se stát i vaší.
> Náš personál má bohaté zkušenosti s pobyty dětí různých věkových kategorií a jejich stravováním.
> Domácí strava z čerstvých potravin, ne z náhražkových průmyslových produktů.

### 5.5 Sekce okolí

> **Prozkoumejte okolí**
> Křižovatka turistických tras v blízkosti penzionu
> Krásné výhledy – rozhledny Kelčský Javorník a Maruška

### 5.6 Wellness texty

> **Wellness – Péče o Vaše tělo i ducha**
> V rámci Vašeho ubytování u nás, ale i mimo něj pro Vás poskytujeme rehabilitace ve formě masáží, ale i jiných wellness a léčebných technik.
> Zkušený terapeut, masér, učitel jógy s dlouholetou praxí z Mariánských Lázní, Františkových Lázní a Karlových Varů Vám nabízí své služby.

---

## 6. CENÍK – KOMPLETNÍ DATA

> ⚠️ **Pozor:** Na úvodní stránce a na stránce /cenik/ jsou rozdílné ceny i typy pokojů. Klient si musí ujasnit, který ceník je aktuální! Níže uvádím obojí.

### 6.1 Ceník z úvodní stránky (zřejmě AKTUÁLNÍ – jsou tu vyšší ceny)

| Typ pokoje | Vybavení | Cena 2+ nocí | Cena 1 noc |
|---|---|---|---|
| Obsazenost 1 osobou | povlečení, koupelna, ručníky, WC, balkon | 650 Kč/noc | 950 Kč |
| Velký rodinný pokoj s vlastní koupelnou (č. 11, 21) | manželská postel, palanda, povlečení, koupelna, ručníky, WC, balkon, společná kuchyňka | 2 100 Kč/noc | 2 400 Kč |
| Dvoulůžkový pokoj se sdílenou koupelnou (č. 12, 15, 22, 24, 25) | povlečení, koupelna, ručníky, WC, balkon, společná kuchyňka | 800 Kč/noc | 1 100 Kč |
| Pokoj s manželskou postelí, vlastní koupelna (č. 14) | TV, vlastní koupelna, ručníky, WC, balkon, společná kuchyňka | 1 100 Kč/noc | 1 400 Kč |
| Palanda + 2 lůžka, vlastní koupelna (č. 16) | vlastní koupelna, ručníky, WC, balkon, společná kuchyňka | 1 890 Kč/noc | 2 190 Kč |
| Rodinný dvoupokoj, vlastní koupelna (č. 16+17) | 1× manž. postel, 1× palanda, 2× oddělené postele, TV, vlastní koupelna, ručníky, WC, balkon | 2 490 Kč/noc | 2 690 Kč |
| Apartmán v přízemí pro až 9 osob | 3× palanda, 1× jednolůžko, rozkládací gauč, kuch. kout, mikrovlnka, vařič, koupelna s vanou, herna NERF, společenské hry | 590 Kč/noc | 780 Kč |
| Apartmán 2 ložnice, až 11 lidí | 2× manž. postel, 1× palanda, 2× rozkl. gauč, TV, kuch. kout, koupelna, balkon | 3 990 Kč/noc | 7 980 Kč |

**Pes do 4 kg:** 150 Kč/noc

### 6.2 Ceník ze stránky /cenik/ (zřejmě STARŠÍ verze – jiné typy pokojů)

| Typ pokoje | Vybavení | Cena 2+ nocí | Cena 1 noc |
|---|---|---|---|
| 2× jednolůžkový pokoj | kuch. kout, vařič, konvice, lednice, koupelna, ručníky, WC | 650 Kč/noc | 850 Kč |
| 4× dvoulůžkový pokoj, samostatné postele | TV, koupelna, ručníky, WC, balkon | 990 Kč/noc | 1 190 Kč |
| 3× dvoulůžkový pokoj, manželská postel | TV, koupelna, ručníky, WC, balkon | 990 Kč/noc | 1 190 Kč |
| 1× třílůžkový (3 samostatná lůžka) | koupelna, ručníky, WC, balkon | 1 400 Kč/noc | 1 650 Kč |
| 2× čtyřlůžkový rodinný | 1× manž. postel, 1× palanda, TV, koupelna, ručníky, WC, balkon | 1 790 Kč/noc | 2 090 Kč |
| Apartmán v přízemí pro 4–9 os. | 3× palanda, 1× jednolůžko, rozkl. gauč, TV, kuch. kout, mikrovlnka, koupelna s vanou, herna | 450 Kč/noc/os. | 650 Kč/noc/os. |
| Apartmán 7 lůžek + 2× rozkl. gauč | 2× manž. postel, 2× palanda (?), TV, kuch. kout, koupelna, velký balkon | 3 100 Kč/noc | 3 350 Kč |

**Pes:** 190 Kč/den  
**Postýlka pro děti do 2 let:** zdarma

### 6.3 Stravování

- Snídaně po dohodě: dítě do 12 let **130 Kč**, dospělý **160 Kč**
- **Skupiny dětí (školy v přírodě, lyžařské/pěvecké kurzy, tábory):**
  - 3× denně strava + 2× denně svačina + pitný režim po celý den
  - **748 Kč / dítě / den** (na 10 dětí dospělý zdarma při plném obsazení penzionu)
  - **Pondělí–pátek 2 990 Kč / dítě**

### 6.4 Provozní podmínky

- Check-in: od 14:00 (nebo dle telefonické dohody)
- Check-out: do 10:00
- Změna cen vyhrazena
- **Důležité upozornění:** Vezměte si s sebou přezůvky.

---

## 7. CENÍK MASÁŽÍ A WELLNESS

| Služba | 30 min | 60 min |
|---|---|---|
| Klasická masáž | 400 Kč | 800 Kč |
| Zábal z nahřátých obilných zrn (předehřátí před masáží) | 250 Kč (20 min) | 450 Kč (40 min) |
| Horké kameny | 700 Kč | 1 400 Kč |
| Baňkování (20 min) | 300 Kč | – |
| Reflexní masáž plosky nohou | 600 Kč | 1 200 Kč |
| Masáže dětí a těhotných žen | 400 Kč | 800 Kč |
| Sportovní masáž | 500 Kč | 1 000 Kč |
| Akupresurní odblokování krční páteře | 600 Kč | 1 200 Kč |
| Měkké stabilizační techniky | 400 Kč | – |
| Energetická masáž Reiki | 600 Kč | 1 200 Kč |
| Jógovo-rehabilitační cvičení | 400 Kč | – |

---

## 8. POPIS STÁVAJÍCÍHO DESIGNU A STYLU

### Vizuální charakter
- **Šablona Webnode 2** – obecná, drag & drop
- **Layout:** klasický shora dolů s plnoplošnými hero obrázky a obsahovými bloky
- **Typografie:** běžný sans-serif (pravděpodobně Webnode default), velké množství tučného textu, mnoho `<h2>` na věcech, které nadpisy nejsou (například ceny pokojů jsou stylované jako nadpisy)
- **Barevnost:** převážně neutrální, dominují fotografické hero plochy s přírodou (zelená, modrá obloha) a interiéry (teplé tóny dřeva)
- **Logo:** Textové "Tesák-Čerňava" + telefonní číslo v hlavičce
- **Menu:** Standardní horizontální menu (Úvod, Tesák, Masáže, Galerie, Ceník, Kontakt)

### Tone of voice
- **Hřejivý, rodinný** – často oslovují "Vás" s velkým V
- **Důraz na klid a přírodu** – ekologický a wellness rozměr
- **Nostalgická poznámka** – "U nás jako doma", "Retro objekt"
- **Praktický** – velký důraz na konkrétní informace (kapacity, vybavení, ceny)
- **Místy nestrukturovaný** – text se občas opakuje, různé informace jsou na různých místech

### Fotografický materiál
- Smíšená kvalita: některé fotky vypadají profesionálně (HDR snímky interiérů), jiné jsou z mobilu (hero a okolí)
- **Doporučení:** pro modernizovaný web nafotit jednotnou sadu nových fotografií (interiéry, pokoje, exteriér, jídlo, aktivity dětí)

---

## 9. ⚠️ CO ZNEKVALITŇUJE STÁVAJÍCÍ WEB (CO OPRAVIT V NOVÉ VERZI)

### Faktické chyby
1. **GPS na stránce /kontakt/ je špatná!** Uvedeno: `50.083946, 14.424548` – to je **Praha**. Skutečná GPS dle vložené mapy je `49.3670216, 17.775705`. **Nutno opravit!**
2. **Dva různé ceníky** s různými typy pokojů a různými cenami (úvodní stránka vs. /cenik/). Nutno sjednotit jednu aktuální verzi.
3. **Kapacita uváděna nejednotně:** "52 lůžek", "51 lůžek + 6 přistýlek", "51 lůžek + až 8 přistýlek".

### Pravopisné a typografické chyby
- "ciklisty" → správně **cyklisty**
- "zrn obylných" → správně **obilných**
- "vařit i samy" → správně **sami**
- "morčaty" je v pořádku, ale na různých místech jednou "morče" jednou "morčaty"
- "tasák-čerňava" v meta tagu og:site_name (pravděpodobně překlep) → mělo by být **Tesák-Čerňava**
- "po milovníky zvířat" → správně **pro milovníky zvířat**
- Nadbytečné mezery, dvojité tečky, nezavřené uvozovky
- "Garfíld" → správně **Garfield**
- Dlouhé pomlčky vs. spojovníky používány nekonzistentně

### Strukturní problémy
- Nadpisy `H2` použity na věcech, které nadpisy nejsou (ceny, popisky)
- Žádná hierarchie info – všechno má stejnou váhu
- Žádné CTA tlačítko (rezervace, dotaz)
- Žádný kontaktní/poptávkový formulář
- Galerie načítaná dynamicky JS, není dostupná pro vyhledávače (špatné SEO)
- Žádný online rezervační systém ani kalendář obsazenosti
- Chybí strukturovaný popis aktivit v okolí (pouze letmá zmínka)
- Žádné reference / recenze / hodnocení od hostů
- Chybí FAQ sekce
- Chybí GDPR / informace o zpracování osobních údajů (kromě cookies banneru)
- Chybí social media odkazy

### UX problémy
- Web neobsahuje mobilní vylepšení nad rámec defaultní Webnode šablony
- Telefon v hlavičce je tučně vedle názvu, ale není to klikací odkaz `tel:`
- Email kontakt není klikací mailto
- Mapa na /kontakt/ je vložena jako iframe Google Maps (legacy způsob)

### SEO
- Meta description duplikované na více stránkách
- Klíčová slova v meta tagu rozvláčné a nestrukturované
- Chybí structured data (LocalBusiness, LodgingBusiness schema.org)
- Chybí alt texty u většiny obrázků

---

## 10. DOPORUČENÁ STRUKTURA NOVÉHO WEBU

```
HOMEPAGE
├── Hero sekce (foto + slogan + 2× CTA: Rezervovat / Zjistit více)
├── 3–4 hlavní benefity (ikony + krátký text)
├── Pro koho jsme (4 dlaždice: školy v přírodě, rodiny, firmy, sportovci)
├── O penzionu (krátký intro + foto)
├── Náhled pokojů (3–4 typy s "Více →")
├── Aktivity v okolí (mapa + tipy)
├── Recenze hostů (Google reviews / vlastní)
├── CTA: Rezervovat pobyt
└── Footer (kontakt, mapa, social, GDPR)

UBYTOVÁNÍ
├── Úvodní text (filozofie, retro objekt 2012, kapacita)
├── Karty pokojů (foto + popis + cena + tlačítko Rezervovat)
├── Vybavení (kuchyně, společenská místnost, krb, voliéra)
└── CTA Rezervace

PRO SKUPINY (NOVÁ STRÁNKA)
├── Školy v přírodě (program, balíček, cena)
├── Tábory a soustředění
├── Firemní akce / teambuilding
├── Oslavy a setkání
└── Poptávkový formulář pro skupiny

WELLNESS A MASÁŽE
├── O terapeutovi (lázeňské zkušenosti)
├── Ceník služeb
└── Rezervační formulář / kontakt

OKOLÍ A AKTIVITY (rozšířená sekce)
├── Pěší turistika (rozhledny, trasy)
├── Cykloturistika
├── Lyžování
├── Pro děti (dětské hřiště, voliéra, ohniště)
└── Mapa okolí

CENÍK (sjednocený)
├── Ubytování (tabulka)
├── Stravování
├── Skupinové balíčky
├── Wellness
└── Doplňkové služby (pes, postýlka, parkování)

GALERIE
├── Filtry (Pokoje / Společné prostory / Okolí / Aktivity)
└── Lightbox

KONTAKT
├── Telefon (klikací) + email (klikací)
├── Adresa + GPS (správná!)
├── Mapa (interaktivní)
├── Fakturační údaje
├── Poptávkový formulář
└── Otevírací doba / kontaktní hodiny
```

---

## 11. DOPORUČENÉ TECHNICKÉ PARAMETRY NOVÉHO WEBU

- **Mobile-first design** (responsivní)
- **Online rezervační systém** (např. Previo, Bookassist, Hotelina nebo vlastní formulář s kalendářem)
- **Structured data** (schema.org LodgingBusiness, LocalBusiness, Restaurant)
- **Open Graph + Twitter Cards** pro social sharing
- **GDPR compliance** (cookie lišta + zásady)
- **Sitemap.xml + robots.txt**
- **Google Tag Manager** (klient ho už má, GTM-542MMSL – lze přenést)
- **Optimalizace obrázků** (WebP, lazy loading)
- **Vícejazyčnost?** – stávající web pouze CZ; klient potvrdil, že není potřeba dalších jazyků
- **Integrace Google Reviews** (zobrazovat aktuální hodnocení)
- **Klikací telefon a email**
- **Google Maps integrace** s vlastním pinem (správné GPS!)

---

## 12. VIZUÁLNÍ NÁVRH (DOPORUČENÍ)

### Stylové směřování
Doporučuji **moderní rustikální / boutique horský penzion** styl:
- Velkorysé fotografie přírody a interiérů
- Teplá, přírodní paleta (smaragdová zeleň, dřevěné tóny, krémově bílá, antracit)
- Serif font na nadpisech (např. *Fraunces*, *Cormorant*) pro nostalgický nádech
- Sans-serif body text (např. *Inter*, *Manrope*)
- Velké volné plochy (whitespace), méně textu více vizuálu
- Mikroanimace (hover efekty, parallax na hero fotce)
- Jemné zaoblení rohů, žádné ostré korporátní hrany

### Inspirace ke stylové referenci
- moderní české horské penziony jako **Hotel Sklárna Harrachov**, **Modrava**, **Penzion Pod Pradědem** – kombinace tradice a modernity
- Boutique penziony v Beskydech a Hrubém Jeseníku

---

## 13. PODKLADY PRO AGENTA – CO MÁ K DISPOZICI

V přiložených souborech najdete:
1. **`01_BRIEF_PRO_AI_AGENTA.md`** – tento dokument
2. **`02_seznam_obrazku.md`** – kompletní seznam URL všech obrázků na webu
3. **`03_stahnout_obrazky.sh`** – Bash skript pro hromadné stažení všech obrázků
4. **`04_texty_po_stranach.md`** – syrové texty z webu rozdělené dle stránek (pro snadné kopírování)

### Doporučený postup agenta
1. Stáhnout a projít obrázky (skript v souboru 03)
2. S klientem ujasnit: aktuální ceník (úvodní vs. ceník), kapacitu, GPS opravit
3. Vyžádat nové fotografie (jednotná sada – pokoje, exteriér, jídlo, aktivity)
4. Vyžádat 2–3 reference / hodnocení od hostů (text + jméno)
5. Sestavit wireframy dle struktury výše
6. Implementace s důrazem na rezervační flow

---

**Konec briefu.**
