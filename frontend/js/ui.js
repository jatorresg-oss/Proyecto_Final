/**
 * Utilidades compartidas de interfaz (Día 7).
 * Las usan conductores.js y vehiculos.js para no repetir código de renderizado.
 * Cualquier dato que viene de la API se inserta como texto (nunca como HTML).
 */
const UI = {
  /** Muestra un mensaje de éxito o error con la clase correspondiente. */
  mostrarMensaje(elemento, texto, tipo = "success") {
    if (!elemento) {
      return;
    }
    elemento.textContent = texto;
    elemento.className = `message ${tipo}`;
  },

  limpiarMensaje(elemento) {
    if (!elemento) {
      return;
    }
    elemento.textContent = "";
    elemento.className = "message";
  },

  crearBadge(texto, variante = "") {
    const badge = document.createElement("span");
    badge.className = `badge ${variante}`.trim();
    badge.textContent = texto;
    return badge;
  },

  celdaTexto(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto == null ? "" : String(texto);
    return celda;
  },

  botonAccion(texto, accion, id, claseExtra = "") {
    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = `btn-small ${claseExtra}`.trim();
    boton.textContent = texto;
    boton.dataset.accion = accion;
    boton.dataset.id = String(id);
    return boton;
  },

  /** Fila única para tablas vacías o sin resultados. */
  filaVacia(columnas, mensaje) {
    const fila = document.createElement("tr");
    const celda = document.createElement("td");
    celda.colSpan = columnas;
    celda.className = "empty";
    celda.textContent = mensaje;
    fila.appendChild(celda);
    return fila;
  },

  hoyISO() {
    const ahora = new Date();
    const local = new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
  },

  /** Formatea números (por ejemplo, la capacidad de carga en toneladas). */
  numero(valor, decimales = 2) {
    const numero = Number(valor);
    return Number.isFinite(numero) ? numero.toFixed(decimales) : "—";
  },

  confirmar(mensaje) {
    return window.confirm(mensaje);
  },
};
