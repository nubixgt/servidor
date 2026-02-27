/**
 * Sistema de Registro de DPI - VISAN 10910
 * JavaScript principal para manejo de DataTables y actualización de estados
 */

// Variable global para la tabla
let tablaDPI;

// Cuando el documento esté listo
$(document).ready(function () {
  // Inicializar DataTable
  inicializarDataTable();

  // Actualizar estadísticas
  actualizarEstadisticas();
});

/**
 * Inicializa el DataTable con configuración personalizada
 */
function inicializarDataTable() {
  tablaDPI = $("#tablaDPI").DataTable({
    ajax: {
      url: "api/obtener_registros.php",
      type: "GET",
      dataSrc: "data",
      error: function (xhr, error, thrown) {
        console.error("Error al cargar datos:", error);
        Swal.fire({
          icon: "error",
          title: "Error al cargar datos",
          text: "No se pudieron cargar los registros. Por favor, verifica la conexión a la base de datos.",
          confirmButtonText: "Entendido",
        });
      },
    },
    columns: [
      {
        data: "fila",
        width: "60px",
        orderable: true,
        render: function (data) {
          // Mostrar el número de fila real de la base de datos
          return data;
        },
      },
      {
        data: "nombre",
        width: "250px",
      },
      {
        data: "dpi",
        width: "140px",
        render: function (data) {
          return "<strong>" + data + "</strong>";
        },
      },
      {
        data: "comunidad",
        width: "180px",
      },
      {
        data: "estado",
        width: "140px",
        render: function (data) {
          if (data === "DPI Físico") {
            return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> DPI Físico</span>';
          } else {
            return '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Sin Registrar</span>';
          }
        },
      },
      {
        data: null,
        width: "150px",
        orderable: false,
        render: function (data, type, row) {
          if (row.estado === "DPI Físico") {
            return '<button class="btn btn-success btn-sm" disabled><i class="fas fa-check"></i> Registrado</button>';
          } else {
            return (
              '<button class="btn btn-primary btn-sm btn-guardar" data-dpi="' +
              row.dpi +
              '" data-nombre="' +
              row.nombre +
              '"><i class="fas fa-save"></i> Guardar Registro</button>'
            );
          }
        },
      },
    ],
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
    },
    responsive: false, // Deshabilitado para usar scroll horizontal
    scrollX: true, // Habilitar scroll horizontal en móviles
    pageLength: 10, // Mostrar 10 registros por defecto
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "Todos"],
    ],
    order: [[0, "asc"]], // Ordenar por fila
    drawCallback: function () {
      // Agregar event listeners a los botones después de cada redibujado
      agregarEventListeners();
    },
    initComplete: function () {
      console.log("DataTable inicializado correctamente");
      actualizarEstadisticas();
    },
  });
}

/**
 * Agrega event listeners a los botones de guardar
 */
function agregarEventListeners() {
  $(".btn-guardar")
    .off("click")
    .on("click", function () {
      const dpi = $(this).data("dpi");
      const nombre = $(this).data("nombre");
      confirmarGuardarRegistro(dpi, nombre);
    });
}

/**
 * Muestra confirmación antes de guardar el registro
 */
function confirmarGuardarRegistro(dpi, nombre) {
  Swal.fire({
    title: "¿Confirmar registro?",
    html: `
            <div style="text-align: left; padding: 10px;">
                <p><strong>Nombre:</strong> ${nombre}</p>
                <p><strong>DPI:</strong> ${dpi}</p>
                <p style="margin-top: 15px;">¿Deseas marcar este DPI como <strong>DPI Físico</strong>?</p>
            </div>
        `,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-check"></i> Sí, guardar',
    cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
    reverseButtons: true,
  }).then((result) => {
    if (result.isConfirmed) {
      guardarRegistro(dpi, nombre);
    }
  });
}

/**
 * Guarda el registro actualizando el estado en la base de datos
 */
function guardarRegistro(dpi, nombre) {
  // Mostrar loading
  Swal.fire({
    title: "Guardando...",
    html: "Por favor espera mientras se actualiza el registro",
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    },
  });

  // Realizar petición AJAX
  $.ajax({
    url: "api/actualizar_estado.php",
    type: "POST",
    contentType: "application/json",
    data: JSON.stringify({
      dpi: dpi,
    }),
    success: function (response) {
      if (response.success) {
        // Mostrar mensaje de éxito
        Swal.fire({
          icon: "success",
          title: "¡Registro guardado!",
          html: `
                        <div style="text-align: left; padding: 10px;">
                            <p><strong>Nombre:</strong> ${nombre}</p>
                            <p><strong>DPI:</strong> ${dpi}</p>
                            <p style="margin-top: 15px; color: #10b981;">
                                <i class="fas fa-check-circle"></i> 
                                Estado actualizado a <strong>DPI Físico</strong>
                            </p>
                        </div>
                    `,
          confirmButtonText: "Entendido",
          timer: 3000,
        }).then(() => {
          // Recargar la tabla
          tablaDPI.ajax.reload(null, false); // false para mantener la página actual
          actualizarEstadisticas();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: response.message || "No se pudo actualizar el registro",
          confirmButtonText: "Entendido",
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error de conexión",
        text: "No se pudo conectar con el servidor. Por favor, intenta nuevamente.",
        confirmButtonText: "Entendido",
      });
    },
  });
}

/**
 * Actualiza las estadísticas en las tarjetas superiores
 */
function actualizarEstadisticas() {
  $.ajax({
    url: "api/obtener_registros.php",
    type: "GET",
    success: function (response) {
      if (response.success && response.data) {
        const datos = response.data;
        const total = datos.length;
        const registrados = datos.filter(
          (r) => r.estado === "DPI Físico",
        ).length;
        const sinRegistrar = total - registrados;
        
        // Calcular porcentajes
        const porcentajeRegistrados = total > 0 ? ((registrados / total) * 100).toFixed(2) : 0;
        const porcentajePendientes = total > 0 ? ((sinRegistrar / total) * 100).toFixed(2) : 100;

        // Actualizar los contadores con animación
        animarContador("#totalRegistros", total);
        animarContador("#registradosCount", registrados);
        animarContador("#sinRegistrarCount", sinRegistrar);
        
        // Actualizar porcentajes
        $("#porcentajeRegistrados").text(porcentajeRegistrados + "%");
        $("#porcentajePendientes").text(porcentajePendientes + "%");
      }
    },
    error: function (xhr, status, error) {
      console.error("Error al obtener estadísticas:", error);
    },
  });
}

/**
 * Anima el contador de un elemento
 */
function animarContador(selector, valorFinal) {
  const elemento = $(selector);
  const valorInicial = parseInt(elemento.text().replace(/,/g, "")) || 0;
  const duracion = 1000; // 1 segundo
  const pasos = 50;
  const incremento = (valorFinal - valorInicial) / pasos;
  let valorActual = valorInicial;
  let paso = 0;

  const intervalo = setInterval(() => {
    paso++;
    valorActual += incremento;

    if (paso >= pasos) {
      clearInterval(intervalo);
      elemento.text(valorFinal.toLocaleString("es-ES"));
    } else {
      elemento.text(Math.round(valorActual).toLocaleString("es-ES"));
    }
  }, duracion / pasos);
}
