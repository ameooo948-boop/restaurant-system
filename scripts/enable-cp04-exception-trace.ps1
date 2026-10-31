$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$testPath = Join-Path $root 'tests/Feature/Store/StoreCheckoutTest.php'
$backupPath = "$testPath.pre-cp04-exception-trace.bak"

if (-not (Test-Path -LiteralPath $testPath -PathType Leaf)) {
    throw 'StoreCheckoutTest.php was not found. Run this script from the Laravel project root.'
}
if (Test-Path -LiteralPath $backupPath -PathType Leaf) {
    throw 'A diagnostic backup already exists. Restore it before enabling diagnostics again.'
}

$content = [System.IO.File]::ReadAllText($testPath, [System.Text.Encoding]::UTF8)
$marker = 'private function addToGuestCart(Branch $branch, ProductVariant $variant, ?ModifierOption $option = null): Cart'
$markerIndex = $content.IndexOf($marker, [System.StringComparison]::Ordinal)
if ($markerIndex -lt 0) {
    throw 'The addToGuestCart helper was not found. No files were changed.'
}
$braceIndex = $content.IndexOf('{', $markerIndex)
if ($braceIndex -lt 0) {
    throw 'The addToGuestCart method opening brace was not found. No files were changed.'
}
$existingMethod = $content.Substring($braceIndex, [Math]::Min(240, $content.Length - $braceIndex))
if ($existingMethod.Contains('withoutExceptionHandling')) {
    throw 'Exception tracing already appears to be enabled in this helper. No files were changed.'
}

Copy-Item -LiteralPath $testPath -Destination $backupPath
$insert = "`r`n        // TEMPORARY DIAGNOSTIC: bubble up the original exception behind HTTP 404.`r`n        `$this->withoutExceptionHandling();`r`n"
$updated = $content.Insert($braceIndex + 1, $insert)
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($testPath, $updated, $utf8NoBom)

Write-Host 'Original test backed up and exception tracing enabled.' -ForegroundColor Green
Write-Host 'Run only this test first:' -ForegroundColor Cyan
Write-Host 'php artisan test --filter=test_checkout_is_idempotent_for_the_same_converted_cart --stop-on-failure' -ForegroundColor White
Write-Host 'After collecting the original exception trace, restore with:' -ForegroundColor Cyan
Write-Host 'powershell -ExecutionPolicy Bypass -File .\scripts\restore-cp04-exception-trace.ps1' -ForegroundColor White
