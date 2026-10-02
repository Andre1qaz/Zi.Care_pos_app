$ErrorActionPreference = 'Stop'

$odooRoot = 'C:\Program Files\Odoo 19.0.20260701'
$configPath = Join-Path $odooRoot 'server\odoo.conf'
$odooBin = Join-Path $odooRoot 'server\odoo-bin'
$pythonPath = Join-Path $odooRoot 'python\python.exe'
$database = 'pos_db_odoo19_copy'
$serviceName = 'odoo-server-19.0'

$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Open PowerShell with Run as administrator, then run this script again.'
}

foreach ($path in @($configPath, $odooBin, $pythonPath)) {
    if (-not (Test-Path -LiteralPath $path)) {
        throw "Required Odoo 19 path not found: $path"
    }
}

$service = Get-Service -Name $serviceName -ErrorAction Stop
if ($service.Status -ne 'Running') {
    throw "Odoo 19 service '$serviceName' is not running. Start it, then run this script again."
}

# The temporary shell file prompts directly, so the new password is never
# written to a file or included in this PowerShell process's command line.
$shellFile = Join-Path $env:TEMP ("odoo19-reset-admin.{0}.py" -f [guid]::NewGuid().ToString('N'))
$shellCode = @'
import getpass
import sys

target_db = "pos_db_odoo19_copy"
if env.cr.dbname != target_db:
    print("Refusing to modify unexpected database: %s" % env.cr.dbname, file=sys.stderr)
    sys.exit(2)

users = env["res.users"].sudo().search([("login", "=", "admin")])
if len(users) != 1 or not users.active:
    print("Expected one active Odoo login named 'admin'; no password was changed.", file=sys.stderr)
    sys.exit(3)

password = getpass.getpass("Password baru untuk akun Odoo admin (min. 12 karakter): ")
confirmation = getpass.getpass("Masukkan lagi untuk konfirmasi: ")
if password != confirmation:
    print("Password tidak sama; tidak ada perubahan.", file=sys.stderr)
    sys.exit(4)
if len(password) < 12:
    print("Gunakan password minimal 12 karakter; tidak ada perubahan.", file=sys.stderr)
    sys.exit(5)
if "\n" in password or "\r" in password:
    print("Password tidak boleh berisi baris baru; tidak ada perubahan.", file=sys.stderr)
    sys.exit(6)

users.write({"password": password})
env.cr.commit()
print("Password akun Odoo 'admin' berhasil diganti pada database pos_db_odoo19_copy.")
sys.exit(0)
'@

try {
    [IO.File]::WriteAllText($shellFile, $shellCode, [Text.UTF8Encoding]::new($false))
    $arguments = @(
        $odooBin,
        'shell',
        '--config', $configPath,
        '--database', $database,
        '--no-http',
        '--shell-file', $shellFile
    )
    & $pythonPath @arguments
    if ($LASTEXITCODE -ne 0) {
        throw "Odoo shell exited with code $LASTEXITCODE. The password may not have been changed."
    }
} finally {
    Remove-Item -LiteralPath $shellFile -Force -ErrorAction SilentlyContinue
}
