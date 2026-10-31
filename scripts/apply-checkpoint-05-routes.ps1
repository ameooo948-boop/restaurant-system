$ErrorActionPreference = 'Stop'

$routesFile = Join-Path (Get-Location) 'routes/web.php'
if (-not (Test-Path $routesFile)) {
    throw 'routes/web.php was not found. Run this script from the Laravel project root.'
}

$backup = "$routesFile.pre-cp05.bak"
if (-not (Test-Path $backup)) {
    Copy-Item $routesFile $backup
}

$encoding = New-Object System.Text.UTF8Encoding($false)
$content = [System.IO.File]::ReadAllText($routesFile)

# Fix the known PHPUnit application-reboot issue in storefront route loading.
$content = [System.Text.RegularExpressions.Regex]::Replace(
    $content,
    'require_once(?=[^;]*restaurant_store\.php)',
    'require'
)

if ($content -notmatch "restaurant_store\.php") {
    $content = $content.TrimEnd() + "`r`n`r`nrequire __DIR__.'/restaurant_store.php';`r`n"
}

if ($content -notmatch "restaurant_kitchen\.php") {
    $content = $content.TrimEnd() + "`r`n`r`n// Kitchen display and station management routes.`r`nrequire __DIR__.'/restaurant_kitchen.php';`r`n"
}

[System.IO.File]::WriteAllText($routesFile, $content, $encoding)
Write-Host 'Checkpoint 05 routes registered.' -ForegroundColor Green
Write-Host "Backup: $backup" -ForegroundColor DarkGray
Write-Host 'Storefront routes are loaded with require on each Laravel application boot.' -ForegroundColor DarkGray
