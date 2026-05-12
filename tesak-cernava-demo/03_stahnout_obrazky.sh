#!/bin/bash
# ===========================================================
# Skript pro hromadné stažení všech obrázků z webu
# tesak-cernava.cz
#
# Použití (Linux/macOS):
#   chmod +x 03_stahnout_obrazky.sh
#   ./03_stahnout_obrazky.sh
#
# Pro Windows: spustit přes Git Bash, WSL nebo přepsat do PowerShellu
#
# Vytvoří složku ./obrazky/ a do ní stáhne všechny obrázky.
# Vyžaduje nainstalovaný curl (standardně součástí systému).
# ===========================================================

set -e

OUT_DIR="./obrazky"
mkdir -p "$OUT_DIR"

# Hlavičky, které simulují běžný prohlížeč (kvůli ochraně CDN Webnode)
REFERER="https://www.tesak-cernava.cz/"
UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"

# Pole URL adres všech obrázků z webu (s parametrem ?ph= pro autorizaci CDN)
URLS=(
  # --- Úvodní stránka ---
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000679-cce3bcce3e/IMG_3217-1.jpeg?ph=9291f65e3a"
  "https://duyn491kcolsw.cloudfront.net/files/0s/0s0/0s0eg6.png?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001069-96e6396e65/P1055117-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001081-450d5450d8/P1055164-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001075-167881678a/P1055139-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001066-9169e916a0/P1055293-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001057-c4eadc4eaf/P1055266-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001050-2e0942e096/P1055260-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001087-ea0c9ea0cb/P1055135-HDR.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000693-c44d8c44da/IMG_3300-5.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000072-45b1745b19/IMG20220528194536%20-%20kopie%20%282%29.jpg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001031-53f1953f1a/IMG_20250802_102255-1.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001073-5229f522a0/IMG20220525204625%20-%20kopie.jpeg?ph=9291f65e3a"

  # --- Stránka Ubytování ---
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000764-06f7f06f82/IMG_3199-6.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000401-1a24d1a24f/IMG20231006100649.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000319-819c9819cb/IMG20240126193956.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000261-155d4155d5/DJI_0097.JPG?ph=9291f65e3a"

  # --- Stránka Masáže ---
  "https://duyn491kcolsw.cloudfront.net/files/1w/1w4/1w4se3.jpg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001099-83e9c83e9e/logo%20Mas%C3%A1%C5%BEe.png?ph=9291f65e3a"

  # --- Stránka Galerie ---
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001019-d8eded8ee0/IMG_20250802_102215.jpeg?ph=9291f65e3a"
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200000950-0630406309/IMG20220625095749.jpeg?ph=9291f65e3a"

  # --- Stránka Ceník ---
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001021-cd8aacd8ac/IMG_20250802_102255.jpeg?ph=9291f65e3a"

  # --- Stránka Kontakt ---
  "https://9291f65e3a.clvaw-cdnwnd.com/588a397e9cb4dd01775369d08f85661a/200001023-4811048111/IMG_20250802_101926.jpeg?ph=9291f65e3a"
)

echo "Stahuji ${#URLS[@]} obrázků do složky $OUT_DIR ..."
echo ""

count=0
failed=0
for url in "${URLS[@]}"; do
  count=$((count+1))
  # Získání čistého názvu souboru z URL (bez parametrů)
  clean_url="${url%%\?*}"
  filename=$(basename "$clean_url")
  # Dekódování URL-encoded znaků v názvu (např. %20 → mezera)
  filename=$(printf '%b' "${filename//%/\\x}")

  echo "[$count/${#URLS[@]}] $filename"

  if curl -fsSL \
       -H "Referer: $REFERER" \
       -H "User-Agent: $UA" \
       -H "Accept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8" \
       -o "$OUT_DIR/$filename" \
       "$url"; then
    echo "    ✓ OK"
  else
    echo "    ✗ Selhalo"
    failed=$((failed+1))
  fi
done

echo ""
echo "=========================================="
echo "Hotovo. Staženo: $((count-failed))/$count"
echo "Soubory najdete v: $OUT_DIR"
echo "=========================================="

if [ $failed -gt 0 ]; then
  echo ""
  echo "Pokud něco selhalo, zkus:"
  echo "  1. Otevřít URL v prohlížeči a uložit obrázek ručně (Pravým → Uložit jako)"
  echo "  2. Nainstalovat wget a zkusit:"
  echo "     wget --referer=https://www.tesak-cernava.cz/ <URL>"
fi
