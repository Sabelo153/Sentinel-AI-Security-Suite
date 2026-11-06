param(
    [Parameter(Mandatory=$true)][string]$ApkPath,
    [Parameter(Mandatory=$true)][string]$BuildToolsDirectory,
    [string]$DestinationDirectory = (Join-Path $PSScriptRoot '../public/downloads')
)
$ErrorActionPreference='Stop'
$resolvedApk=(Resolve-Path -LiteralPath $ApkPath).Path
$resolvedTools=(Resolve-Path -LiteralPath $BuildToolsDirectory).Path
$signer=Join-Path $resolvedTools 'apksigner.bat'
$aapt=Join-Path $resolvedTools 'aapt2.exe'
if(-not (Test-Path -LiteralPath $signer) -or -not (Test-Path -LiteralPath $aapt)){throw 'Android build tools with apksigner and aapt2 are required.'}
$verification=& $signer verify --verbose --print-certs $resolvedApk 2>&1
if($LASTEXITCODE -ne 0){throw 'APK signature verification failed.'}
if(($verification -join "`n") -match 'CN=Android Debug'){throw 'Do not publish a debug APK as the company production release.'}
$badging=& $aapt dump badging $resolvedApk 2>&1
if($LASTEXITCODE -ne 0){throw 'Cannot read APK package metadata.'}
$metadata=[regex]::Match(($badging -join "`n"),"package: name='([^']+)' versionCode='([0-9]+)' versionName='([^']+)'")
if(-not $metadata.Success -or $metadata.Groups[1].Value -ne 'com.sentinel.guard'){throw 'This is not the Sentinel Guard package.'}
$certificate=[regex]::Match(($verification -join "`n"),'Signer #1 certificate SHA-256 digest: ([a-fA-F0-9:]+)')
if(-not $certificate.Success){throw 'Signing certificate fingerprint is missing.'}
$target=[IO.Path]::GetFullPath($DestinationDirectory)
New-Item -ItemType Directory -Force -Path $target | Out-Null
$targetApk=Join-Path $target 'sentinel-guard.apk'
if($resolvedApk -ne $targetApk){Copy-Item -LiteralPath $resolvedApk -Destination $targetApk -Force}
$release=[ordered]@{
    apk='sentinel-guard.apk'
    versionCode=[long]$metadata.Groups[2].Value
    versionName=$metadata.Groups[3].Value
    sha256=(Get-FileHash -LiteralPath $targetApk -Algorithm SHA256).Hash.ToLowerInvariant()
    signingCertificateSha256=$certificate.Groups[1].Value.Replace(':','').ToLowerInvariant()
}
$release | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $target 'guard-release.json') -Encoding UTF8
Write-Output ('Distribution package ready at '+$target)
Write-Output 'Publish both files through the approved HTTPS company server. Keep the signing keystore in secure backup.'
