# Importa db/logitrans.sql en MySQL (XAMPP).
# Uso: powershell -ExecutionPolicy Bypass -File scripts\import-db.ps1

$ErrorActionPreference = 'Stop'

$mysqlBin = 'C:\xampp\mysql\bin\mysql.exe'
$repo     = Split-Path -Parent $PSScriptRoot
$sqlFile  = Join-Path $repo 'db\logitrans.sql'

if (-not (Test-Path $mysqlBin)) {
    throw "No se encontró MySQL en $mysqlBin. Ajusta \$mysqlBin en este script."
}

if (-not (Test-Path $sqlFile)) {
    throw "No se encontró el esquema en $sqlFile."
}

Write-Host 'Importando db/logitrans.sql (se recrean las tablas)...'
# Se usa cmd para que la redirección envíe los bytes UTF-8 tal cual.
cmd /c "`"$mysqlBin`" -u root --default-character-set=utf8mb4 < `"$sqlFile`""

if ($LASTEXITCODE -ne 0) {
    throw "MySQL devolvió el código $LASTEXITCODE. ¿Está iniciado el servicio desde XAMPP?"
}

Write-Host 'Base de datos logitrans creada/actualizada correctamente.'
& $mysqlBin -u root -e "USE logitrans; SHOW TABLES;"
