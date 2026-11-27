# ms-rutas · Rutas y programación

Microservicio de rutas y programación de viajes del sistema **LogiTrans Express**
(control de rutas y transporte de carga).

## Stack

- PHP 8+
- Slim Framework 4
- Eloquent ORM (`illuminate/database`)
- MySQL / MariaDB
- Composer

## Estructura

```
ms-rutas/
├─ app/
│  ├─ Controllers/
│  │  ├─ RutasController.php           Reglas de negocio y validaciones
│  │  └─ ProgramacionesController.php  Listado inicial (Día 10)
│  ├─ Models/                          Ruta.php, ProgramacionViaje.php, Usuario.php
│  ├─ Presentation/
│  │  ├─ Repositories/                 RutasRepository.php, ProgramacionesRepository.php
│  │  └─ Routers/                      endpoints.php
│  ├─ Middlewares/
│  │  ├─ cors.php
│  │  └─ auth.php                      Valida token Bearer contra logitrans
│  └─ global/
│     └─ database_config.php
├─ public/index.php                    Punto de entrada
├─ db/                                 Scripts de la base de datos
├─ endpoints.http                      Pruebas manuales de la API
├─ .env.example
├─ composer.json
└─ README.md
```

## Puesta en marcha

```bash
composer install
copy .env.example .env          # Linux/macOS: cp .env.example .env
C:\xampp\php\php.exe -S 127.0.0.1:8004 -t public
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

## Endpoints de rutas

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/health` | Estado del microservicio (público) |
| GET | `/rutas` | Listado; acepta `?ciudad=&origen=&destino=&tiempo=` |
| GET | `/rutas/buscar` | Alias de búsqueda con los mismos filtros |
| GET | `/rutas/ciudad/{ciudad}` | Rutas cuyo origen **o** destino coincide |
| GET | `/rutas/tiempos` | Consulta de tiempos estimados |
| GET | `/rutas/{id}` | Detalle de una ruta |
| POST | `/rutas` | Crear ruta (`201`) |
| PUT | `/rutas/{id}` | Editar todos los datos |
| PATCH | `/rutas/{id}` | Editar datos parciales |
| DELETE | `/rutas/{id}` | Eliminar (bloqueado si tiene programaciones) |

Campos de la ruta: `ciudad_origen`, `ciudad_destino`, `distancia`,
`tiempo_estimado` y `observaciones` (opcional).

### Ejemplo de creación

```http
POST /rutas
Authorization: Bearer <token>
Content-Type: application/json

{
  "ciudad_origen": "Bogotá",
  "ciudad_destino": "Bucaramanga",
  "distancia": 390.50,
  "tiempo_estimado": "7 horas",
  "observaciones": "Ruta de prueba"
}
```

## Validaciones

- Campos obligatorios: `ciudad_origen`, `ciudad_destino`, `distancia` y
  `tiempo_estimado`; las ciudades tienen entre 2 y 100 caracteres y el tiempo
  estimado no supera los 50.
- **Ruta duplicada** (origen + destino): la tabla `rutas` no tiene `UNIQUE`, así
  que la regla se valida en PHP e ignora mayúsculas (`409`).
- `distancia` numérica, **mayor que 0**, con máximo 2 decimales y dentro del
  rango `DECIMAL(10,2)` (`422` si no cumple).
- `observaciones` es opcional; un valor vacío se guarda como `NULL`.
- Al eliminar, se verifica que la ruta no esté en `programaciones_viajes`.

Respuestas de error con `{ "message": "..." }`:

| Código | Situación |
|---|---|
| 400 | Cuerpo JSON inválido |
| 401 | Token ausente, inválido o sesión cerrada |
| 404 | Ruta no encontrada |
| 409 | Origen + destino duplicados o ruta con programaciones |
| 422 | Datos inválidos (obligatorios, formato o distancia) |
| 500 | Error de base de datos |

## Programación de viajes

`GET /programaciones` sigue disponible con el listado inicial. El alta, los
filtros y la reprogramación se implementan en el **Día 10** en este mismo
microservicio, porque el enunciado asigna la programación a `ms-rutas`.

El contrato completo (endpoints, payloads, validaciones, solapamientos y
responsabilidades frente a `ms-viajes`) quedó documentado en el Día 9 en
[`docs/contrato-programacion.md`](../../docs/contrato-programacion.md).
Resumen de lo previsto:

| Método | Ruta prevista | Uso |
|---|---|---|
| GET | `/programaciones?conductor_id=&vehiculo_id=&ruta_id=&estado=&fecha=` | Listado filtrado |
| GET | `/programaciones/{id}` | Detalle |
| POST | `/programaciones` | Programar (siempre nace `programado`) |
| PUT / PATCH | `/programaciones/{id}` | Reprogramar o cancelar |

No se prevé `DELETE`: la cancelación es `PATCH { "estado": "cancelado" }`.
Las reglas se revalidan en backend contra la base compartida: conductor no
inactivo, vehículo sin mantenimiento, fechas válidas y sin solapamiento en la
misma fecha. No hay llamadas HTTP a otros microservicios.

## Base de datos

Base de datos: **`logitrans`**, compartida con todos los servicios del
proyecto. Este microservicio vive en `backend/ms-rutas/` dentro del repositorio
único. La tabla `programaciones_viajes` se consulta directamente (sin llamadas
HTTP a otros microservicios) para validar las referencias lógicas.

## Estado del proyecto

- **Día 1:** esqueleto del microservicio, CORS, conexión a la base de datos y
  endpoint de salud.
- **Día 2:** base única `logitrans`, modelo ajustado y datos de prueba.
- **Día 3:** middleware de token compartido por los 5 microservicios.
- **Día 8:** CRUD de rutas, duplicados en PHP, distancia > 0 y búsqueda por ciudad.
- **Día 9:** diseño del contrato de programación para el Día 10 en
  `docs/contrato-programacion.md`.
