# Laptop Focus Lock Script for O/L Study Tracker
# This script will lock your Windows laptop during study times to help you focus.
# Please keep this terminal open or minimized while studying.

$URL = "http://localhost/study%20tracker/api_check_lock.php"
$INTERVAL = 15 # Check every 15 seconds for more responsive locking

Write-Host "==========================================" -ForegroundColor Green
Write-Host "   O/L STUDY TRACKER - AUTOMATIC LOCK" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Running... (Do not close this window)" -ForegroundColor Gray

function Get-LockStatus {
    try {
        $response = Invoke-RestMethod -Uri $URL -Method Get -TimeoutSec 10
        return $response
    }
    catch {
        Write-Host "Error connecting to tracker: $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

while ($true) {
    $status = Get-LockStatus
    
    if ($status -and $status.locked) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] LOCK TRIGGERED: It is study time!" -ForegroundColor Yellow
        # The actual lock command
        rundll32.exe user32.dll, LockWorkStation
    } elseif ($status) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Status: Active | Lock: Idle (No study session now)" -ForegroundColor Green
    }
    
    Start-Sleep -Seconds $INTERVAL
}
