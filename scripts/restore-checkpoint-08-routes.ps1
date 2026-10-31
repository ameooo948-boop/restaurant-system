$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$restored = 0
foreach ($relative in @('routes/web.php', 'bootstrap/app.php')) {
    $path = Join-Path $root $relative
    $backup = "$path.pre-cp08.bak"
    if (Test-Path $backup) {
        Copy-Item $backup $path -Force
        Remove-Item $backup -Force
        $restored++
    }
}
Write-Host "Restored $restored file(s)." -ForegroundColor Green
