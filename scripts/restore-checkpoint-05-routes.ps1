$ErrorActionPreference = 'Stop'
$routesFile = Join-Path (Get-Location) 'routes/web.php'
$backup = "$routesFile.pre-cp05.bak"

if (-not (Test-Path $backup)) {
    throw 'Checkpoint 05 backup not found. No files were changed.'
}

Copy-Item $backup $routesFile -Force
Write-Host 'Original routes/web.php restored.' -ForegroundColor Green
