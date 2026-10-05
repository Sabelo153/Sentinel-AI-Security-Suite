[CmdletBinding()]
param(
    [string] $ServerUrl = 'http://127.0.0.1'
)

$ErrorActionPreference = 'Stop'
$project = 'C:\Development\Sentinel-AI-Security-Suite'
$privateDirectory = Join-Path $project 'storage\private'
$logDirectory = Join-Path $project 'storage\logs'
$credentialPath = Join-Path $privateDirectory 'manual-test-account.json'
$reportPath = Join-Path $logDirectory 'manual-server-smoke-test.json'
New-Item -ItemType Directory -Path $privateDirectory, $logDirectory -Force | Out-Null

if (Test-Path -LiteralPath $credentialPath) {
    $credentials = Get-Content -LiteralPath $credentialPath -Raw | ConvertFrom-Json
} else {
    $bytes = New-Object byte[] 12
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generator.GetBytes($bytes) } finally { $generator.Dispose() }
    $password = 'St!' + ([BitConverter]::ToString($bytes)).Replace('-', '').ToLowerInvariant()
    $credentials = [pscustomobject]@{
        server_url = $ServerUrl
        company = 'Sentinel Manual Test'
        site = 'Manual Test Site'
        email = 'manual.owner@sentinel.local'
        password = $password
    }

    $signupBody = @{
        plan_code = 'FREE'
        billing_market = 'ZA'
        company_name = $credentials.company
        site_name = $credentials.site
        first_name = 'Manual'
        last_name = 'Tester'
        email = $credentials.email
        phone = '+27 10 000 0000'
        password = $credentials.password
    } | ConvertTo-Json

    Invoke-RestMethod `
        -Uri ($ServerUrl + '/api/v1/auth/signup') `
        -Method Post `
        -ContentType 'application/json' `
        -Headers @{ Accept = 'application/json' } `
        -Body $signupBody `
        -TimeoutSec 20 | Out-Null

    $credentials | ConvertTo-Json | Set-Content -LiteralPath $credentialPath -Encoding UTF8
}

$loginBody = @{
    identifier = $credentials.email
    password = $credentials.password
} | ConvertTo-Json
$login = Invoke-RestMethod `
    -Uri ($ServerUrl + '/api/v1/auth/login') `
    -Method Post `
    -ContentType 'application/json' `
    -Headers @{ Accept = 'application/json' } `
    -Body $loginBody `
    -TimeoutSec 20
$token = $login.data.token
if ([string]::IsNullOrWhiteSpace($token)) { throw 'Login returned no access token.' }

$headers = @{ Accept = 'application/json'; Authorization = 'Bearer ' + $token }
$checks = @(
    '/api/v1/auth/me',
    '/api/v1/auth/tenant',
    '/api/v1/subscription',
    '/api/v1/dashboard/summary',
    '/api/v1/occurrence-entries',
    '/api/v1/incidents',
    '/api/v1/visitors'
)
$results = foreach ($path in $checks) {
    try {
        $response = Invoke-WebRequest -Uri ($ServerUrl + $path) -Headers $headers -UseBasicParsing -TimeoutSec 20
        [pscustomobject]@{ path = $path; status = [int] $response.StatusCode; passed = $response.StatusCode -eq 200 }
    } catch {
        $status = if ($_.Exception.Response) { [int] $_.Exception.Response.StatusCode } else { 0 }
        [pscustomobject]@{ path = $path; status = $status; passed = $false }
    }
}

$report = [pscustomobject]@{
    tested_at = (Get-Date).ToString('o')
    server_url = $ServerUrl
    passed = -not ($results.passed -contains $false)
    requests = $results
}
$report | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $reportPath -Encoding UTF8
$report

if (-not $report.passed) { exit 1 }
