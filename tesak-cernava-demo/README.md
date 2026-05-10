# 📦 Modernizace webu Tesák-Čerňava – kompletní podklady

Tento balíček obsahuje vše potřebné pro modernizaci webu **tesak-cernava.cz**.

## 📁 Obsah

| Soubor | Popis | Pro koho |
|---|---|---|
| **`README.md`** | Tento soubor – úvodní orientace | Pro tebe |
| **`01_BRIEF_PRO_AI_AGENTA.md`** | 🎯 **Hlavní dokument** – kompletní analýza webu, struktura, doporučení, opravy | Pro AI agenta i tebe |
| **`02_seznam_obrazku.md`** | Seznam všech obrázků s URL adresami | Pro orientaci |
| **`03_stahnout_obrazky.sh`** | Bash skript pro hromadné stažení obrázků | K spuštění |
| **`04_texty_po_stranach.md`** | Syrové texty z webu pro snadné kopírování | Pro AI agenta |

## 🚀 Jak postupovat

### Krok 1 – Stáhnout obrázky
```bash
chmod +x 03_stahnout_obrazky.sh
./03_stahnout_obrazky.sh
```
Stáhne 23 obrázků do složky `./obrazky/`.

> **Pozn.:** Galerie na `/galerie/` se načítá JavaScriptem a její fotky tu nejsou.
> Pro jejich získání se přihlas do Webnode adminu nebo procházej stránku v prohlížeči.

### Krok 2 – Předat balíček AI agentovi
Hlavní dokument pro něj je **`01_BRIEF_PRO_AI_AGENTA.md`** – obsahuje:
- Kdo penzion provozuje a pro koho je
- Kompletní texty z webu
- Dva ceníky (s upozorněním na rozdíly)
- Mapu stávající struktury
- ⚠️ **Seznam chyb k opravě** (mj. špatná GPS, pravopis, duplicitní ceníky)
- Návrh nové struktury webu
- Doporučení designu a tone of voice
- Technické parametry nového webu

### Krok 3 – Před začátkem prací s tebou ujasnit
1. **Který ceník je aktuální?** Na úvodu jsou jiné typy pokojů a ceny než na /cenik/
2. **Aktuální kapacita** – web udává 51 i 52 lůžek
3. **Nové fotografie** – staré fotky jsou různorodé kvality, doporučuji nafotit jednotnou sadu
4. **Reference / recenze** od hostů – pro novou sekci
5. **Online rezervační systém** – chceš nějaký napojit? (Previo, Bookassist…)
6. **Hosting a doména** – zachovat doménu, kde hostovat?

## ⚠️ Hlavní chyby v stávajícím webu

1. **Špatná GPS v kontaktu** – `50.083946, 14.424548` je Praha! Správně je `49.367, 17.776`
2. **Dva různé ceníky** s různými typy pokojů
3. **Pravopisné chyby** – "ciklisty", "obylných", "samy", "Garfíld"
4. **Telefon/email nejsou klikací**
5. **Žádný rezervační formulář**
6. **Žádná SEO struktura** (chybí schema.org)

Detaily najdeš v sekci 9 hlavního briefu.

---

*Připravil Claude (Anthropic), 7. května 2026*
