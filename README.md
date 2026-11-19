# frontend-logistica

Aplicación frontend de **LogiTrans Express**: control de rutas y transporte de carga.
Desarrollada con HTML5, CSS3 y JavaScript Vanilla (sin frameworks ni jQuery).

## Estructura

```
frontend-logistica/
├─ index.html               Inicio de sesión
├─ pages/
│  └─ dashboard.html        Panel operativo
├─ css/
│  └─ styles.css
├─ js/
│  ├─ api.js                Configuración de microservicios y fetch centralizado
│  ├─ auth.js               Manejo del token y de la sesión
│  ├─ login.js              Formulario de inicio de sesión
│  └─ dashboard.js          Panel posterior al login
└─ README.md
```

## Ejecución

```bash
C:\xampp\php\php.exe -S 127.0.0.1:5501
```

Luego abrir <http://127.0.0.1:5501>. También se puede servir desde Apache
copiando la carpeta a `C:\xampp\htdocs`.

## Microservicios

| Servicio | URL base | Módulo |
|---|---|---|
| ms-auth | http://127.0.0.1:8001 | Autenticación |
| ms-conductores | http://127.0.0.1:8002 | Conductores |
| ms-vehiculos | http://127.0.0.1:8003 | Vehículos |
| ms-rutas | http://127.0.0.1:8004 | Rutas y programación |
| ms-viajes | http://127.0.0.1:8005 | Viajes y seguimiento |

## Autenticación

El token se guarda en `sessionStorage` (`logitrans.token`) y se envía en la
cabecera `Authorization: Bearer <token>`. Si un microservicio responde `401`,
la sesión se limpia y se vuelve al login.

## Estado del proyecto

- **Día 1:** estructura del proyecto, estilos base, capa de acceso a la API y
  manejo de sesión. El login quedará operativo cuando `ms-auth` exponga
  `POST /login` (Día 2).