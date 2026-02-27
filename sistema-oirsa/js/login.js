document.addEventListener("DOMContentLoaded", function () {
  // Verificar si viene de un logout exitoso
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('logout') === 'success') {
      Swal.fire({
          icon: 'success',
          title: '¡Sesión cerrada!',
          text: 'Has cerrado sesión exitosamente',
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#667eea',
          timer: 3000,
          timerProgressBar: true
      });
      // Limpiar la URL
      window.history.replaceState({}, document.title, window.location.pathname);
  }
  
  const loginForm = document.getElementById("loginForm");

  loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const usuario = document.getElementById("usuario").value.trim();
    const password = document.getElementById("password").value;

    // Validación básica
    if (usuario === "" || password === "") {
      Swal.fire({
        icon: "warning",
        title: "Campos vacíos",
        text: "Por favor, completa todos los campos",
        confirmButtonText: "Entendido",
        confirmButtonColor: "#667eea",
      });
      return;
    }

    // Enviar datos al servidor
    const formData = new FormData();
    formData.append("usuario", usuario);
    formData.append("password", password);

    fetch("login.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            icon: "success",
            title: "¡Bienvenido!",
            text: data.message,
            confirmButtonText: "Continuar",
            confirmButtonColor: "#667eea",
            timer: 2000,
            timerProgressBar: true,
          }).then(() => {
            window.location.href = data.redirect;
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error de acceso",
            text: data.message,
            confirmButtonText: "Intentar de nuevo",
            confirmButtonColor: "#667eea",
          });
        }
      })
      .catch((error) => {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Ocurrió un error al procesar la solicitud",
          confirmButtonText: "Entendido",
          confirmButtonColor: "#667eea",
        });
        console.error("Error:", error);
      });
  });
});
