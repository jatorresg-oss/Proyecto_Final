/**
 * Módulo de vehículos (Día 7).
 * Consume ms-vehiculos (8003) con el token Bearer de la sesión.
 * La validación del cliente es solo apoyo: el backend vuelve a validar todo.
 */

const ESTADOS_VEHICULO = {
  disponible: "Disponible",
  en_ruta: "En ruta",
  mantenimiento: "Mantenimiento",
  inactivo: "Inactivo",
};

const CAMPOS_VEHICULO = [
  "placa",
  "tipo_vehiculo",
  "capacidad_carga",
  "modelo",
  "marca",
  "estado",
];

let dom = {};

if (Session.requireAuth()) {
  initVehiculos();
}

async function initVehiculos() {
  const valid = await Session.validateSession();
  if (!valid) {
    return;
  }

  dom = {
    userName: document.getElementById("userName"),
    pageMessage: document.getElementById("pageMessage"),
    filtros: document.forms["filtrosForm"],
    body: document.getElementById("vehiculosBody"),
    panel: document.getElementById("formPanel"),
    form: document.forms["vehiculoForm"],
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
    await cargarVehiculos();
  });

  document.getElementById("limpiarFiltros").addEventListener("click", async () => {
    dom.filtros.reset();
    UI.limpiarMensaje(dom.pageMessage);
    await cargarVehiculos();
  });

  document.getElementById("nuevoBtn").addEventListener("click", abrirNuevo);
  document.getElementById("cancelarBtn").addEventListener("click", cerrarFormulario);
  dom.form.addEventListener("submit", guardarVehiculo);

  dom.body.addEventListener("click", (event) => {
    const button = event.target.closest("button[data-accion]");
    if (!button) {
      return;
    }

    const id = Number(button.dataset.id);
    if (button.dataset.accion === "editar") {
      editarVehiculo(id);
    } else if (button.dataset.accion === "eliminar") {
      eliminarVehiculo(id);
    }
  });

  dom.body.addEventListener("change", (event) => {
    const select = event.target.closest("select[data-accion='cambiar-estado']");
    if (select) {
      cambiarEstado(Number(select.dataset.id), select.value);
    }
  });

  await cargarVehiculos();
}

/** Lista los vehículos aplicando los filtros del formulario. */
async function cargarVehiculos(limpiarMensajeAlCargar = true) {
  const datos = new FormData(dom.filtros);
  const filtros = {};

  for (const clave of ["placa", "tipo", "estado"]) {
    const valor = String(datos.get(clave) || "").trim();
    if (valor !== "") {
      filtros[clave] = valor;
    }
  }

  try {
    const vehiculos = await Api.vehiculos.listar(filtros);
    renderTabla(Array.isArray(vehiculos) ? vehiculos : []);
    if (limpiarMensajeAlCargar) {
      UI.limpiarMensaje(dom.pageMessage);
    }
  } catch (error) {
    renderTabla([]);
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible cargar los vehículos.", "error");
  }
}

/** Dibuja la tabla; los datos se insertan como texto para evitar HTML inyectado. */
function renderTabla(vehiculos) {
  dom.body.textContent = "";

  if (vehiculos.length === 0) {
    dom.body.appendChild(UI.filaVacia(7, "No hay vehículos que coincidan con la búsqueda."));
    return;
  }

  const fragmento = document.createDocumentFragment();

  vehiculos.forEach((vehiculo) => {
    const fila = document.createElement("tr");

    fila.appendChild(UI.celdaTexto(vehiculo.placa));
    fila.appendChild(UI.celdaTexto(vehiculo.tipo_vehiculo));
    fila.appendChild(UI.celdaTexto(UI.numero(vehiculo.capacidad_carga, 2)));
    fila.appendChild(UI.celdaTexto(vehiculo.modelo));
    fila.appendChild(UI.celdaTexto(vehiculo.marca));

    const estado = vehiculo.estado || "disponible";
    const celdaEstado = document.createElement("td");
    celdaEstado.appendChild(
      UI.crearBadge(ESTADOS_VEHICULO[estado] || estado, `estado-${estado}`)
    );
    fila.appendChild(celdaEstado);

    const acciones = document.createElement("td");
    acciones.className = "acciones";

    const selector = document.createElement("select");
    selector.className = "btn-small select-estado";
    selector.title = "Cambiar estado";
    selector.dataset.accion = "cambiar-estado";
    selector.dataset.id = String(vehiculo.id);
    Object.entries(ESTADOS_VEHICULO).forEach(([valor, etiqueta]) => {
      const opcion = document.createElement("option");
      opcion.value = valor;
      opcion.textContent = etiqueta;
      selector.appendChild(opcion);
    });
    selector.value = estado;
    acciones.appendChild(selector);

    acciones.appendChild(UI.botonAccion("Editar", "editar", vehiculo.id));
    acciones.appendChild(UI.botonAccion("Eliminar", "eliminar", vehiculo.id, "btn-danger"));
    fila.appendChild(acciones);

    fragmento.appendChild(fila);
  });

  dom.body.appendChild(fragmento);
}

/** Prepara el formulario para crear un vehículo nuevo. */
function abrirNuevo() {
  dom.form.reset();
  dom.form.elements["estado"].value = "disponible";
  document.getElementById("vehiculoId").value = "";
  dom.formTitulo.textContent = "Nuevo vehículo";
  dom.guardarBtn.textContent = "Guardar vehículo";
  UI.limpiarMensaje(dom.formMessage);
  dom.panel.hidden = false;
  document.getElementById("placa").focus();
  dom.panel.scrollIntoView({ behavior: "smooth", block: "start" });
}

/** Carga un vehículo existente y lo coloca en el formulario. */
async function editarVehiculo(id) {
  try {
    const vehiculo = await Api.vehiculos.obtener(id);
    dom.form.reset();

    CAMPOS_VEHICULO.forEach((campo) => {
      const elemento = dom.form.elements[campo];
      if (!elemento) {
        return;
      }

      let valor = vehiculo[campo] == null ? "" : String(vehiculo[campo]);
      if (campo === "capacidad_carga" && valor !== "") {
        valor = UI.numero(valor, 2);
      }
      elemento.value = valor;
    });

    document.getElementById("vehiculoId").value = String(vehiculo.id);
    dom.formTitulo.textContent = `Editar vehículo #${vehiculo.id}`;
    dom.guardarBtn.textContent = "Actualizar vehículo";
    UI.limpiarMensaje(dom.formMessage);
    dom.panel.hidden = false;
    dom.panel.scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (error) {
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible cargar el vehículo.", "error");
  }
}

function cerrarFormulario() {
  dom.panel.hidden = true;
  dom.form.reset();
  UI.limpiarMensaje(dom.formMessage);
}

/** Crea o actualiza un vehículo según haya o no ID en el formulario. */
async function guardarVehiculo(event) {
  event.preventDefault();

  const datos = {};
  CAMPOS_VEHICULO.forEach((campo) => {
    const elemento = dom.form.elements[campo];
    datos[campo] = elemento ? String(elemento.value).trim() : "";
  });

  const errores = validarVehiculo(datos);
  if (errores.length > 0) {
    UI.mostrarMensaje(dom.formMessage, errores.join(" "), "error");
    return;
  }

  const id = document.getElementById("vehiculoId").value;
  const boton = dom.guardarBtn;
  boton.disabled = true;

  try {
    if (id) {
      await Api.vehiculos.actualizar(id, datos);
    } else {
      await Api.vehiculos.crear(datos);
    }

    cerrarFormulario();
    UI.mostrarMensaje(
      dom.pageMessage,
      id ? "Vehículo actualizado correctamente." : "Vehículo creado correctamente.",
      "success"
    );
    await cargarVehiculos(false);
  } catch (error) {
    UI.mostrarMensaje(dom.formMessage, error.message || "No fue posible guardar el vehículo.", "error");
  } finally {
    boton.disabled = false;
  }
}

/** Cambia el estado desde el selector de la tabla (PATCH /vehiculos/{id}/estado). */
async function cambiarEstado(id, estado) {
  if (!Object.prototype.hasOwnProperty.call(ESTADOS_VEHICULO, estado)) {
    UI.mostrarMensaje(dom.pageMessage, "Estado no válido.", "error");
    await cargarVehiculos(false);
    return;
  }

  try {
    await Api.vehiculos.cambiarEstado(id, estado);
    UI.mostrarMensaje(dom.pageMessage, "Estado del vehículo actualizado.", "success");
  } catch (error) {
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible cambiar el estado.", "error");
  } finally {
    await cargarVehiculos(false);
  }
}

async function eliminarVehiculo(id) {
  if (!UI.confirmar("¿Eliminar este vehículo? Esta acción no se puede deshacer.")) {
    await cargarVehiculos();
    return;
  }

  try {
    await Api.vehiculos.eliminar(id);
    UI.mostrarMensaje(dom.pageMessage, "Vehículo eliminado correctamente.", "success");
  } catch (error) {
    UI.mostrarMensaje(dom.pageMessage, error.message || "No fue posible eliminar el vehículo.", "error");
  } finally {
    await cargarVehiculos(false);
  }
}

/** Validación en cliente equivalente a las reglas de VehiculosController. */
function validarVehiculo(datos) {
  const errores = [];
  const obligatorios = CAMPOS_VEHICULO.filter((campo) => campo !== "estado");

  obligatorios.forEach((campo) => {
    if (!datos[campo]) {
      errores.push(`El campo ${campo.replace(/_/g, " ")} es obligatorio.`);
    }
  });

  if (datos.placa) {
    // El backend también normaliza la placa a mayúsculas.
    datos.placa = datos.placa.toUpperCase();
    if (!/^[A-Z0-9\-]{3,10}$/.test(datos.placa)) {
      errores.push("Placa no válida (3 a 10 caracteres alfanuméricos o guion).");
    }
  }

  ["tipo_vehiculo", "modelo", "marca"].forEach((campo) => {
    if (datos[campo] && datos[campo].length > 60) {
      errores.push(`El campo ${campo.replace(/_/g, " ")} no puede superar los 60 caracteres.`);
    }
  });

  if (datos.capacidad_carga) {
    const texto = String(datos.capacidad_carga).trim();
    const valor = Number(texto);
    if (!/^\d+(\.\d{1,2})?$/.test(texto) || !(valor > 0) || valor > 99999999.99) {
      errores.push("Capacidad de carga no válida: debe ser mayor que 0 y tener máximo 2 decimales.");
    }
  }

  if (!Object.prototype.hasOwnProperty.call(ESTADOS_VEHICULO, datos.estado)) {
    errores.push("Estado no válido.");
  }

  return errores;
}
