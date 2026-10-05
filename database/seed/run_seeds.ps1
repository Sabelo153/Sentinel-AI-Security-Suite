# Provides the run_seeds implementation.

param(
    [string]$Env = "",
    [string]$Profile = "",
    [switch]$DryRun
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$runner = Join-Path $scriptDir "run_seeds.php"

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP CLI is required to run seeds."
    exit 1
}

$args = @()

if ($Env -ne "") {
    $args += "--env=$Env"
}

if ($Profile -ne "") {
    $args += "--profile=$Profile"
}

if ($DryRun) {
    $args += "--dry-run"
}

php $runner @args
exit $LASTEXITCODE
