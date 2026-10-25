#!/usr/bin/env bash
# ---------------------------------------------------------------------------
#  Fridge Frenemy - aktiviert ein hochgeladenes Release auf dem Server.
#  Wird von deploy/deploy.ps1 per ssh aufgerufen:
#     bash remote-deploy.sh <base-dir> <release-name>
#
#  Layout auf dem Server:
#     <base>/current  -> releases/<release>   (Document Root: <base>/current/public)
#     <base>/releases/<release>/
#     <base>/shared/.env
#     <base>/shared/storage/
#     <base>/uploads/                           (temporaere Uploads)
# ---------------------------------------------------------------------------
set -euo pipefail

BASE="$(cd "$HOME" && cd "$1" && pwd)"
RELEASE="$2"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
RELEASE_DIR="$BASE/releases/$RELEASE"
ARCHIVE="$BASE/uploads/fridge-frenemy-$RELEASE.tgz"

log() { printf '  -> %s\n' "$*"; }

# --- PHP finden -------------------------------------------------------------
find_php() {
    local candidates=("${PHP_BIN:-}" /opt/plesk/php/8.4/bin/php /opt/plesk/php/8.3/bin/php /opt/plesk/php/8.2/bin/php \
        /usr/local/php84/bin/php /usr/local/php83/bin/php /usr/local/php82/bin/php php8.4 php8.3 php8.2 php)
    for c in "${candidates[@]}"; do
        [ -z "$c" ] && continue
        if command -v "$c" >/dev/null 2>&1 && "$c" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; then
            echo "$c"; return 0
        fi
    done
    return 1
}
PHP="$(find_php)" || { echo "[FEHLER] Kein PHP >= 8.2 gefunden. Setze \$PhpBin in deploy.config.ps1." >&2; exit 1; }
log "PHP: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

# --- Voraussetzungen --------------------------------------------------------
[ -f "$BASE/shared/.env" ] || { echo "[FEHLER] $BASE/shared/.env fehlt. Bitte zuerst deploy\\setup-server.bat ausfuehren." >&2; exit 1; }
[ -f "$ARCHIVE" ] || { echo "[FEHLER] Archiv $ARCHIVE nicht gefunden." >&2; exit 1; }

# --- Entpacken + verlinken --------------------------------------------------
log "Entpacke Release $RELEASE"
mkdir -p "$RELEASE_DIR"
tar -xzf "$ARCHIVE" -C "$RELEASE_DIR"
rm -f "$ARCHIVE"

rm -rf "$RELEASE_DIR/storage"
ln -s "$BASE/shared/storage" "$RELEASE_DIR/storage"
ln -sfn "$BASE/shared/.env" "$RELEASE_DIR/.env"
mkdir -p "$RELEASE_DIR/bootstrap/cache"

cd "$RELEASE_DIR"

# --- Laravel vorbereiten ----------------------------------------------------
log "Package Discovery"
"$PHP" artisan package:discover --ansi

log "Wartungsmodus an"
if [ -L "$BASE/current" ]; then (cd "$BASE/current" && "$PHP" artisan down --retry=15 || true); fi
# Bei einem Fehler (z. B. Migration) die alte Version wieder freigeben; "current" bleibt unveraendert.
trap 'echo "[FEHLER] Deployment abgebrochen - altes Release bleibt aktiv." >&2; (cd "$RELEASE_DIR" && "$PHP" artisan up >/dev/null 2>&1) || true' ERR

log "Migrationen"
"$PHP" artisan migrate --force

log "Caches"
"$PHP" artisan optimize

# --- Umschalten (atomar) ----------------------------------------------------
log "Aktiviere Release"
ln -sfn "releases/$RELEASE" "$BASE/current.tmp"
mv -Tf "$BASE/current.tmp" "$BASE/current"

"$PHP" artisan up

# --- Aufraeumen -------------------------------------------------------------
log "Behalte die letzten $KEEP_RELEASES Releases"
cd "$BASE/releases"
ls -1t | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf
rm -f "$BASE/uploads/remote-deploy.sh"

log "Release $RELEASE ist aktiv."
