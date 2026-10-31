$ErrorActionPreference='Stop'
$file=Join-Path (Get-Location).Path 'routes/web.php'
$backup="$file.pre-cp07.bak"
if(-not(Test-Path $backup)){throw 'Checkpoint 07 route backup was not found; no files changed.'}
Copy-Item -LiteralPath $backup -Destination $file -Force
Write-Host 'Original routes/web.php restored.' -ForegroundColor Yellow
