/* Vista "Variables técnicas": catálogo de referencia de las variables que se capturan por parcela. */
const ViewVariables = (() => {
  const GRUPOS = [
    { id: 'soil', label: 'Suelo', icon: '🪨', variables: ['Profundidad de suelo', 'Tipo / textura', 'Estructura', 'Compactación', 'Materia orgánica', 'Infiltración', 'Riesgo de erosión', 'Color', 'pH', 'Humedad'] },
    { id: 'water', label: 'Agua', icon: '💧', variables: ['Escorrentía superficial', 'Tasa de infiltración', 'Encharcamiento', 'Drenaje', 'Fuentes de agua', 'Ríos / quebradas', 'Nacimientos', 'Reservorios', 'Sistema de riego'] },
    { id: 'rainfall', label: 'Lluvia', icon: '🌧️', variables: ['Lluvia anual acumulada', 'Lluvia mensual', 'Evento máximo', 'Días de lluvia', 'Fuente del dato', 'Fecha de captura'] },
    { id: 'talpetate', label: 'Talpetate', icon: '🪨', variables: ['Presencia', 'Profundidad', 'Espesor', 'Dureza', 'Ubicación en el perfil', 'Porcentaje estimado'] },
    { id: 'bioindicators', label: 'Bioindicadores', icon: '🌱', variables: ['Lombrices', 'Insectos', 'Hongos', 'Raíces', 'Plantas nativas', 'Organismos del suelo', 'Residuo orgánico / hojarasca'] },
    { id: 'topography', label: 'Topografía', icon: '⛰️', variables: ['Elevación', 'Pendiente', 'Orientación', 'Curvas de nivel', 'Punto clave (keypoint)', 'Línea keyline', 'Cresta', 'Valle'] },
  ];

  async function render(container) {
    container.innerHTML = `
      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Catálogo de variables técnicas</h3><p>Referencia rápida de las variables que se capturan al registrar una parcela keyline, agrupadas por diagnóstico.</p></div>
        </div>
        <p class="hint">Usa esta guía como apoyo en campo: te recuerda qué observar y registrar en cada grupo del formulario de registro.</p>
      </section>
      <div class="grid3">
        ${GRUPOS.map(grupoHtml).join('')}
      </div>
    `;
  }

  function grupoHtml(g) {
    return `
      <div class="glossary-group">
        <h4>${g.icon} ${g.label}</h4>
        <div class="chip-list">${g.variables.map((v) => `<span class="chip">${UI.escapeHtml(v)}</span>`).join('')}</div>
      </div>
    `;
  }

  return { render };
})();
