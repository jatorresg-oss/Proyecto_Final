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
│  ├─ dashboard.html        Panel operativo (acceso a los módulos)
│  ├─ conductores.html      ABM de conductores, filtros y estados
│  ├─ vehiculos.html        ABM de vehículos, filtros y estados
│  └─ rutas.html            ABM de rutas, filtros y tiempos estimados
├─ css/
│  └─ styles.css
├─ js/
│  ├─ api.js                Servicios, fetch centralizado y contratos por recurso
│  ├─ auth.js               Manejo del token y de la sesión
│  ├─ login.js              Formulario de inicio de sesión
│  ├─ dashboard.js          Panel posterior al login
│  ├─ ui.js                 Utilidades compartidas (mensajes, badges, tablas)
│  ├─ conductores.js        Listado, formulario y acciones de conductores
│  ├─ vehiculos.js          Listado, formulario y acciones de vehículos
│  └─ rutas.js              Listado, formulario, filtros y tiempos de rutas
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

## Módulo de conductores (Día 5)

Página `pages/conductores.html`, accesible desde la tarjeta «Conductores» del
panel. Usa `GET /conductores` (con filtros `documento`, `licencia` y `estado`),
`GET /conductores/{id}`, `POST /conductores`, `PUT /conductores/{id}`,
`PATCH /conductores/{id}/estado` y `DELETE /conductores/{id}` de ms-conductores.

Incluye tabla con badges de estado, buscador, formulario de alta/edición,
cambio rápido de estado y eliminación con confirmación. Las reglas de
validación se repiten en `conductores.js` y en `ConductoresController.php`; la
respuesta del backend (incluidos los errores 409/422) se muestra al usuario.
Toda la sesión se valida antes de cargar la página y cualquier `401` devuelve
al login.

## Módulo de vehículos (Día 7)

Página `pages/vehiculos.html`, accesible desde la tarjeta «Vehículos» del panel
y desde la navegación superior de conductores. Usa `GET /vehiculos` (filtros
`placa`, `tipo` y `estado`), `GET /vehiculos/{id}`, `POST /vehiculos`,
`PUT /vehiculos/{id}`, `PATCH /vehiculos/{id}/estado` y `DELETE /vehiculos/{id}`
de ms-vehiculos.

Incluye tabla con badges de estado (incluido `mantenimiento`), buscador,
formulario de alta/edición, cambio rápido de estado y eliminación con
confirmación. Las reglas de validación se repiten en `vehiculos.js` y en
`VehiculosController.php`: placa única de 3 a 10 caracteres normalizada a
mayúsculas, capacidad mayor que 0 con máximo 2 decimales y estado dentro del
ENUM. El backend vuelve a validar y sus errores 409/422 se muestran al usuario.

### Refactor compartido

- `api.js` mantiene `apiRequest()` como único `fetch` y agrega `buildQuery()` y
  el objeto `Api` con los contratos de `auth`, `conductores`, `vehiculos` y
  `rutas`.
- `ui.js` centraliza mensajes, badges, celdas, botones, filas vacías, fecha ISO,
  formato numérico y confirmaciones. Los tres módulos lo usan.

## Módulo de rutas (Día 9)

Página `pages/rutas.html`, accesible desde la tarjeta «Rutas» del panel y desde
la navegación superior de conductores y vehículos. Usa `GET /rutas` (filtros
`ciudad`, `origen`, `destino` y `tiempo`), `GET /rutas/buscar`,
`GET /rutas/ciudad/{ciudad}`, `GET /rutas/tiempos`, `GET /rutas/{id}`,
`POST /rutas`, `PUT /rutas/{id}` y `DELETE /rutas/{id}` de ms-rutas.

Incluye tabla con distancia formateada, observaciones y botones de
alta/edición/eliminación con confirmación; filtros combinables; y un panel
«Ver tiempos estimados» que consume `GET /rutas/tiempos` (4 columnas). Las
reglas de validación se repiten en `rutas.js` y en `RutasController.php`:
origen/destino obligatorios de 2 a 100 caracteres, distancia > 0 con máximo 2
decimales y tiempo estimado de hasta 50 caracteres. El backend revalida y sus
errores 409/422 se muestran al usuario.

La programación de viajes no se implementa todavía: su contrato quedó diseñado
en [`docs/contrato-programacion.md`](../docs/contrato-programacion.md) para el
Día 10 y su frontend para el Día 11.

## Estado del proyecto

- **Día 1:** estructura del proyecto, estilos base, capa de acceso a la API y
  manejo de sesión.
- **Día 2:** reubicación dentro del repositorio único y datos de prueba.
- **Día 3:** login operativo, validación de sesión y cierre de sesión real.
- **Día 5:** módulo completo de conductores en el frontend.
- **Día 7:** frontend de vehículos y capas compartidas `api.js`/`ui.js`.
- **Día 9:** frontend de rutas y diseño del contrato de programación.
