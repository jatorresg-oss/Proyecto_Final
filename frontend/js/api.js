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
