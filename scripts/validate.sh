#!/bin/sh
set -eu

REPOSITORY_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

for script in "$REPOSITORY_DIR"/installers/*.sh "$REPOSITORY_DIR"/scripts/*.sh; do
    sh -n "$script"
done

if command -v php >/dev/null 2>&1; then
    php "$REPOSITORY_DIR/scripts/test-attachments.php"
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
grep -q "/opt/mk-auth/mkfiles" \
    "$REPOSITORY_DIR/addons/contratos/upload_assinatura_provedor.php"
grep -q "assinatura_provedor" \
    "$REPOSITORY_DIR/addons/contratos/upload_assinatura_provedor.php"
grep -q 'contratos_assinatura_csrf' \
    "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q 'action="index.php"' \
    "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q 'name="contratos_action"' \
    "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q 'css/index.css?v=' \
    "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q 'js/index.js?v=' \
    "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q "require_once __DIR__ . '/addons.class.php'" \
    "$REPOSITORY_DIR/addons/contratos/upload_assinatura_provedor.php"
grep -q "normalizarAssinaturaProvedor" \
    "$REPOSITORY_DIR/addons/contratos/upload_assinatura_provedor.php"
grep -q "imagepng" \
    "$REPOSITORY_DIR/addons/contratos/functions/normalizar_assinatura.php"
grep -q "v1.4.0" "$REPOSITORY_DIR/installers/github-install.sh"
grep -q "pdf-keep-together" "$REPOSITORY_DIR/addons/contratos/js/contrato.js"
grep -q "registrar_evento.php" "$REPOSITORY_DIR/addons/contratos/js/contrato.js"
grep -q "sis_contrato_historico" "$REPOSITORY_DIR/addons/contratos/functions/contract_history.php"
grep -q "data-status=\"expired\"" "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q "renewalModalFrame" "$REPOSITORY_DIR/addons/contratos/index.php"
grep -q "contratos-renovacao-refresh" "$REPOSITORY_DIR/addons/contratos/renovacao.php"
! grep -q "window.open" "$REPOSITORY_DIR/addons/contratos/js/index.js"
[ -s "$REPOSITORY_DIR/addons/contratos/vendor/html2pdf.bundle.min.js" ]

echo "Validacao concluida com sucesso."
