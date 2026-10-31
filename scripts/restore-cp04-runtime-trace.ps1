$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$targets = @(
    'app/Http/Middleware/EnsureStoreBranchAvailable.php',
    'app/Modules/Cart/Http/Controllers/StoreCartController.php',
    'app/Modules/Cart/Services/CartService.php',
    'app/Modules/Cart/Services/CartManager.php'
)
$backupSuffix = '.pre-cp04-runtime-trace.bak'
$restored = 0
foreach ($relative in $targets) {
    $path = Join-Path $root $relative
    $backup = $path + $backupSuffix
    if (Test-Path -LiteralPath $backup -PathType Leaf) {
        Copy-Item -LiteralPath $backup -Destination $path -Force
        Remove-Item -LiteralPath $backup -Force
        $restored++
    }
}
$helperPath = Join-Path $root 'app/Support/Cp04DiagnosticTrace.php'
$helperBackup = $helperPath + $backupSuffix
$createdMarker = Join-Path $root 'app/Support/.cp04-diagnostic-trace-created'
if (Test-Path -LiteralPath $helperBackup -PathType Leaf) {
    Copy-Item -LiteralPath $helperBackup -Destination $helperPath -Force
    Remove-Item -LiteralPath $helperBackup -Force
    $restored++
} elseif (Test-Path -LiteralPath $createdMarker -PathType Leaf) {
    Remove-Item -LiteralPath $helperPath -Force -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $createdMarker -Force
    $restored++
}
if ($restored -eq 0) {
    throw 'No runtime trace backups were found. No files were changed.'
}
Write-Host "Restored $restored original PHP file(s). Trace log was retained at storage/logs/cp04-cart-trace.log." -ForegroundColor Green
