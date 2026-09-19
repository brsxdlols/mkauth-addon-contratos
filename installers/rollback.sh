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

# File-only rollback: never restore or delete contract records, even from old backups.
printf '%s\n' "[contratos] rollback concluido"
printf '%s\n' "[contratos] backup restaurado: $BACKUP_DIR"
printf '%s\n' "[contratos] estado anterior ao rollback: $ROLLBACK_SAFETY"
