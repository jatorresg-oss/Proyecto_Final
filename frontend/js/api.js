// Configuración central de los microservicios.
const API = {
  auth: "http://127.0.0.1:8001",
  conductores: "http://127.0.0.1:8002",
  vehiculos: "http://127.0.0.1:8003",
  rutas: "http://127.0.0.1:8004",
  viajes: "http://127.0.0.1:8005",
};

/**
 * Realiza una petición JSON a un microservicio.
 *
 * @param {string} base   URL base del microservicio (API.auth, API.rutas, ...).
 * @param {string} path   Ruta del endpoint, por ejemplo "/conductores".
 * @param {object} options method, body y auth.
 */
async function apiRequest(base, path, { method = "GET", body = null, auth = true } = {}) {
  const headers = { Accept: "application/json" };

  if (body) {
    headers["Content-Type"] = "application/json";
  }

  if (auth && Session.getToken()) {
    headers["Authorization"] = `Bearer ${Session.getToken()}`;
  }

  const response = await fetch(`${base}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  let data = null;
  try {
    data = await response.json();
  } catch (error) {
    console.warn("Respuesta sin JSON:", error);
  }

  if (response.status === 401) {
    // En la página de login solo se limpia la sesión; en el resto se redirige.
    Session.handleUnauthorized();
  }

  if (!response.ok) {
    throw new Error((data && data.message) || `Error ${response.status}`);
  }

  return data;
}

/** Convierte filtros en query string, omitiendo los valores vacíos. */
function buildQuery(filtros = {}) {
  const params = new URLSearchParams();

  Object.entries(filtros).forEach(([clave, valor]) => {
    const texto = String(valor == null ? "" : valor).trim();
    if (texto !== "") {
      params.set(clave, texto);
    }
  });

  const query = params.toString();
  return query ? `?${query}` : "";
}

/**
 * Contratos de los recursos usados por el frontend (Día 7).
 * Las páginas no repiten la URL base ni el verbo HTTP: solo llaman a estos
 * métodos. apiRequest sigue siendo la única función que hace fetch.
 */
const Api = {
  auth: {
    login(credenciales) {
      return apiRequest(API.auth, "/login", {
        method: "POST",
        auth: false,
        body: credenciales,
      });
    },
    validate() {
      return apiRequest(API.auth, "/validate");
    },
    logout() {
      return apiRequest(API.auth, "/logout", { method: "POST" });
    },
  },

  conductores: {
    listar(filtros = {}) {
      return apiRequest(API.conductores, `/conductores${buildQuery(filtros)}`);
    },
    obtener(id) {
      return apiRequest(API.conductores, `/conductores/${id}`);
    },
    crear(datos) {
      return apiRequest(API.conductores, "/conductores", { method: "POST", body: datos });
    },
    actualizar(id, datos) {
      return apiRequest(API.conductores, `/conductores/${id}`, { method: "PUT", body: datos });
    },
    cambiarEstado(id, estado) {
      return apiRequest(API.conductores, `/conductores/${id}/estado`, {
        method: "PATCH",
        body: { estado },
      });
    },
    eliminar(id) {
      return apiRequest(API.conductores, `/conductores/${id}`, { method: "DELETE" });
    },
  },

  vehiculos: {
    listar(filtros = {}) {
      return apiRequest(API.vehiculos, `/vehiculos${buildQuery(filtros)}`);
    },
    obtener(id) {
      return apiRequest(API.vehiculos, `/vehiculos/${id}`);
    },
    crear(datos) {
      return apiRequest(API.vehiculos, "/vehiculos", { method: "POST", body: datos });
    },
    actualizar(id, datos) {
      return apiRequest(API.vehiculos, `/vehiculos/${id}`, { method: "PUT", body: datos });
    },
    cambiarEstado(id, estado) {
      return apiRequest(API.vehiculos, `/vehiculos/${id}/estado`, {
        method: "PATCH",
        body: { estado },
      });
    },
    eliminar(id) {
      return apiRequest(API.vehiculos, `/vehiculos/${id}`, { method: "DELETE" });
    },
  },
};
