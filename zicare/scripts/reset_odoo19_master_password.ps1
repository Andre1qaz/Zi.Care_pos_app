$ErrorActionPreference = 'Stop'

$odooRoot = 'C:\Program Files\Odoo 19.0.20260701'
$configPath = Join-Path $odooRoot 'server\odoo.conf'
$pythonPath = Join-Path $odooRoot 'python\python.exe'
$serviceName = 'odoo-server-19.0'

$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script from PowerShell opened with Run as administrator.'
}
foreach ($path in @($configPath, $pythonPath)) {
    if (-not (Test-Path -LiteralPath $path)) { throw "Required path not found: $path" }
}

$configText = [IO.File]::ReadAllText($configPath)
$adminPasswordPattern = '(?im)^\s*admin_passwd\s*='
if ([regex]::Matches($configText, $adminPasswordPattern).Count -ne 1) {
    throw 'Expected exactly one admin_passwd setting in the local Odoo 19 config.'
}

$newPassword = Read-Host 'Enter a new Odoo database master password' -AsSecureString
$confirmPassword = Read-Host 'Enter it again to confirm' -AsSecureString
if ($newPassword.Length -lt 12) { throw 'Use a master password with at least 12 characters.' }

$passwordBstr = [IntPtr]::Zero
$confirmBstr = [IntPtr]::Zero
$plainPassword = $null
$confirmPlainPassword = $null
$hashScriptPath = Join-Path $env:TEMP ("odoo19-master-hash.{0}.py" -f [guid]::NewGuid().ToString('N'))
try {
    $passwordBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($newPassword)
    $confirmBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($confirmPassword)
    $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordBstr)
    $confirmPlainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmBstr)
    if ($plainPassword -ne $confirmPlainPassword) { throw 'The entered passwords do not match.' }
    if ($plainPassword.Contains("`r") -or $plainPassword.Contains("`n")) {
        throw 'The master password cannot contain a newline.'
    }

    $hashCode = @'
import sys
from passlib.context import CryptContext
password = sys.stdin.readline().rstrip("\r\n")
context = CryptContext(schemes=["pbkdf2_sha512", "plaintext"], deprecated=["plaintext"], pbkdf2_sha512__rounds=600000)
print(context.hash(password))
'@
    [IO.File]::WriteAllText($hashScriptPath, $hashCode, [Text.UTF8Encoding]::new($false))

    $startInfo = [Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = $pythonPath
    $startInfo.Arguments = '-s "' + $hashScriptPath + '"'
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardInput = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $process = [Diagnostics.Process]::new()
    $process.StartInfo = $startInfo
    if (-not $process.Start()) { throw 'Could not start Odoo Python to hash the new master password.' }
    $process.StandardInput.WriteLine($plainPassword)
    $process.StandardInput.Close()
    $hash = $process.StandardOutput.ReadToEnd().Trim()
    $pythonError = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0 -or $hash -notmatch '^\$pbkdf2-sha512\$') {
        throw "Could not create the Odoo password hash. $pythonError"
    }
} finally {
    if ($passwordBstr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordBstr) }
    if ($confirmBstr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmBstr) }
    $plainPassword = $null
    $confirmPlainPassword = $null
    Remove-Item -LiteralPath $hashScriptPath -Force -ErrorAction SilentlyContinue
}

$configBackup = Join-Path $env:TEMP ("odoo19.conf.before-master-reset.{0}.bak" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
Copy-Item -LiteralPath $configPath -Destination $configBackup
try {
    $replacement = [System.Text.RegularExpressions.MatchEvaluator]{ param($match) "admin_passwd = $hash" }
    $newConfig = [regex]::Replace($configText, '(?im)^\s*admin_passwd\s*=.*$', $replacement, 1)
    [IO.File]::WriteAllText($configPath, $newConfig, [Text.UTF8Encoding]::new($false))

    Restart-Service -Name $serviceName -Force
    (Get-Service -Name $serviceName).WaitForStatus('Running', [TimeSpan]::FromSeconds(30))
    Start-Sleep -Seconds 4
    $body = @{ jsonrpc = '2.0'; method = 'call'; params = @{ service = 'common'; method = 'version'; args = @() }; id = 1 } | ConvertTo-Json -Depth 5
    $version = (Invoke-RestMethod -Uri 'http://127.0.0.1:8069/jsonrpc' -Method Post -ContentType 'application/json' -Body $body -TimeoutSec 10).result.server_version
    if (-not $version.StartsWith('19.0')) { throw "Expected Odoo 19 on port 8069; received '$version'." }
} catch {
    Copy-Item -LiteralPath $configBackup -Destination $configPath -Force
    try { Restart-Service -Name $serviceName -Force } catch { }
    throw
}

Write-Output "Odoo master password updated. Odoo version: $version"
Write-Output "Config backup: $configBackup"
