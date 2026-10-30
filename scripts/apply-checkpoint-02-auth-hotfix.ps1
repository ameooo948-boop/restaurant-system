$ErrorActionPreference = 'Stop'

$root = (Get-Location).Path
$providersPath = Join-Path $root 'bootstrap/providers.php'
$webRoutesPath = Join-Path $root 'routes/web.php'
$authRoutesPath = Join-Path $root 'routes/restaurant_auth.php'

foreach ($required in @($providersPath, $webRoutesPath, $authRoutesPath)) {
    if (-not (Test-Path -LiteralPath $required -PathType Leaf)) {
        throw "Required file not found: $required. Run this script from the Laravel project root and ensure Checkpoint 02 files are extracted."
    }
}

function Backup-Once([string]$Path) {
    $backup = "$Path.pre-cp02-hotfix.bak"
    if (-not (Test-Path -LiteralPath $backup)) {
        Copy-Item -LiteralPath $Path -Destination $backup
        Write-Host "Backup created: $backup" -ForegroundColor DarkGray
    }
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $encoding)
}

# 1) Ensure both custom providers are registered without replacing the provider list.
$providers = Get-Content -LiteralPath $providersPath -Raw
$providerClasses = @(
    'App\Providers\RestaurantServiceProvider::class,',
    'App\Providers\RestaurantFortifyServiceProvider::class,'
)
$providersChanged = $false

foreach ($providerClass in $providerClasses) {
    if ($providers -notmatch [regex]::Escape($providerClass.TrimEnd(','))) {
        $anchor = 'App\Providers\AppServiceProvider::class,'
        if ($providers.Contains($anchor)) {
            $providers = $providers.Replace($anchor, $anchor + "`r`n    " + $providerClass)
        } else {
            $closing = $providers.LastIndexOf('];')
            if ($closing -lt 0) {
                throw "Could not safely locate the providers array in $providersPath. No changes were written to that file."
            }
            $providers = $providers.Insert($closing, "    $providerClass`r`n")
        }
        $providersChanged = $true
    }
}

if ($providersChanged) {
    Backup-Once $providersPath
    Write-Utf8NoBom $providersPath $providers
    Write-Host 'Registered RestaurantServiceProvider and RestaurantFortifyServiceProvider.' -ForegroundColor Green
} else {
    Write-Host 'Both restaurant service providers are already registered.' -ForegroundColor Green
}

# 2) Load the branch/account routes from the existing web route file.
$webRoutes = Get-Content -LiteralPath $webRoutesPath -Raw
$includeLine = "require __DIR__.'/restaurant_auth.php';"

if ($webRoutes -notmatch [regex]::Escape($includeLine)) {
    Backup-Once $webRoutesPath
    $webRoutes = $webRoutes.TrimEnd() + "`r`n`r`n// Restaurant account and branch administration routes.`r`n$includeLine`r`n"
    Write-Utf8NoBom $webRoutesPath $webRoutes
    Write-Host 'Loaded routes/restaurant_auth.php from routes/web.php.' -ForegroundColor Green
} else {
    Write-Host 'Restaurant auth routes are already included.' -ForegroundColor Green
}

Write-Host ''
Write-Host 'Hotfix wiring completed. No database migrations or data were changed.' -ForegroundColor Green
Write-Host 'Next run: php artisan optimize:clear' -ForegroundColor Cyan
Write-Host 'Then verify: php artisan route:list --path=admin/branches' -ForegroundColor Cyan
Write-Host 'Then run the two Checkpoint 02 test commands in the setup guide.' -ForegroundColor Cyan
