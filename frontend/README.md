# Frontend LogiTrans Express

Aplicación web del sistema **LogiTrans Express**: control de rutas y transporte
de carga. Desarrollada con HTML5, CSS3 y JavaScript Vanilla (sin frameworks ni
jQuery).

Vive dentro del repositorio único del proyecto, en la carpeta `frontend/`. La
documentación general y los microservicios están en el `README.md` de la raíz.

## Estructura

```
frontend/
├─ index.html               Inicio de sesión
├─ pages/
│  └─ dashboard.html        Panel operativo
├─ css/
│  └─ styles.css
├─ js/
│  ├─ api.js                Configuración de servicios y fetch centralizado
│  ├─ auth.js               Manejo del token y de la sesión
│  ├─ login.js              Formulario de inicio de sesión
│  └─ dashboard.js          Panel posterior al login
└─ README.md
```

## Ejecución

```powershell
cd frontend
C:\xampp\php\php.exe -S 127.0.0.1:5501
```

Luego abrir <http://127.0.0.1:5501>. También se puede servir desde Apache
copiando la carpeta a `C:\xampp\htdocs`.

## Servicios que consume

| Servicio | URL base | Módulo |
|---|---|---|
| ms-auth | http://127.0.0.1:8001 | Autenticación |
| ms-conductores | http://127.0.0.1:8002 | Conductores |
| ms-vehiculos | http://127.0.0.1:8003 | Vehículos |
| ms-rutas | http://127.0.0.1:8004 | Rutas y programación |
| ms-viajes | http://127.0.0.1:8005 | Viajes y seguimiento |

Todos los servicios trabajan sobre la misma base de datos `logitrans`.

## Autenticación

El token se guarda en `sessionStorage` (`logitrans.token`) y se envía en la
cabecera `Authorization: Bearer <token>`.

Flujo implementado:

1. `login.js` envía `POST /login` a ms-auth con `{ usuario, password }` y guarda
   `{ token, user }`.
2. Al entrar al panel, `dashboard.js` llama a `GET /validate`; si falla, limpia
   la sesión y devuelve al login.
3. `apiRequest()` detecta cualquier `401`, limpia la sesión y redirige al login.
4. El botón «Cerrar sesión» llama a `POST /logout` para invalidar el token en la
   base de datos antes de limpiar `sessionStorage`.

## Estado del proyecto

- **Día 1:** estructura del proyecto, estilos base, capa de acceso a la API y
  manejo de sesión.
- **Día 2:** reubicación dentro del repositorio único y datos de prueba.
- **Día 3:** login operativo, validación de sesión y cierre de sesión real.
