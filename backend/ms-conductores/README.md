# ms-conductores · Conductores

Microservicio de gestión de conductores del sistema **LogiTrans Express** (control de rutas y transporte de carga).

## Stack

- PHP 8+
- Slim Framework 4
- Eloquent ORM (`illuminate/database`)
- MySQL / MariaDB
- Composer

## Estructura

```
ms-conductores/
├─ app/
│  ├─ Controllers/
│  │  └─ ConductoresController.php  Reglas de negocio y validaciones
│  ├─ Models/                       Conductor.php, Usuario.php (middleware)
│  ├─ Presentation/
│  │  ├─ Repositories/              ConductoresRepository.php
│  │  └─ Routers/                   endpoints.php
│  ├─ Middlewares/
│  │  ├─ cors.php
│  │  └─ auth.php                   Valida token Bearer contra logitrans
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
C:\xampp\php\php.exe -S 127.0.0.1:8002 -t public
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
| GET | `/conductores` | Listado; acepta `?documento=&licencia=&estado=` |
| GET | `/conductores/buscar` | Alias de búsqueda con los mismos filtros |
| GET | `/conductores/estado/{estado}` | Listado por estado |
| GET | `/conductores/{id}` | Detalle de un conductor |
| POST | `/conductores` | Crear conductor (`201`) |
| PUT | `/conductores/{id}` | Editar todos los datos |
| PATCH | `/conductores/{id}` | Editar datos parciales |
| PATCH | `/conductores/{id}/estado` | Cambiar estado |
| DELETE | `/conductores/{id}` | Eliminar (bloqueado si tiene programaciones) |

Campos del conductor: `nombres`, `apellidos`, `documento`, `telefono`, `correo`,
`numero_licencia`, `categoria_licencia`, `fecha_vencimiento_licencia` y `estado`.

Estados permitidos: `disponible`, `en_ruta`, `inactivo`.

### Ejemplo de creación

```http
POST /conductores
Authorization: Bearer <token>
Content-Type: application/json

{
  "nombres": "Ana María",
  "apellidos": "López",
  "documento": "11223344",
  "telefono": "3005551122",
  "correo": "ana.lopez@logitrans.com",
  "numero_licencia": "LIC-11223344",
  "categoria_licencia": "C1",
  "fecha_vencimiento_licencia": "2029-08-15"
}
```

## Validaciones

- Campos obligatorios con longitudes acordes al esquema (`nombres` y
  `apellidos` entre 2 y 120 caracteres).
- `documento`, `correo` y `numero_licencia` **únicos**; se ignoran al editar el
  propio registro.
- Correo con formato válido; teléfono, documento y licencia con formatos
  restringidos.
- `fecha_vencimiento_licencia` en formato `YYYY-MM-DD` y **no vencida**.
- `estado` dentro del ENUM permitido.
- Al eliminar, se verifica que el conductor no esté en `programaciones_viajes`.

Respuestas de error con `{ "message": "..." }`:

| Código | Situación |
|---|---|
| 400 | Cuerpo JSON inválido |
| 401 | Token ausente, inválido o sesión cerrada |
| 404 | Conductor no encontrado |
| 409 | Documento/correo/licencia duplicados o conductor con programaciones |
| 422 | Datos inválidos (formato, obligatorios o estado) |
| 500 | Error de base de datos |

## Base de datos

Base de datos: **`logitrans`**, compartida con todos los servicios del
proyecto. Este microservicio vive en `backend/ms-conductores/` dentro del
repositorio único.

## Estado del proyecto

- **Día 1:** esqueleto del microservicio, CORS, conexión a la base de datos y
  endpoint de salud.
- **Día 2:** base única `logitrans`, modelo ajustado y datos de prueba.
- **Día 3:** middleware de token compartido por los 5 microservicios.
- **Día 4:** CRUD, búsquedas, cambio de estado y validaciones de conductores.
