/**
 * VIDER - MAGA Guatemala
 * JavaScript Principal
 */

// =====================================================
// CONFIGURACIÓN GLOBAL
// =====================================================

const VIDER = {
  baseUrl: "",
  mapData: {},
  charts: {},
  currentDepartment: null,
  currentMunicipio: null,
  filters: {
    departamento: "",
    dependencia: "",
    periodo: "",
  },
};

// =====================================================
// INICIALIZACIÓN
// =====================================================

document.addEventListener("DOMContentLoaded", function () {
  initializeApp();
});

function initializeApp() {
  // Inicializar componentes
  initSidebar();
  initTabs();
  initTooltips();
  initAnimations();

  // Cargar datos iniciales si estamos en el dashboard
  if (document.getElementById("dashboard-page")) {
    loadDashboardData();
    initMap();
    initCharts();
  }

  // Inicializar upload si existe
  if (document.getElementById("upload-zone")) {
    initUpload();
  }

  // Inicializar filtros
  initFilters();
}

// =====================================================
// SIDEBAR Y NAVEGACIÓN
// =====================================================

function initSidebar() {
  const menuToggle = document.querySelector(".menu-toggle");
  const sidebar = document.querySelector(".sidebar");

  if (menuToggle) {
    menuToggle.addEventListener("click", () => {
      sidebar.classList.toggle("open");
    });
  }

  // Marcar enlace activo
  const currentPath = window.location.pathname;
  document.querySelectorAll(".nav-link").forEach((link) => {
    if (
      link.getAttribute("href") === currentPath ||
      (currentPath.includes(link.getAttribute("href")) &&
        link.getAttribute("href") !== "/")
    ) {
      link.classList.add("active");
    }
  });
}

// =====================================================
// TABS
// =====================================================

function initTabs() {
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const tabGroup = this.closest(".tabs-container");
      const targetId = this.dataset.tab;

      // Actualizar botones
      tabGroup
        .querySelectorAll(".tab-btn")
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      // Actualizar contenido
      tabGroup.querySelectorAll(".tab-content").forEach((content) => {
        content.classList.remove("active");
      });
      document.getElementById(targetId).classList.add("active");
    });
  });
}

// =====================================================
// MAPA INTERACTIVO DE GUATEMALA
// =====================================================

function initMap() {
  const mapWrapper = document.getElementById("guatemala-map");
  if (!mapWrapper) return;

  // SVG del mapa de Guatemala
  createGuatemalaMap(mapWrapper);

  // Cargar datos por departamento
  loadMapData();
}

function createGuatemalaMap(container) {
  // Coordenadas simplificadas de los departamentos de Guatemala
  const departamentos = {
    Petén: {
      path: "M180,20 L320,20 L340,80 L320,140 L280,160 L220,150 L180,120 L160,60 Z",
      center: [250, 90],
    },
    Huehuetenango: {
      path: "M40,140 L100,130 L120,160 L110,200 L70,210 L30,190 Z",
      center: [75, 170],
    },
    Quiché: {
      path: "M100,130 L180,120 L200,150 L180,190 L140,200 L110,180 Z",
      center: [145, 160],
    },
    "Alta Verapaz": {
      path: "M180,120 L280,160 L290,200 L250,230 L200,220 L180,190 Z",
      center: [235, 180],
    },
    Izabal: {
      path: "M280,160 L380,150 L400,200 L350,240 L290,220 Z",
      center: [340, 195],
    },
    "San Marcos": {
      path: "M20,200 L70,210 L80,250 L50,280 L20,260 Z",
      center: [50, 240],
    },
    Quetzaltenango: {
      path: "M70,210 L110,200 L120,240 L100,270 L70,260 Z",
      center: [95, 240],
    },
    Totonicapán: {
      path: "M110,200 L140,200 L145,230 L120,240 Z",
      center: [125, 220],
    },
    Sololá: {
      path: "M120,240 L145,230 L155,260 L130,280 Z",
      center: [140, 255],
    },
    Retalhuleu: {
      path: "M50,280 L100,270 L110,310 L70,320 Z",
      center: [80, 295],
    },
    Suchitepéquez: {
      path: "M100,270 L130,280 L140,320 L110,330 Z",
      center: [120, 300],
    },
    Chimaltenango: {
      path: "M145,230 L180,220 L190,260 L160,270 L145,250 Z",
      center: [165, 245],
    },
    Sacatepéquez: {
      path: "M160,270 L190,260 L195,290 L170,295 Z",
      center: [180, 280],
    },
    Escuintla: {
      path: "M140,320 L200,300 L220,350 L160,360 Z",
      center: [180, 335],
    },
    Guatemala: {
      path: "M190,260 L230,250 L240,290 L210,300 L195,290 Z",
      center: [215, 275],
    },
    "Baja Verapaz": {
      path: "M180,190 L200,220 L240,210 L230,180 Z",
      center: [210, 200],
    },
    "El Progreso": {
      path: "M230,250 L270,240 L280,270 L250,280 Z",
      center: [255, 260],
    },
    Zacapa: {
      path: "M290,200 L350,240 L340,280 L300,270 L280,230 Z",
      center: [315, 245],
    },
    Chiquimula: {
      path: "M300,270 L340,280 L350,320 L310,330 Z",
      center: [325, 300],
    },
    Jalapa: {
      path: "M270,280 L310,270 L320,310 L280,320 Z",
      center: [295, 295],
    },
    Jutiapa: {
      path: "M280,320 L350,320 L360,360 L300,370 Z",
      center: [320, 345],
    },
    "Santa Rosa": {
      path: "M220,350 L280,320 L300,370 L250,380 Z",
      center: [260, 355],
    },
  };

  let svg = `
        <svg viewBox="0 0 420 400" class="guatemala-svg">
            <defs>
                <linearGradient id="mapGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:#1a3a5c;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#4a90d9;stop-opacity:1" />
                </linearGradient>
                <filter id="glow">
                    <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                    <feMerge>
                        <feMergeNode in="coloredBlur"/>
                        <feMergeNode in="SourceGraphic"/>
                    </feMerge>
                </filter>
            </defs>
    `;

  // Agregar paths de departamentos
  Object.entries(departamentos).forEach(([nombre, data]) => {
    svg += `
            <path 
                class="dept-path" 
                d="${data.path}" 
                data-dept="${nombre}"
                data-center-x="${data.center[0]}"
                data-center-y="${data.center[1]}"
            >
                <title>${nombre}</title>
            </path>
        `;
  });

  // Agregar labels de departamentos
  Object.entries(departamentos).forEach(([nombre, data]) => {
    const fontSize = nombre.length > 10 ? 6 : 8;
    svg += `
            <text 
                x="${data.center[0]}" 
                y="${data.center[1]}" 
                class="dept-label"
                text-anchor="middle"
                font-size="${fontSize}"
                fill="white"
                pointer-events="none"
                style="font-family: 'Space Grotesk', sans-serif; font-weight: 600; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);"
            >${nombre}</text>
        `;
  });

  svg += "</svg>";

  container.innerHTML = svg;

  // Agregar eventos a los departamentos
  container.querySelectorAll(".dept-path").forEach((path) => {
    path.addEventListener("click", function () {
      selectDepartment(this.dataset.dept);
    });

    path.addEventListener("mouseenter", function (e) {
      showMapTooltip(e, this.dataset.dept);
    });

    path.addEventListener("mouseleave", function () {
      hideMapTooltip();
    });
  });
}

function loadMapData() {
  fetch("api/get_map_data.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        VIDER.mapData = data.data;
        updateMapColors();
      }
    })
    .catch((error) => console.error("Error loading map data:", error));
}

function updateMapColors() {
  const paths = document.querySelectorAll(".dept-path");
  const maxValue = Math.max(
    ...Object.values(VIDER.mapData).map((d) => d.total_beneficiarios || 0),
  );

  paths.forEach((path) => {
    const dept = path.dataset.dept;
    const data = VIDER.mapData[dept];

    if (data && data.total_beneficiarios > 0) {
      const intensity = data.total_beneficiarios / maxValue;
      const lightness = 35 + 30 * (1 - intensity);
      path.style.fill = `hsl(145, 60%, ${lightness}%)`;
      path.classList.remove("no-data");
    } else {
      path.classList.add("no-data");
    }
  });
}

function selectDepartment(deptName) {
  // Quitar selección anterior
  document
    .querySelectorAll(".dept-path.selected")
    .forEach((p) => p.classList.remove("selected"));

  // Seleccionar nuevo
  const path = document.querySelector(`.dept-path[data-dept="${deptName}"]`);
  if (path) {
    path.classList.add("selected");
  }

  VIDER.currentDepartment = deptName;

  // Actualizar panel de información
  updateMapInfoPanel(deptName);

  // Cargar municipios del departamento
  loadMunicipiosData(deptName);

  // Zoom al departamento (animación)
  zoomToDepartment(path);
}

function zoomToDepartment(path) {
  const svg = document.querySelector(".guatemala-svg");
  const centerX = parseFloat(path.dataset.centerX);
  const centerY = parseFloat(path.dataset.centerY);

  // Calcular viewBox para zoom
  const zoomSize = 150;
  const newViewBox = `${centerX - zoomSize / 2} ${centerY - zoomSize / 2} ${zoomSize} ${zoomSize}`;

  svg.style.transition = "all 0.5s ease";
  svg.setAttribute("viewBox", newViewBox);

  // Agregar botón de reset zoom
  showResetZoomButton();
}

function resetMapZoom() {
  const svg = document.querySelector(".guatemala-svg");
  svg.style.transition = "all 0.5s ease";
  svg.setAttribute("viewBox", "0 0 420 400");

  // Quitar selección
  document
    .querySelectorAll(".dept-path.selected")
    .forEach((p) => p.classList.remove("selected"));
  VIDER.currentDepartment = null;

  // Ocultar botón reset
  const resetBtn = document.querySelector(".btn-reset-zoom");
  if (resetBtn) resetBtn.remove();

  // Restaurar panel info
  updateMapInfoPanel(null);
}

function showResetZoomButton() {
  if (document.querySelector(".btn-reset-zoom")) return;

  const controls = document.querySelector(".map-controls");
  if (!controls) return;

  const btn = document.createElement("button");
  btn.className = "btn btn-secondary btn-icon btn-reset-zoom";
  btn.innerHTML = '<i class="fas fa-compress-arrows-alt"></i>';
  btn.title = "Restablecer vista";
  btn.onclick = resetMapZoom;
  controls.appendChild(btn);
}

function updateMapInfoPanel(deptName) {
  const panel = document.getElementById("map-info-panel");
  if (!panel) return;

  if (!deptName) {
    panel.innerHTML = `
            <h3 class="map-info-title">República de Guatemala</h3>
            <div class="map-info-stats">
                <div class="map-info-stat">
                    <span class="map-info-stat-label">Departamentos</span>
                    <span class="map-info-stat-value">22</span>
                </div>
                <div class="map-info-stat">
                    <span class="map-info-stat-label">Seleccione un departamento</span>
                </div>
            </div>
        `;
    return;
  }

  const data = VIDER.mapData[deptName] || {};

  panel.innerHTML = `
        <h3 class="map-info-title">${deptName}</h3>
        <div class="map-info-stats">
            <div class="map-info-stat">
                <span class="map-info-stat-label">Beneficiarios</span>
                <span class="map-info-stat-value">${formatNumber(data.total_beneficiarios || 0)}</span>
            </div>
            <div class="map-info-stat">
                <span class="map-info-stat-label">Hombres</span>
                <span class="map-info-stat-value">${formatNumber(data.total_hombres || 0)}</span>
            </div>
            <div class="map-info-stat">
                <span class="map-info-stat-label">Mujeres</span>
                <span class="map-info-stat-value">${formatNumber(data.total_mujeres || 0)}</span>
            </div>
            <div class="map-info-stat">
                <span class="map-info-stat-label">Programado</span>
                <span class="map-info-stat-value">${formatNumber(data.total_programado || 0)}</span>
            </div>
            <div class="map-info-stat">
                <span class="map-info-stat-label">Ejecutado</span>
                <span class="map-info-stat-value">${formatNumber(data.total_ejecutado || 0)}</span>
            </div>
            <div class="map-info-stat">
                <span class="map-info-stat-label">% Ejecución</span>
                <span class="map-info-stat-value text-${data.porcentaje_ejecucion >= 50 ? "success" : "warning"}">${formatPercent(data.porcentaje_ejecucion || 0)}%</span>
            </div>
            <div class="map-info-stat">
                <span class="map-info-stat-label">Financiero Vigente</span>
                <span class="map-info-stat-value">${formatCurrency(data.total_financiero_vigente || 0)}</span>
            </div>
        </div>
        <button class="btn btn-primary btn-sm mt-2 w-100" onclick="viewDepartmentDetails('${deptName}')">
            <i class="fas fa-eye"></i> Ver Detalles
        </button>
    `;
}

function showMapTooltip(event, deptName) {
  let tooltip = document.getElementById("map-tooltip");
  if (!tooltip) {
    tooltip = document.createElement("div");
    tooltip.id = "map-tooltip";
    tooltip.className = "tooltip";
    document.body.appendChild(tooltip);
  }

  const data = VIDER.mapData[deptName] || {};

  tooltip.innerHTML = `
        <div class="tooltip-title">${deptName}</div>
        <div class="tooltip-content">
            <div class="tooltip-row">
                <span class="tooltip-label">Beneficiarios:</span>
                <span class="tooltip-value">${formatNumber(data.total_beneficiarios || 0)}</span>
            </div>
            <div class="tooltip-row">
                <span class="tooltip-label">% Ejecución:</span>
                <span class="tooltip-value">${formatPercent(data.porcentaje_ejecucion || 0)}%</span>
            </div>
        </div>
    `;

  tooltip.style.left = event.pageX + 10 + "px";
  tooltip.style.top = event.pageY + 10 + "px";
  tooltip.classList.add("show");
}

function hideMapTooltip() {
  const tooltip = document.getElementById("map-tooltip");
  if (tooltip) {
    tooltip.classList.remove("show");
  }
}

function loadMunicipiosData(deptName) {
  fetch(`api/get_municipios.php?departamento=${encodeURIComponent(deptName)}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateMunicipiosTable(data.data);
      }
    })
    .catch((error) => console.error("Error loading municipios:", error));
}

function updateMunicipiosTable(municipios) {
  const container = document.getElementById("municipios-table");
  if (!container) return;

  if (!municipios || municipios.length === 0) {
    container.innerHTML =
      '<p class="text-muted text-center">No hay datos de municipios</p>';
    return;
  }

  let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Municipio</th>
                    <th>Beneficiarios</th>
                    <th>Programado</th>
                    <th>Ejecutado</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
    `;

  municipios.forEach((m) => {
    html += `
            <tr>
                <td>${m.municipio}</td>
                <td>${formatNumber(m.total_beneficiarios)}</td>
                <td>${formatNumber(m.total_programado)}</td>
                <td>${formatNumber(m.total_ejecutado)}</td>
                <td>
                    <span class="badge badge-${m.porcentaje >= 50 ? "success" : "warning"}">
                        ${formatPercent(m.porcentaje)}%
                    </span>
                </td>
            </tr>
        `;
  });

  html += "</tbody></table>";
  container.innerHTML = html;
}

// =====================================================
// GRÁFICOS (Charts)
// =====================================================

function initCharts() {
  loadChartData();
}

function loadChartData() {
  fetch("api/get_dashboard_stats.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateDashboardStats(data.stats);
        createCharts(data);
      }
    })
    .catch((error) => console.error("Error loading chart data:", error));
}

function updateDashboardStats(stats) {
  // Actualizar cards de estadísticas con animación
  animateValue("stat-beneficiarios", 0, stats.total_beneficiarios || 0, 1500);
  animateValue("stat-programado", 0, stats.total_programado || 0, 1500);
  animateValue("stat-ejecutado", 0, stats.total_ejecutado || 0, 1500);
  animateValue(
    "stat-porcentaje",
    0,
    stats.porcentaje_ejecucion || 0,
    1500,
    true,
  );

  // Financiero
  if (document.getElementById("stat-financiero-vigente")) {
    animateCurrency(
      "stat-financiero-vigente",
      0,
      stats.total_financiero_vigente || 0,
      1500,
    );
  }
}

function createCharts(data) {
  // Gráfico de Beneficiarios por Departamento
  if (document.getElementById("chart-departamentos")) {
    createDepartamentosChart(data.por_departamento);
  }

  // Gráfico de Ejecución por Dependencia
  if (document.getElementById("chart-dependencias")) {
    createDependenciasChart(data.por_dependencia);
  }

  // Gráfico de Género
  if (document.getElementById("chart-genero")) {
    createGeneroChart(data.stats);
  }

  // Gráfico de Ejecución Financiera
  if (document.getElementById("chart-financiero")) {
    createFinancieroChart(data.stats);
  }
}

function createDepartamentosChart(data) {
  const ctx = document.getElementById("chart-departamentos").getContext("2d");

  // Detect current theme - check both DOM and localStorage
  const savedTheme = localStorage.getItem('vider-theme');
  const domTheme = document.documentElement.getAttribute('data-theme');
  const isLightMode = savedTheme === 'light' || domTheme === 'light';
  const textColor = isLightMode ? '#1a3a5c' : '#e2e8f0';
  const gridColor = isLightMode ? 'rgba(26, 58, 92, 0.1)' : 'rgba(255, 255, 255, 0.1)';

  // Ordenar por beneficiarios y tomar top 10
  const sorted = Object.entries(data)
    .sort(
      (a, b) =>
        (b[1].total_beneficiarios || 0) - (a[1].total_beneficiarios || 0),
    )
    .slice(0, 10);

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: sorted.map((d) => d[0]),
      datasets: [
        {
          label: "Beneficiarios",
          data: sorted.map((d) => d[1].total_beneficiarios || 0),
          backgroundColor: "rgba(74, 144, 217, 0.8)",
          borderColor: "rgba(74, 144, 217, 1)",
          borderWidth: 1,
          borderRadius: 8,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: gridColor,
          },
          ticks: {
            color: textColor,
          },
        },
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: textColor,
            maxRotation: 45,
          },
        },
      },
    },
  });
}

function createDependenciasChart(data) {
  const ctx = document.getElementById("chart-dependencias").getContext("2d");

  // Detect current theme - check both DOM and localStorage
  const savedTheme = localStorage.getItem('vider-theme');
  const domTheme = document.documentElement.getAttribute('data-theme');
  const isLightMode = savedTheme === 'light' || domTheme === 'light';
  const textColor = isLightMode ? '#1a3a5c' : '#e2e8f0';

  const colors = [
    "rgba(74, 144, 217, 0.8)",
    "rgba(247, 185, 40, 0.8)",
    "rgba(59, 130, 246, 0.8)",
    "rgba(16, 185, 129, 0.8)",
    "rgba(239, 68, 68, 0.8)",
  ];

  new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: data.map((d) => d.siglas || d.dependencia.substring(0, 20)),
      datasets: [
        {
          data: data.map((d) => d.total_beneficiarios || 0),
          backgroundColor: colors,
          borderColor: isLightMode ? "#ffffff" : "#1a1f2e",
          borderWidth: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            color: textColor,
            padding: 20,
            usePointStyle: true,
          },
        },
      },
      cutout: "60%",
    },
  });
}

function createGeneroChart(stats) {
  const ctx = document.getElementById("chart-genero").getContext("2d");

  // Detect current theme - check both DOM and localStorage
  const savedTheme = localStorage.getItem('vider-theme');
  const domTheme = document.documentElement.getAttribute('data-theme');
  const isLightMode = savedTheme === 'light' || domTheme === 'light';
  const textColor = isLightMode ? '#1a3a5c' : '#e2e8f0';

  new Chart(ctx, {
    type: "pie",
    data: {
      labels: ["Hombres", "Mujeres"],
      datasets: [
        {
          data: [stats.total_hombres || 0, stats.total_mujeres || 0],
          backgroundColor: [
            "rgba(59, 130, 246, 0.8)",
            "rgba(236, 72, 153, 0.8)",
          ],
          borderColor: isLightMode ? "#ffffff" : "#1a1f2e",
          borderWidth: 3,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            color: textColor,
            padding: 20,
            usePointStyle: true,
          },
        },
      },
    },
  });
}

function createFinancieroChart(stats) {
  const ctx = document.getElementById("chart-financiero").getContext("2d");

  // Detect current theme - check both DOM and localStorage
  const savedTheme = localStorage.getItem('vider-theme');
  const domTheme = document.documentElement.getAttribute('data-theme');
  const isLightMode = savedTheme === 'light' || domTheme === 'light';
  const textColor = isLightMode ? '#1a3a5c' : '#e2e8f0';
  const gridColor = isLightMode ? 'rgba(26, 58, 92, 0.1)' : 'rgba(255, 255, 255, 0.1)';

  new Chart(ctx, {
    type: "bar",
    data: {
      labels: ["Presupuesto"],
      datasets: [
        {
          label: "Vigente",
          data: [stats.total_financiero_vigente || 0],
          backgroundColor: "rgba(74, 144, 217, 0.8)",
          borderRadius: 8,
        },
        {
          label: "Ejecutado",
          data: [stats.total_financiero_ejecutado || 0],
          backgroundColor: "rgba(247, 185, 40, 0.8)",
          borderRadius: 8,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      indexAxis: "y",
      plugins: {
        legend: {
          position: "bottom",
          labels: {
            color: textColor,
          },
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            color: gridColor,
          },
          ticks: {
            color: textColor,
            callback: function (value) {
              return "Q" + formatNumber(value);
            },
          },
        },
        y: {
          grid: {
            display: false,
          },
          ticks: {
            color: textColor,
          },
        },
      },
    },
  });
}

// =====================================================
// UPLOAD DE ARCHIVOS
// =====================================================

function initUpload() {
  const uploadZone = document.getElementById("upload-zone");
  const uploadInput = document.getElementById("upload-input");

  if (!uploadZone || !uploadInput) return;

  // Drag & Drop events
  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    uploadZone.addEventListener(eventName, preventDefaults, false);
  });

  ["dragenter", "dragover"].forEach((eventName) => {
    uploadZone.addEventListener(eventName, () =>
      uploadZone.classList.add("dragover"),
    );
  });

  ["dragleave", "drop"].forEach((eventName) => {
    uploadZone.addEventListener(eventName, () =>
      uploadZone.classList.remove("dragover"),
    );
  });

  uploadZone.addEventListener("drop", handleDrop);
  uploadInput.addEventListener("change", handleFileSelect);
}

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

function handleDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  handleFiles(files);
}

function handleFileSelect(e) {
  const files = e.target.files;
  handleFiles(files);
}

function handleFiles(files) {
  if (files.length === 0) return;

  const file = files[0];
  const allowedTypes = [
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "application/vnd.ms-excel",
    "text/csv",
  ];

  const extension = file.name.split(".").pop().toLowerCase();

  if (!["xlsx", "xls", "csv"].includes(extension)) {
    showToast(
      "error",
      "Formato no válido. Solo se permiten archivos Excel (.xlsx, .xls) o CSV.",
    );
    return;
  }

  if (file.size > 50 * 1024 * 1024) {
    showToast("error", "El archivo es demasiado grande. Máximo 50MB.");
    return;
  }

  uploadFile(file);
}

function uploadFile(file) {
  const formData = new FormData();
  formData.append("file", file);

  const progressContainer = document.querySelector(".progress-container");
  const progressFill = document.querySelector(".progress-fill");
  const progressText = document.querySelector(".progress-percent");
  const progressStatus = document.querySelector(".progress-status");

  progressContainer.classList.add("show");
  progressStatus.textContent = "Subiendo archivo...";

  const xhr = new XMLHttpRequest();

  xhr.upload.addEventListener("progress", (e) => {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      progressFill.style.width = percent + "%";
      progressText.textContent = percent + "%";
    }
  });

  xhr.addEventListener("load", () => {
    if (xhr.status === 200) {
      try {
        const response = JSON.parse(xhr.responseText);
        if (response.success) {
          progressStatus.textContent = "Procesando datos...";
          processImport(response.import_id);
        } else {
          showToast("error", response.message || "Error al subir archivo");
          progressContainer.classList.remove("show");
        }
      } catch (e) {
        showToast("error", "Error al procesar respuesta");
        progressContainer.classList.remove("show");
      }
    } else {
      showToast("error", "Error al subir archivo");
      progressContainer.classList.remove("show");
    }
  });

  xhr.addEventListener("error", () => {
    showToast("error", "Error de conexión");
    progressContainer.classList.remove("show");
  });

  xhr.open("POST", "api/upload.php");
  xhr.send(formData);
}

function processImport(importId) {
  const progressStatus = document.querySelector(".progress-status");

  fetch("api/process_import.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ import_id: importId }),
  })
    .then((response) => response.json())
    .then((data) => {
      document.querySelector(".progress-container").classList.remove("show");

      if (data.success) {
        showImportResults(data);
        showToast("success", "Importación completada exitosamente");
      } else {
        showToast("error", data.message || "Error al procesar importación");
      }
    })
    .catch((error) => {
      document.querySelector(".progress-container").classList.remove("show");
      showToast("error", "Error al procesar importación");
    });
}

function showImportResults(data) {
  const resultsContainer = document.getElementById("import-results");
  if (!resultsContainer) return;

  resultsContainer.innerHTML = `
        <div class="card animate-fade-in">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-check-circle text-success"></i> Resultados de Importación</h3>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-file-excel"></i></div>
                    <div class="stat-value">${formatNumber(data.total_registros)}</div>
                    <div class="stat-label">Registros Totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check text-success"></i></div>
                    <div class="stat-value">${formatNumber(data.importados)}</div>
                    <div class="stat-label">Importados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-copy text-warning"></i></div>
                    <div class="stat-value">${formatNumber(data.duplicados)}</div>
                    <div class="stat-label">Duplicados (Omitidos)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                    <div class="stat-value">${formatNumber(data.errores)}</div>
                    <div class="stat-label">Errores</div>
                </div>
            </div>
        </div>
    `;

  resultsContainer.style.display = "block";
}

// =====================================================
// FILTROS
// =====================================================

function initFilters() {
  const departamentoSelect = document.getElementById("filter-departamento");
  const dependenciaSelect = document.getElementById("filter-dependencia");

  if (departamentoSelect) {
    departamentoSelect.addEventListener("change", applyFilters);
  }

  if (dependenciaSelect) {
    dependenciaSelect.addEventListener("change", applyFilters);
  }
}

function applyFilters() {
  VIDER.filters.departamento =
    document.getElementById("filter-departamento")?.value || "";
  VIDER.filters.dependencia =
    document.getElementById("filter-dependencia")?.value || "";

  // Recargar datos con filtros
  loadFilteredData();
}

function loadFilteredData() {
  const params = new URLSearchParams(VIDER.filters);

  fetch(`api/get_filtered_data.php?${params}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateDataTable(data.data);
      }
    })
    .catch((error) => console.error("Error loading filtered data:", error));
}

function updateDataTable(data) {
  const container = document.getElementById("data-table-container");
  if (!container) return;

  if (!data || data.length === 0) {
    container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                <h4 class="empty-state-title">No hay datos</h4>
                <p>No se encontraron registros con los filtros seleccionados</p>
            </div>
        `;
    return;
  }

  let html = `
        <table class="data-table">
            <thead>
                <tr>
                    <th>Departamento</th>
                    <th>Municipio</th>
                    <th>Dependencia</th>
                    <th>Producto</th>
                    <th>Beneficiarios</th>
                    <th>Programado</th>
                    <th>Ejecutado</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
    `;

  data.forEach((row) => {
    html += `
            <tr>
                <td>${row.departamento}</td>
                <td>${row.municipio}</td>
                <td><span class="badge badge-primary">${row.siglas || "N/A"}</span></td>
                <td title="${row.producto}">${truncateText(row.producto, 50)}</td>
                <td>${formatNumber(row.total_personas)}</td>
                <td>${formatNumber(row.programado)}</td>
                <td>${formatNumber(row.ejecutado)}</td>
                <td>
                    <span class="badge badge-${row.porcentaje_ejecucion >= 50 ? "success" : "warning"}">
                        ${formatPercent(row.porcentaje_ejecucion)}%
                    </span>
                </td>
            </tr>
        `;
  });

  html += "</tbody></table>";
  container.innerHTML = html;
}

// =====================================================
// TOOLTIPS
// =====================================================

function initTooltips() {
  document.querySelectorAll("[data-tooltip]").forEach((elem) => {
    elem.addEventListener("mouseenter", showTooltipElem);
    elem.addEventListener("mouseleave", hideTooltipElem);
  });
}

function showTooltipElem(e) {
  const tooltip = document.createElement("div");
  tooltip.className = "tooltip show";
  tooltip.textContent = e.target.dataset.tooltip;
  tooltip.style.left = e.pageX + 10 + "px";
  tooltip.style.top = e.pageY + 10 + "px";
  document.body.appendChild(tooltip);
  e.target._tooltip = tooltip;
}

function hideTooltipElem(e) {
  if (e.target._tooltip) {
    e.target._tooltip.remove();
  }
}

// =====================================================
// ANIMACIONES
// =====================================================

function initAnimations() {
  // Intersection Observer para animaciones al scroll
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("animate-fade-in");
        }
      });
    },
    { threshold: 0.1 },
  );

  document.querySelectorAll(".card, .stat-card").forEach((el) => {
    observer.observe(el);
  });
}

function animateValue(elementId, start, end, duration, isPercent = false) {
  const element = document.getElementById(elementId);
  if (!element) return;

  const startTime = performance.now();

  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const easeOut = 1 - Math.pow(1 - progress, 3);
    const current = Math.floor(start + (end - start) * easeOut);

    element.textContent = isPercent
      ? formatPercent(current) + "%"
      : formatNumber(current);

    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

function animateCurrency(elementId, start, end, duration) {
  const element = document.getElementById(elementId);
  if (!element) return;

  const startTime = performance.now();

  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const easeOut = 1 - Math.pow(1 - progress, 3);
    const current = start + (end - start) * easeOut;

    element.textContent = formatCurrency(current);

    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

// =====================================================
// TOASTS / NOTIFICACIONES
// =====================================================

function showToast(type, message, duration = 5000) {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const icons = {
    success: "fa-check-circle",
    warning: "fa-exclamation-triangle",
    danger: "fa-times-circle",
    error: "fa-times-circle",
    info: "fa-info-circle",
  };

  const toast = document.createElement("div");
  toast.className = `toast toast-${type === "error" ? "danger" : type}`;
  toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info} toast-icon"></i>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = "0";
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// =====================================================
// MODALES
// =====================================================

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("show");
    document.body.style.overflow = "hidden";
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("show");
    document.body.style.overflow = "";
  }
}

// Cerrar modal al hacer clic fuera
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("modal-overlay")) {
    e.target.classList.remove("show");
    document.body.style.overflow = "";
  }
});

// =====================================================
// UTILIDADES
// =====================================================

function formatNumber(num) {
  if (num === null || num === undefined) return "0";
  return new Intl.NumberFormat("es-GT").format(Math.round(num));
}

function formatCurrency(amount) {
  if (amount === null || amount === undefined) return "Q 0.00";
  return (
    "Q " +
    new Intl.NumberFormat("es-GT", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount)
  );
}

function formatPercent(num) {
  if (num === null || num === undefined) return "0";
  return parseFloat(num).toFixed(1);
}

function truncateText(text, maxLength) {
  if (!text) return "";
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
}

function viewDepartmentDetails(deptName) {
  window.location.href = `datos.php?departamento=${encodeURIComponent(deptName)}`;
}

function exportData(format) {
  const params = new URLSearchParams(VIDER.filters);
  params.append("format", format);
  window.location.href = `api/export.php?${params}`;
}

// =====================================================
// DASHBOARD DATA LOADER
// =====================================================

function loadDashboardData() {
  // El dashboard carga automáticamente los datos
  // a través de las funciones de inicialización
}

// Exportar funciones globales
window.VIDER = VIDER;
window.selectDepartment = selectDepartment;
window.resetMapZoom = resetMapZoom;
window.viewDepartmentDetails = viewDepartmentDetails;
window.exportData = exportData;
window.openModal = openModal;
window.closeModal = closeModal;
window.showToast = showToast;
