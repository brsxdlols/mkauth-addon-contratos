#!/bin/sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
REPOSITORY_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
MKAUTH_ROOT=${MKAUTH_ROOT:-/opt/mk-auth}
ADDONS_DIR="$MKAUTH_ROOT/admin/addons"
SOURCE_DIR="$REPOSITORY_DIR/addons/contratos"
TARGET_DIR="$ADDONS_DIR/contratos"
STORAGE_DIR="$MKAUTH_ROOT/admin/arquivos"
SIGNATURE_DIR="$MKAUTH_ROOT/mkfiles"
SIGNATURE_BACKUP_DIR=${CONTRATOS_SIGNATURE_BACKUP_DIR:-/var/backups/mkauth-addon-contratos-assinaturas}
BACKUP_ROOT=${CONTRATOS_BACKUP_ROOT:-/root/backups}
VERSION=$(tr -d '\r\n' < "$REPOSITORY_DIR/VERSION")
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$BACKUP_ROOT/mkauth-addon-contratos-$TIMESTAMP-v$VERSION"
STAGE_DIR="$ADDONS_DIR/.contratos.install.$$"
SUCCESS=0
MUTATED=0

log() {
    printf '%s\n' "[contratos] $*"
}

fail() {
    printf '%s\n' "[contratos] ERRO: $*" >&2
    exit 1
}

restore_on_error() {
    exit_code=$?
    if [ "$SUCCESS" -eq 1 ]; then
        return
    fi

    if [ "$MUTATED" -eq 1 ] && [ -d "$BACKUP_DIR" ]; then
        printf '%s\n' "[contratos] A instalacao falhou; restaurando o backup..." >&2

        if [ -e "$TARGET_DIR" ]; then
            failed_dir="$ADDONS_DIR/.contratos.failed.$$"
            mv "$TARGET_DIR" "$failed_dir" 2>/dev/null || true
        fi

        if [ -d "$BACKUP_DIR/addon" ]; then
            mv "$BACKUP_DIR/addon" "$TARGET_DIR" 2>/dev/null || true
        fi

        if [ -f "$BACKUP_DIR/addon.js" ] && [ -f "$BACKUP_DIR/addon-js-path" ]; then
            addon_js_restore=$(cat "$BACKUP_DIR/addon-js-path")
            cp -a "$BACKUP_DIR/addon.js" "$addon_js_restore" 2>/dev/null || true
        fi

        if [ -f "$BACKUP_DIR/contracts-before.sql" ]; then
            mysql --default-character-set=utf8 -uroot -p"${MKAUTH_DB_PASSWORD:-vertrigo}" mkradius \
                -e "DELETE FROM sis_contrato
                     WHERE codigo IN ('addoncontrato_fidelidade_1ano','addoncontrato_internet_padrao')
                        OR nome IN (
                          'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO',
                          'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET'
                        );" >/dev/null 2>&1 || true
            mysql --default-character-set=utf8 -uroot -p"${MKAUTH_DB_PASSWORD:-vertrigo}" mkradius \
                < "$BACKUP_DIR/contracts-before.sql" >/dev/null 2>&1 || true
        fi
    fi

    if [ -d "$STAGE_DIR" ]; then
        rm -rf "$STAGE_DIR"
    fi

    exit "$exit_code"
}
trap restore_on_error EXIT HUP INT TERM

[ "$(id -u)" -eq 0 ] || fail "execute como root"
[ -d "$ADDONS_DIR" ] || fail "MK Auth nao encontrado em $MKAUTH_ROOT"
[ -d "$SOURCE_DIR" ] || fail "payload do addon nao encontrado em $SOURCE_DIR"
[ -s "$SOURCE_DIR/modelo_contrato_padrao.html" ] || fail "modelo de contrato padrao ausente"
[ -s "$SOURCE_DIR/modelo_contrato_fidelidade.html" ] || fail "modelo com fidelidade ausente"

for command_name in php mysql mysqldump find cp mv mkdir chmod chown date; do
    command -v "$command_name" >/dev/null 2>&1 || fail "comando obrigatorio ausente: $command_name"
done

if [ -f "$ADDONS_DIR/addon.js" ]; then
    ADDON_JS="$ADDONS_DIR/addon.js"
elif [ -f "$MKAUTH_ROOT/admin/addon.js" ]; then
    ADDON_JS="$MKAUTH_ROOT/admin/addon.js"
else
    ADDON_JS="$ADDONS_DIR/addon.js"
fi

log "validando PHP do pacote"
find "$SOURCE_DIR" -type f -name '*.php' -exec php -l {} \; >/dev/null
php -l "$SCRIPT_DIR/seed-contracts.php" >/dev/null
php -l "$SCRIPT_DIR/update-addon-js.php" >/dev/null

log "criando backup em $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
printf '%s\n' "$ADDON_JS" > "$BACKUP_DIR/addon-js-path"
printf '%s\n' "$VERSION" > "$BACKUP_DIR/version"

if [ -d "$TARGET_DIR" ]; then
    cp -a "$TARGET_DIR" "$BACKUP_DIR/addon"
    printf '%s\n' "yes" > "$BACKUP_DIR/addon-existed"
else
    printf '%s\n' "no" > "$BACKUP_DIR/addon-existed"
fi

if [ -f "$ADDON_JS" ]; then
    cp -a "$ADDON_JS" "$BACKUP_DIR/addon.js"
fi

mysqldump \
    --default-character-set=utf8 \
    --no-create-info \
    --skip-triggers \
    --compact \
    --where="codigo IN ('addoncontrato_fidelidade_1ano','addoncontrato_internet_padrao') OR nome IN ('CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO','CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET')" \
    -uroot \
    -p"${MKAUTH_DB_PASSWORD:-vertrigo}" \
    mkradius sis_contrato > "$BACKUP_DIR/contracts-before.sql"

log "preparando arquivos do addon"
[ "$STAGE_DIR" != "$ADDONS_DIR" ] || fail "diretorio temporario invalido"
rm -rf "$STAGE_DIR"
mkdir -p "$STAGE_DIR"
cp -a "$SOURCE_DIR/." "$STAGE_DIR/"
find "$STAGE_DIR" -type f -iname 'desktop.ini' -delete
find "$STAGE_DIR" -type d -exec chmod 0755 {} \;
find "$STAGE_DIR" -type f -exec chmod 0644 {} \;
find "$STAGE_DIR" -type f -name '*.sh' -exec chmod 0755 {} \;
chown -R www-data:www-data "$STAGE_DIR"

MUTATED=1
if [ -d "$TARGET_DIR" ]; then
    rm -rf "$TARGET_DIR"
fi
mv "$STAGE_DIR" "$TARGET_DIR"

log "registrando menu no addon.js"
php "$SCRIPT_DIR/update-addon-js.php" "$ADDON_JS"
chown www-data:www-data "$ADDON_JS"
chmod 0644 "$ADDON_JS"

log "criando ou atualizando os dois contratos iniciais"
php "$SCRIPT_DIR/seed-contracts.php" "$TARGET_DIR"

log "ajustando diretorios de PDFs e da assinatura"
mkdir -p "$STORAGE_DIR"
chown www-data:www-data "$STORAGE_DIR"
chmod 0777 "$STORAGE_DIR"
mkdir -p "$SIGNATURE_DIR" "$SIGNATURE_BACKUP_DIR"
chown www-data:www-data "$SIGNATURE_BACKUP_DIR"
chmod 0777 "$SIGNATURE_DIR"
chmod 0700 "$SIGNATURE_BACKUP_DIR"

CONTRACT_COUNT=$(mysql --default-character-set=utf8 -uroot -p"${MKAUTH_DB_PASSWORD:-vertrigo}" -N -B mkradius -e "
SELECT COUNT(DISTINCT nome)
FROM sis_contrato
WHERE nome IN (
  'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET COM FIDELIDADE DE 1 ANO',
  'CONTRATO DE PRESTAÇÃO DE SERVIÇOS DE INTERNET'
);")

[ "$CONTRACT_COUNT" -eq 2 ] || fail "validacao do banco falhou: esperado 2, encontrado $CONTRACT_COUNT"
grep -q 'MKAUTH-CONTRATOS-MENU-BEGIN' "$ADDON_JS" || fail "atalho do menu nao foi gravado"
[ -f "$TARGET_DIR/index.php" ] || fail "arquivo principal do addon nao foi instalado"
[ -f "$TARGET_DIR/upload_assinatura_provedor.php" ] || fail "upload da assinatura nao foi instalado"
[ -w "$SIGNATURE_BACKUP_DIR" ] || fail "diretorio de backup da assinatura sem permissao de escrita"

ln -sfn "$BACKUP_DIR" "$BACKUP_ROOT/mkauth-addon-contratos-latest"
SUCCESS=1

log "instalacao concluida com sucesso"
log "addon: $TARGET_DIR"
log "menu: $ADDON_JS"
log "backup: $BACKUP_DIR"
log "acesso: /admin/addons/contratos/"
