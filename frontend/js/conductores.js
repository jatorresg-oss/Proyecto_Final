/**
 * Módulo de conductores (Día 5).
 * Consume ms-conductores (8002) con el token Bearer de la sesión.
 * La validación del cliente es solo apoyo: el backend vuelve a validar todo.
 */

const ESTADOS_CONDUCTOR = {
  disponible: "Disponible",
  en_ruta: "En ruta",
  inactivo: "Inactivo",
};

const CAMPOS_CONDUCTOR = [
  "nombres",
  "apellidos",
  "documento",
  "telefono",
  "correo",
  "numero_licencia",
  "categoria_licencia",
  "fecha_vencimiento_licencia",
  "estado",
];

// Utilidades compartidas (ui.js): se conservan los nombres que ya usaba el módulo.
const mostrarMensaje = UI.mostrarMensaje;
const limpiarMensaje = UI.limpiarMensaje;
const crearBadge = UI.crearBadge;
const celdaTexto = UI.celdaTexto;
const botonAccion = UI.botonAccion;
const hoyISO = UI.hoyISO;

let dom = {};

if (Session.requireAuth()) {
  initConductores();
}

async function initConductores() {
  const valid = await Session.validateSession();
  if (!valid) {
    return;
  }

  dom = {
    userName: document.getElementById("userName"),
    pageMessage: document.getElementById("pageMessage"),
    filtros: document.forms["filtrosForm"],
    body: document.getElementById("conductoresBody"),
    panel: document.getElementById("formPanel"),
    form: document.forms["conductorForm"],
    formTitulo: document.getElementById("formTitulo"),
    formMessage: document.getElementById("formMessage"),
    guardarBtn: document.getElementById("guardarBtn"),
    logoutBtn: document.getElementById("logoutBtn"),
  };

  const user = Session.getUser() || {};
  dom.userName.textContent = user.nombre || user.usuario || user.correo || "Usuario";

  document.getElementById("fecha_vencimiento_licencia").min = hoyISO();

  dom.logoutBtn.addEventListener("click", async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    button.textContent = "Cerrando sesión…";
    await Session.logout();
  });

  dom.filtros.addEventListener("submit", async (event) => {
    event.preventDefault();
    await cargarConductores();
  });

  document.getElementById("limpiarFiltros").addEventListener("click", async () => {
    dom.filtros.reset();
    limpiarMensaje(dom.pageMessage);
    await cargarConductores();
  });

  document.getElementById("nuevoBtn").addEventListener("click", abrirNuevo);
  document.getElementById("cancelarBtn").addEventListener("click", cerrarFormulario);
  dom.form.addEventListener("submit", guardarConductor);

  dom.body.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-accion]");
    if (!button) {
      return;
    }

    const id = Number(button.dataset.id);
    if (button.dataset.accion === "editar") {
      editarConductor(id);
    } else if (button.dataset.accion === "eliminar") {
      eliminarConductor(id);
    }
  });

  dom.body.addEventListener("change", (event) => {
    const select = event.target.closest("select[data-accion='cambiar-estado']");
    if (select) {
      cambiarEstado(Number(select.dataset.id), select.value);
    }
  });

  await cargarConductores();
}

/** Lista los conductores aplicando los filtros del formulario. */
async function cargarConductores(limpiarMensajeAlCargar = true) {
  const datos = new FormData(dom.filtros);
  const filtros = {};

  for (const clave of ["documento", "licencia", "estado"]) {
    const valor = String(datos.get(clave) || "").trim();
    if (valor !== "") {
      filtros[clave] = valor;
    }
  }

  try {
    const conductores = await Api.conductores.listar(filtros);
    renderTabla(Array.isArray(conductores) ? conductores : []);
    if (limpiarMensajeAlCargar) {
      limpiarMensaje(dom.pageMessage);
    }
  } catch (error) {
    renderTabla([]);
    mostrarMensaje(dom.pageMessage, error.message || "No fue posible cargar los conductores.", "error");
  }
}

/** Dibuja la tabla; los datos se insertan como texto para evitar HTML inyectado. */
function renderTabla(conductores) {
  dom.body.textContent = "";

  if (conductores.length === 0) {
    dom.body.appendChild(
      UI.filaVacia(8, "No hay conductores que coincidan con la búsqueda.")
    );
    return;
  }

  const fragmento = document.createDocumentFragment();

  conductores.forEach((conductor) => {
    const fila = document.createElement("tr");

    const nombre = document.createElement("td");
    nombre.textContent = `${conductor.nombres || ""} ${conductor.apellidos || ""}`.trim();
    fila.appendChild(nombre);

    fila.appendChild(celdaTexto(conductor.documento));
    fila.appendChild(celdaTexto(conductor.telefono));
    fila.appendChild(celdaTexto(conductor.correo));

    const licencia = document.createElement("td");
    licencia.appendChild(document.createTextNode(conductor.numero_licencia || ""));
    if (conductor.categoria_licencia) {
      const categoria = document.createElement("span");
      categoria.className = "cell-note";
      categoria.textContent = `Cat. ${conductor.categoria_licencia}`;
      licencia.appendChild(categoria);
    }
    fila.appendChild(licencia);

    const vencimiento = document.createElement("td");
    const fecha = String(conductor.fecha_vencimiento_licencia || "").slice(0, 10);
    vencimiento.textContent = fecha;
    if (fecha && fecha < hoyISO()) {
      vencimiento.appendChild(crearBadge("Vencida", "warning"));
    }
    fila.appendChild(vencimiento);

    const estado = conductor.estado || "disponible";
    const celdaEstado = document.createElement("td");
    celdaEstado.appendChild(crearBadge(ESTADOS_CONDUCTOR[estado] || estado, `estado-${estado}`));
    fila.appendChild(celdaEstado);

    const acciones = document.createElement("td");
    acciones.className = "acciones";

    const selector = document.createElement("select");
    selector.className = "btn-small select-estado";
    selector.title = "Cambiar estado";
    selector.dataset.accion = "cambiar-estado";
    selector.dataset.id = String(conductor.id);
    Object.entries(ESTADOS_CONDUCTOR).forEach(([valor, etiqueta]) => {
      const opcion = document.createElement("option");
      opcion.value = valor;
      opcion.textContent = etiqueta;
      selector.appendChild(opcion);
    });
    selector.value = estado;
    acciones.appendChild(selector);

    acciones.appendChild(botonAccion("Editar", "editar", conductor.id));
    acciones.appendChild(botonAccion("Eliminar", "eliminar", conductor.id, "btn-danger"));
    fila.appendChild(acciones);

    fragmento.appendChild(fila);
  });

  dom.body.appendChild(fragmento);
}

/** Prepara el formulario para crear un conductor nuevo. */
function abrirNuevo() {
  dom.form.reset();
  dom.form.elements["estado"].value = "disponible";
  document.getElementById("conductorId").value = "";
  document.getElementById("fecha_vencimiento_licencia").min = hoyISO();
  dom.formTitulo.textContent = "Nuevo conductor";
  dom.guardarBtn.textContent = "Guardar conductor";
  limpiarMensaje(dom.formMessage);
  dom.panel.hidden = false;
  document.getElementById("nombres").focus();
  dom.panel.scrollIntoView({ behavior: "smooth", block: "start" });
}

/** Carga un conductor existente y lo coloca en el formulario. */
async function editarConductor(id) {
  try {
    const conductor = await Api.conductores.obtener(id);
    dom.form.reset();

    CAMPOS_CONDUCTOR.forEach((campo) => {
      const elemento = dom.form.elements[campo];
      if (!elemento) {
        return;
      }

      const valor = conductor[campo] == null ? "" : String(conductor[campo]);
      elemento.value = campo === "fecha_vencimiento_licencia" ? valor.slice(0, 10) : valor;
    });

    document.getElementById("conductorId").value = String(conductor.id);
    document.getElementById("fecha_vencimiento_licencia").min = hoyISO();
    dom.formTitulo.textContent = `Editar conductor #${conductor.id}`;
    dom.guardarBtn.textContent = "Actualizar conductor";
    limpiarMensaje(dom.formMessage);
    dom.panel.hidden = false;
    dom.panel.scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (error) {
    mostrarMensaje(dom.pageMessage, error.message || "No fue posible cargar el conductor.", "error");
  }
}

function cerrarFormulario() {
  dom.panel.hidden = true;
  dom.form.reset();
  limpiarMensaje(dom.formMessage);
}

/** Crea o actualiza un conductor según haya o no ID en el formulario. */
async function guardarConductor(event) {
  event.preventDefault();

  const datos = {};
  CAMPOS_CONDUCTOR.forEach((campo) => {
    const elemento = dom.form.elements[campo];
    datos[campo] = elemento ? String(elemento.value).trim() : "";
  });

  const errores = validarConductor(datos);
  if (errores.length > 0) {
    mostrarMensaje(dom.formMessage, errores.join(" "), "error");
    return;
  }

  const id = document.getElementById("conductorId").value;
  const boton = dom.guardarBtn;
  boton.disabled = true;

  try {
    if (id) {
      await Api.conductores.actualizar(id, datos);
    } else {
      await Api.conductores.crear(datos);
    }
    cerrarFormulario();
    mostrarMensaje(
      dom.pageMessage,
      id ? "Conductor actualizado correctamente." : "Conductor creado correctamente.",
      "success"
    );
    await cargarConductores(false);
  } catch (error) {
    mostrarMensaje(dom.formMessage, error.message || "No fue posible guardar el conductor.", "error");
  } finally {
    boton.disabled = false;
  }
}

/** Cambia el estado desde el selector de la tabla (PATCH /conductores/{id}/estado). */
async function cambiarEstado(id, estado) {
  if (!Object.prototype.hasOwnProperty.call(ESTADOS_CONDUCTOR, estado)) {
    mostrarMensaje(dom.pageMessage, "Estado no válido.", "error");
    await cargarConductores(false);
    return;
  }

  try {
    await Api.conductores.cambiarEstado(id, estado);
    mostrarMensaje(dom.pageMessage, "Estado del conductor actualizado.", "success");
  } catch (error) {
    mostrarMensaje(dom.pageMessage, error.message || "No fue posible cambiar el estado.", "error");
  } finally {
    await cargarConductores(false);
  }
}

async function eliminarConductor(id) {
  if (!UI.confirmar("¿Eliminar este conductor? Esta acción no se puede deshacer.")) {
    await cargarConductores();
    return;
  }

  try {
    await Api.conductores.eliminar(id);
    mostrarMensaje(dom.pageMessage, "Conductor eliminado correctamente.", "success");
  } catch (error) {
    mostrarMensaje(dom.pageMessage, error.message || "No fue posible eliminar el conductor.", "error");
  } finally {
    await cargarConductores(false);
  }
}

/** Validación en cliente equivalente a las reglas de ConductoresController. */
function validarConductor(datos) {
  const errores = [];
  const obligatorios = ["nombres", "apellidos", "documento", "telefono", "correo", "numero_licencia", "categoria_licencia", "fecha_vencimiento_licencia"];

  obligatorios.forEach((campo) => {
    if (!datos[campo]) {
      errores.push(`El campo ${campo.replace(/_/g, " ")} es obligatorio.`);
    }
  });

  if (datos.nombres && (datos.nombres.length < 2 || datos.nombres.length > 120)) {
    errores.push("Nombres debe tener entre 2 y 120 caracteres.");
  }
  if (datos.apellidos && (datos.apellidos.length < 2 || datos.apellidos.length > 120)) {
    errores.push("Apellidos debe tener entre 2 y 120 caracteres.");
  }
  if (datos.documento && !/^[A-Za-z0-9.\-]{5,30}$/.test(datos.documento)) {
    errores.push("Documento no válido (5 a 30 caracteres alfanuméricos, punto o guion).");
  }
  if (datos.telefono && !/^[0-9+()\s\-]{7,30}$/.test(datos.telefono)) {
    errores.push("Teléfono no válido (7 a 30 caracteres).");
  }
  if (datos.correo && (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(datos.correo) || datos.correo.length > 150)) {
    errores.push("Correo electrónico no válido.");
  }
  if (datos.numero_licencia && !/^[A-Za-z0-9\-]{4,50}$/.test(datos.numero_licencia)) {
    errores.push("Número de licencia no válido (4 a 50 caracteres alfanuméricos o guion).");
  }
  if (datos.categoria_licencia && !/^[A-Za-z0-9\-]{1,10}$/.test(datos.categoria_licencia)) {
    errores.push("Categoría de licencia no válida (máximo 10 caracteres).");
  }

  if (datos.fecha_vencimiento_licencia) {
    const fecha = datos.fecha_vencimiento_licencia;
    const valida = /^\d{4}-\d{2}-\d{2}$/.test(fecha) && !Number.isNaN(Date.parse(fecha));
    if (!valida) {
      errores.push("Fecha de vencimiento de la licencia debe tener formato YYYY-MM-DD.");
    } else if (fecha < hoyISO()) {
      errores.push("La licencia del conductor debe estar vigente (fecha no vencida).");
    }
  }

  if (!Object.prototype.hasOwnProperty.call(ESTADOS_CONDUCTOR, datos.estado)) {
    errores.push("Estado no válido.");
  }

  return errores;
}

// fin del módulo de conductores
