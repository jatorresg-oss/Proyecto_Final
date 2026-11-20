# Verifica que los 5 microservicios respondan /health.
# Uso: powershell -ExecutionPolicy Bypass -File scripts\health-check.ps1

$ports = 8001..8005

foreach ($port in $ports) {
    try {
        $response = Invoke-WebRequest "http://127.0.0.1:$port/health" -UseBasicParsing -TimeoutSec 5
        Write-Host ("{0} -> {1}" -f $port, $response.Content)
    }
    catch {
        Write-Warning "$port sin respuesta. ¿Está levantado el servicio?"
    }
}
