<#
.SYNOPSIS
    Stellt den aktuellen Stand von Fridge Frenemy auf netcup online.

.DESCRIPTION
    1. Prueft, dass alles committet ist, und fuehrt Tests + Code-Style-Check aus
    2. Pusht den Branch zu GitHub
    3. Baut lokal ein Release (git archive + composer --no-dev + npm run build)
    4. Laedt es per scp hoch und aktiviert es per ssh (Migrationen, Caches,
       atomarer Symlink-Wechsel auf "current", alte Releases aufraeumen)

    Auf dem Server werden weder git noch composer noch node benoetigt.

.PARAMETER SkipTests
    Tests und Pint-Check ueberspringen (nur im Notfall).

.EXAMPLE
    .\deploy.bat
    .\deploy.bat -SkipTests
#>
param(
    [switch]$SkipTests
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

function Step($text) { Write-Host "`n==> $text" -ForegroundColor Cyan }
function Fail($text) { Write-Host "`n[FEHLER] $text" -ForegroundColor Red; exit 1 }
function Run($exe, [string[]]$arguments) {
    & $exe @arguments
    if ($LASTEXITCODE -ne 0) { Fail "$exe $($arguments -join ' ') ist fehlgeschlagen (Exit $LASTEXITCODE)." }
}

# --- Konfiguration ----------------------------------------------------------
$ConfigFile = Join-Path $PSScriptRoot 'deploy.config.ps1'
if (-not (Test-Path $ConfigFile)) {
    Fail "deploy\deploy.config.ps1 fehlt. Kopiere deploy\deploy.config.example.ps1 und trage die netcup-Zugangsdaten ein."
}
. $ConfigFile
foreach ($tool in 'git', 'ssh', 'scp', 'tar', 'composer', 'npm', 'php') {
    if (-not (Get-Command $tool -ErrorAction SilentlyContinue)) { Fail "$tool wurde nicht gefunden." }
}
$Remote = "$SshUser@$SshHost"
$SshArgs = @('-p', "$SshPort")
$ScpArgs = @('-P', "$SshPort")

# --- 1. Sauberer Stand + Tests ---------------------------------------------
Step 'Pruefe Git-Status'
$current = (git rev-parse --abbrev-ref HEAD).Trim()
if ($current -ne $Branch) { Fail "Du bist auf Branch '$current', deployt wird '$Branch'. Bitte erst wechseln/mergen." }
if (git status --porcelain) { Fail 'Es gibt nicht committete Aenderungen. Bitte erst committen.' }
$Commit = (git rev-parse --short HEAD).Trim()

if (-not $SkipTests) {
    Step 'Tests und Code-Style'
    Run 'php' @('artisan', 'test')
    Run 'php' @('vendor/bin/pint', '--test')
}

# --- 2. Push zu GitHub ------------------------------------------------------
Step "Pushe $Branch zu GitHub"
Run 'git' @('push', 'origin', $Branch)

# --- 3. Release bauen -------------------------------------------------------
$Release = (Get-Date -Format 'yyyyMMdd-HHmmss') + "-$Commit"
$Work = Join-Path ([IO.Path]::GetTempPath()) "fridge-frenemy-$Release"
$Archive = Join-Path ([IO.Path]::GetTempPath()) "fridge-frenemy-$Release.tgz"
New-Item -ItemType Directory -Path $Work | Out-Null

try {
    Step "Baue Release $Release"
    $tarFile = Join-Path ([IO.Path]::GetTempPath()) "fridge-frenemy-$Release-src.tar"
    Run 'git' @('archive', '--format=tar', "--output=$tarFile", 'HEAD')
    Run 'tar' @('-xf', $tarFile, '-C', $Work)
    Remove-Item $tarFile

    Step 'Frontend-Assets bauen (npm run build)'
    Run 'npm' @('ci', '--no-audit', '--no-fund')
    Run 'npm' @('run', 'build')
    Copy-Item -Recurse (Join-Path $Root 'public\build') (Join-Path $Work 'public\build')

    Step 'PHP-Abhaengigkeiten (composer --no-dev)'
    Push-Location $Work
    try {
        Run 'composer' @('install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-scripts', '--no-progress')
    } finally { Pop-Location }

    # Nicht benoetigte Dateien entfernen
    foreach ($p in 'tests', 'UI_Mockups', 'node_modules', '.env', 'database\database.sqlite') {
        $full = Join-Path $Work $p
        if (Test-Path $full) { Remove-Item -Recurse -Force $full }
    }
    Set-Content -Path (Join-Path $Work 'REVISION') -Value $Commit -NoNewline

    Run 'tar' @('-czf', $Archive, '-C', $Work, '.')
    $sizeMb = [math]::Round((Get-Item $Archive).Length / 1MB, 1)
    Write-Host "Release-Archiv: $sizeMb MB"

    # --- 4. Hochladen + aktivieren -----------------------------------------
    Step "Lade auf $SshHost hoch"
    Run 'ssh' ($SshArgs + @($Remote, "mkdir -p '$RemoteBase/uploads'"))
    # Zieldateinamen explizit angeben: MSYS-scp (Git Bash) uebernimmt sonst den kompletten Windows-Pfad als Namen.
    Run 'scp' ($ScpArgs + @($Archive, "${Remote}:$RemoteBase/uploads/fridge-frenemy-$Release.tgz"))
    Run 'scp' ($ScpArgs + @((Join-Path $PSScriptRoot 'server\remote-deploy.sh'), "${Remote}:$RemoteBase/uploads/remote-deploy.sh"))

    Step 'Aktiviere Release auf dem Server'
    $remoteCmd = "sed -i 's/\r`$//' '$RemoteBase/uploads/remote-deploy.sh' && " +
                 "PHP_BIN='$PhpBin' KEEP_RELEASES='$KeepReleases' bash '$RemoteBase/uploads/remote-deploy.sh' '$RemoteBase' '$Release'"
    Run 'ssh' ($SshArgs + @($Remote, $remoteCmd))
}
finally {
    if (Test-Path $Work) { Remove-Item -Recurse -Force $Work }
    if (Test-Path $Archive) { Remove-Item -Force $Archive }
    # npm ci hat lokale node_modules neu installiert, composer wurde nur im Temp-Ordner ausgefuehrt.
}

Write-Host "`nFertig: Release $Release ($Commit) ist online." -ForegroundColor Green
