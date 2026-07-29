#!/bin/sh
set -eu

REPOSITORY_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

for script in "$REPOSITORY_DIR"/installers/*.sh "$REPOSITORY_DIR"/scripts/*.sh; do
    sh -n "$script"
done

if command -v php >/dev/null 2>&1; then
    find "$REPOSITORY_DIR/addons/contratos" "$REPOSITORY_DIR/installers" \
        -type f -name '*.php' -exec php -l {} \; >/dev/null
fi

for model in \
    "$REPOSITORY_DIR/addons/contratos/modelo_contrato_padrao.html" \
    "$REPOSITORY_DIR/addons/contratos/modelo_contrato_fidelidade.html"
do
    [ -s "$model" ]
    grep -q '%nomecliente%' "$model"
    grep -q '%cpfcliente%' "$model"
    grep -q '%provedorcidade%' "$model"
done

grep -q 'MKAUTH-CONTRATOS-MENU-BEGIN' "$REPOSITORY_DIR/installers/update-addon-js.php"
grep -q 'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO' \
    "$REPOSITORY_DIR/installers/seed-contracts.php"

echo "Validacao concluida com sucesso."
