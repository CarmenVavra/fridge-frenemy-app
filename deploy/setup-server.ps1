<#
.SYNOPSIS
    Einmalige Einrichtung von Fridge Frenemy auf dem netcup-Webspace.

.DESCRIPTION
    Legt per ssh die Ordnerstruktur (releases/, shared/storage, shared/.env) an
    und erzeugt eine .env aus .env.production.example mit frischem APP_KEY.
    Kann gefahrlos mehrfach ausgefuehrt werden - eine vorhandene .env bleibt unveraendert.
#>
$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot

$ConfigFile = Join-Path $PSScriptRoot 'deploy.config.ps1'
if (-not (Test-Path $ConfigFile)) {
    Write-Host '[FEHLER] deploy\deploy.config.ps1 fehlt. Kopiere deploy\deploy.config.example.ps1 und trage die Zugangsdaten ein.' -ForegroundColor Red
    exit 1
}
. $ConfigFile
$Remote = "$SshUser@$SshHost"

Write-Host "==> Richte $Remote ein (Ordner: ~/$RemoteBase)" -ForegroundColor Cyan

$envTmp = Join-Path ([IO.Path]::GetTempPath()) 'env.production'
Copy-Item (Join-Path $Root '.env.production.example') $envTmp -Force

& ssh -p $SshPort $Remote "mkdir -p '$RemoteBase/uploads'"
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

# Zieldateinamen explizit angeben: MSYS-scp (Git Bash) uebernimmt sonst den kompletten Windows-Pfad als Namen.
& scp -P $SshPort $envTmp "${Remote}:$RemoteBase/uploads/env.production"
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
& scp -P $SshPort (Join-Path $PSScriptRoot 'server\remote-setup.sh') "${Remote}:$RemoteBase/uploads/remote-setup.sh"
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
Remove-Item $envTmp

& ssh -p $SshPort $Remote "sed -i 's/\r`$//' '$RemoteBase/uploads/remote-setup.sh' '$RemoteBase/uploads/env.production' && bash '$RemoteBase/uploads/remote-setup.sh' '$RemoteBase'"
exit $LASTEXITCODE
