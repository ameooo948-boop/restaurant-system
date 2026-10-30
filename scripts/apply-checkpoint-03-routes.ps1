$ErrorActionPreference = 'Stop'

# Keep this script ASCII-only so Windows PowerShell 5.1 can parse it reliably
# even when the file is extracted without a UTF-8 BOM.
$root = (Get-Location).Path
$authRoutesPath = Join-Path $root 'routes/restaurant_auth.php'
$menuRoutesPath = Join-Path $root 'routes/restaurant_menu.php'

foreach ($requiredPath in @($authRoutesPath, $menuRoutesPath)) {
    if (-not (Test-Path -LiteralPath $requiredPath -PathType Leaf)) {
        throw "Required file not found: $requiredPath. Extract Checkpoint 02 and Checkpoint 03, then run this script from the Laravel project root."
    }
}

$content = [System.IO.File]::ReadAllText($authRoutesPath, [System.Text.Encoding]::UTF8)
$includePattern = 'restaurant_menu\.php'

if ([System.Text.RegularExpressions.Regex]::IsMatch($content, $includePattern)) {
    Write-Host 'Checkpoint 03 menu routes are already included. No changes made.' -ForegroundColor Green
    exit 0
}

$backupPath = "$authRoutesPath.pre-cp03.bak"
if (-not (Test-Path -LiteralPath $backupPath -PathType Leaf)) {
    Copy-Item -LiteralPath $authRoutesPath -Destination $backupPath
    Write-Host "Backup created: $backupPath" -ForegroundColor DarkGray
}

$includeLine = "require __DIR__ . '/restaurant_menu.php';"
$updated = $content.TrimEnd() + "`r`n`r`n// Load restaurant menu administration routes.`r`n$includeLine`r`n"
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($authRoutesPath, $updated, $utf8NoBom)

Write-Host 'Checkpoint 03 menu routes registered successfully.' -ForegroundColor Green
Write-Host 'Next: php artisan optimize:clear' -ForegroundColor Cyan
Write-Host 'Verify: php artisan route:list --path=admin/branches' -ForegroundColor Cyan
