# LogiTrans Express

Sistema Web de Control de Rutas y Transporte de Carga. Repositorio único del
proyecto: contiene el **frontend** y los **5 microservicios** del backend, todos
conectados a una sola base de datos (`logitrans`).

- **Frontend:** HTML5, CSS3 y JavaScript Vanilla (`fetch`).
- **Backend:** PHP 8 + Slim Framework 4 + Eloquent ORM + Composer.
- **Base de datos:** MySQL / MariaDB, base única `logitrans`.
- **Autenticación:** token simple validado contra la tabla `usuarios`.

## Estructura

```
frontend-logistica/
├─ frontend/                     Interfaz web (puerto 5501)
│  ├─ index.html                 Inicio de sesión
│  ├─ pages/
│  │  ├─ dashboard.html          Panel operativo
│  │  ├─ conductores.html        ABM de conductores
│  │  └─ vehiculos.html          ABM de vehículos
│  ├─ css/styles.css
│  └─ js/                        api.js, auth.js, login.js, dashboard.js,
│                                ui.js, conductores.js, vehiculos.js
├─ backend/
│  ├─ ms-auth/                   Autenticación            (8001)
│  ├─ ms-conductores/            Conductores              (8002)
│  ├─ ms-vehiculos/              Vehículos                (8003)
│  ├─ ms-rutas/                  Rutas y programación     (8004)
│  └─ ms-viajes/                 Viajes y seguimiento     (8005)
├─ db/
│  ├─ logitrans.sql              Esquema único + datos de prueba
│  └─ README.md                  Instrucciones de importación
├─ scripts/
│  ├─ start-all.ps1              Levanta frontend y servicios
│  ├─ health-check.ps1           Verifica /health
│  └─ import-db.ps1              Importa db/logitrans.sql
└─ README.md
```

Cada microservicio es **independiente**: tiene su propio `composer.json`,
`vendor/`, `.env` y puerto, y mantiene el patrón del profesor
(`Controllers / Models / Presentation\Repositories / Presentation\Routers /
Middlewares / global`). Comparten únicamente la base de datos `logitrans`.

## Requisitos

- XAMPP con PHP 8.2 y MySQL/MariaDB iniciados.
- Composer 2 (`C:\xampp\php\composer.phar`).

## Puesta en marcha

### 1. Base de datos

```powershell
powershell -ExecutionPolicy Bypass -File scripts\import-db.ps1
```

o desde phpMyAdmin importando `db/logitrans.sql`. Ver `db/README.md`.

### 2. Dependencias (una sola vez)

```powershell
C:\xampp\php\php.exe C:\xampp\php\composer.phar install --working-dir=backend\ms-auth
C:\xampp\php\php.exe C:\xampp\php\composer.phar install --working-dir=backend\ms-conductores
C:\xampp\php\php.exe C:\xampp\php\composer.phar install --working-dir=backend\ms-vehiculos
C:\xampp\php\php.exe C:\xampp\php\composer.phar install --working-dir=backend\ms-rutas
C:\xampp\php\php.exe C:\xampp\php\composer.phar install --working-dir=backend\ms-viajes
```

### 3. Servicios y frontend

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-all.ps1
powershell -ExecutionPolicy Bypass -File scripts\health-check.ps1
```

Abrir <http://127.0.0.1:5501>.

### Arranque manual (una terminal por servicio)

```powershell
cd backend\ms-auth;        C:\xampp\php\php.exe -S 127.0.0.1:8001 -t public
cd backend\ms-conductores; C:\xampp\php\php.exe -S 127.0.0.1:8002 -t public
cd backend\ms-vehiculos;   C:\xampp\php\php.exe -S 127.0.0.1:8003 -t public
cd backend\ms-rutas;       C:\xampp\php\php.exe -S 127.0.0.1:8004 -t public
cd backend\ms-viajes;      C:\xampp\php\php.exe -S 127.0.0.1:8005 -t public
cd frontend;               C:\xampp\php\php.exe -S 127.0.0.1:5501
```

> El puerto 5500 está ocupado por el listener de Oracle (`tnslsnr`); por eso el
> frontend usa el 5501.

## Endpoints

| Servicio | Puerto | Base | Endpoints implementados |
|---|---|---|---|
| ms-auth | 8001 | `logitrans` | `GET /health`, `POST /login`, `GET /validate`, `POST /logout`, `GET /usuarios` |
| ms-conductores | 8002 | `logitrans` | `GET /health`, CRUD de `/conductores`, búsquedas y cambio de estado |
| ms-vehiculos | 8003 | `logitrans` | `GET /health`, CRUD de `/vehiculos`, búsquedas y cambio de estado |
| ms-rutas | 8004 | `logitrans` | `GET /health`, `GET /rutas`, `GET /programaciones` |
| ms-viajes | 8005 | `logitrans` | `GET /health`, `GET /seguimientos` |

Cada servicio documenta su contrato en su propio `README.md` y en
`endpoints.http`.

## Autenticación

Todos los endpoints excepto `/health` y `POST /login` requieren la cabecera
`Authorization: Bearer <token>`. El token se obtiene con:

```http
POST http://127.0.0.1:8001/login
Content-Type: application/json

{ "usuario": "admin", "password": "admin123" }
```

Cada microservicio valida el token contra la tabla `usuarios` de `logitrans`
(consultando directamente la base compartida; no hay comunicación HTTP entre
servicios). El frontend guarda el token en `sessionStorage` y valida la sesión
al cargar el panel.

## Usuarios de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| `admin` | `admin123` | administrador |
| `logistica` | `logistica123` | logistica |

## Estado del proyecto

- **Día 1:** esqueleto de los 5 servicios, CORS, `/health`, login maquetado.
- **Día 2:** consolidación en un solo repositorio, base única `logitrans` con
  las 6 tablas, modelos ajustados y datos de prueba.
- **Día 3:** login, logout y `/validate` en ms-auth; middleware de token en los
  5 microservicios; guard de sesión en el frontend.
- **Día 4:** CRUD de conductores con búsquedas, cambio de estado y validaciones.
- **Día 5:** frontend de conductores: tabla con badges, filtros, formulario de
  alta/edición, cambio de estado y eliminación.
- **Día 6:** CRUD de vehículos con búsquedas por placa/estado/tipo, cambio de
  estado y validaciones.
- **Día 7:** frontend de vehículos y refactor de `api.js`/`ui.js` compartidos
  por los módulos de conductores y vehículos.

## Requisitos de entrega

- `vendor/`, `.env` y `composer.lock` **no** se versionan.
- Documentación de endpoints en el `README.md` y `endpoints.http` de cada
  servicio.
- Commits con el formato `Nombre: [descripción breve]`.
