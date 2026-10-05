#Requires -RunAsAdministrator
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
if (Get-Service SentinelMariaDB -ErrorAction SilentlyContinue) {
    throw 'Sentinel already uses the replacement database. Use Repair-SentinelSchema.ps1 for schema maintenance; do not reprovision the retired instance.'
}
$client = 'C:\Program Files\MariaDB 12.3\bin\mariadb.exe'
$serverConfig = 'C:\Program Files\MariaDB 12.3\data\my.ini'
$project = 'C:\Development\Sentinel-AI-Security-Suite'
$environmentFile = Join-Path $project '.env'
$schemaRunner = 'C:/Development/Sentinel-AI-Security-Suite/database/schema/000_run_all.sql'
$referenceData = 'C:/Development/Sentinel-AI-Security-Suite/database/seed/production/005_platform_kernel_reference_data.sql'
$log = Join-Path $project 'storage\logs\database-provisioning.log'
$baseArguments = @('--protocol=tcp','--skip-ssl','--host=127.0.0.1','--port=3306','--user=root','--batch','--skip-column-names')

New-Item -ItemType Directory -Path (Split-Path -Parent $log) -Force | Out-Null
Start-Transcript -Path $log -Append | Out-Null

try {
    $schemaExists = (& $client @baseArguments -e "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='sentinel_ai_security';" | Select-Object -Last 1).Trim()
    if ($LASTEXITCODE -ne 0) {
        throw 'MariaDB administrative authentication failed.'
    }

    if ($schemaExists -eq '0') {
        Push-Location (Join-Path $project 'database\schema')
        try {
            & $client @baseArguments -e "SOURCE $schemaRunner" | Out-Null
            if ($LASTEXITCODE -ne 0) {
                throw 'Canonical Sentinel schema creation failed.'
            }
        } finally {
            Pop-Location
        }
    }

    # Global reference data is required by tenant-scoped employee assignments.
    # It is idempotent, so it also repairs an older local database that already
    # has tables but lacks the supported authority levels.
    & $client @baseArguments -e ("SOURCE " + $referenceData) | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'Sentinel reference data provisioning failed.'
    }

    $bytes = New-Object byte[] 48
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    $databasePassword = ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
    $temporarySql = Join-Path $env:TEMP ("sentinel-db-user-" + [Guid]::NewGuid().ToString('N') + '.sql')

    try {
        @"
CREATE USER IF NOT EXISTS 'sentinel_app'@'127.0.0.1' IDENTIFIED BY '$databasePassword';
ALTER USER 'sentinel_app'@'127.0.0.1' IDENTIFIED BY '$databasePassword';
GRANT SELECT, INSERT, UPDATE, DELETE ON sentinel_ai_security.* TO 'sentinel_app'@'127.0.0.1';
FLUSH PRIVILEGES;
"@ | Set-Content -LiteralPath $temporarySql -Encoding ASCII
        & $client @baseArguments -e ("SOURCE " + $temporarySql.Replace('\','/')) | Out-Null
        if ($LASTEXITCODE -ne 0) {
            throw 'Sentinel application database account creation failed.'
        }
    } finally {
        Remove-Item -LiteralPath $temporarySql -Force -ErrorAction SilentlyContinue
    }

    $lines = Get-Content -LiteralPath $environmentFile
    $replacements = @{
        DB_HOST = '127.0.0.1'
        DB_PORT = '3306'
        DB_DATABASE = 'sentinel_ai_security'
        DB_USERNAME = 'sentinel_app'
        DB_PASSWORD = $databasePassword
    }
    foreach ($name in $replacements.Keys) {
        $replaced = $false
        for ($index = 0; $index -lt $lines.Count; $index++) {
            if ($lines[$index] -match ('^' + [regex]::Escape($name) + '=')) {
                $lines[$index] = $name + '=' + $replacements[$name]
                $replaced = $true
                break
            }
        }
        if (-not $replaced) { $lines += $name + '=' + $replacements[$name] }
    }
    Set-Content -LiteralPath $environmentFile -Value $lines -Encoding UTF8

    $serverLines = Get-Content -LiteralPath $serverConfig
    if (-not ($serverLines -match '^bind-address\s*=\s*127\.0\.0\.1\s*$')) {
        $backup = $serverConfig + '.sentinel-backup'
        if (-not (Test-Path -LiteralPath $backup)) {
            Copy-Item -LiteralPath $serverConfig -Destination $backup
        }
        $mysqldLine = [Array]::IndexOf($serverLines, '[mysqld]')
        if ($mysqldLine -lt 0) { throw 'MariaDB server configuration has no [mysqld] section.' }
        $before = if ($mysqldLine -ge 0) { $serverLines[0..$mysqldLine] } else { @() }
        $after = if ($mysqldLine + 1 -lt $serverLines.Count) { $serverLines[($mysqldLine + 1)..($serverLines.Count - 1)] } else { @() }
        $serverLines = @($before) + 'bind-address=127.0.0.1' + @($after)
        Set-Content -LiteralPath $serverConfig -Value $serverLines -Encoding ASCII
        Restart-Service -Name 'MariaDB'
    }

    $serviceProcessId = (Get-CimInstance Win32_Service -Filter "Name='MariaDB'").ProcessId
    Get-Process -Name 'mysqld' -ErrorAction SilentlyContinue |
        Where-Object { $_.Id -ne $serviceProcessId } |
        Stop-Process -Force -ErrorAction SilentlyContinue

    $tableCount = & $client @baseArguments -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='sentinel_ai_security';"
    Write-Host ("Sentinel database is ready with {0} tables." -f ($tableCount | Select-Object -Last 1)) -ForegroundColor Green
} finally {
    Stop-Transcript | Out-Null
}
