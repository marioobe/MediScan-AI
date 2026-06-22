$RootDir = Split-Path -Parent $MyInvocation.MyCommand.Path

$C_Ports = @(
    @{ Name = "FastAPI (AI Service)"; Port = 8001; Dir = "ai-service"; Cmd = "python -m uvicorn app.main:app --reload --host 0.0.0.0 --port 8001" }
    @{ Name = "Laravel (Web App)";    Port = 8080;  Dir = "laravel";       Cmd = "php artisan serve --port=8080" }
    @{ Name = "Vite (Asset Dev)";     Port = 5173;  Dir = "laravel";       Cmd = "npm run dev" }
)

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  MediScan AI - Starting All Servers" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$hasErrors = $false
$startedProcs = @()

foreach ($svc in $C_Ports) {
    $portFree = $true
    $existing = netstat -ano 2>$null | Select-String ":$($svc.Port)\s"
    if ($existing) {
        Write-Host "  [WARN] Port $($svc.Port) is already in use! " -ForegroundColor Yellow -NoNewline
        Write-Host "$($svc.Name) might not start correctly." -ForegroundColor Yellow
        $portFree = $false
    }

    $fullPath = Join-Path $RootDir $svc.Dir
    if (-not (Test-Path $fullPath)) {
        Write-Host "  [SKIP] Directory not found: $fullPath" -ForegroundColor DarkYellow
        $hasErrors = $true
        continue
    }

    $startPreamble = "cd '$fullPath'"
    if ($svc.Dir -eq "ai-service") {
        $startPreamble += "; .\.venv\Scripts\Activate.ps1"
    }

    Write-Host "  [OK] Starting $($svc.Name)..." -ForegroundColor Green
    $proc = Start-Process -PassThru -WindowStyle Normal -FilePath "powershell" -ArgumentList "-NoExit", "-Command", "$startPreamble; $($svc.Cmd)"
    $startedProcs += $proc
    Start-Sleep -Milliseconds 800
}

Write-Host "`n----------------------------------------" -ForegroundColor Cyan
Write-Host "  Summary" -ForegroundColor Cyan
Write-Host "----------------------------------------" -ForegroundColor Cyan

$baseDir = (Get-Item $RootDir).Name
Write-Host "  Working Directory : $RootDir" -ForegroundColor Gray
Write-Host "`n  Service               Port   Status" -ForegroundColor White
Write-Host "  --------------------  -----  ----------" -ForegroundColor Gray

$urls = @()

foreach ($svc in $C_Ports) {
    $fullPath = Join-Path $RootDir $svc.Dir
    if (-not (Test-Path $fullPath)) {
        Write-Host ("  {0,-21} {1,5}  SKIPPED" -f $svc.Name, $svc.Port) -ForegroundColor DarkYellow
        continue
    }

    $isUsed = netstat -ano 2>$null | Select-String ":$($svc.Port)\s"
    $status = "STARTED"
    $color = "Green"
    if (-not $isUsed) {
        $status = "PENDING"
        $color = "Yellow"
    }
    Write-Host ("  {0,-21} {1,5}  {2}" -f $svc.Name, $svc.Port, $status) -ForegroundColor $color

    switch ($svc.Port) {
        8001 { $urls += "  http://localhost:8001/docs  (FastAPI Swagger)" }
        8080 { $urls += "  http://localhost:8080       (Laravel App)" }
        5173 { $urls += "  http://localhost:5173       (Vite Dev Server)" }
    }
}

Write-Host "`n  URLs:" -ForegroundColor White
foreach ($u in $urls) {
    Write-Host $u -ForegroundColor Blue
}

Write-Host "`n  Close the server windows to stop, or run:  .\stop-servers.ps1" -ForegroundColor Gray
Write-Host "========================================`n" -ForegroundColor Cyan
