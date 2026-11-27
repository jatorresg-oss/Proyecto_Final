# Contrato de programación de viajes (diseño — Día 9)

Documento de diseño para la **programación de viajes** que se implementará en el
**Día 10** en `ms-rutas` (puerto 8004) y se conectará al frontend en el **Día 11**.
El listado `GET /programaciones` ya existe; el resto de los endpoints de esta
tabla **todavía no están implementados**.

## 1. Restricción de arquitectura

- No hay comunicación entre microservicios (prohibido por el enunciado).
- Los 5 servicios comparten la base `logitrans`: `ms-rutas` consulta
  `conductores`, `vehiculos`, `rutas` y `programaciones_viajes` directamente en
  esa base. No llama a `ms-conductores` ni a `ms-vehiculos` por HTTP.
- El frontend **pre-validará** y deshabilitará opciones usando los listados de
  `ms-conductores` y `ms-vehiculos`, pero **`ms-rutas` vuelve a validar todo en
  backend** con los datos de la base compartida antes de guardar.

## 2. Datos: `programaciones_viajes`

| Campo | Tipo / regla |
|---|---|
| `id` | PK autoincremental |
| `conductor_id` | Referencia lógica a `conductores.id` (sin FK) |
| `vehiculo_id` | Referencia lógica a `vehiculos.id` (sin FK) |
| `ruta_id` | Referencia lógica a `rutas.id` (sin FK) |
| `fecha_salida` | `DATE` (`YYYY-MM-DD`) |
| `hora_salida` | `TIME` (`HH:MM:SS`; se normaliza `HH:MM` → `HH:MM:00`) |
| `fecha_estimada_llegada` | `DATE` (`YYYY-MM-DD`) |
| `observaciones` | Texto opcional |
| `estado` | ENUM(`programado`, `en_transito`, `retrasado`, `finalizado`, `cancelado`) |

El alta siempre crea el registro con `estado = programado`, aunque el cuerpo
traiga otro valor. Los cambios posteriores de estado de un viaje en curso son
responsabilidad de `ms-viajes` (Día 12) sobre la misma tabla.

## 3. Endpoints previstos (ms-rutas)

Todos requieren `Authorization: Bearer <token>` (el token se valida en la base
compartida). `/health` sigue público.

| Método | Ruta | Descripción | Respuesta |
|---|---|---|---|
| GET | `/programaciones` | Listado con filtros (ya implementado, se ampliará) | 200 + array |
| GET | `/programaciones/{id}` | Detalle de una programación | 200 + objeto |
| POST | `/programaciones` | Programar un viaje | 201 + objeto |
| PUT | `/programaciones/{id}` | Reprogramar (todos los campos editables) | 200 + objeto |
| PATCH | `/programaciones/{id}` | Reprogramación parcial / cancelación | 200 + objeto |

No se expone `DELETE /programaciones/{id}`: un viaje se **cancela** con
`PATCH { "estado": "cancelado" }` para conservar el historial.

### Filtros de `GET /programaciones`

`?conductor_id=&vehiculo_id=&ruta_id=&estado=&fecha=`

- `fecha` filtra por `fecha_salida` exacta.
- Los IDs se comparan por igualdad; `estado` por el valor del ENUM.
- Se ordena por `fecha_salida` descendente (comportamiento actual).

### Ejemplo de alta

```http
POST /programaciones
Authorization: Bearer <token>
Content-Type: application/json

{
  "conductor_id": 1,
  "vehiculo_id": 1,
  "ruta_id": 1,
  "fecha_salida": "2026-11-27",
  "hora_salida": "06:00",
  "fecha_estimada_llegada": "2026-11-28",
  "observaciones": "Salida desde el centro de carga"
}
```

Respuesta `201` con el objeto creado, incluyendo `id`, `estado: "programado"`
y los timestamps.

### Ejemplo de reprogramación

```http
PUT /programaciones/1
Authorization: Bearer <token>
Content-Type: application/json

{
  "conductor_id": 2,
  "vehiculo_id": 3,
  "ruta_id": 1,
  "fecha_salida": "2026-11-28",
  "hora_salida": "07:30:00",
  "fecha_estimada_llegada": "2026-11-29",
  "observaciones": "Reprogramado por mantenimiento"
}
```

`PATCH` acepta cualquiera de esos campos de forma parcial (por ejemplo
`{ "fecha_salida": "2026-11-29" }` o `{ "estado": "cancelado" }`).

## 4. Reglas de validación en backend

| Regla | Código | Mensaje sugerido |
|---|---|---|
| Cuerpo JSON inválido | 400 | `Cuerpo JSON inválido.` |
| Token ausente/inválido o sesión cerrada | 401 | (middleware) |
| Programación inexistente en detalle/edición | 404 | `Programación no encontrada.` |
| Faltan campos obligatorios (IDS, fechas y hora) | 422 | `... es obligatorio.` |
| IDs inexistentes en las tablas referenciadas | 422 | `El conductor/vehículo/ruta indicado no existe.` |
| Formato de fecha u hora inválido | 422 | `Fecha u hora no válida.` |
| `fecha_salida` en el pasado | 422 | `La fecha de salida no puede ser anterior a hoy.` |
| `fecha_estimada_llegada < fecha_salida` | 422 | `La llegada estimada no puede ser anterior a la salida.` |
| Estado fuera del ENUM | 422 | `Estado no válido.` |
| Conductor con estado `inactivo` | 409 | `El conductor no está disponible.` |
| Vehículo en `mantenimiento` o `inactivo` | 409 | `El vehículo no está disponible.` |
| Solapamiento del conductor o vehículo en la misma fecha | 409 | `El conductor o el vehículo ya tiene un viaje en esa fecha.` |
| Reprogramar un viaje `en_transito`, `finalizado` o `cancelado` | 409 | `Solo se puede reprogramar un viaje programado o retrasado.` |

### Solapamiento

Se considera ocupado un conductor/vehículo cuando existe otra programación con
la **misma `fecha_salida`** y estado `programado`, `en_transito` o `retrasado`.
No cuentan `finalizado` ni `cancelado`. En `PUT`/`PATCH` se excluye el propio
`id` de la consulta.

### Cancelación

Solo desde `programado` o `retrasado`. Un viaje `en_transito` o `finalizado` no
se puede cancelar desde `ms-rutas` (409); debe gestionarse como novedad en
`ms-viajes`.

Todas las respuestas de error usan `{ "message": "..." }` y
`Content-Type: application/json`.

## 5. Contrato previsto en el frontend (Día 11)

`api.js` agregará el objeto `Api.programaciones` (mismo patrón que
`conductores`, `vehiculos` y `rutas`):

```js
Api.programaciones.listar(filtros)      // GET /programaciones?...
Api.programaciones.obtener(id)         // GET /programaciones/{id}
Api.programaciones.crear(datos)        // POST /programaciones
Api.programaciones.reprogramar(id, datos) // PUT /programaciones/{id}
Api.programaciones.cancelar(id)        // PATCH { estado: "cancelado" }
```

Página `pages/programacion.html` + `js/programacion.js`:

1. Cargar catálogos en paralelo con `Api.conductores.listar({ estado: "disponible" })`,
   `Api.vehiculos.listar({ estado: "disponible" })` y `Api.rutas.listar()` para
   llenar los `<select>` dinámicos.
2. Mostrar datos auxiliares (licencia/telefono del conductor, capacidad del
   vehículo, distancia/tiempo de la ruta) y bloquear opciones no disponibles.
3. Validar en cliente fechas, hora y disponibilidad; el backend revalida.
4. Tabla de programaciones con filtros y badges de estado; alta, reprogramación
   y cancelación con mensajes de éxito/error.
5. Cualquier `401` limpia la sesión y vuelve al login (`apiRequest`).

## 6. Límite con ms-viajes (Día 12)

`ms-viajes` **no reprograma**: lee `programaciones_viajes` y actualiza solo
`estado`, además de insertar filas en `seguimientos_viajes`. Contrato previsto:

| Método | Ruta | Regla |
|---|---|---|
| POST | `/viajes/{id}/iniciar` | `programado` → `en_transito`; 409 si está cancelado |
| PATCH | `/viajes/{id}/estado` | Transición dentro del ENUM |
| POST | `/viajes/{id}/novedades` | Inserta seguimiento y actualiza el estado actual |
| POST | `/viajes/{id}/finalizar` | Solo si fue iniciado; 409 en caso contrario |
| GET | `/viajes/{id}/seguimiento` | Historial de novedades y estados |

## 7. Criterios de aceptación para el Día 10

- [ ] `GET /programaciones` admite los 5 filtros y mantiene 200 con el seed.
- [ ] `GET /programaciones/{id}` responde 200 / 404 correctamente.
- [ ] `POST /programaciones` crea con `estado = programado` y responde 201.
- [ ] No se puede programar conductor inactivo ni vehículo en mantenimiento (409).
- [ ] No se permite solapamiento por conductor/vehículo en la misma fecha (409).
- [ ] `PUT`/`PATCH` reprograman y validan el estado permitido (200 / 409 / 422).
- [ ] `PATCH { "estado": "cancelado" }` funciona solo desde `programado`/`retrasado`.
- [ ] Errores con `{ "message": "..." }` y códigos 400/401/404/409/422/500.
- [ ] `endpoints.http` y `README.md` de `ms-rutas` documentan los endpoints.
