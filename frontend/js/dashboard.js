if (Session.requireAuth()) {
  initDashboard();
}

async function initDashboard() {
  // El guard valida el token contra ms-auth antes de mostrar datos del panel.
  const valid = await Session.validateSession();
  if (!valid) {
    return;
  }

  const user = Session.getUser() || {};
  const displayName = user.nombre || user.usuario || user.correo || "Usuario";

  document.getElementById("userName").textContent = displayName;
  document.getElementById("welcome").textContent = `Sesión iniciada como ${displayName} (${user.rol || "sin rol"}).`;

  document.getElementById("logoutBtn").addEventListener("click", async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    button.textContent = "Cerrando sesión…";

    await Session.logout();
  });
}
