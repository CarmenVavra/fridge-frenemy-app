# ---------------------------------------------------------------------------
#  Fridge Frenemy - Deployment-Konfiguration
#  Kopiere diese Datei nach deploy\deploy.config.ps1 und trage deine netcup-Daten ein.
#  deploy.config.ps1 ist in .gitignore und wird nie committet.
# ---------------------------------------------------------------------------

# SSH-Zugang (netcup CCP -> Webhosting -> SSH bzw. Plesk -> "Websites & Domains" -> SSH-Zugang)
$SshUser = 'hosting123456'
$SshHost = 'hosting123456.a2e7f.netcup.net'
$SshPort = 22

# Ordner fuer die App, relativ zum SSH-Home (= Webspace-Root bei netcup).
# Der Document Root der Subdomain muss auf  <RemoteBase>/current/public  zeigen.
$RemoteBase = 'fridgefrenemy-app'

# PHP-CLI auf dem Server (mind. 8.2). Leer lassen = automatisch suchen.
# Bei netcup/Plesk z. B. '/opt/plesk/php/8.3/bin/php' oder '/usr/local/php83/bin/php'
$PhpBin = ''

# Git-Branch, der deployt wird
$Branch = 'main'

# Wie viele alte Releases auf dem Server bleiben (fuer schnellen Rollback)
$KeepReleases = 5
