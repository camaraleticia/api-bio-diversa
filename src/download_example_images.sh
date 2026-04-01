#!/bin/bash
set -euo pipefail

# Detecta diretório do script (fica em <repo>/src)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# Se existir app/ML dentro dele, estamos realmente dentro de src
if [ -d "$SCRIPT_DIR/app/ML" ]; then
  BASE="$SCRIPT_DIR"   # BASE aponta para .../src
else
  # Caso improvável: script movido. Tenta achar src relativa.
  if [ -d "$SCRIPT_DIR/src/app/ML" ]; then
    BASE="$SCRIPT_DIR/src"
  else
    echo "[ERRO] Não encontrei pasta app/ML. Execute a partir do repositório clonado."
    exit 1
  fi
fi

info() { echo -e "\e[32m$1\e[0m"; }
warn() { echo -e "\e[33m$1\e[0m"; }
err()  { echo -e "\e[31m$1\e[0m"; }

command -v curl >/dev/null 2>&1 || { err "curl não encontrado. Instale (Windows: Git Bash já deve trazer)"; exit 1; }

DATASET_DIR="$BASE/app/ML/Dataset"
TEST_DIR="$BASE/public/uploads/test_images"

mkdir -p "$DATASET_DIR/fauna/quero_quero" \
         "$DATASET_DIR/fauna/capivara" \
         "$DATASET_DIR/fauna/graxaim" \
         "$DATASET_DIR/flora/babosa_do_campo" \
         "$DATASET_DIR/flora/trevo_nativo" \
         "$TEST_DIR"

info "Baixando imagens de exemplo para treinamento e teste..."

#download_image() {
#  local url="$1"; shift
#  local output="$1"; shift
#  if [ ! -f "$output" ]; then
#    echo "Baixando: $(basename "$output")"
#    if curl -fsSL "$url" -o "$output"; then
#      info "✓ Ok: $output"
#    else
#      warn "Falhou download: $url"
#      rm -f "$output" || true
#    fi
#  else
#    echo "✓ Já existe: $output"
#  fi
#}

download_image() {
  local url="$1"; shift
  local output="$1"; shift
  if [ ! -f "$output" ]; then
    echo "Baixando: $(basename "$output")"
    if curl -fsSL -A "Mozilla/5.0" "$url" -o "$output"; then
      echo "✓ Ok: $output"
    else
      echo "Falhou download: $url"
      rm -f "$output" || true
    fi
  else
    echo "✓ Já existe: $output"
  fi
}

# Função auxiliar para compor caminho dentro de dataset
p() { echo "$DATASET_DIR/$1"; }
t() { echo "$TEST_DIR/$1"; }

# Quero-quero
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Southern_lapwing_%28Vanellus_chilensis%29.jpg/1200px-Southern_lapwing_%28Vanellus_chilensis%29.jpg" "$(p fauna/quero_quero/quero_quero_01.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/7/79/Vanellus_chilensis_-_Flickr_-_Lip_Kee.jpg" "$(p fauna/quero_quero/quero_quero_02.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Southern_Lapwing_%28Vanellus_chilensis%29.jpg/1280px-Southern_Lapwing_%28Vanellus_chilensis%29.jpg" "$(p fauna/quero_quero/quero_quero_03.jpg)"

# Capivara
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/Capybara_%28Hydrochoerus_hydrochaeris%29.JPG/1200px-Capybara_%28Hydrochoerus_hydrochaeris%29.JPG" "$(p fauna/capivara/capivara_01.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/0/02/Capybara_portrait.jpg/1280px-Capybara_portrait.jpg" "$(p fauna/capivara/capivara_02.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/b/b1/Capybara_%28Hydrochoerus_hydrochaeris%29_in_Esteros_del_Ibera.jpg/1280px-Capybara_%28Hydrochoerus_hydrochaeris%29_in_Esteros_del_Ibera.jpg" "$(p fauna/capivara/capivara_03.jpg)"

# Graxaim
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/2/23/Graxaim-do-campo_-_Pseudalopex_gymnocercus.jpg/1200px-Graxaim-do-campo_-_Pseudalopex_gymnocercus.jpg" "$(p fauna/graxaim/graxaim_01.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/Pseudalopex_gymnocercus_-_Pampas_fox_-_Zorro_de_la_pampa_-_Graxaim-do-campo_-_Aguara_chaim.jpg/1280px-Pseudalopex_gymnocercus_-_Pampas_fox_-_Zorro_de_la_pampa_-_Graxaim-do-campo_-_Aguara_chaim.jpg" "$(p fauna/graxaim/graxaim_02.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/7/7b/Pampas_fox_%28Lycalopex_gymnocercus%29.jpg/1280px-Pampas_fox_%28Lycalopex_gymnocercus%29.jpg" "$(p fauna/graxaim/graxaim_03.jpg)"

# Babosa-do-campo
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Eryngium_horridum_-_Flickr_-_aspidoscelis_%281%29.jpg/1200px-Eryngium_horridum_-_Flickr_-_aspidoscelis_%281%29.jpg" "$(p flora/babosa_do_campo/babosa_01.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/8/8c/Eryngium_horridum_-_Flickr_-_aspidoscelis.jpg/1280px-Eryngium_horridum_-_Flickr_-_aspidoscelis.jpg" "$(p flora/babosa_do_campo/babosa_02.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Eryngium_horridum_-_Flickr_-_aspidoscelis_%282%29.jpg/1280px-Eryngium_horridum_-_Flickr_-_aspidoscelis_%282%29.jpg" "$(p flora/babosa_do_campo/babosa_03.jpg)"

# Trevo-nativo
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/d/db/Trifolium_pratense_-_Keila.jpg/1200px-Trifolium_pratense_-_Keila.jpg" "$(p flora/trevo_nativo/trevo_01.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Trifolium_pratense0.jpg/1280px-Trifolium_pratense0.jpg" "$(p flora/trevo_nativo/trevo_02.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/0/08/Trifolium_pratense_-_Keila2.jpg/1280px-Trifolium_pratense_-_Keila2.jpg" "$(p flora/trevo_nativo/trevo_03.jpg)"

# Teste

download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Southern_lapwing_%28Vanellus_chilensis%29.jpg/1200px-Southern_lapwing_%28Vanellus_chilensis%29.jpg" "$(t quero_quero_test.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/Capybara_%28Hydrochoerus_hydrochaeris%29.JPG/1200px-Capybara_%28Hydrochoerus_hydrochaeris%29.JPG" "$(t capivara_test.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/2/23/Graxaim-do-campo_-_Pseudalopex_gymnocercus.jpg/1200px-Graxaim-do-campo_-_Pseudalopex_gymnocercus.jpg" "$(t graxaim_test.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d2/Eryngium_horridum_-_Flickr_-_aspidoscelis_%281%29.jpg/1200px-Eryngium_horridum_-_Flickr_-_aspidoscelis_%281%29.jpg" "$(t babosa_test.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/d/db/Trifolium_pratense_-_Keila.jpg/1200px-Trifolium_pratense_-_Keila.jpg" "$(t trevo_test.jpg)"
download_image "https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/Cat03.jpg/1200px-Cat03.jpg" "$(t generic_test.jpg)"

info "Concluído. Dataset: $DATASET_DIR"
info "Imagens de teste: $TEST_DIR"

echo -e "\nPara treinar (de fora do container):"
echo "docker compose exec php php app/ML/train_model.php"

echo -e "\nPara classificar exemplo:"
echo "docker compose exec php php app/ML/classify_image.php --image=/var/www/html/public/uploads/test_images/quero_quero_test.jpg"

# Dica se nenhum arquivo baixou
if ! ls "$DATASET_DIR/fauna/quero_quero"/* >/dev/null 2>&1; then
  warn "Nenhuma imagem baixada. Verifique sua conexão ou firewall (talvez bloquear HTTPS)."
fi