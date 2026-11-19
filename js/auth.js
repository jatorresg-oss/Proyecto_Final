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

  requireAuth() {
    if (!this.isLogged()) {
      window.location.href = "../index.html";
    }
  },

  redirectIfLogged() {
    if (this.isLogged()) {
      window.location.href = "pages/dashboard.html";
    }
  },
};