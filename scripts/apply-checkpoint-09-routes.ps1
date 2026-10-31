$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$routesFile = Join-Path $projectRoot 'routes\restaurant_auth.php'

if (-not (Test-Path -LiteralPath $routesFile)) {
    throw "Routes file not found: $routesFile"
}

$content = [System.IO.File]::ReadAllText($routesFile)
if ($content -match "restaurant_reports\.php") {
    Write-Host 'Checkpoint 09 routes are already included.' -ForegroundColor Green
    exit 0
}

$backup = "$routesFile.pre-cp09-reports.bak"
if (-not (Test-Path -LiteralPath $backup)) {
    Copy-Item -LiteralPath $routesFile -Destination $backup
}

$line = "`r`n`r`n// Load branch-scoped reports and expense routes on every application boot.`r`nrequire __DIR__ . '/restaurant_reports.php';`r`n"
$content = $content.TrimEnd() + $line
$encoding = New-Object -TypeName System.Text.UTF8Encoding -ArgumentList $false
[System.IO.File]::WriteAllText($routesFile, $content, $encoding)

Write-Host 'Checkpoint 09 routes registered. Backup saved.' -ForegroundColor Green
