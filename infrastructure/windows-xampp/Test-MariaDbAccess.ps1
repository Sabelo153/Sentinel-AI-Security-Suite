#Requires -RunAsAdministrator
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$client = 'C:\Program Files\MariaDB 12.3\bin\mariadb.exe'
$result = 'C:\Development\Sentinel-AI-Security-Suite\storage\logs\mariadb-access-test.txt'

$arguments = @('--protocol=tcp','--skip-ssl','--host=127.0.0.1','--port=3306','--user=root')
if (Get-Service SentinelMariaDB -ErrorAction SilentlyContinue) {
    $adminSettings = 'C:\ProgramData\SentinelDatabase\private\admin-client.ini'
    if (-not (Test-Path -LiteralPath $adminSettings)) { throw 'Replacement administrator settings are unavailable.' }
    $arguments = @("--defaults-file=$adminSettings", '--protocol=tcp', '--skip-ssl', '--host=127.0.0.1', '--port=3307')
}
& $client @arguments --batch --skip-column-names -e "SELECT VERSION(), CURRENT_USER(); SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'sentinel%';" 2>&1 |
    Set-Content -LiteralPath $result -Encoding UTF8

if ($LASTEXITCODE -ne 0) {
    throw 'MariaDB administrative authentication failed.'
}
