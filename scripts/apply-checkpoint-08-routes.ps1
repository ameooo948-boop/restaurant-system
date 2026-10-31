$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$webPath = Join-Path $root 'routes/web.php'
$bootstrapPath = Join-Path $root 'bootstrap/app.php'
$requiredRoutes = @('routes/restaurant_payments.php', 'routes/restaurant_delivery.php')

if (-not (Test-Path $webPath)) { throw 'Run this script from the Laravel project root.' }
if (-not (Test-Path $bootstrapPath)) { throw 'bootstrap/app.php was not found.' }
foreach ($relative in $requiredRoutes) {
    if (-not (Test-Path (Join-Path $root $relative))) { throw "Required route file is missing: $relative" }
}

function Backup-Once([string]$Path) {
    $backup = "$Path.pre-cp08.bak"
    if (-not (Test-Path $backup)) { Copy-Item $Path $backup }
}

$web = [System.IO.File]::ReadAllText($webPath)
$bootstrap = [System.IO.File]::ReadAllText($bootstrapPath)
$changedWeb = $false
$changedBootstrap = $false

# Ensure route files are re-required every time Laravel rebuilds its application (important for PHPUnit).
$storePattern = '(?m)^([ \t]*)require_once([ \t]+__DIR__[ \t]*\.[ \t]*[''\"]/restaurant_store\.php[''\"][ \t]*;)'
$webFixed = [regex]::Replace($web, $storePattern, '$1require$2')
if ($webFixed -ne $web) { $web = $webFixed; $changedWeb = $true }

$paymentsLine = "require __DIR__.'/restaurant_payments.php';"
$deliveryLine = "require __DIR__.'/restaurant_delivery.php';"
foreach ($line in @($paymentsLine, $deliveryLine)) {
    if ($web -notmatch [regex]::Escape($line)) {
        $web = $web.TrimEnd() + "`r`n`r`n// Checkpoint 08 routes`r`n" + $line + "`r`n"
        $changedWeb = $true
    }
}

$csrfPath = 'webhooks/paymob/transactions'
$callPattern = 'validateCsrfTokens\s*\(\s*except\s*:\s*\[([^\]]*)\]\s*\)'
$existingCall = [regex]::Match($bootstrap, $callPattern)
if ($existingCall.Success) {
    if ($existingCall.Groups[1].Value -notmatch [regex]::Escape($csrfPath)) {
        $items = $existingCall.Groups[1].Value.Trim()
        if ($items -and -not $items.TrimEnd().EndsWith(',')) { $items += ', ' }
        $items += "'$csrfPath'"
        $replacement = 'validateCsrfTokens(except: [' + $items + '])'
        $next = [regex]::Replace($bootstrap, $callPattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $replacement }, 1)
        if ($next -eq $bootstrap) { throw 'Could not safely update the existing CSRF exception list. No files were changed.' }
        $bootstrap = $next
        $changedBootstrap = $true
    }
} elseif ($bootstrap -match 'validateCsrfTokens\s*\(') {
    throw 'bootstrap/app.php has a custom CSRF configuration that must be reviewed manually. No files were changed.'
} else {
    $middlewarePattern = '(->withMiddleware\(function\s*\(Middleware\s+\$middleware\):\s*void\s*\{)'
    $replacement = '$1' + "`r`n        `$middleware->validateCsrfTokens(except: ['$csrfPath']);"
    $next = [regex]::Replace($bootstrap, $middlewarePattern, $replacement, 1)
    if ($next -eq $bootstrap) {
        throw 'Could not safely locate the withMiddleware closure. No files were changed.'
    }
    $bootstrap = $next
    $changedBootstrap = $true
}

# Only write after both files have passed all structural checks.
if ($changedWeb) { Backup-Once $webPath; [System.IO.File]::WriteAllText($webPath, $web, [System.Text.UTF8Encoding]::new($false)) }
if ($changedBootstrap) { Backup-Once $bootstrapPath; [System.IO.File]::WriteAllText($bootstrapPath, $bootstrap, [System.Text.UTF8Encoding]::new($false)) }

Write-Host 'Checkpoint 08 routes registered and only the Paymob webhook CSRF exception configured.' -ForegroundColor Green
Write-Host 'Review routes/web.php and bootstrap/app.php changes before continuing.' -ForegroundColor Yellow
