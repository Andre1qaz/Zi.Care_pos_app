$ErrorActionPreference = 'Stop'

$odooRoot = 'C:\Program Files\Odoo 19.0.20260701'
$configPath = Join-Path $odooRoot 'server\odoo.conf'
$pythonPath = Join-Path $odooRoot 'python\python.exe'
$postgresBin = 'C:\Program Files\PostgreSQL\18\bin'
$sourceAddon = Join-Path $PSScriptRoot '..\odoo_addons\pos_sales_dashboard'
$customAddons = Join-Path $odooRoot 'custom-addons'
$targetAddon = Join-Path $customAddons 'pos_sales_dashboard'
$backupZip = 'C:\Users\ASUS\Downloads\pos_db_2026-09-29_13-32-29.zip'
$odooPort = 8069
$databaseFilter = '^pos_db_odoo19_copy$'

$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script from PowerShell opened with Run as administrator.'
}

foreach ($path in @($configPath, $pythonPath, (Join-Path $postgresBin 'psql.exe'), $sourceAddon, $backupZip)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required path not found: $path"
    }
}

$sourceManifest = Get-Content -LiteralPath (Join-Path $sourceAddon '__manifest__.py') -Raw
$sourceVersion = [regex]::Match($sourceManifest, "(?m)^\s*'version'\s*:\s*'([^']+)'").Groups[1].Value
if ($sourceVersion -ne '19.0.1.0.0') {
    throw "Expected the Odoo 19 POS Sales Dashboard module; found version '$sourceVersion'."
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($backupZip)
try {
    $entry = $archive.GetEntry('manifest.json')
    if (-not $entry) { throw 'Backup manifest.json is missing.' }
    $reader = [IO.StreamReader]::new($entry.Open())
    try { $backupManifest = $reader.ReadToEnd() | ConvertFrom-Json }
    finally { $reader.Dispose() }
} finally {
    $archive.Dispose()
}
if ($backupManifest.version -ne '19.0' -or $backupManifest.modules.pos_sales_dashboard -ne $sourceVersion) {
    throw 'The database backup and POS Sales Dashboard module versions do not match.'
}

$service = Get-Service -Name 'odoo-server-19.0' -ErrorAction Stop
$odoo17Service = Get-Service -Name 'odoo-server-17.0' -ErrorAction Stop
$addonAlreadyStaged = Test-Path -LiteralPath $targetAddon
if ($addonAlreadyStaged) {
    $targetManifest = Get-Content -LiteralPath (Join-Path $targetAddon '__manifest__.py') -Raw
    $targetVersion = [regex]::Match($targetManifest, "(?m)^\s*'version'\s*:\s*'([^']+)'").Groups[1].Value
    if ($targetVersion -ne $sourceVersion) {
        throw "A different addon version already exists; refusing to overwrite: $targetAddon"
    }
}

$configBackup = Join-Path $env:TEMP ("odoo19.conf.before-custom-addon.{0}.bak" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
Copy-Item -LiteralPath $configPath -Destination $configBackup

try {
    New-Item -ItemType Directory -Path $customAddons -Force | Out-Null
    if (-not $addonAlreadyStaged) {
        Copy-Item -LiteralPath $sourceAddon -Destination $customAddons -Recurse
    }

    $sitePackages = Join-Path $odooRoot 'python\Lib\site-packages'
    & $pythonPath -m pip install --disable-pip-version-check --no-warn-script-location --only-binary=:all: --upgrade --target $sitePackages 'PyMySQL==1.2.2'
    if ($LASTEXITCODE -ne 0) { throw 'PyMySQL installation failed.' }
    & $pythonPath -s -c 'import pymysql; print(pymysql.VERSION_STRING)'
    if ($LASTEXITCODE -ne 0) { throw 'PyMySQL could not be imported by Odoo Python.' }

    $configText = [IO.File]::ReadAllText($configPath)
    $configLines = [System.Collections.Generic.List[string]]::new()
    $foundAddonsPath = $false
    $foundHttpPort = $false
    $foundPgPath = $false
    $foundDbTemplate = $false
    $foundDbFilter = $false
    foreach ($line in ($configText -split "`r?`n")) {
        if ($line -match '^([ \t]*addons_path[ \t]*=[ \t]*)(.*?)([ \t]*)$') {
            $foundAddonsPath = $true
            $paths = @($Matches[2] -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ })
            if (-not ($paths | Where-Object { $_.TrimEnd('\') -ieq $customAddons.TrimEnd('\') })) {
                $paths += $customAddons
            }
            $configLines.Add($Matches[1] + ($paths -join ',') + $Matches[3])
        } elseif ($line -match '^([ \t]*http_port[ \t]*=[ \t]*)(.*?)([ \t]*)$') {
            $foundHttpPort = $true
            $configLines.Add($Matches[1] + $odooPort + $Matches[3])
        } elseif ($line -match '^([ \t]*pg_path[ \t]*=[ \t]*)(.*?)([ \t]*)$') {
            $foundPgPath = $true
            $configLines.Add($Matches[1] + $postgresBin + $Matches[3])
        } elseif ($line -match '^([ \t]*db_template[ \t]*=[ \t]*)(.*?)([ \t]*)$') {
            $foundDbTemplate = $true
            $configLines.Add($Matches[1] + 'template1' + $Matches[3])
        } elseif ($line -match '^([ \t]*dbfilter[ \t]*=[ \t]*)(.*?)([ \t]*)$') {
            $foundDbFilter = $true
            $configLines.Add($Matches[1] + $databaseFilter + $Matches[3])
        } else {
            $configLines.Add($line)
        }
    }
    if (-not $foundAddonsPath) { throw 'The addons_path setting was not found in odoo.conf.' }
    if (-not $foundHttpPort) { $configLines.Add("http_port = $odooPort") }
    if (-not $foundPgPath) { $configLines.Add("pg_path = $postgresBin") }
    if (-not $foundDbTemplate) { $configLines.Add('db_template = template1') }
    if (-not $foundDbFilter) { $configLines.Add("dbfilter = $databaseFilter") }
    [IO.File]::WriteAllText($configPath, ($configLines -join "`r`n"), [Text.UTF8Encoding]::new($false))

    if ($odoo17Service.Status -ne 'Stopped') {
        Stop-Service -Name 'odoo-server-17.0' -Force
        (Get-Service -Name 'odoo-server-17.0').WaitForStatus('Stopped', [TimeSpan]::FromSeconds(30))
    }
    Set-Service -Name 'odoo-server-17.0' -StartupType Manual

    Restart-Service -Name 'odoo-server-19.0' -Force
    (Get-Service -Name 'odoo-server-19.0').WaitForStatus('Running', [TimeSpan]::FromSeconds(30))
    Start-Sleep -Seconds 5

    $body = @{ jsonrpc = '2.0'; method = 'call'; params = @{ service = 'common'; method = 'version'; args = @() }; id = 1 } | ConvertTo-Json -Depth 5
    $version = (Invoke-RestMethod -Uri "http://127.0.0.1:$odooPort/jsonrpc" -Method Post -ContentType 'application/json' -Body $body -TimeoutSec 10).result.server_version
    if (-not $version.StartsWith('19.0')) { throw "Expected Odoo 19 on port $odooPort; received '$version'." }
    $dbBody = @{ jsonrpc = '2.0'; method = 'call'; params = @{ service = 'db'; method = 'list'; args = @() }; id = 2 } | ConvertTo-Json -Depth 5
    $dbResponse = Invoke-RestMethod -Uri "http://127.0.0.1:$odooPort/jsonrpc" -Method Post -ContentType 'application/json' -Body $dbBody -TimeoutSec 10
    if ($dbResponse.error) { throw "Odoo could not connect to PostgreSQL: $($dbResponse.error.data.message)" }
    $managerPage = Invoke-WebRequest -Uri "http://127.0.0.1:$odooPort/web/database/manager" -UseBasicParsing -TimeoutSec 10
    if ($managerPage.StatusCode -ne 200) { throw 'Odoo database manager did not return HTTP 200.' }
} catch {
    Copy-Item -LiteralPath $configBackup -Destination $configPath -Force
    try { Restart-Service -Name 'odoo-server-19.0' -Force } catch { }
    throw
}

Write-Output "Odoo version: $version"
Write-Output "Odoo URL: http://localhost:$odooPort"
Write-Output 'PostgreSQL connection: verified'
Write-Output 'Database manager: verified'
Write-Output "Custom addon installed at: $targetAddon"
Write-Output "Config backup: $configBackup"
