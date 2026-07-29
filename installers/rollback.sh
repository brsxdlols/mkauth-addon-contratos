#!/bin/sh
set -eu

MKAUTH_ROOT=${MKAUTH_ROOT:-/opt/mk-auth}
ADDONS_DIR="$MKAUTH_ROOT/admin/addons"
TARGET_DIR="$ADDONS_DIR/contratos"
BACKUP_ROOT=${CONTRATOS_BACKUP_ROOT:-/root/backups}
BACKUP_DIR=${1:-"$BACKUP_ROOT/mkauth-addon-contratos-latest"}

fail() {
    printf '%s\n' "[contratos] ERRO: $*" >&2
    exit 1
}

[ "$(id -u)" -eq 0 ] || fail "execute como root"
[ -e "$BACKUP_DIR" ] || fail "backup nao encontrado: $BACKUP_DIR"
BACKUP_DIR=$(CDPATH= cd -- "$BACKUP_DIR" && pwd)

case "$BACKUP_DIR" in
    "$BACKUP_ROOT"/mkauth-addon-contratos-*) ;;
    *) fail "o caminho informado nao e um backup reconhecido do addon" ;;
esac

[ -f "$BACKUP_DIR/addon-existed" ] || fail "metadados do backup ausentes"
[ -f "$BACKUP_DIR/addon-js-path" ] || fail "caminho do addon.js ausente"
[ -f "$BACKUP_DIR/contracts-before.sql" ] || fail "backup do banco ausente"

ROLLBACK_SAFETY="$BACKUP_DIR/current-before-rollback-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$ROLLBACK_SAFETY"

if [ -d "$TARGET_DIR" ]; then
    mv "$TARGET_DIR" "$ROLLBACK_SAFETY/addon"
fi

if [ "$(cat "$BACKUP_DIR/addon-existed")" = "yes" ]; then
    [ -d "$BACKUP_DIR/addon" ] || fail "arquivos do addon original ausentes"
    cp -a "$BACKUP_DIR/addon" "$TARGET_DIR"
fi

ADDON_JS=$(cat "$BACKUP_DIR/addon-js-path")
if [ -f "$ADDON_JS" ]; then
    cp -a "$ADDON_JS" "$ROLLBACK_SAFETY/addon.js"
fi
if [ -f "$BACKUP_DIR/addon.js" ]; then
    cp -a "$BACKUP_DIR/addon.js" "$ADDON_JS"
fi

mysql --default-character-set=utf8 -uroot -p"${MKAUTH_DB_PASSWORD:-vertrigo}" mkradius -e "
DELETE FROM sis_contrato
WHERE codigo IN ('addoncontrato_fidelidade_1ano','addoncontrato_internet_padrao')
   OR nome IN (
     'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO',
     'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET'
   );"
mysql --default-character-set=utf8 -uroot -p"${MKAUTH_DB_PASSWORD:-vertrigo}" mkradius \
    < "$BACKUP_DIR/contracts-before.sql"

printf '%s\n' "[contratos] rollback concluido"
printf '%s\n' "[contratos] backup restaurado: $BACKUP_DIR"
printf '%s\n' "[contratos] estado anterior ao rollback: $ROLLBACK_SAFETY"
