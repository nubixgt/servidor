// js/login.js

document.addEventListener("DOMContentLoaded", function () {
  const loginForm = document.getElementById("loginForm");
  const usuarioInput = document.getElementById("usuario");
  const passwordInput = document.getElementById("password");
  const errorMessage = document.getElementById("errorMessage");

  // Enfocar el campo de usuario al cargar
  if (usuarioInput) {
    usuarioInput.focus();
  }

  // Validación del formulario
  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      let isValid = true;
      let errorMsg = "";

      // Validar usuario
      if (usuarioInput.value.trim() === "") {
        isValid = false;
        errorMsg = "Por favor, ingresa tu usuario";
      }

      // Validar contraseña
      if (passwordInput.value === "") {
        isValid = false;
        errorMsg = "Por favor, ingresa tu contraseña";
      }

      if (!isValid) {
        e.preventDefault();
        mostrarError(errorMsg);
      }
    });
  }

  // Limpiar mensaje de error al escribir
  usuarioInput.addEventListener("input", limpiarError);
  passwordInput.addEventListener("input", limpiarError);

  // Función para mostrar error
  function mostrarError(mensaje) {
    if (errorMessage) {
      errorMessage.textContent = mensaje;
      errorMessage.style.display = "block";
    } else {
      // Crear mensaje de error si no existe
      const newError = document.createElement("div");
      newError.className = "error-message";
      newError.id = "errorMessage";
      newError.textContent = mensaje;
      loginForm.insertBefore(newError, loginForm.firstChild);
    }
  }

  // Función para limpiar error
  function limpiarError() {
    if (errorMessage) {
      errorMessage.style.display = "none";
    }
  }

  // Prevenir espacios al inicio del usuario
  usuarioInput.addEventListener("keydown", function (e) {
    if (e.key === " " && this.value.length === 0) {
      e.preventDefault();
    }
  });

  // Enter en el campo usuario pasa al campo password
  usuarioInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      passwordInput.focus();
    }
  });
});
