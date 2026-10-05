# Provides the run_migrations implementation.

param(
    [string]$Env = "",
    [string]$Until = "",
    [string]$Domain = "",
    [switch]$DryRun,
    [switch]$Rollback
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$runner = Join-Path $scriptDir "run_migrations.php"

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP CLI is required to run migrations."
    exit 1
}

$args = @()

if ($Env -ne "") {
    $args += "--env=$Env"
}

if ($Until -ne "") {
    $args += "--until=$Until"
}

if ($Domain -ne "") {
    $args += "--domain=$Domain"
}

if ($DryRun) {
    $args += "--dry-run"
}

if ($Rollback) {
    $args += "--rollback"
}

php $runner @args

exit $LASTEXITCODE
