$ErrorActionPreference = 'Stop'

$odooRoot = 'C:\Program Files\Odoo 19.0.20260701'
$configPath = Join-Path $odooRoot 'server\odoo.conf'
$odooBin = Join-Path $odooRoot 'server\odoo-bin'
$pythonPath = Join-Path $odooRoot 'python\python.exe'
$database = 'pos_db_odoo19_copy'

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

$shellFile = Join-Path $env:TEMP ("odoo19-contact-access.{0}.py" -f [guid]::NewGuid().ToString('N'))
$shellCode = @'
import sys
from odoo import Command

target_db = "pos_db_odoo19_copy"
if env.cr.dbname != target_db:
    print("Refusing to modify unexpected database: %s" % env.cr.dbname, file=sys.stderr)
    sys.exit(2)

users = env["res.users"].sudo().search([("login", "=", "admin")])
if len(users) != 1 or not users.active:
    print("Expected one active Odoo login named 'admin'; no access was changed.", file=sys.stderr)
    sys.exit(3)

groups = env["res.groups"].sudo()
internal_user = env.ref("base.group_user")
contact_creation = env.ref("base.group_partner_manager")
portal_user = env.ref("base.group_portal")
public_user = env.ref("base.group_public")
users.write({"group_ids": [
    Command.unlink(portal_user.id),
    Command.unlink(public_user.id),
    Command.link(internal_user.id),
    Command.link(contact_creation.id),
]})
env.cr.commit()
print("Converted Odoo login 'admin' to an internal user and granted Contact / Creation in pos_db_odoo19_copy.")
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
        throw "Odoo shell exited with code $LASTEXITCODE. No access change is confirmed."
    }
} finally {
    Remove-Item -LiteralPath $shellFile -Force -ErrorAction SilentlyContinue
}
