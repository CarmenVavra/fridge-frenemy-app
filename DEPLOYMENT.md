# Deployment auf netcup – fridgefrenemy.caryssa.at

Nach der einmaligen Einrichtung reicht **ein Befehl**, um den aktuellen Stand online zu stellen:

```bat
deploy.bat
```

Das Skript prüft den Git-Stand, führt Tests und Pint aus, pusht zu GitHub, baut lokal ein Release
(Composer ohne Dev-Pakete + `npm run build`), lädt es per `scp` hoch und schaltet es per `ssh` scharf:
Migrationen, Laravel-Caches und ein atomarer Wechsel des Symlinks `current`.
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
   Benötigte Extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`.
   Sie sind bei netcup standardmäßig aktiv.
3. **Datenbank** anlegen (MariaDB/MySQL). Name, Benutzer und Passwort notieren.
4. **Postfach** `butler@fridgefrenemy.caryssa.at` anlegen. Es wird für „Passwort vergessen“ gebraucht.
5. **SSL**: Let's-Encrypt-Zertifikat für die Subdomain aktivieren und „HTTP → HTTPS umleiten“ einschalten.

### 3. Server vorbereiten

```bat
deploy\setup-server.bat
```

Das legt `~/fridgefrenemy-app/` an, mit `releases/`, `shared/storage/` und `shared/.env`.
Die `.env` bekommt dabei einen frisch generierten `APP_KEY`.

Danach per SSH oder über den Plesk-Dateimanager die Datei `fridgefrenemy-app/shared/.env` öffnen und eintragen:

| Variable | Wert |
| --- | --- |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | aus Schritt 2.3 |
| `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` | aus Schritt 2.4, also Postfach-Adresse, Passwort und SMTP-Server laut Postfach-Einstellungen |
| `GEMINI_API_KEY` | optional, von https://aistudio.google.com/apikey |

### 4. Document Root umstellen

Plesk → Websites & Domains → `fridgefrenemy.caryssa.at` → Hosting-Einstellungen:

```
Dokumentstamm:  fridgefrenemy-app/current/public
```

### 5. Cronjob (Butler-Alarm täglich 08:00)

Plesk → Geplante Aufgaben → Aufgabe hinzufügen → „Befehl ausführen“, **jede Minute** (`* * * * *`):

```sh
cd ~/fridgefrenemy-app/current && /opt/plesk/php/8.3/bin/php artisan schedule:run >> /dev/null 2>&1
```

Den PHP-Pfad passend zur eingestellten Version wählen.
Wenn `deploy.bat` läuft, zeigt es den gefundenen Pfad in der Zeile `PHP: …` an.

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
| Logs ansehen | `~/fridgefrenemy-app/shared/storage/logs/laravel-*.log` |
| Rollback | `cd ~/fridgefrenemy-app && ls releases`, dann `ln -sfn releases/<älteres-release> current`. Achtung: Migrationen werden dabei nicht zurückgedreht. |
| Alarm manuell auslösen | `cd ~/fridgefrenemy-app/current && php artisan fridge:send-alerts` |

## Struktur auf dem Server

```
~/fridgefrenemy-app/
├── current -> releases/20261025-120000-abc1234   ← Document Root: current/public
├── releases/                                     ← die letzten 5 Releases
├── shared/
│   ├── .env
│   └── storage/                                  ← Logs, Sessions-Fallback, Caches
└── uploads/                                      ← temporär während des Deployments
```

## Fehlersuche

- **„Kein PHP >= 8.2 gefunden“**: In Plesk unter „PHP-Einstellungen“ steht der Pfad, z. B. `/opt/plesk/php/8.3/bin/php`. Diesen in `deploy.config.ps1` als `$PhpBin` eintragen.
- **500-Fehler nach dem Deployment**: Zuerst `shared/storage/logs` prüfen. Danach kontrollieren, ob die DB-Zugangsdaten in `shared/.env` stimmen.
- **Kamera/Scanner geht nicht**: Browser erlauben den Kamerazugriff nur über HTTPS. Prüfen, ob das SSL-Zertifikat aktiv ist.
- **Migration schlägt fehl**: Das alte Release bleibt aktiv, der Wartungsmodus wird automatisch beendet. Den Fehler beheben, committen und erneut `deploy.bat` ausführen.
