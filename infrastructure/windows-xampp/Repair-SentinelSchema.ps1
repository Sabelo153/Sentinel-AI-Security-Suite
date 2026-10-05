#Requires -RunAsAdministrator
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$client = 'C:\Program Files\MariaDB 12.3\bin\mariadb.exe'
$schemaDirectory = 'C:\Development\Sentinel-AI-Security-Suite\database\schema'
$log = 'C:\Development\Sentinel-AI-Security-Suite\storage\logs\schema-repair.log'
$arguments = @('--protocol=tcp','--skip-ssl','--host=127.0.0.1','--port=3306','--user=root')
$adminSettings = 'C:\ProgramData\SentinelDatabase\private\admin-client.ini'
if (Get-Service SentinelMariaDB -ErrorAction SilentlyContinue) {
    if (-not (Test-Path -LiteralPath $adminSettings)) { throw 'Replacement administrator settings are unavailable.' }
    $arguments = @("--defaults-file=$adminSettings", '--protocol=tcp', '--skip-ssl', '--host=127.0.0.1', '--port=3307')
}
$files = @(
    '039_employee_assignments.sql',
    '040_assignment_scopes.sql',
    '041_delegations.sql',
    '044_business_objects.sql',
    '045_object_relationships.sql',
    '046_gps_locations.sql',
    '048_gps_events.sql',
    '049_activity_logs.sql',
    '050_guard_site_assignments.sql',
    '051_patrol_sessions.sql',
    '052_patrol_checkpoint_events.sql',
    '054_device_assignments.sql',
    '055_device_sessions.sql',
    '056_incidents.sql',
    '057_occurrence_book.sql',
    '058_visitors.sql',
    '059_vehicles_and_access_events.sql',
    '060_inspections.sql',
    '061_alerts.sql',
    '062_notifications.sql',
    '063_mobile_operations.sql',
    '064_company_subscriptions.sql',
    '065_patrol_schedule_windows.sql'
)

Set-Content -LiteralPath $log -Value ("Schema repair started " + (Get-Date).ToString('o')) -Encoding UTF8
Push-Location $schemaDirectory
try {
    foreach ($file in $files) {
        Add-Content -LiteralPath $log -Value ("`r`n### " + $file)
        $previousPreference = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        $output = & $client @arguments -e ("SOURCE " + $file) 2>&1
        $exitCode = $LASTEXITCODE
        $ErrorActionPreference = $previousPreference
        if ($output) { $output | Add-Content -LiteralPath $log }
        if ($exitCode -ne 0) {
            throw ("Schema repair failed at " + $file + ". See " + $log)
        }
    }
} finally {
    Pop-Location
}

& $client @arguments -e "SOURCE C:/Development/Sentinel-AI-Security-Suite/database/seed/production/005_platform_kernel_reference_data.sql" 2>&1 | Add-Content -LiteralPath $log
if ($LASTEXITCODE -ne 0) {
    throw ("Reference data seed failed. See " + $log)
}

Write-Host 'All missing Sentinel schema files were applied.' -ForegroundColor Green
