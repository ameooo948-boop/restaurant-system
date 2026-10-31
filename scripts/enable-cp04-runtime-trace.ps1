$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$targets = @(
    'app/Http/Middleware/EnsureStoreBranchAvailable.php',
    'app/Modules/Cart/Http/Controllers/StoreCartController.php',
    'app/Modules/Cart/Services/CartService.php',
    'app/Modules/Cart/Services/CartManager.php'
)
$backupSuffix = '.pre-cp04-runtime-trace.bak'
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)

foreach ($relative in $targets) {
    $path = Join-Path $root $relative
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) {
        throw "Required file not found: $relative. No files were changed."
    }
    if (Test-Path -LiteralPath ($path + $backupSuffix) -PathType Leaf) {
        throw "Backup already exists for $relative. Restore the previous trace first."
    }
}

$helperRelative = 'app/Support/Cp04DiagnosticTrace.php'
$helperPath = Join-Path $root $helperRelative
$helperDir = Split-Path -Parent $helperPath
New-Item -ItemType Directory -Force -Path $helperDir | Out-Null
$helperCreatedMarker = Join-Path $root 'app/Support/.cp04-diagnostic-trace-created'
if ((Test-Path -LiteralPath $helperPath) -and (Test-Path -LiteralPath ($helperPath + $backupSuffix))) {
    throw 'A diagnostic helper backup already exists. Restore the previous trace first.'
}

function Replace-Once([string]$Text, [string]$Pattern, [string]$Replacement, [string]$Label) {
    $regex = [regex]::new($Pattern)
    if (-not $regex.IsMatch($Text)) {
        throw "Could not locate insertion point: $Label. No application changes were written."
    }
    return $regex.Replace($Text, $Replacement, 1)
}

$helper = @'
<?php

namespace App\Support;

final class Cp04DiagnosticTrace
{
    public static function write(string $stage, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s %s%s",
            date(DATE_ATOM),
            $stage,
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );

        file_put_contents(storage_path('logs/cp04-cart-trace.log'), $line, FILE_APPEND | LOCK_EX);
    }
}
'@

# Read and stage every replacement in memory first; no partial writes on a pattern mismatch.
$staged = @{}

# Middleware: confirm whether the request enters and passes branch availability checks.
$p = Join-Path $root $targets[0]
$c = [System.IO.File]::ReadAllText($p, [System.Text.Encoding]::UTF8)
$insert = @'
        \App\Support\Cp04DiagnosticTrace::write('middleware.enter', [
            'route' => $request->route()?->uri(),
            'branch_type' => get_debug_type($request->route()?->parameter('branch')),
            'branch_value' => is_object($request->route()?->parameter('branch'))
                ? ($request->route()->parameter('branch')->id ?? null)
                : $request->route()?->parameter('branch'),
        ]);
'@
$c = Replace-Once $c '(public function handle\(Request \$request, Closure \$next\): Response\s*\{)' ('$1' + "`r`n" + $insert) 'store branch middleware entry'
$c = Replace-Once $c '(return \$next\(\$request\);)' ("\App\Support\Cp04DiagnosticTrace::write('middleware.pass');`r`n        `$1") 'store branch middleware pass'
$staged[$targets[0]] = $c

# Controller: record controller entry and any exception escaping the cart service.
$p = Join-Path $root $targets[1]
$c = [System.IO.File]::ReadAllText($p, [System.Text.Encoding]::UTF8)
$insert = @'
        \App\Support\Cp04DiagnosticTrace::write('controller.store.enter', ['branch_id' => $branch->id]);
'@
$c = Replace-Once $c '(public function store\(Branch \$branch, AddCartItemRequest \$request, CartService \$service\): RedirectResponse\s*\{)' ('$1' + "`r`n" + $insert) 'cart controller store entry'
$c = Replace-Once $c '(\$service->addItem\(\$branch, \$request, \$request->validated\(\)\);)' @'
        try {
            $1
        } catch (\Throwable $exception) {
            \App\Support\Cp04DiagnosticTrace::write('controller.store.exception', [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);
            throw $exception;
        }
        \App\Support\Cp04DiagnosticTrace::write('controller.store.service_pass');
'@ 'cart controller service call'
$staged[$targets[1]] = $c

# CartService: split the chained variant lookup from price resolution for clear trace points.
$p = Join-Path $root $targets[2]
$c = [System.IO.File]::ReadAllText($p, [System.Text.Encoding]::UTF8)
$insert = @'
        \App\Support\Cp04DiagnosticTrace::write('cart_service.enter', [
            'branch_id' => $branch->id,
            'variant_id' => $data['product_variant_id'] ?? null,
            'modifiers_count' => count($data['modifiers'] ?? []),
        ]);
'@
$c = Replace-Once $c '(public function addItem\(Branch \$branch, Request \$request, array \$data\): Cart\s*\{)' ('$1' + "`r`n" + $insert) 'cart service addItem entry'
$old = @'
        $resolved = $this->pricing->validateAndResolve(
            $branch,
            ProductVariant::query()->findOrFail($data['product_variant_id']),
            $data['modifiers'] ?? [],
        );
        $cart = $this->manager->resolve($branch, $request);
'@
$new = @'
        \App\Support\Cp04DiagnosticTrace::write('cart_service.variant_lookup.start', ['variant_id' => $data['product_variant_id']]);
        $variant = ProductVariant::query()->findOrFail($data['product_variant_id']);
        \App\Support\Cp04DiagnosticTrace::write('cart_service.variant_lookup.pass', ['variant_id' => $variant->id, 'product_id' => $variant->product_id]);

        \App\Support\Cp04DiagnosticTrace::write('cart_service.pricing.start');
        $resolved = $this->pricing->validateAndResolve(
            $branch,
            $variant,
            $data['modifiers'] ?? [],
        );
        \App\Support\Cp04DiagnosticTrace::write('cart_service.pricing.pass', ['unit_price_minor' => $resolved['unit_price_minor']]);

        \App\Support\Cp04DiagnosticTrace::write('cart_service.cart_manager.start');
        $cart = $this->manager->resolve($branch, $request);
        \App\Support\Cp04DiagnosticTrace::write('cart_service.cart_manager.pass', ['cart_id' => $cart->id]);
'@
if (-not $c.Contains($old)) { throw 'Could not locate CartService pricing/manager block. No application changes were written.' }
$c = $c.Replace($old, $new)
$c = Replace-Once $c '(\$locked = Cart::query\(\)->whereKey\(\$cart->id\)->lockForUpdate\(\)->firstOrFail\(\);)' ('$1' + "`r`n            \App\Support\Cp04DiagnosticTrace::write('cart_service.locked_cart.pass', ['cart_id' => `$locked->id]);") 'cart service locked cart query'
$staged[$targets[2]] = $c

# CartManager: expose branch lock, existing-cart lookup, and creation stages.
$p = Join-Path $root $targets[3]
$c = [System.IO.File]::ReadAllText($p, [System.Text.Encoding]::UTF8)
$insert = @'
        \App\Support\Cp04DiagnosticTrace::write('cart_manager.enter', ['branch_id' => $branch->id]);
'@
$c = Replace-Once $c '(public function resolve\(Branch \$branch, Request \$request\): Cart\s*\{)' ('$1' + "`r`n" + $insert) 'cart manager resolve entry'
$c = Replace-Once $c '(Branch::query\(\)->whereKey\(\$branch->id\)->lockForUpdate\(\)->firstOrFail\(\);)' ('$1' + "`r`n                \App\Support\Cp04DiagnosticTrace::write('cart_manager.branch_lock.pass', ['branch_id' => `$branch->id]);") 'cart manager branch lock'
$c = Replace-Once $c '(if \(\$cart\) \{\s*return \$cart;\s*\})' @'
                \App\Support\Cp04DiagnosticTrace::write('cart_manager.active_cart_lookup', ['found' => (bool) $cart, 'cart_id' => $cart?->id]);
                $1
'@ 'cart manager active cart lookup'
$c = Replace-Once $c '(return Cart::create\(\[)' ("\App\Support\Cp04DiagnosticTrace::write('cart_manager.create.start', ['branch_id' => `$branch->id]);`r`n                `$createdCart = Cart::create([") 'cart manager cart create start'
$c = Replace-Once $c '(\x27expires_at\x27 => now\(\)->addDays\(30\),\s*\]\);)' ('$1' + "`r`n                \App\Support\Cp04DiagnosticTrace::write('cart_manager.create.pass', ['cart_id' => `$createdCart->id]);`r`n                return `$createdCart;") 'cart manager cart create finish'
$c = Replace-Once $c '(catch \(QueryException \$exception\) \{)' ('$1' + "`r`n            \App\Support\Cp04DiagnosticTrace::write('cart_manager.query_exception', ['message' => `$exception->getMessage()]);") 'cart manager query exception'
$staged[$targets[3]] = $c

# Verify all staged PHP parses before touching the user's files.
$tempRoot = Join-Path $env:TEMP ('cp04-trace-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Force -Path $tempRoot | Out-Null
try {
    foreach ($relative in $targets) {
        $tempFile = Join-Path $tempRoot ([IO.Path]::GetFileName($relative))
        [System.IO.File]::WriteAllText($tempFile, $staged[$relative], $utf8NoBom)
        $lint = & php -l $tempFile 2>&1
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed for $relative`: $lint" }
    }
    $helperTemp = Join-Path $tempRoot 'Cp04DiagnosticTrace.php'
    [System.IO.File]::WriteAllText($helperTemp, $helper, $utf8NoBom)
    $lint = & php -l $helperTemp 2>&1
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed for diagnostic helper: $lint" }
} finally {
    Remove-Item -LiteralPath $tempRoot -Recurse -Force -ErrorAction SilentlyContinue
}

# Back up every target and apply the already-validated replacements.
foreach ($relative in $targets) {
    $path = Join-Path $root $relative
    Copy-Item -LiteralPath $path -Destination ($path + $backupSuffix)
}
if (Test-Path -LiteralPath $helperPath -PathType Leaf) {
    Copy-Item -LiteralPath $helperPath -Destination ($helperPath + $backupSuffix)
} else {
    [System.IO.File]::WriteAllText($helperPath, $helper, $utf8NoBom)
    Set-Content -LiteralPath $helperCreatedMarker -Value 'created' -Encoding ASCII
}
foreach ($relative in $targets) {
    [System.IO.File]::WriteAllText((Join-Path $root $relative), $staged[$relative], $utf8NoBom)
}
$logPath = Join-Path $root 'storage/logs/cp04-cart-trace.log'
New-Item -ItemType Directory -Force -Path (Split-Path -Parent $logPath) | Out-Null
[System.IO.File]::WriteAllText($logPath, '', $utf8NoBom)

Write-Host 'CP04 runtime trace enabled. Backups were saved beside each modified PHP file.' -ForegroundColor Green
Write-Host 'Run: php artisan test --filter=StoreCheckoutTest --stop-on-failure' -ForegroundColor Cyan
Write-Host 'Then inspect: Get-Content .\storage\logs\cp04-cart-trace.log' -ForegroundColor Cyan
Write-Host 'Restore: powershell -ExecutionPolicy Bypass -File .\scripts\restore-cp04-runtime-trace.ps1' -ForegroundColor Yellow
