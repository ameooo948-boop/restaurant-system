$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$testPath = Join-Path $root 'tests/Feature/Store/StoreCheckoutTest.php'
$backupPath = "$testPath.pre-cp04-cart-diagnostic.bak"

if (-not (Test-Path -LiteralPath $testPath -PathType Leaf)) {
    throw 'StoreCheckoutTest.php was not found. Run this script from the Laravel project root.'
}
if (Test-Path -LiteralPath $backupPath -PathType Leaf) {
    throw 'A diagnostic backup already exists. Run the restore script before enabling diagnostics again.'
}

$content = [System.IO.File]::ReadAllText($testPath, [System.Text.Encoding]::UTF8)
$pattern = '(?s)(private function addToGuestCart\(Branch \$branch, ProductVariant \$variant, \?ModifierOption \$option = null\): Cart\s*\{\s*)\$this->post\((.*?)\)->assertRedirect\(\);'
if ($content -notmatch $pattern) {
    throw 'Could not safely locate the addToGuestCart request. No files were changed.'
}

$replacement = @'
$1$diagBranch = Branch::query()->with('restaurant')->find($branch->id);
        $diagVariant = ProductVariant::query()->with('product')->find($variant->id);
        $diagContext = [
            'branch_id' => $branch->id,
            'branch_found' => $diagBranch !== null,
            'branch_active' => $diagBranch?->is_active,
            'restaurant_id' => $branch->restaurant_id,
            'restaurant_found' => $diagBranch?->restaurant !== null,
            'restaurant_active' => $diagBranch?->restaurant?->is_active,
            'variant_id' => $variant->id,
            'variant_found' => $diagVariant !== null,
            'variant_active' => $diagVariant?->is_active,
            'variant_product_id' => $diagVariant?->product_id,
            'variant_product_restaurant_id' => $diagVariant?->product?->restaurant_id,
        ];
        fwrite(STDERR, "`nCP04_DIAG_CONTEXT ".json_encode($diagContext, JSON_UNESCAPED_UNICODE)."`n");

        $response = $this->post($2);
        if ($response->status() === 404) {
            $body = html_entity_decode(strip_tags($response->getContent()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $body = preg_replace('/\\s+/', ' ', $body) ?: $body;
            fwrite(STDERR, "`nCP04_DIAG_404_BODY ".substr($body, 0, 6000)."`n");
        }
        $response->assertRedirect();
'@

$updated = [regex]::Replace($content, $pattern, $replacement, 1)
Copy-Item -LiteralPath $testPath -Destination $backupPath
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($testPath, $updated, $utf8NoBom)
Write-Host 'CP04 cart route diagnostics enabled. Original test file backed up.' -ForegroundColor Green
Write-Host 'Run: php artisan test --filter=StoreCheckoutTest --stop-on-failure' -ForegroundColor Cyan
Write-Host 'Then restore using scripts/restore-cp04-cart-route-diagnostic.ps1.' -ForegroundColor Cyan
