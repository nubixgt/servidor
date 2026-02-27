// web/js/dashboard_tecnico1.js
// JavaScript personalizado para Dashboard de Técnico 1 - Área Legal

$(document).ready(function () {
  // Inicializar DataTable con configuración completa
  $("#tablaDenuncias").DataTable({
    scrollX: true,
    language: {
      url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "excel",
        text: '<i class="fas fa-file-excel"></i> Excel',
        className: "btn-dt",
        title: "Denuncias_Area_Legal",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
      },
      {
        extend: "pdf",
        text: '<i class="fas fa-file-pdf"></i> PDF',
        className: "btn-dt",
        title: "Denuncias_Area_Legal",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
        customize: function (doc) {
          doc.content[1].table.widths = Array(
            doc.content[1].table.body[0].length + 1
          )
            .join("*")
            .split("");
        },
      },
      {
        extend: "copy",
        text: '<i class="fas fa-copy"></i> Copiar',
        className: "btn-dt",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
      },
      {
        extend: "print",
        text: '<i class="fas fa-print"></i> Imprimir',
        className: "btn-dt",
        title: "Denuncias - Área Legal",
        exportOptions: {
          columns: [0, 1, 2, 3, 4, 5, 6, 7, 8],
        },
      },
    ],
    order: [[0, "desc"]],
    pageLength: 10,
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "Todos"],
    ],
    columnDefs: [{ orderable: false, targets: 9 }],
  });
});
