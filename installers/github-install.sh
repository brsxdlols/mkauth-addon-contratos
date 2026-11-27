#!/bin/sh
set -eu

REPOSITORY=${CONTRATOS_REPOSITORY:-brsxdlols/mkauth-addon-contratos}
REF=${CONTRATOS_REF:-}
# The dashboard and standalone installer resolve the same published stable commit.
TEMP_DIR=$(mktemp -d)

cleanup() {
    rm -rf "$TEMP_DIR"
}
trap cleanup EXIT HUP INT TERM

if [ "$(id -u)" -ne 0 ]; then
    echo "Execute como root." >&2
    exit 1
fi

if [ -z "$REF" ]; then
    STABLE_URL="https://raw.githubusercontent.com/$REPOSITORY/main/installers/stable-ref"
    if command -v curl >/dev/null 2>&1; then
        REF=$(curl -fsSL --retry 3 --connect-timeout 15 "$STABLE_URL")
    elif command -v wget >/dev/null 2>&1; then
        REF=$(wget -qO - "$STABLE_URL")
    else echo 'Instale curl ou wget.' >&2; exit 1; fi
    printf '%s' "$REF" | grep -Eq '^[a-f0-9]{40}$' || { echo 'Referencia estavel invalida.' >&2; exit 1; }
fi
printf '[contratos] Versao de origem: %s\n' "$REF"
ARCHIVE_URL="https://codeload.github.com/$REPOSITORY/tar.gz/$REF"
ARCHIVE_PATH="$TEMP_DIR/source.tar.gz"

if command -v curl >/dev/null 2>&1; then
    curl -fsSL --retry 3 --connect-timeout 15 "$ARCHIVE_URL" -o "$ARCHIVE_PATH"
elif command -v wget >/dev/null 2>&1; then
    wget -qO "$ARCHIVE_PATH" "$ARCHIVE_URL"
else
    echo "Instale curl ou wget para continuar." >&2
    exit 1
fi

tar -tzf "$ARCHIVE_PATH" >/dev/null
if tar -tzf "$ARCHIVE_PATH" | grep -Eq '(^|/)\.\.(/|$)|^/'; then
    echo "Pacote do GitHub contem caminhos inseguros." >&2
    exit 1
fi

tar -xzf "$ARCHIVE_PATH" -C "$TEMP_DIR"
SOURCE_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d | head -n 1)

[ -n "$SOURCE_DIR" ] || {
    echo "Pacote do GitHub invalido." >&2
    exit 1
}

sh "$SOURCE_DIR/installers/install.sh"

