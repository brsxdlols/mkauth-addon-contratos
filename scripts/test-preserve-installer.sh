#!/bin/sh
set -eu
repo=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
[ "$(id -u)" -eq 0 ] || { echo "Run as root in a test environment"; exit 1; }
fixture=$(mktemp -d /tmp/contratos-installer-test.XXXXXX)
trap 'rm -rf "$fixture"' EXIT
export MKAUTH_ROOT="$fixture/mk"
export CONTRATOS_BACKUP_ROOT="$fixture/backups"
export CONTRATOS_SIGNATURE_BACKUP_DIR="$fixture/signature-backups"
mkdir -p "$MKAUTH_ROOT/admin/addons" "$MKAUTH_ROOT/admin/arquivos/client" "$MKAUTH_ROOT/mkfiles" "$fixture/bin"
# Fail if installation or rollback tries to use SQL commands.
for tool in mysql mysqldump; do
 printf '#!/bin/sh\necho SQL_CALLED >> "%s/sql-called"\nexit 99\n' "$fixture" > "$fixture/bin/$tool"
 chmod +x "$fixture/bin/$tool"
done
export PATH="$fixture/bin:$PATH"
! grep -E 'seed-contracts|sis_contrato|mysql' "$repo/installers/install.sh" "$repo/installers/rollback.sh"
printf 'original signed document' > "$MKAUTH_ROOT/admin/arquivos/client/contrato_original.pdf"
printf 'signature image' > "$MKAUTH_ROOT/mkfiles/assinatura_provedor"
sha256sum "$MKAUTH_ROOT/admin/arquivos/client/contrato_original.pdf" "$MKAUTH_ROOT/mkfiles/assinatura_provedor" > "$fixture/doc-check"
sh "$repo/installers/install.sh" > "$fixture/new-install.log"
addon="$MKAUTH_ROOT/admin/addons/contratos"
printf '<?php /* custom database connection */\n' > "$addon/database/conexao.php"
printf '<?php /* custom storage config */\n' > "$addon/config.php"
printf 'CUSTOM CONTRACT TEMPLATE' > "$addon/modelo_contrato_padrao.html"
printf 'diagnostic history' > "$addon/logs/fixture.log"
sha256sum "$addon/database/conexao.php" "$addon/config.php" "$addon/modelo_contrato_padrao.html" "$addon/logs/fixture.log" > "$fixture/config-check"
sh "$repo/installers/install.sh" > "$fixture/upgrade.log"
sha256sum -c "$fixture/config-check"
sha256sum -c "$fixture/doc-check"
# Even old backups containing SQL must never restore database content.
latest=$(readlink -f "$CONTRATOS_BACKUP_ROOT/mkauth-addon-contratos-latest")
printf 'DELETE FROM sis_contrato;' > "$latest/contracts-before.sql"
sh "$repo/installers/rollback.sh" > "$fixture/rollback.log"
sha256sum -c "$fixture/config-check"
sha256sum -c "$fixture/doc-check"
# Force an installation failure after file replacement; verify automatic rollback.
cp -a "$repo" "$fixture/broken"
printf '<?php exit(17);\n' > "$fixture/broken/installers/update-addon-js.php"
if sh "$fixture/broken/installers/install.sh" > "$fixture/failure.log" 2>&1; then
 echo "Expected installer failure"; exit 1
fi
sha256sum -c "$fixture/config-check"
sha256sum -c "$fixture/doc-check"
[ ! -e "$fixture/sql-called" ]
echo 'PASS: new install, update, rollback and failure recovery preserve configuration and documents without SQL.'
