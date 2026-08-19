/* Vista "Bioindicadores": salud biológica del suelo reportada en campo (Mafer Md) */
const ViewBioindicadores = (() => {
  async function render(container) {
    container.innerHTML = `<div class="loading-spin"></div>`;
    const [{ parcelas }, resumen] = await Promise.all([Api.listarParcelas(), Api.dashboardResumen()]);
    const conBio = parcelas.filter((p) => (p.bioindicadores || '').trim().length > 0);
    const total = parcelas.length || 1;
    const pct = Math.round((conBio.length / total) * 100);

    container.innerHTML = `
      <div class="stat-row" style="grid-template-columns:repeat(3,minmax(0,1fr));">
        ${stat('🌱', 'Parcelas con bioindicadores', conBio.length, `${pct}% del total (${parcelas.length} parcelas)`)}
        ${stat('🪱', 'Indicadores distintos registrados', resumen.topBioindicadores.length, 'Términos únicos reportados por técnicos')}
        ${stat('📏', 'Profundidad promedio de suelo', `${resumen.diagnosticoFisico.profundidadProm.toFixed(1)} cm`, `${resumen.diagnosticoFisico.muestras} mediciones`)}
      </div>

      <section class="panel glass">
        <div class="panel-head"><div><h3>Bioindicadores más frecuentes</h3><p>Lombrices, hongos, hormigas, hojarasca y otros signos de actividad biológica del suelo (metodología Mafer Md).</p></div></div>
        <div class="chip-list">${resumen.topBioindicadores.length ? resumen.topBioindicadores.map((b) => `<span class="chip">${UI.escapeHtml(b.nombre)} · ${b.conteo}</span>`).join('') : '<span class="hint">Aún no hay bioindicadores capturados por el equipo técnico.</span>'}</div>
      </section>

      <section class="panel glass">
        <div class="panel-head"><div><h3>Parcelas con bioindicadores reportados</h3><p>Detalle por parcela de los signos biológicos observados en campo.</p></div></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Parcela</th><th>Departamento</th><th>Bioindicadores</th><th>Técnico</th></tr></thead>
            <tbody>
              ${conBio.length ? conBio.map((p) => `
                <tr>
                  <td><strong>${UI.escapeHtml(p.nombreParcela)}</strong><br><span class="hint">${UI.escapeHtml(p.codigo)}</span></td>
                  <td>${UI.escapeHtml(p.departamento)} / ${UI.escapeHtml(p.municipio)}</td>
                  <td>${p.bioindicadores.split(/[,;/]+/).map((s) => s.trim()).filter(Boolean).map((b) => `<span class="chip">${UI.escapeHtml(b)}</span>`).join(' ')}</td>
                  <td>${UI.escapeHtml(p.tecnicoNombre)}</td>
                </tr>
              `).join('') : `<tr><td colspan="4" class="empty-state">Todavía no hay registros de bioindicadores.</td></tr>`}
            </tbody>
          </table>
        </div>
      </section>
    `;
  }

  function stat(icon, label, value, sub) {
    return `<div class="stat-card glass"><span class="s-icon">${icon}</span><div class="s-label">${label}</div><div class="s-value">${value}</div><div class="s-sub">${sub}</div></div>`;
  }

  return { render };
})();
