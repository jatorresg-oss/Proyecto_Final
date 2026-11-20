Session.requireAuth();

const user = Session.getUser();
const displayName = (user && (user.nombre || user.username || user.correo)) || "Usuario";

document.getElementById("userName").textContent = displayName;
document.getElementById("welcome").textContent = `Sesión iniciada como ${displayName}.`;

document.getElementById("logoutBtn").addEventListener("click", () => {
  // El cierre de sesión contra ms-auth se completa el Día 3.
  Session.clear();
  window.location.href = "../index.html";
});