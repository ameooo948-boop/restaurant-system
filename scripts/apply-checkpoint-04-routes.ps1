$ErrorActionPreference = 'Stop'

# ASCII-only to remain compatible with Windows PowerShell 5.1 default encodings.
$root = (Get-Location).Path
$webRoutesPath = Join-Path $root 'routes/web.php'
$storeRoutesPath = Join-Path $root 'routes/restaurant_store.php'

if (-not (Test-Path -LiteralPath $webRoutesPath -PathType Leaf)) {
    throw "routes/web.php was not found. Run this script from the Laravel project root."
}
if (-not (Test-Path -LiteralPath $storeRoutesPath -PathType Leaf)) {
    throw "routes/restaurant_store.php was not found. Extract Checkpoint 04 first."
}

$content = [System.IO.File]::ReadAllText($webRoutesPath, [System.Text.Encoding]::UTF8)
if ($content -match 'restaurant_store\.php') {
    Write-Host 'Checkpoint 04 routes are already included. No changes made.' -ForegroundColor Green
    exit 0
}

$backupPath = "$webRoutesPath.pre-cp04.bak"
if (-not (Test-Path -LiteralPath $backupPath -PathType Leaf)) {
    Copy-Item -LiteralPath $webRoutesPath -Destination $backupPath
    Write-Host "Backup created: $backupPath" -ForegroundColor DarkGray
}

$includeLine = "require_once __DIR__ . '/restaurant_store.php';"
$updated = $content.TrimEnd() + "`r`n`r`n// Load restaurant customer storefront, cart and checkout routes.`r`n$includeLine`r`n"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($webRoutesPath, $updated, $utf8NoBom)

Write-Host 'Checkpoint 04 store routes registered successfully.' -ForegroundColor Green
Write-Host 'Next: php artisan optimize:clear' -ForegroundColor Cyan
Write-Host 'Verify: php artisan route:list --path=branches' -ForegroundColor Cyan
