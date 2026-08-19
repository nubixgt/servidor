/* Vista "Reportes y análisis": centro de exportación e indicadores consolidados. */
const ViewReportes = (() => {
  async function render(container) {
    container.innerHTML = `<div class="loading-spin"></div>`;
    const [resumen, { parcelas }] = await Promise.all([Api.dashboardResumen(), Api.listarParcelas()]);

    container.innerHTML = `
      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Centro de exportación</h3><p>Descarga la información consolidada del proyecto para reportes externos.</p></div>
        </div>
        <div class="toolbar">
          <div class="left">
            <button class="btn btn-primary" id="btnExportCsv">⬇️ Exportar parcelas (CSV)</button>
            <button class="btn btn-secondary" id="btnExportResumenJson">⬇️ Exportar indicadores (JSON)</button>
          </div>
        </div>
        <p class="hint">El reporte PDF por parcela con ficha técnica y fotografías está planificado como módulo futuro.</p>
      </section>

      <section class="panel glass">
        <div class="panel-head"><div><h3>Indicadores consolidados</h3><p>Resumen ejecutivo listo para copiar en informes.</p></div></div>
        <div class="grid3">
          ${indicador('Parcelas registradas', resumen.totales.parcelas)}
          ${indicador('Área acumulada', `${resumen.totales.areaHa.toLocaleString('es-GT', { maximumFractionDigits: 1 })} ha`)}
          ${indicador('Cobertura territorial', `${resumen.totales.coberturaPct}% (${resumen.totales.departamentos}/${resumen.totales.metaDepartamentos} deptos.)`)}
          ${indicador('Municipios alcanzados', resumen.totales.municipios)}
          ${indicador('Familias beneficiadas', resumen.totales.familiasBeneficiadas)}
          ${indicador('Parcelas implementadas', resumen.totales.implementadas)}
          ${indicador('Validadas / pendientes', `${resumen.totales.validadas} / ${resumen.totales.pendientesValidacion}`)}
          ${indicador('Profundidad promedio de suelo', `${resumen.diagnosticoFisico.profundidadProm.toFixed(1)} cm`)}
          ${indicador('Parcelas con talpetate', resumen.diagnosticoFisico.conTalpetate)}
        </div>
      </section>

      <section class="panel glass">
        <div class="panel-head"><div><h3>Ranking por departamento</h3><p>Base para reportes de avance territorial.</p></div></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Departamento</th><th>Parcelas</th><th>Área (ha)</th><th>Implementadas</th></tr></thead>
            <tbody>
              ${resumen.porDepartamento.length ? resumen.porDepartamento.map((d) => `
                <tr><td><strong>${UI.escapeHtml(d.departamento)}</strong></td><td>${d.cantidad}</td><td>${d.area.toLocaleString('es-GT', { maximumFractionDigits: 1 })}</td><td>${d.implementadas}</td></tr>
              `).join('') : `<tr><td colspan="4" class="empty-state">Sin datos aún.</td></tr>`}
            </tbody>
          </table>
        </div>
      </section>
    `;

    document.getElementById('btnExportCsv').addEventListener('click', () => exportCsv(parcelas));
    document.getElementById('btnExportResumenJson').addEventListener('click', () => exportJson(resumen));
  }

  function indicador(label, value) {
    return `<div class="traffic-card"><div class="traffic-head">${UI.escapeHtml(label)}</div><div class="mini-value">${value}</div></div>`;
  }

  function exportCsv(parcelas) {
    if (!parcelas.length) { UI.toast('No hay datos para exportar.', 'info'); return; }
    const cols = ['codigo', 'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'areaHa', 'estado', 'estadoValidacion', 'usoActual', 'tipoSuelo', 'pendiente', 'altitud', 'agua', 'fuenteAgua', 'riesgoErosion', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'observaciones', 'tecnicoNombre', 'fechaRegistro', 'latitud', 'longitud'];
    const rows = [cols.join(',')].concat(parcelas.map((p) => cols.map((c) => csvEscape(p[c])).join(',')));
    downloadBlob(new Blob(['﻿' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' }), `parcelas_keyline_${new Date().toISOString().slice(0, 10)}.csv`);
  }
  function exportJson(resumen) {
    downloadBlob(new Blob([JSON.stringify(resumen, null, 2)], { type: 'application/json' }), `indicadores_keyline_${new Date().toISOString().slice(0, 10)}.json`);
  }
  function csvEscape(v) { const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s; }
  function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename; a.click();
    URL.revokeObjectURL(url);
  }

  return { render };
})();
