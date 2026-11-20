const loginForm = document.forms["loginForm"];
const loginMessage = document.getElementById("loginMessage");

function showLoginMessage(text, type = "error") {
  loginMessage.textContent = text;
  loginMessage.className = `message ${type}`;
}

loginForm.addEventListener("submit", async (event) => {
  event.preventDefault();

  const identifier = loginForm.identifier.value.trim();
  const password = loginForm.password.value;

  if (!identifier || !password) {
    showLoginMessage("Ingresa usuario o correo y contraseña.");
    return;
  }

  try {
    // Contrato acordado para ms-auth: POST /login -> { token, user }
    const data = await apiRequest(API.auth, "/login", {
      method: "POST",
      auth: false,
      body: { usuario: identifier, password },
    });

    Session.save(data.token, data.user);
    window.location.href = "pages/dashboard.html";
  } catch (error) {
    showLoginMessage(error.message || "No fue posible iniciar sesión.");
  }
});

Session.redirectIfLogged();