#Requires -RunAsAdministrator
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$apache = 'C:\xampp\apache\bin\httpd.exe'
$log = 'C:\Development\Sentinel-AI-Security-Suite\storage\logs\windows-host-setup.log'

New-Item -ItemType Directory -Path (Split-Path -Parent $log) -Force | Out-Null
Start-Transcript -Path $log -Append | Out-Null

try {
    & $apache -t
    if ($LASTEXITCODE -ne 0) {
        throw 'Apache configuration validation failed.'
    }

    $service = Get-Service -Name 'Apache2.4' -ErrorAction SilentlyContinue
    if ($null -eq $service) {
        # Stop a manually launched copy before registering the same binary as a
        # service. There is no service control endpoint until installation.
        Get-Process -Name 'httpd' -ErrorAction SilentlyContinue |
            Stop-Process -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 2
        & $apache -k install
        if ($LASTEXITCODE -ne 0) {
            throw 'Apache service installation failed.'
        }
    }

    Set-Service -Name 'Apache2.4' -StartupType Automatic
    Start-Service -Name 'Apache2.4'

    $ruleName = 'Sentinel Web (Private HTTP)'
    if ($null -eq (Get-NetFirewallRule -DisplayName $ruleName -ErrorAction SilentlyContinue)) {
        New-NetFirewallRule `
            -DisplayName $ruleName `
            -Direction Inbound `
            -Action Allow `
            -Protocol TCP `
            -LocalPort 80 `
            -Profile Private | Out-Null
    }

    Write-Host 'Sentinel Apache hosting is enabled for the private network.' -ForegroundColor Green
} finally {
    Stop-Transcript | Out-Null
}
