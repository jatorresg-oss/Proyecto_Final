# ms-viajes · Viajes y seguimiento

Microservicio de viajes, estados y seguimiento del sistema **LogiTrans Express** (control de rutas y transporte de carga).

## Stack

- PHP 8+
- Slim Framework 4
- Eloquent ORM (`illuminate/database`)
- MySQL / MariaDB
- Composer

## Estructura

```
ms-viajes/
├─ app/
│  ├─ Controllers/          Lógica de negocio
│  ├─ Models/               Modelos Eloquent (incluye Usuario para auth)
│  ├─ Presentation/
│  │  ├─ Repositories/      Adaptadores HTTP (request → response)
│  │  └─ Routers/           Definición de endpoints
│  ├─ Middlewares/
│  │  ├─ cors.php
│  │  └─ auth.php            Valida token Bearer contra logitrans
│  └─ global/
│     └─ database_config.php
├─ public/index.php         Punto de entrada
├─ db/                      Scripts de la base de datos
├─ endpoints.http           Pruebas manuales de la API
├─ .env.example
├─ composer.json
└─ README.md
```

## Puesta en marcha

```bash
composer install
copy .env.example .env          # Linux/macOS: cp .env.example .env
C:\xampp\php\php.exe -S 127.0.0.1:8005 -t public
```

> `vendor/`, `.env` y `composer.lock` **no** se versionan (requisito del examen).

## Endpoints

| Método | Ruta | Autenticación | Descripción |
|---|---|---|---|
| GET | `/health` | No | Estado del microservicio |
| GET | `/seguimientos` | Sí (Bearer) | Listado de seguimientos (verificación de BD) |

Todos los recursos excepto `/health` exigen `Authorization: Bearer <token>`. El
token se obtiene en `POST http://127.0.0.1:8001/login` y se valida contra la base
compartida `logitrans`, sin comunicación HTTP con ms-auth.

## Base de datos

Base de datos: **`logitrans`**, compartida con todos los servicios del
proyecto. Este microservicio vive en `backend/ms-viajes/` dentro del repositorio único.

### Programaciones

`ms-rutas` crea las filas de `programaciones_viajes`. `ms-viajes` lee esa misma
tabla (está en la base compartida `logitrans`) para iniciar el viaje, cambiar su
estado y finalizarlo. Cada novedad se registra en `seguimientos_viajes`.

## Estado del proyecto

- **Día 1:** esqueleto del microservicio, CORS, conexión a la base de datos y
  endpoint de salud.
- **Día 2:** base única `logitrans`, modelo ajustado y datos de prueba.
- **Día 3:** middleware de token compartido por los 5 microservicios.