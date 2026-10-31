$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$testPath = Join-Path $root 'tests/Feature/Store/StoreCheckoutTest.php'
$backupPath = "$testPath.pre-cp04-cart-diagnostic.bak"

if (-not (Test-Path -LiteralPath $backupPath -PathType Leaf)) {
    throw 'Diagnostic backup was not found. No files were changed.'
}
Copy-Item -LiteralPath $backupPath -Destination $testPath -Force
Remove-Item -LiteralPath $backupPath -Force
Write-Host 'Original StoreCheckoutTest.php restored.' -ForegroundColor Green
