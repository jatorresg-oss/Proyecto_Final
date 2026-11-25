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
    // Contrato de ms-auth: POST /login -> { token, user }
    const data = await Api.auth.login({ usuario: identifier, password });

    Session.save(data.token, data.user);
    window.location.href = "pages/dashboard.html";
  } catch (error) {
    showLoginMessage(error.message || "No fue posible iniciar sesión.");
  }
});

// Si queda un token de una pestaña anterior, se valida antes de entrar al panel.
if (Session.isLogged()) {
  Session.validateSession().then((valid) => {
    if (valid) {
      Session.redirectIfLogged();
    }
  });
}
