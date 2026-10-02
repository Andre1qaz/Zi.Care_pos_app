$ErrorActionPreference = 'Stop'

$odooRoot = 'C:\Program Files\Odoo 19.0.20260701'
$destinationRoot = Join-Path $odooRoot 'custom-addons'
$zipPath = 'C:\Users\ASUS\Downloads\om_account_accountant-19.0.1.0.3.zip'
$serviceName = 'odoo-server-19.0'
$expectedModules = @(
    'accounting_pdf_reports',
    'om_account_accountant',
    'om_account_asset',
    'om_account_budget',
    'om_account_daily_reports',
    'om_account_followup',
    'om_fiscal_year',
    'om_recurring_payments'
)

$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Open PowerShell with Run as administrator, then run this script again.'
}
foreach ($path in @($destinationRoot, $zipPath)) {
    if (-not (Test-Path -LiteralPath $path)) { throw "Required path not found: $path" }
}

$service = Get-Service -Name $serviceName -ErrorAction Stop
if ($service.Status -ne 'Running') { throw "Odoo service '$serviceName' must be running before installation." }

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $modulesInZip = @(
        foreach ($entry in $archive.Entries) {
            if ($entry.FullName -match '^([^/]+)/__manifest__\.py$') { $Matches[1] }
        }
    ) | Sort-Object -Unique
} finally {
    $archive.Dispose()
}

if (($modulesInZip -join ',') -ne (($expectedModules | Sort-Object) -join ',')) {
    throw "Unexpected addon folders in the Accounting ZIP: $($modulesInZip -join ', ')"
}

$customDashboard = Join-Path $destinationRoot 'pos_sales_dashboard'
if (-not (Test-Path -LiteralPath (Join-Path $customDashboard '__manifest__.py'))) {
    throw 'The POS Sales Dashboard addon already configured for this Odoo install was not found.'
}

foreach ($module in $expectedModules) {
    $target = Join-Path $destinationRoot $module
    if (Test-Path -LiteralPath $target) {
        throw "Refusing to overwrite an existing addon folder: $target"
    }
}

$tempRoot = [IO.Path]::GetFullPath($env:TEMP).TrimEnd('\') + '\'
$stageName = "odoo19-accounting-stage.{0}" -f [guid]::NewGuid().ToString('N')
$stage = [IO.Path]::GetFullPath((Join-Path -Path $tempRoot -ChildPath $stageName))
if (-not $stage.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase)) {
    throw "Refusing to extract outside the Windows temporary folder: $stage"
}
try {
    [IO.Compression.ZipFile]::ExtractToDirectory($zipPath, $stage)
    foreach ($module in $expectedModules) {
        $source = Join-Path $stage $module
        if (-not (Test-Path -LiteralPath (Join-Path $source '__manifest__.py'))) {
            throw "The ZIP is missing the manifest for addon '$module'."
        }
        Copy-Item -LiteralPath $source -Destination (Join-Path $destinationRoot $module) -Recurse
    }
} finally {
    Remove-Item -LiteralPath $stage -Recurse -Force -ErrorAction SilentlyContinue
}

Restart-Service -Name $serviceName -Force
(Get-Service -Name $serviceName).WaitForStatus('Running', [TimeSpan]::FromSeconds(45))
Start-Sleep -Seconds 4

$body = @{ jsonrpc = '2.0'; method = 'call'; params = @{ service = 'common'; method = 'version'; args = @() }; id = 1 } | ConvertTo-Json -Depth 5
$version = (Invoke-RestMethod -Uri 'http://127.0.0.1:8069/jsonrpc' -Method Post -ContentType 'application/json' -Body $body -TimeoutSec 15).result.server_version
if (-not $version.StartsWith('19.0')) { throw "Expected Odoo 19 after restart; received '$version'." }

Write-Output "Installed Accounting addons: $($expectedModules -join ', ')"
Write-Output "Odoo version: $version"
Write-Output 'POS Sales Dashboard was already installed and was left unchanged.'
