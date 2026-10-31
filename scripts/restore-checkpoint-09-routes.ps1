$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$routesFile = Join-Path $projectRoot 'routes\restaurant_auth.php'
$backup = "$routesFile.pre-cp09-reports.bak"

if (-not (Test-Path -LiteralPath $backup)) {
    throw 'Checkpoint 09 route backup was not found; no files were changed.'
}

Copy-Item -LiteralPath $backup -Destination $routesFile -Force
Write-Host 'Original restaurant_auth.php restored.' -ForegroundColor Yellow
