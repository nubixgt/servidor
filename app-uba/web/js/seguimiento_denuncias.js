// web/js/seguimiento_denuncias.js

// ============================================
// SEGUIMIENTO DE DENUNCIAS - ÁREAS TÉCNICAS
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  // ==========================================
  // MANEJO DE ARCHIVOS ADJUNTOS
  // ==========================================
  const fileInput = document.getElementById("archivos");
  const fileUploadArea = document.querySelector(".file-upload-area");
  const filesList =
    document.getElementById("filesList") ||
    document.getElementById("filePreview");
  let selectedFiles = [];

  if (fileInput && fileUploadArea) {
    // Click en el área de carga
    fileUploadArea.addEventListener("click", function () {
      fileInput.click();
    });

    // Drag & Drop
    fileUploadArea.addEventListener("dragover", function (e) {
      e.preventDefault();
      this.style.borderColor = "var(--color-primario)";
      this.style.backgroundColor = "rgba(30, 58, 138, 0.05)";
    });

    fileUploadArea.addEventListener("dragleave", function (e) {
      e.preventDefault();
      this.style.borderColor = "var(--border-color)";
      this.style.backgroundColor = "#f8fafc";
    });

    fileUploadArea.addEventListener("drop", function (e) {
      e.preventDefault();
      this.style.borderColor = "var(--border-color)";
      this.style.backgroundColor = "#f8fafc";

      const files = e.dataTransfer.files;
      handleFiles(files);
    });

    // Selección de archivos
    fileInput.addEventListener("change", function (e) {
      handleFiles(e.target.files);
    });
  }

  // Procesar archivos seleccionados
  function handleFiles(files) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = [
      "image/jpeg",
      "image/jpg",
      "image/png",
      "image/webp",
      "application/pdf",
      "application/msword",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      "application/vnd.ms-excel",
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "audio/mpeg",
      "audio/mp3",
      "video/mp4",
      "video/mpeg",
    ];

    let archivosAgregados = 0;

    for (let file of files) {
      // Verificar si el archivo ya está en la lista (por nombre y tamaño)
      const yaExiste = selectedFiles.some(
        (f) => f.name === file.name && f.size === file.size
      );
      if (yaExiste) {
        Swal.fire({
          icon: "info",
          title: "Archivo duplicado",
          text: `El archivo "${file.name}" ya está en la lista`,
          confirmButtonColor: "#3b82f6",
          timer: 2000,
        });
        continue;
      }

      // Validar tipo
      if (!allowedTypes.includes(file.type)) {
        Swal.fire({
          icon: "error",
          title: "Tipo de archivo no válido",
          text: `El archivo "${file.name}" no es un tipo permitido`,
          confirmButtonColor: "#ef4444",
        });
        continue;
      }

      // Validar tamaño
      if (file.size > maxSize) {
        Swal.fire({
          icon: "error",
          title: "Archivo muy grande",
          text: `El archivo "${file.name}" supera los 10MB`,
          confirmButtonColor: "#ef4444",
        });
        continue;
      }

      // Agregar a la lista
      selectedFiles.push(file);
      archivosAgregados++;
    }

    // Mostrar mensaje de éxito si se agregaron archivos
    if (archivosAgregados > 0) {
      const mensaje =
        archivosAgregados === 1
          ? "1 archivo agregado correctamente"
          : `${archivosAgregados} archivos agregados correctamente`;

      Swal.fire({
        icon: "success",
        title: "¡Archivos agregados!",
        text: mensaje,
        confirmButtonColor: "#10b981",
        timer: 1500,
        showConfirmButton: false,
      });
    }

    // Limpiar el input para permitir seleccionar los mismos archivos nuevamente si se eliminan
    if (fileInput) {
      fileInput.value = "";
    }

    // Actualizar vista
    updateFilesList();
  }

  // Actualizar lista de archivos
  function updateFilesList() {
    if (!filesList) return;

    filesList.innerHTML = "";

    if (selectedFiles.length === 0) {
      return;
    }

    // Agregar encabezado con contador
    const header = document.createElement("div");
    header.style.cssText =
      "padding: 10px; background: #f1f5f9; border-radius: 8px; margin-bottom: 10px; font-weight: 600; color: #1e293b;";
    header.innerHTML = `
            <i class="fas fa-paperclip"></i> 
            ${selectedFiles.length} archivo${
      selectedFiles.length !== 1 ? "s" : ""
    } seleccionado${selectedFiles.length !== 1 ? "s" : ""}
        `;
    filesList.appendChild(header);

    selectedFiles.forEach((file, index) => {
      const fileItem = document.createElement("div");
      fileItem.className = "file-item";

      // Si es imagen, mostrar preview
      if (file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (e) {
          fileItem.innerHTML = `
                        <div class="file-item-info">
                            <img src="${
                              e.target.result
                            }" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 10px;">
                            <div>
                                <div class="file-item-name">${file.name}</div>
                                <div class="file-item-size">${formatFileSize(
                                  file.size
                                )}</div>
                            </div>
                        </div>
                        <button type="button" class="file-item-remove" onclick="removeFile(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
        };
        reader.readAsDataURL(file);
      } else {
        const fileIcon = getFileIcon(file.type);
        const fileSize = formatFileSize(file.size);

        fileItem.innerHTML = `
                    <div class="file-item-info">
                        <i class="fas ${fileIcon}"></i>
                        <div>
                            <div class="file-item-name">${file.name}</div>
                            <div class="file-item-size">${fileSize}</div>
                        </div>
                    </div>
                    <button type="button" class="file-item-remove" onclick="removeFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
      }

      filesList.appendChild(fileItem);
    });
  }

  // Obtener icono según tipo de archivo
  function getFileIcon(mimeType) {
    if (mimeType.startsWith("image/")) return "fa-image";
    if (mimeType.startsWith("video/")) return "fa-video";
    if (mimeType.startsWith("audio/")) return "fa-music";
    if (mimeType === "application/pdf") return "fa-file-pdf";
    if (mimeType.includes("word")) return "fa-file-word";
    if (mimeType.includes("excel") || mimeType.includes("sheet"))
      return "fa-file-excel";
    return "fa-file";
  }

  // Formatear tamaño de archivo
  function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
  }

  // Eliminar archivo de la lista
  window.removeFile = function (index) {
    selectedFiles.splice(index, 1);
    updateFilesList();
  };

  // ==========================================
  // CONTADOR DE CARACTERES PARA COMENTARIO
  // ==========================================
  const comentarioTextarea = document.getElementById("comentario");

  if (comentarioTextarea) {
    const contadorComentario = document.createElement("small");
    contadorComentario.style.cssText =
      "color: #64748b; margin-top: 5px; display: block;";
    comentarioTextarea.parentNode.appendChild(contadorComentario);

    function actualizarContador() {
      const length = comentarioTextarea.value.length;
      contadorComentario.textContent = `${length} caracteres`;

      if (length < 20) {
        contadorComentario.style.color = "#ef4444";
      } else {
        contadorComentario.style.color = "#10b981";
      }
    }

    comentarioTextarea.addEventListener("input", actualizarContador);
    actualizarContador();
  }

  // ==========================================
  // VALIDACIÓN Y ENVÍO DEL FORMULARIO
  // ==========================================
  const formProcesar = document.getElementById("formProcesar");

  if (formProcesar) {
    formProcesar.addEventListener("submit", function (e) {
      e.preventDefault();

      const comentario = document.getElementById("comentario").value.trim();
      const accionBtn = document.activeElement;
      const accion =
        accionBtn.getAttribute("name") === "accion"
          ? accionBtn.value
          : accionBtn.getAttribute("data-accion");

      // Validar comentario
      if (comentario === "") {
        Swal.fire({
          icon: "warning",
          title: "Campo requerido",
          text: "El comentario es obligatorio",
          confirmButtonColor: "#f59e0b",
        });
        return;
      }

      if (comentario.length < 20) {
        Swal.fire({
          icon: "warning",
          title: "Comentario muy corto",
          text: "El comentario debe tener al menos 20 caracteres",
          confirmButtonColor: "#f59e0b",
        });
        return;
      }

      // Mensaje de confirmación según acción
      let titulo = "";
      let texto = "";
      let icono = "question";
      let confirmButtonColor = "";

      switch (accion) {
        case "siguiente_paso":
          titulo = "¿Enviar al siguiente paso?";
          texto = "La denuncia avanzará a la siguiente etapa del proceso";
          icono = "question";
          confirmButtonColor = "#10b981";
          break;
        case "rechazado":
          titulo = "¿Rechazar esta denuncia?";
          texto =
            "La denuncia será marcada como rechazada. Esta acción es definitiva.";
          icono = "warning";
          confirmButtonColor = "#ef4444";
          break;
        case "resuelto":
          titulo = "¿Resolver esta denuncia?";
          texto = "La denuncia será marcada como resuelta y finalizada.";
          icono = "success";
          confirmButtonColor = "#3b82f6";
          break;
      }

      Swal.fire({
        title: titulo,
        text: texto,
        icon: icono,
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Sí, confirmar",
        cancelButtonText: "Cancelar",
      }).then((result) => {
        if (result.isConfirmed) {
          // Crear FormData con archivos
          const formData = new FormData();
          formData.append("comentario", comentario);
          formData.append("accion", accion);
          formData.append(
            "id_denuncia",
            document.querySelector('input[name="id_denuncia"]').value
          );

          // Agregar archivos
          selectedFiles.forEach((file, index) => {
            formData.append("archivos[]", file);
          });

          // Enviar formulario
          submitForm(formData);
        }
      });
    });
  }

  // Enviar formulario con archivos
  function submitForm(formData) {
    // Mostrar loading
    Swal.fire({
      title: "Procesando...",
      html: "Por favor espere mientras se procesa la denuncia",
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });

    // Enviar con fetch
    fetch("guardar_seguimiento.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((data) => {
        // Si la respuesta es HTML (redirección PHP)
        if (data.includes("<!DOCTYPE") || data.includes("<html")) {
          window.location.href = "index.php";
        } else {
          // Si es JSON
          try {
            const jsonData = JSON.parse(data);
            if (jsonData.success) {
              Swal.fire({
                icon: "success",
                title: "¡Éxito!",
                text: jsonData.message,
                confirmButtonColor: "#10b981",
              }).then(() => {
                window.location.href = "index.php";
              });
            } else {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: jsonData.message,
                confirmButtonColor: "#ef4444",
              });
            }
          } catch (e) {
            // Si no es JSON, redirigir
            window.location.href = "index.php";
          }
        }
      })
      .catch((error) => {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Hubo un error al procesar la solicitud",
          confirmButtonColor: "#ef4444",
        });
      });
  }

  // ==========================================
  // DATATABLES PARA LISTADO DE DENUNCIAS
  // ==========================================
  const tablaDenunciasElement = document.getElementById("tablaDenuncias");

  if (tablaDenunciasElement && typeof $ !== "undefined") {
    $(document).ready(function () {
      $("#tablaDenuncias").DataTable({
        language: {
          url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
        },
        pageLength: 10,
        responsive: false,
        scrollX: true,
        dom: "Bfrtip",
        buttons: [
          {
            extend: "copy",
            text: '<i class="fas fa-copy"></i> Copiar',
            className: "btn-dt",
          },
          {
            extend: "excel",
            text: '<i class="fas fa-file-excel"></i> Excel',
            className: "btn-dt",
            title: "Denuncias_Area_Tecnica",
          },
          {
            extend: "pdf",
            text: '<i class="fas fa-file-pdf"></i> PDF',
            className: "btn-dt",
            title: "Denuncias - Área Técnica",
            orientation: "landscape",
            pageSize: "LEGAL",
          },
          {
            extend: "print",
            text: '<i class="fas fa-print"></i> Imprimir',
            className: "btn-dt",
          },
        ],
        order: [[0, "desc"]],
        columnDefs: [{ orderable: false, targets: -1 }],
      });
    });
  }

  // ==========================================
  // CONFIRMACIÓN AL CANCELAR
  // ==========================================
  const btnCancelar = document.querySelector(".btn-cancelar");

  if (btnCancelar) {
    btnCancelar.addEventListener("click", function (e) {
      e.preventDefault();

      const url = this.getAttribute("href");

      Swal.fire({
        title: "¿Cancelar?",
        text: "Los cambios no guardados se perderán",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#6366f1",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "Continuar",
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = url;
        }
      });
    });
  }
});
