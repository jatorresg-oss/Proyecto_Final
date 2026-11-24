# ms-vehiculos · Vehículos

Microservicio de gestión de vehículos del sistema **LogiTrans Express** (control de rutas y transporte de carga).

## Stack

- PHP 8+
- Slim Framework 4
- Eloquent ORM (`illuminate/database`)
- MySQL / MariaDB
- Composer

## Estructura

```
ms-vehiculos/
├─ app/
│  ├─ Controllers/
│  │  └─ VehiculosController.php   Reglas de negocio y validaciones
│  ├─ Models/                      Vehiculo.php, Usuario.php (middleware)
│  ├─ Presentation/
│  │  ├─ Repositories/             VehiculosRepository.php
│  │  └─ Routers/                  endpoints.php
│  ├─ Middlewares/
│  │  ├─ cors.php
│  │  └─ auth.php                  Valida token Bearer contra logitrans
│  └─ global/
│     └─ database_config.php
├─ public/index.php                Punto de entrada
├─ db/                             Scripts de la base de datos
├─ endpoints.http                  Pruebas manuales de la API
├─ .env.example
├─ composer.json
└─ README.md
```

## Puesta en marcha

```bash
composer install
copy .env.example .env          # Linux/macOS: cp .env.example .env
C:\xampp\php\php.exe -S 127.0.0.1:8003 -t public
```

> `vendor/`, `.env` y `composer.lock` **no** se versionan (requisito del examen).

## Autenticación

Todos los endpoints, excepto `/health`, requieren:

```http
Authorization: Bearer <token>
```

El token se obtiene en `POST http://127.0.0.1:8001/login`. El middleware valida
el token contra la tabla `usuarios` de `logitrans` (base compartida), sin
comunicación HTTP con ms-auth.

## Endpoints

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado del microservicio (público) |
| GET | `/vehiculos` | Listado; acepta `?placa=&tipo=&estado=` |
| GET | `/vehiculos/buscar` | Alias de búsqueda con los mismos filtros |
| GET | `/vehiculos/estado/{estado}` | Listado por estado |
| GET | `/vehiculos/tipo/{tipo}` | Listado por tipo de vehículo |
| GET | `/vehiculos/{id}` | Detalle de un vehículo |
| POST | `/vehiculos` | Crear vehículo (`201`) |
| PUT | `/vehiculos/{id}` | Editar todos los datos |
| PATCH | `/vehiculos/{id}` | Editar datos parciales |
| PATCH | `/vehiculos/{id}/estado` | Cambiar estado |
| DELETE | `/vehiculos/{id}` | Eliminar (bloqueado si tiene programaciones) |

Campos del vehículo: `placa`, `tipo_vehiculo`, `capacidad_carga`, `modelo`,
`marca` y `estado`.

Estados permitidos: `disponible`, `en_ruta`, `mantenimiento`, `inactivo`.

### Ejemplo de creación

```http
POST /vehiculos
Authorization: Bearer <token>
Content-Type: application/json

{
  "placa": "DEF456",
  "tipo_vehiculo": "Camión",
  "capacidad_carga": 15.75,
  "modelo": "2022",
  "marca": "Volvo",
  "estado": "disponible"
}
```

Si se omite `estado`, se asigna `disponible`. La placa se normaliza a
mayúsculas antes de guardarse y de comprobarse la unicidad.

## Validaciones

- Campos obligatorios: `placa`, `tipo_vehiculo`, `capacidad_carga`, `modelo` y
  `marca`; `tipo_vehiculo`, `modelo` y `marca` no superan los 60 caracteres.
- `placa` **única**, con 3 a 10 caracteres alfanuméricos o guion; se ignoran al
  editar el propio registro (acepta `UNIQUE` de la tabla).
- `capacidad_carga` numérica, **mayor que 0**, con máximo 2 decimales y dentro
  del rango `DECIMAL(10,2)`.
- `estado` dentro del ENUM permitido, tanto al crear/editar como al cambiarlo.
- Al eliminar, se verifica que el vehículo no esté en `programaciones_viajes`.

Respuestas de error con `{ "message": "..." }`:

| Código | Situación |
|---|---|
| 400 | Cuerpo JSON inválido |
| 401 | Token ausente, inválido o sesión cerrada |
| 404 | Vehículo no encontrado |
| 409 | Placa duplicada o vehículo con programaciones |
| 422 | Datos inválidos (obligatorios, formato, capacidad o estado) |
| 500 | Error de base de datos |

## Base de datos

Base de datos: **`logitrans`**, compartida con todos los servicios del
proyecto. Este microservicio vive en `backend/ms-vehiculos/` dentro del
repositorio único.

## Estado del proyecto

- **Día 1:** esqueleto del microservicio, CORS, conexión a la base de datos y
  endpoint de salud.
- **Día 2:** base única `logitrans`, modelo ajustado y datos de prueba.
- **Día 3:** middleware de token compartido por los 5 microservicios.
- **Día 6:** CRUD, búsquedas por placa/estado/tipo y validaciones de vehículos.
