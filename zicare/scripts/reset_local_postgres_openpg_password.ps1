$ErrorActionPreference = 'Stop'

$odooRoot = 'C:\Program Files\Odoo 19.0.20260701'
$odooConfig = Join-Path $odooRoot 'server\odoo.conf'
$pythonPath = Join-Path $odooRoot 'python\python.exe'
$postgresData = 'C:\Program Files\PostgreSQL\18\data'
$hbaPath = Join-Path $postgresData 'pg_hba.conf'
$postgresService = 'postgresql-x64-18'
$odooService = 'odoo-server-19.0'
$marker = '# Temporary local Odoo database password recovery'

$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script from PowerShell opened with Run as administrator.'
}
foreach ($path in @($odooConfig, $pythonPath, $hbaPath)) {
    if (-not (Test-Path -LiteralPath $path)) { throw "Required path not found: $path" }
}

function Get-ConfigValue([string]$text, [string]$key) {
    $pattern = '(?im)^[ \t]*' + [regex]::Escape($key) + '[ \t]*=[ \t]*(.*?)[ \t]*$'
    $match = [regex]::Match($text, $pattern)
    if ($match.Success) { return $match.Groups[1].Value.Trim() }
    return ''
}

$odooConfigText = [IO.File]::ReadAllText($odooConfig)
$dbUser = Get-ConfigValue $odooConfigText 'db_user'
$dbHost = Get-ConfigValue $odooConfigText 'db_host'
$dbPort = Get-ConfigValue $odooConfigText 'db_port'
if ($dbUser -notmatch '^[A-Za-z_][A-Za-z0-9_$]*$') { throw 'Unexpected PostgreSQL role name in Odoo 19 config.' }
if ($dbHost -notin @('localhost', '127.0.0.1', '::1') -or $dbPort -ne '5432') {
    throw 'This recovery script only supports the configured local PostgreSQL server on port 5432.'
}

$originalHba = [IO.File]::ReadAllText($hbaPath)
if ($originalHba.Contains($marker)) { throw 'A previous temporary recovery rule is still present; inspect pg_hba.conf first.' }
$newPassword = Read-Host 'Enter a new PostgreSQL password for the local Odoo database user' -AsSecureString
$confirmPassword = Read-Host 'Enter it again to confirm' -AsSecureString
if ($newPassword.Length -lt 12) { throw 'Use a PostgreSQL password with at least 12 characters.' }

$passwordBstr = [IntPtr]::Zero
$confirmBstr = [IntPtr]::Zero
$plainPassword = $null
$confirmPlainPassword = $null
$pythonScriptPath = Join-Path $env:TEMP ("odoo19-postgres-recovery.{0}.py" -f [guid]::NewGuid().ToString('N'))
$pgHbaBackup = Join-Path $env:TEMP ("pg_hba.before-openpg-reset.{0}.bak" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
$odooConfigBackup = Join-Path $env:TEMP ("odoo19.conf.before-openpg-reset.{0}.bak" -f (Get-Date -Format 'yyyyMMdd-HHmmss'))
$temporaryRulesAdded = $false
$rolePasswordChanged = $false
$odooConfigChanged = $false
$odooWasRunning = (Get-Service -Name $odooService -ErrorAction Stop).Status -eq 'Running'

try {
    $passwordBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($newPassword)
    $confirmBstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($confirmPassword)
    $plainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($passwordBstr)
    $confirmPlainPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($confirmBstr)
    if ($plainPassword -ne $confirmPlainPassword) { throw 'The entered passwords do not match.' }
    if ($plainPassword.Contains("`r") -or $plainPassword.Contains("`n")) {
        throw 'The password cannot contain a newline.'
    }
    if ($plainPassword -notmatch '^[\x20-\x7E]+$') {
        throw 'Use printable ASCII characters for this PostgreSQL password.'
    }

    $pythonCode = @'
import sys
import psycopg2
from psycopg2 import extensions, sql

mode, role, port = sys.argv[1:4]
password = sys.stdin.readline().rstrip("\r\n")
admin_role = "postgres"
options = dict(host="localhost", port=int(port), user=admin_role if mode == "set" else role,
               dbname="postgres", connect_timeout=10)
if mode == "verify":
    options["password"] = password
connection = psycopg2.connect(**options)
try:
    if mode == "set":
        connection.autocommit = True
        quoted_password = extensions.adapt(password).getquoted().decode("ascii")
        with connection.cursor() as cursor:
            cursor.execute("SELECT 1 FROM pg_roles WHERE rolname = %s", (role,))
            action = "ALTER ROLE" if cursor.fetchone() else "CREATE ROLE"
            statement = sql.SQL("{} {} WITH LOGIN SUPERUSER CREATEDB CREATEROLE PASSWORD {}").format(
                sql.SQL(action), sql.Identifier(role), sql.SQL(quoted_password)
            )
            cursor.execute(statement)
    elif mode == "verify":
        with connection.cursor() as cursor:
            cursor.execute("SELECT 1")
            assert cursor.fetchone() == (1,)
    else:
        raise ValueError("Unknown operation")
finally:
    connection.close()
print("OK")
'@
    [IO.File]::WriteAllText($pythonScriptPath, $pythonCode, [Text.UTF8Encoding]::new($false))
    Copy-Item -LiteralPath $hbaPath -Destination $pgHbaBackup
    Copy-Item -LiteralPath $odooConfig -Destination $odooConfigBackup

    if ($odooWasRunning) {
        Stop-Service -Name $odooService -Force
        (Get-Service -Name $odooService).WaitForStatus('Stopped', [TimeSpan]::FromSeconds(30))
    }

    $hbaLines = @(
        $marker,
        'host all postgres 127.0.0.1/32 trust',
        'host all postgres ::1/128 trust'
    )
    $temporaryRulesAdded = $true
    [IO.File]::WriteAllText($hbaPath, (($hbaLines -join "`r`n") + "`r`n" + $originalHba), [Text.UTF8Encoding]::new($false))

    Restart-Service -Name $postgresService -Force
    (Get-Service -Name $postgresService).WaitForStatus('Running', [TimeSpan]::FromSeconds(60))

    $startInfo = [Diagnostics.ProcessStartInfo]::new()
    $startInfo.FileName = $pythonPath
    $startInfo.Arguments = '-s "' + $pythonScriptPath + '" set "' + $dbUser + '" ' + $dbPort
    $startInfo.UseShellExecute = $false
    $startInfo.CreateNoWindow = $true
    $startInfo.RedirectStandardInput = $true
    $startInfo.RedirectStandardOutput = $true
    $startInfo.RedirectStandardError = $true
    $process = [Diagnostics.Process]::new()
    $process.StartInfo = $startInfo
    if (-not $process.Start()) { throw 'Could not start Odoo Python to update the PostgreSQL role.' }
    $process.StandardInput.WriteLine($plainPassword)
    $process.StandardInput.Close()
    $pythonOutput = $process.StandardOutput.ReadToEnd().Trim()
    $pythonError = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    if ($process.ExitCode -ne 0 -or $pythonOutput -ne 'OK') {
        throw "Could not update the local PostgreSQL role. $pythonError"
    }
    $rolePasswordChanged = $true

    $dbPasswordPattern = '(?im)^[ \t]*db_password[ \t]*=.*$'
    if ([regex]::IsMatch($odooConfigText, $dbPasswordPattern)) {
        $replacement = [System.Text.RegularExpressions.MatchEvaluator]{ param($match) "db_password = $plainPassword" }
        $newOdooConfig = [regex]::Replace($odooConfigText, $dbPasswordPattern, $replacement, 1)
    } else {
        $newOdooConfig = $odooConfigText.TrimEnd() + "`r`ndb_password = $plainPassword`r`n"
    }
    [IO.File]::WriteAllText($odooConfig, $newOdooConfig, [Text.UTF8Encoding]::new($false))
    $odooConfigChanged = $true

    Copy-Item -LiteralPath $pgHbaBackup -Destination $hbaPath -Force
    $temporaryRulesAdded = $false
    Restart-Service -Name $postgresService -Force
    (Get-Service -Name $postgresService).WaitForStatus('Running', [TimeSpan]::FromSeconds(60))

    $verifyInfo = [Diagnostics.ProcessStartInfo]::new()
    $verifyInfo.FileName = $pythonPath
    $verifyInfo.Arguments = '-s "' + $pythonScriptPath + '" verify "' + $dbUser + '" ' + $dbPort
    $verifyInfo.UseShellExecute = $false
    $verifyInfo.CreateNoWindow = $true
    $verifyInfo.RedirectStandardInput = $true
    $verifyInfo.RedirectStandardOutput = $true
    $verifyInfo.RedirectStandardError = $true
    $verifyProcess = [Diagnostics.Process]::new()
    $verifyProcess.StartInfo = $verifyInfo
    if (-not $verifyProcess.Start()) { throw 'Could not start the PostgreSQL credential verification.' }
    $verifyProcess.StandardInput.WriteLine($plainPassword)
    $verifyProcess.StandardInput.Close()
    $verifyOutput = $verifyProcess.StandardOutput.ReadToEnd().Trim()
    $verifyError = $verifyProcess.StandardError.ReadToEnd()
    $verifyProcess.WaitForExit()
    if ($verifyProcess.ExitCode -ne 0 -or $verifyOutput -ne 'OK') {
        throw "PostgreSQL rejected the new password after restoring normal access rules. $verifyError"
    }

    Restart-Service -Name $odooService -Force
    (Get-Service -Name $odooService).WaitForStatus('Running', [TimeSpan]::FromSeconds(30))
    Start-Sleep -Seconds 4
    $body = @{ jsonrpc = '2.0'; method = 'call'; params = @{ service = 'db'; method = 'list'; args = @() }; id = 1 } | ConvertTo-Json -Depth 5
    $response = Invoke-RestMethod -Uri 'http://127.0.0.1:8069/jsonrpc' -Method Post -ContentType 'application/json' -Body $body -TimeoutSec 10
    if ($response.error) { throw 'Odoo 19 could not list databases after the PostgreSQL password change.' }
} catch {
    if ($temporaryRulesAdded) {
        Copy-Item -LiteralPath $pgHbaBackup -Destination $hbaPath -Force
        try {
            Restart-Service -Name $postgresService -Force
            (Get-Service -Name $postgresService).WaitForStatus('Running', [TimeSpan]::FromSeconds(60))
        } catch { }
        $temporaryRulesAdded = $false
    } elseif ($rolePasswordChanged) {
        try {
            Copy-Item -LiteralPath $pgHbaBackup -Destination $hbaPath -Force
            Restart-Service -Name $postgresService -Force
            (Get-Service -Name $postgresService).WaitForStatus('Running', [TimeSpan]::FromSeconds(60))
        } catch { }
    }
    if ($rolePasswordChanged -and -not $odooConfigChanged) {
        try {
            $dbPasswordPattern = '(?im)^[ \t]*db_password[ \t]*=.*$'
            if ([regex]::IsMatch($odooConfigText, $dbPasswordPattern)) {
                $replacement = [System.Text.RegularExpressions.MatchEvaluator]{ param($match) "db_password = $plainPassword" }
                $recoveryConfig = [regex]::Replace($odooConfigText, $dbPasswordPattern, $replacement, 1)
            } else {
                $recoveryConfig = $odooConfigText.TrimEnd() + "`r`ndb_password = $plainPassword`r`n"
            }
            [IO.File]::WriteAllText($odooConfig, $recoveryConfig, [Text.UTF8Encoding]::new($false))
            Restart-Service -Name $odooService -Force
        } catch { }
    }
    if ($odooWasRunning) {
        try { Start-Service -Name $odooService } catch { }
    }
    throw
} finally {
    if ($passwordBstr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($passwordBstr) }
    if ($confirmBstr -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($confirmBstr) }
    $plainPassword = $null
    $confirmPlainPassword = $null
    Remove-Item -LiteralPath $pythonScriptPath -Force -ErrorAction SilentlyContinue
}

Write-Output 'PostgreSQL password updated and verified for Odoo 19.'
Write-Output 'The temporary PostgreSQL authentication rules were removed.'
Write-Output "pg_hba.conf backup: $pgHbaBackup"
Write-Output "Odoo 19 config backup: $odooConfigBackup"
