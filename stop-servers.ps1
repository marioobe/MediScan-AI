$C_Ports = @(
    @{ Name = "FastAPI (AI Service)"; Port = 8001 }
    @{ Name = "Laravel (Web App)";    Port = 8080  }
    @{ Name = "Vite (Asset Dev)";     Port = 5173  }
)

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  MediScan AI - Stopping All Servers" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$foundAny = $false

foreach ($svc in $C_Ports) {
    $connections = netstat -ano 2>$null | Select-String ":$($svc.Port)\s"
    $procIds = @()
    foreach ($conn in $connections) {
        $parts = ($conn -split '\s+') | Where-Object { $_ -ne '' }
        if ($parts.Count -ge 5) {
            $foundId = $parts[-1]
            if ($foundId -match '^\d+$' -and $foundId -notin $procIds) {
                $procIds += $foundId
            }
        }
    }

    if ($procIds.Count -eq 0) {
        Write-Host ("  [SKIP] {0,-25} (port {1}) - not running" -f $svc.Name, $svc.Port) -ForegroundColor DarkYellow
        continue
    }

    $foundAny = $true
    $procNames = @()

    foreach ($foundId in $procIds) {
        try {
            $proc = Get-Process -Id $foundId -ErrorAction Stop
            $procNames += "$($proc.Name).exe (PID $foundId)"
            Stop-Process -Id $foundId -Force -ErrorAction Stop
        } catch {
            Write-Host "  [WARN] Failed to stop PID $foundId : $_" -ForegroundColor Yellow
        }
    }

    Write-Host ("  [OK] Stopped {0,-25} (port {1}) - {2}" -f $svc.Name, $svc.Port, ($procNames -join ", ")) -ForegroundColor Green
}

if (-not $foundAny) {
    Write-Host "`n  No running servers found on the expected ports." -ForegroundColor Gray
}

Write-Host "========================================`n" -ForegroundColor Cyan
