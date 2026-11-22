# ms-auth · Autenticación

Microservicio de autenticación y sesiones del sistema **LogiTrans Express** (control de rutas y transporte de carga).

## Stack

- PHP 8+
- Slim Framework 4
- Eloquent ORM (`illuminate/database`)
- MySQL / MariaDB
- Composer

## Estructura

```
ms-auth/
├─ app/
│  ├─ Controllers/
│  │  ├─ AuthController.php        Login, logout y validación
│  │  └─ UsuariosController.php    Consulta de usuarios
│  ├─ Models/                      Usuario.php
│  ├─ Presentation/
│  │  ├─ Repositories/             AuthRepository.php, UsuariosRepository.php
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
C:\xampp\php\php.exe -S 127.0.0.1:8001 -t public
```

> `vendor/`, `.env` y `composer.lock` **no** se versionan (requisito del examen).

## Endpoints

| Método | Ruta | Autenticación | Descripción |
|---|---|---|---|
| GET | `/health` | No | Estado del microservicio |
| POST | `/login` | No | Inicia sesión; cuerpo `{ "usuario", "password" }` (también acepta `correo`) |
| GET | `/validate` | Sí | Verifica token, sesión activa y usuario activo |
| POST | `/logout` | Sí | Invalida el token (`token = NULL`, `sesion_activa = 0`) |
| GET | `/usuarios` | Sí | Listado de usuarios |

### Contrato de `/login`

Respuesta correcta (`200`):

```json
{
  "token": "64 caracteres hexadecimales",
  "user": {
    "id": 1,
    "nombre": "Administrador General",
    "correo": "admin@logitrans.com",
    "usuario": "admin",
    "rol": "administrador"
  }
}
```

Errores:

| Código | Situación | Respuesta |
|---|---|---|
| 400 | Cuerpo inválido o campos faltantes | `{ "message": "..." }` |
| 401 | Credenciales incorrectas o token inválido | `{ "message": "Credenciales incorrectas." }` |
| 403 | Usuario inactivo | `{ "message": "El usuario está inactivo..." }` |
| 500 | Error de base de datos | `{ "message": "..." }` |

El token se genera con `bin2hex(random_bytes(32))`. Las contraseñas se guardan
con `password_hash` (bcrypt) y se comprueban con `password_verify`. Nunca se
devuelven `contrasena` ni `token` dentro del objeto `user`.

## Autenticación en los 5 microservicios

Todos los recursos protegidos exigen la cabecera:

```http
Authorization: Bearer <token>
```

Cada microservicio valida el token directamente contra la tabla `usuarios` de la
base compartida `logitrans` (middleware `app/Middlewares/auth.php`). No hay
comunicación HTTP entre microservicios, tal como exige el enunciado.

## Base de datos

Base de datos: **`logitrans`**, compartida con todos los servicios del
proyecto. Este microservicio vive en `backend/ms-auth/` dentro del repositorio único.

## Estado del proyecto

- **Día 1:** esqueleto del microservicio, CORS, conexión a la base de datos y
  endpoint de salud.
- **Día 2:** base única `logitrans`, modelo ajustado y datos de prueba.
- **Día 3:** login, logout, `/validate` y middleware de token en los 5 servicios.
