/**
 * Módulo de rutas (Día 9).
 * Consume ms-rutas (8004) con el token Bearer de la sesión.
 * La validación del cliente es solo apoyo: el backend vuelve a validar todo.
 */

const CAMPOS_RUTA = [
  "ciudad_origen",
  "ciudad_destino",
  "distancia",
  "tiempo_estimado",
  "observaciones",
];

let dom = {};

if (Session.requireAuth()) {
  initRutas();
}

async function initRutas() {
  const valid = await Session.validateSession();
  if (!valid) {
    return;
  }

  dom = {
    userName: document.getElementById("userName"),
    pageMessage: document.getElementById("pageMessage"),
    filtros: document.forms["filtrosForm"],
    body: document.getElementById("rutasBody"),
    tiemposBtn: document.getElementById("tiemposBtn"),
    tiemposPanel: document.getElementById("tiemposPanel"),
    tiemposBody: document.getElementById("tiemposBody"),
    panel: document.getElementById("formPanel"),
    form: document.forms["rutaForm"],
    formTitulo: document.getElementById("formTitulo"),
    formMessage: document.getElementById("formMessage"),
    guardarBtn: document.getElementById("guardarBtn"),
    logoutBtn: document.getElementById("logoutBtn"),
  };

  const user = Session.getUser() || {};
  dom.userName.textContent = user.nombre || user.usuario || user.correo || "Usuario";

  dom.logoutBtn.addEventListener("click", async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    button.textContent = "Cerrando sesión…";
    await Session.logout();
  });

  dom.filtros.addEventListener("submit", async (event) => {
    event.preventDefault();
    await cargarRutas();
  });

  document.getElementById("limpiarFiltros").addEventListener("click", async () => {
    dom.filtros.reset();
    UI.limpiarMensaje(dom.pageMessage);
    await cargarRutas();
  });

  document.getElementById("nuevoBtn").addEventListener("click", abrirNuevo);
  document.getElementById("cancelarBtn").addEventListener("click", cerrarFormulario);
  dom.tiemposBtn.addEventListener("click", toggleTiempos);
  dom.form.addEventListener("submit", guardarRuta);

  dom.body.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-accion]");
    if (!button) {
      return;
    }

    const id = Number(button.dataset.id);
    if (button.dataset.accion === "editar") {
      editarRuta(id);
    } else if (button.dataset.accion === "eliminar") {
      eliminarRuta(id);
    }
  });

  await cargarRutas();
}

/** Lista las rutas aplicando los filtros del formulario. */
async function cargarRutas(limpiarMensajeAlCargar = true) {
  const datos = new FormData(dom.filtros);
  const filtros = {};

  for (const clave of ["ciudad", "origen", "destino", "tiempo"]) {
    const valor = String(datos.get(clave) || "").trim();
    if (valor !== "") {
      filtros[clave] = valor;
    }
  }

  try {
    const rutas = await Api.rutas.listar(filtros);
    renderTabla(Array.isArray(rutas) ? rutas : []);
    if (limpiarMensajeAlCargar) {
      UI.limpiarMensaje(dom.pageMessage);
    }
  } catch (error) {
    renderTabla([]);
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible cargar las rutas.", "error");
  }
}

/** Dibuja la tabla; los datos se insertan como texto para evitar HTML inyectado. */
function renderTabla(rutas) {
  dom.body.textContent = "";

  if (rutas.length === 0) {
    dom.body.appendChild(UI.filaVacia(6, "No hay rutas que coincidan con la búsqueda."));
    return;
  }

  const fragmento = document.createDocumentFragment();

  rutas.forEach((ruta) => {
    const fila = document.createElement("tr");

    fila.appendChild(UI.celdaTexto(ruta.ciudad_origen));
    fila.appendChild(UI.celdaTexto(ruta.ciudad_destino));
    fila.appendChild(UI.celdaTexto(UI.numero(ruta.distancia, 2)));
    fila.appendChild(UI.celdaTexto(ruta.tiempo_estimado));
    fila.appendChild(UI.celdaTexto(ruta.observaciones || "—"));

    const acciones = document.createElement("td");
    acciones.className = "acciones";
    acciones.appendChild(UI.botonAccion("Editar", "editar", ruta.id));
    acciones.appendChild(UI.botonAccion("Eliminar", "eliminar", ruta.id, "btn-danger"));
    fila.appendChild(acciones);

    fragmento.appendChild(fila);
  });

  dom.body.appendChild(fragmento);
}

/** Muestra u oculta el panel con GET /rutas/tiempos. */
async function toggleTiempos() {
  if (!dom.tiemposPanel.hidden) {
    dom.tiemposPanel.hidden = true;
    dom.tiemposBtn.textContent = "Ver tiempos estimados";
    return;
  }

  try {
    const tiempos = await Api.rutas.tiempos();
    renderTiempos(Array.isArray(tiempos) ? tiempos : []);
    dom.tiemposPanel.hidden = false;
    dom.tiemposBtn.textContent = "Ocultar tiempos";
  } catch (error) {
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible consultar los tiempos.", "error");
  }
}

function renderTiempos(tiempos) {
  dom.tiemposBody.textContent = "";

  if (tiempos.length === 0) {
    dom.tiemposBody.appendChild(UI.filaVacia(3, "No hay tiempos estimados registrados."));
    return;
  }

  const fragmento = document.createDocumentFragment();

  tiempos.forEach((ruta) => {
    const fila = document.createElement("tr");
    fila.appendChild(UI.celdaTexto(ruta.ciudad_origen));
    fila.appendChild(UI.celdaTexto(ruta.ciudad_destino));
    fila.appendChild(UI.celdaTexto(ruta.tiempo_estimado));
    fragmento.appendChild(fila);
  });

  dom.tiemposBody.appendChild(fragmento);
}

/** Prepara el formulario para crear una ruta nueva. */
function abrirNuevo() {
  dom.form.reset();
  document.getElementById("rutaId").value = "";
  dom.formTitulo.textContent = "Nueva ruta";
  dom.guardarBtn.textContent = "Guardar ruta";
  UI.limpiarMensaje(dom.formMessage);
  dom.panel.hidden = false;
  document.getElementById("ciudad_origen").focus();
  dom.panel.scrollIntoView({ behavior: "smooth", block: "start" });
}

/** Carga una ruta existente y la coloca en el formulario. */
async function editarRuta(id) {
  try {
    const ruta = await Api.rutas.obtener(id);
    dom.form.reset();

    CAMPOS_RUTA.forEach((campo) => {
      const elemento = dom.form.elements[campo];
      if (!elemento) {
        return;
      }

      let valor = ruta[campo] == null ? "" : String(ruta[campo]);
      if (campo === "distancia" && valor !== "") {
        valor = UI.numero(valor, 2);
      }
      elemento.value = valor;
    });

    document.getElementById("rutaId").value = String(ruta.id);
    dom.formTitulo.textContent = `Editar ruta #${ruta.id}`;
    dom.guardarBtn.textContent = "Actualizar ruta";
    UI.limpiarMensaje(dom.formMessage);
    dom.panel.hidden = false;
    dom.panel.scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (error) {
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible cargar la ruta.", "error");
  }
}

function cerrarFormulario() {
  dom.panel.hidden = true;
  dom.form.reset();
  UI.limpiarMensaje(dom.formMessage);
}

/** Crea o actualiza una ruta según haya o no ID en el formulario. */
async function guardarRuta(event) {
  event.preventDefault();

  const datos = {};
  CAMPOS_RUTA.forEach((campo) => {
    const elemento = dom.form.elements[campo];
    datos[campo] = elemento ? String(elemento.value).trim() : "";
  });

  const errores = validarRuta(datos);
  if (errores.length > 0) {
    UI.mostrarMensaje(dom.formMessage, errores.join(" "), "error");
    return;
  }

  const id = document.getElementById("rutaId").value;
  const boton = dom.guardarBtn;
  boton.disabled = true;

  try {
    if (id) {
      await Api.rutas.actualizar(id, datos);
    } else {
      await Api.rutas.crear(datos);
    }

    cerrarFormulario();
    UI.mostrarMensaje(
      dom.pageMessage,
      id ? "Ruta actualizada correctamente." : "Ruta creada correctamente.",
      "success"
    );
    await cargarRutas(false);
  } catch (error) {
    UI.mostrarMensaje(dom.formMessage, error.message || "No fue posible guardar la ruta.", "error");
  } finally {
    boton.disabled = false;
  }
}

async function eliminarRuta(id) {
  if (!UI.confirmar("¿Eliminar esta ruta? Esta acción no se puede deshacer.")) {
    return;
  }

  try {
    await Api.rutas.eliminar(id);
    UI.mostrarMensaje(dom.pageMessage, "Ruta eliminada correctamente.", "success");
  } catch (error) {
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible eliminar la ruta.", "error");
  } finally {
    await cargarRutas(false);
  }
}

/** Validación en cliente equivalente a las reglas de RutasController. */
function validarRuta(datos) {
  const errores = [];

  ["ciudad_origen", "ciudad_destino", "distancia", "tiempo_estimado"].forEach((campo) => {
    if (!datos[campo]) {
      errores.push(`El campo ${campo.replace(/_/g, " ")} es obligatorio.`);
    }
  });

  ["ciudad_origen", "ciudad_destino"].forEach((campo) => {
    const valor = datos[campo];
    if (valor && (valor.length < 2 || valor.length > 100)) {
      errores.push(`El campo ${campo.replace(/_/g, " ")} debe tener entre 2 y 100 caracteres.`);
    }
  });

  if (datos.distancia) {
    const texto = String(datos.distancia).trim();
    const valor = Number(texto);
    if (!/^\d+(\.\d{1,2})?$/.test(texto) || !(valor > 0) || valor > 99999999.99) {
      errores.push("Distancia no válida: debe ser mayor que 0 y tener máximo 2 decimales.");
    }
  }

  if (datos.tiempo_estimado && datos.tiempo_estimado.length > 50) {
    errores.push("Tiempo estimado no puede superar los 50 caracteres.");
  }

  return errores;
}
