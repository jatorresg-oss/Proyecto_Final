# Levanta el frontend y los 5 microservicios, cada uno en su ventana.
# Uso: powershell -ExecutionPolicy Bypass -File scripts\start-all.ps1

$ErrorActionPreference = 'Stop'

$phpBin = 'C:\xampp\php\php.exe'
$repo   = Split-Path -Parent $PSScriptRoot

if (-not (Test-Path $phpBin)) {
    throw "No se encontró PHP en $phpBin. Ajusta \$phpBin en este script."
}

$services = @(
    @{ Name = 'ms-auth';        Port = 8001 },
    @{ Name = 'ms-conductores'; Port = 8002 },
    @{ Name = 'ms-vehiculos';   Port = 8003 },
    @{ Name = 'ms-rutas';       Port = 8004 },
    @{ Name = 'ms-viajes';      Port = 8005 }
)

foreach ($service in $services) {
    $workdir = Join-Path $repo "backend\$($service.Name)"

    if (-not (Test-Path (Join-Path $workdir 'vendor'))) {
        Write-Warning "$($service.Name) no tiene vendor/. Ejecuta: C:\xampp\php\php.exe C:\xampp\php\composer.phar install --working-dir=`"$workdir`""
        continue
    }

    Start-Process -FilePath $phpBin `
        -ArgumentList '-S', "127.0.0.1:$($service.Port)", '-t', 'public' `
        -WorkingDirectory $workdir
    Write-Host "Iniciado $($service.Name) en http://127.0.0.1:$($service.Port)"
}

$frontend = Join-Path $repo 'frontend'
Start-Process -FilePath $phpBin -ArgumentList '-S', '127.0.0.1:5501' -WorkingDirectory $frontend
Write-Host 'Iniciado frontend en http://127.0.0.1:5501'
Write-Host ''
Write-Host 'Abre http://127.0.0.1:5501 en el navegador.'
