$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$routesFile = Join-Path $root 'routes/web.php'
if (-not (Test-Path $routesFile)) {
    throw 'Run this script from the Laravel project root; routes/web.php was not found.'
}

$backup = "$routesFile.pre-cp06.bak"
if (-not (Test-Path $backup)) {
    Copy-Item -LiteralPath $routesFile -Destination $backup
}

$encoding = New-Object System.Text.UTF8Encoding($false)
$content = [System.IO.File]::ReadAllText($routesFile)

# Ensure route files are loaded on every Application boot (important for PHPUnit).
$content = [System.Text.RegularExpressions.Regex]::Replace(
    $content,
    'require_once(?=[^;]*restaurant_store\.php)',
    'require'
)

$includes = @(
    @{ Pattern = 'restaurant_store\.php'; Line = "require __DIR__.'/restaurant_store.php';" },
    @{ Pattern = 'restaurant_kitchen\.php'; Line = "require __DIR__.'/restaurant_kitchen.php';" },
    @{ Pattern = 'restaurant_admin\.php'; Line = "require __DIR__.'/restaurant_admin.php';" }
)

foreach ($include in $includes) {
    if ($content -notmatch $include.Pattern) {
        $content = $content.TrimEnd() + "`r`n`r`n" + $include.Line + "`r`n"
    }
}

[System.IO.File]::WriteAllText($routesFile, $content, $encoding)
Write-Host 'Checkpoint 06 routes registered.' -ForegroundColor Green
Write-Host "Backup: $backup" -ForegroundColor DarkGray
