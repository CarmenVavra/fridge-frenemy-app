#!/usr/bin/env bash
# ---------------------------------------------------------------------------
#  Fridge Frenemy - einmalige Server-Einrichtung (idempotent).
#  Wird von deploy/setup-server.ps1 per ssh aufgerufen:
#     bash remote-setup.sh <base-dir>
# ---------------------------------------------------------------------------
set -euo pipefail

mkdir -p "$HOME/$1"
BASE="$(cd "$HOME/$1" && pwd)"

echo "  -> Lege Verzeichnisse in $BASE an"
mkdir -p "$BASE/releases" "$BASE/uploads" \
    "$BASE/shared/storage/app/public" \
    "$BASE/shared/storage/framework/cache/data" \
    "$BASE/shared/storage/framework/sessions" \
    "$BASE/shared/storage/framework/views" \
    "$BASE/shared/storage/logs"
chmod -R u+rwX "$BASE/shared/storage"

if [ ! -f "$BASE/shared/.env" ]; then
    # Chroot-Shells bei netcup haben kein base64, PHP ist aber immer da.
    KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    cp "$BASE/uploads/env.production" "$BASE/shared/.env"
    sed -i "s|^APP_KEY=.*|APP_KEY=$KEY|" "$BASE/shared/.env"
    chmod 600 "$BASE/shared/.env"
    echo "  -> $BASE/shared/.env angelegt (APP_KEY generiert)."
    echo "     BITTE JETZT Datenbank- und Mail-Zugangsdaten darin eintragen!"
else
    echo "  -> $BASE/shared/.env existiert bereits - unveraendert."
fi
rm -f "$BASE/uploads/env.production" "$BASE/uploads/remote-setup.sh"

cat <<EOF

  Server ist vorbereitet. Naechste Schritte:
    1. $BASE/shared/.env bearbeiten (DB_*, MAIL_*, GEMINI_API_KEY)
    2. Document Root der Subdomain auf  $BASE/current/public  setzen
    3. Cronjob jede Minute:  cd $BASE/current && <php> artisan schedule:run
    4. Lokal deploy.bat ausfuehren
EOF
