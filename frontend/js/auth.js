const Session = {
  TOKEN_KEY: "logitrans.token",
  USER_KEY: "logitrans.user",

  save(token, user) {
    sessionStorage.setItem(this.TOKEN_KEY, token);
    sessionStorage.setItem(this.USER_KEY, JSON.stringify(user || {}));
  },

  getToken() {
    return sessionStorage.getItem(this.TOKEN_KEY);
  },

  getUser() {
    try {
      return JSON.parse(sessionStorage.getItem(this.USER_KEY)) || null;
    } catch (error) {
      return null;
    }
  },

  isLogged() {
    return Boolean(this.getToken());
  },

  clear() {
    sessionStorage.removeItem(this.TOKEN_KEY);
    sessionStorage.removeItem(this.USER_KEY);
  },

  /** Limpia la sesión y vuelve al login cuando una API responde 401. */
  handleUnauthorized() {
    this.clear();

    const path = window.location.pathname.replace(/\\/g, "/");
    const enLogin =
      path.endsWith("/index.html") ||
      path.endsWith("/") ||
      path === "" ||
      path.endsWith("/frontend");

    if (!enLogin) {
      window.location.href = "../index.html";
    }
  },

  requireAuth() {
    if (!this.isLogged()) {
      window.location.href = "../index.html";
      return false;
    }
    return true;
  },

  redirectIfLogged() {
    if (this.isLogged()) {
      window.location.href = "pages/dashboard.html";
    }
  },

  /** Comprueba el token contra ms-auth y refresca los datos del usuario. */
  async validateSession() {
    if (!this.isLogged()) {
      this.handleUnauthorized();
      return false;
    }

    try {
      const data = await Api.auth.validate();

      if (data && data.user) {
        sessionStorage.setItem(this.USER_KEY, JSON.stringify(data.user));
      }

      return Boolean(data && data.valid);
    } catch (error) {
      this.handleUnauthorized();
      return false;
    }
  },

  /** Invalida el token en ms-auth antes de limpiar la sesión local. */
  async logout() {
    if (!this.isLogged()) {
      this.clear();
      return;
    }

    try {
      await Api.auth.logout();
    } catch (error) {
      console.warn("No fue posible cerrar la sesión en ms-auth:", error);
    } finally {
      this.handleUnauthorized();
    }
  },
};
