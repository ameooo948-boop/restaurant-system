$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$testPath = Join-Path $root 'tests/Feature/Store/StoreCheckoutTest.php'
$backupPath = "$testPath.pre-cp04-diagnostic.bak"

if (-not (Test-Path -LiteralPath $testPath -PathType Leaf)) {
    throw 'StoreCheckoutTest.php was not found. Run this script from the Laravel project root.'
}

$content = [System.IO.File]::ReadAllText($testPath, [System.Text.Encoding]::UTF8)
$marker = 'private function addToGuestCart(Branch $branch, ProductVariant $variant, ?ModifierOption $option = null): Cart'
if ($content -notmatch [regex]::Escape($marker)) {
    throw 'The expected addToGuestCart helper was not found. No files were changed.'
}
if ($content -match '\$this->withoutExceptionHandling\(\);') {
    Write-Host 'Diagnostic mode is already enabled in StoreCheckoutTest.php.' -ForegroundColor Yellow
    exit 0
}

$needle = "    private function addToGuestCart(Branch `$branch, ProductVariant `$variant, ?ModifierOption `$option = null): Cart`r`n    {`r`n"
$replacement = $needle + "        // TEMPORARY DIAGNOSTIC: expose the original exception behind HTTP 404.`r`n        `$this->withoutExceptionHandling();`r`n"
if (-not $content.Contains($needle)) {
    $needle = "    private function addToGuestCart(Branch `$branch, ProductVariant `$variant, ?ModifierOption `$option = null): Cart`n    {`n"
    $replacement = $needle + "        // TEMPORARY DIAGNOSTIC: expose the original exception behind HTTP 404.`n        `$this->withoutExceptionHandling();`n"
}
if (-not $content.Contains($needle)) {
    throw 'Could not safely locate the helper body. No files were changed.'
}

if (-not (Test-Path -LiteralPath $backupPath -PathType Leaf)) {
    Copy-Item -LiteralPath $testPath -Destination $backupPath
}
$updated = $content.Replace($needle, $replacement)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($testPath, $updated, $utf8NoBom)
Write-Host 'Diagnostic mode enabled. A backup was saved next to StoreCheckoutTest.php.' -ForegroundColor Green
Write-Host 'Run: php artisan test --filter=test_checkout_is_idempotent_for_the_same_converted_cart' -ForegroundColor Cyan
Write-Host 'After collecting the exception trace, run scripts/restore-cp04-store-404-diagnostic.ps1.' -ForegroundColor Cyan
