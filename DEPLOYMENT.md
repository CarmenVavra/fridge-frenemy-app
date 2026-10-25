# Deployment auf netcup – fridgefrenemy.caryssa.at

Nach der einmaligen Einrichtung reicht **ein Befehl**, um den aktuellen Stand online zu stellen:

```bat
deploy.bat
```

Das Skript prüft den Git-Stand, führt Tests und Pint aus, pusht zu GitHub, baut lokal ein Release
(Composer ohne Dev-Pakete + `npm run build`), lädt es per `scp` hoch und schaltet es per `ssh` scharf:
Migrationen, Routen- und Event-Cache und ein atomarer Wechsel des Symlinks `current`.
Auf dem Server werden **weder git noch Composer noch Node** gebraucht – nur PHP ≥ 8.2 und eine Shell.

---

## Einmalige Einrichtung

### 1. Lokal (Windows)

Die folgenden Programme müssen im `PATH` liegen: `git`, `php`, `composer`, `npm`, `ssh`, `scp` und `tar`.
`ssh`, `scp` und `tar` sind bei Windows 10 und 11 schon dabei.

```bat
copy deploy\deploy.config.example.ps1 deploy\deploy.config.ps1
```

Danach in `deploy\deploy.config.ps1` die SSH-Daten aus dem netcup CCP bzw. aus Plesk eintragen:
`$SshUser`, `$SshHost`, optional `$PhpBin`.
Die Datei steht in `.gitignore`.

**SSH-Schlüssel (empfohlen):** Ohne Schlüssel fragt jedes Deployment mehrmals nach dem Passwort.

```bat
ssh-keygen -t ed25519
type %USERPROFILE%\.ssh\id_ed25519.pub
```

Den ausgegebenen Public Key auf dem Server in `~/.ssh/authorized_keys` eintragen.
Alternativ geht das in Plesk unter „SSH-Schlüssel“.

### 2. netcup / Plesk

1. **SSH-Zugang** für das Webhosting aktivieren. Als Shell `/bin/bash` wählen, chrooted ist ok.
2. **PHP 8.3** (oder mindestens 8.2) für die Subdomain `fridgefrenemy.caryssa.at` einstellen.
   In denselben PHP-Einstellungen `open_basedir` auf `{WEBSPACEROOT}{/}{:}{TMP}{/}` setzen.
   Mit dem Standardwert `{DOCROOT}…` kann PHP nur `public/` lesen, und jede Seite liefert einen 500-Fehler.
   Benötigte Extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`.
   Sie sind bei netcup standardmäßig aktiv.
3. **Datenbank** anlegen (MariaDB/MySQL). Host, Name, Benutzer und Passwort notieren.
   Achtung: Der Host ist bei netcup meist eine IP wie `10.35.x.x`, nicht `localhost`.
4. **Postfach** `butler@fridgefrenemy.caryssa.at` anlegen. Es wird für „Passwort vergessen“ gebraucht.
5. **SSL**: Let's-Encrypt-Zertifikat für die Subdomain aktivieren und „HTTP → HTTPS umleiten“ einschalten.

### 3. Server vorbereiten

```bat
deploy\setup-server.bat
```

Das legt `~/<RemoteBase>/` an (hier `fridgefrenemy.caryssa.at/fridgefrenemy-app`), mit `releases/`, `shared/storage/` und `shared/.env`.
Die `.env` bekommt dabei einen frisch generierten `APP_KEY`.

Danach per SSH oder über den Plesk-Dateimanager die Datei `fridgefrenemy.caryssa.at/fridgefrenemy-app/shared/.env` öffnen und eintragen.
Passwörter in einfache Hochkommas setzen, z. B. `DB_PASSWORD='…'`:

| Variable | Wert |
| --- | --- |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | aus Schritt 2.3 |
| `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | aus Schritt 2.4, also SMTP-Server, Postfach-Adresse und Passwort. Der Absender muss das Postfach sein. |
| `GEMINI_API_KEY` | optional, von https://aistudio.google.com/apikey |

### 4. Document Root umstellen

Plesk → Websites & Domains → `fridgefrenemy.caryssa.at` → Hosting-Einstellungen:

```
Dokumentstamm:  fridgefrenemy.caryssa.at/fridgefrenemy-app/current/public
```

Plesk legt dabei einen leeren Ordner `current/public` an. `deploy.bat` schiebt ihn beim ersten Mal automatisch beiseite (`current.plesk-<datum>`).

### 5. Cronjob (Butler-Alarm täglich 08:00)

Plesk → Geplante Aufgaben → Aufgabe hinzufügen → „PHP-Skript ausführen“, **jede Minute** (`* * * * *`):

| Feld | Wert |
| --- | --- |
| Skriptpfad | `fridgefrenemy.caryssa.at/fridgefrenemy-app/current/artisan` |
| Argumente | `schedule:run` |
| PHP-Version | 8.3 |
| Benachrichtigungen | keine |

### 6. Erstes Deployment

```bat
deploy.bat
```

Danach https://fridgefrenemy.caryssa.at öffnen, ein Konto registrieren, fertig.

---

## Alltag

| Was | Wie |
| --- | --- |
| Neue Version online stellen | Änderungen committen, dann `deploy.bat` |
| Notfall ohne Tests | `deploy.bat -SkipTests` |
| Logs ansehen | `~/fridgefrenemy.caryssa.at/fridgefrenemy-app/shared/storage/logs/laravel-*.log`, PHP-Fehler in `/logs/fridgefrenemy.caryssa.at/error_log` |
| Rollback | `cd ~/fridgefrenemy.caryssa.at/fridgefrenemy-app && ls releases`, dann `ln -sfn releases/<älteres-release> current`. Achtung: Migrationen werden dabei nicht zurückgedreht. |
| Alarm manuell auslösen | `cd ~/fridgefrenemy.caryssa.at/fridgefrenemy-app/current && php artisan fridge:send-alerts` |

## Struktur auf dem Server

```
~/fridgefrenemy.caryssa.at/fridgefrenemy-app/
├── current -> releases/20261025-120000-abc1234   ← Document Root: current/public
├── releases/                                     ← die letzten 5 Releases
├── shared/
│   ├── .env
│   └── storage/                                  ← Logs, Sessions-Fallback, Caches
└── uploads/                                      ← temporär während des Deployments
```

## Fehlersuche

- **„Kein PHP >= 8.2 gefunden“**: In Plesk unter „PHP-Einstellungen“ steht der Pfad, z. B. `/opt/plesk/php/8.3/bin/php`. Diesen in `deploy.config.ps1` als `$PhpBin` eintragen.
- **500-Fehler nach dem Deployment**: Zuerst `/logs/fridgefrenemy.caryssa.at/error_log` und `shared/storage/logs` prüfen. Steht dort „open_basedir restriction“, `open_basedir` wie in Schritt 2.2 setzen. Danach kontrollieren, ob die DB-Zugangsdaten in `shared/.env` stimmen.
- **SQLSTATE 1615 „Prepared statement needs to be re-prepared“**: Ist durch `PDO::ATTR_EMULATE_PREPARES` in `config/database.php` behoben. Abschalten ließe es sich mit `DB_EMULATE_PREPARES=false`.
- **Chroot-Shell**: Die SSH-Shell sieht andere absolute Pfade als der Webserver. Deshalb sind die Links zu `shared/` relativ, und es gibt kein `config:cache` bzw. `view:cache`.
- **Kamera/Scanner geht nicht**: Browser erlauben den Kamerazugriff nur über HTTPS. Prüfen, ob das SSL-Zertifikat aktiv ist.
- **Migration schlägt fehl**: Das alte Release bleibt aktiv, der Wartungsmodus wird automatisch beendet. Den Fehler beheben, committen und erneut `deploy.bat` ausführen.
