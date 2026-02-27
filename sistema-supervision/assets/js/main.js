/**
 * Sistema de Supervisión - JavaScript Principal
 */

// Esperar a que el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  console.log("Sistema de Supervisión cargado correctamente");

  // Inicializar funciones
  initDropdowns();
  initForms();
});

/**
 * Inicializar dropdowns
 */
function initDropdowns() {
  const dropdowns = document.querySelectorAll(".dropdown");

  dropdowns.forEach((dropdown) => {
    const toggle = dropdown.querySelector(".dropdown-toggle");
    const menu = dropdown.querySelector(".dropdown-menu");

    if (toggle && menu) {
      // Toggle dropdown con click
      toggle.addEventListener("click", function (e) {
        e.stopPropagation();
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      });

      // Cerrar dropdown al hacer click fuera
      document.addEventListener("click", function () {
        menu.style.display = "none";
      });
    }
  });
}

/**
 * Validaciones y mejoras de formularios
 */
function initForms() {
  const forms = document.querySelectorAll("form");

  forms.forEach((form) => {
    // Validación básica
    form.addEventListener("submit", function (e) {
      const requiredFields = form.querySelectorAll("[required]");
      let isValid = true;

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          isValid = false;
          field.style.borderColor = "var(--danger-color)";
        } else {
          field.style.borderColor = "var(--border-color)";
        }
      });

      if (!isValid) {
        e.preventDefault();
        showAlert("Por favor, complete todos los campos requeridos.", "error");
      }
    });

    // Limpiar error al escribir
    const inputs = form.querySelectorAll("input, select, textarea");
    inputs.forEach((input) => {
      input.addEventListener("input", function () {
        this.style.borderColor = "var(--border-color)";
      });
    });
  });
}

/**
 * Mostrar alertas
 */
function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type}`;
  alertDiv.textContent = message;

  const container = document.querySelector(".container");
  if (container) {
    container.insertBefore(alertDiv, container.firstChild);

    // Ocultar después de 5 segundos
    setTimeout(() => {
      alertDiv.style.opacity = "0";
      setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
  }
}

/**
 * Confirmar eliminación
 */
function confirmDelete(
  message = "¿Está seguro de que desea eliminar este elemento?"
) {
  return confirm(message);
}

/**
 * Formatear teléfono guatemalteco
 */
function formatPhone(phone) {
  // Eliminar espacios y guiones
  phone = phone.replace(/[\s-]/g, "");

  // Formato: 1234-5678
  if (phone.length === 8) {
    return phone.substring(0, 4) + "-" + phone.substring(4);
  }

  return phone;
}
