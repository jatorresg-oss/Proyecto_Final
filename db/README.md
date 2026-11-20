# Base de datos `logitrans`

Todos los servicios del proyecto comparten **una sola base de datos** llamada
`logitrans`. Así se evita duplicar información y las referencias entre
conductores, vehículos, rutas y viajes quedan dentro del mismo esquema.

## Contenido

| Tabla | Servicio que la administra |
|---|---|
| `usuarios` | ms-auth |
| `conductores` | ms-conductores |
| `vehiculos` | ms-vehiculos |
| `rutas` | ms-rutas |
| `programaciones_viajes` | ms-rutas (lectura y escritura desde ms-viajes) |
| `seguimientos_viajes` | ms-viajes |

> No se declaran llaves foráneas entre tablas: las referencias
> (`conductor_id`, `vehiculo_id`, `ruta_id`, `programacion_viaje_id`) se validan
> en la capa de negocio de cada servicio.

## Importación

### Opción 1: consola de MySQL

```powershell
C:\xampp\mysql\bin\mysql.exe -u root < db\logitrans.sql
```

O con el script incluido:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\import-db.ps1
```

### Opción 2: phpMyAdmin

1. Abrir <http://localhost/phpmyadmin>.
2. Pestaña **Importar**.
3. Seleccionar `db/logitrans.sql` y ejecutar.

El script crea la base si no existe, **recrea las tablas** y carga los datos de
prueba. Es seguro ejecutarlo varias veces, pero borra el contenido anterior.

## Usuarios de prueba

| Usuario | Correo | Contraseña | Rol |
|---|---|---|---|
| `admin` | admin@logitrans.com | `admin123` | administrador |
| `logistica` | logistica@logitrans.com | `logistica123` | logistica |

Las contraseñas se guardan con `password_hash()` (bcrypt) y se validan con
`password_verify()` en `ms-auth`.

## Verificar la conexión

Cada servicio expone un listado de su tabla principal:

```powershell
Invoke-WebRequest http://127.0.0.1:8001/usuarios       -UseBasicParsing   # ms-auth
Invoke-WebRequest http://127.0.0.1:8002/conductores    -UseBasicParsing   # ms-conductores
Invoke-WebRequest http://127.0.0.1:8003/vehiculos      -UseBasicParsing   # ms-vehiculos
Invoke-WebRequest http://127.0.0.1:8004/rutas          -UseBasicParsing   # ms-rutas
Invoke-WebRequest http://127.0.0.1:8004/programaciones -UseBasicParsing   # ms-rutas
Invoke-WebRequest http://127.0.0.1:8005/seguimientos   -UseBasicParsing   # ms-viajes
```
