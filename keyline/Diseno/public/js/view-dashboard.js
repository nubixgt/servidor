const ViewDashboard = (() => {
  let charts = {};
  let mapInstance = null;

  async function render(container) {
    container.innerHTML = `<div class="loading-spin"></div>`;
    const data = await Api.dashboardResumen();

    container.innerHTML = `
      <div class="stat-row">
        ${statCard('🗺️', 'Parcelas registradas', data.totales.parcelas, 'Total en la base actual', data.tendencias?.parcelas)}
        ${statCard('🌾', 'Área acumulada', `${data.totales.areaHa.toLocaleString('es-GT', { maximumFractionDigits: 1 })} ha`, 'Hectáreas bajo diagnóstico o intervención', data.tendencias?.areaHa, true)}
        ${statCard('📍', 'Cobertura territorial', `${data.totales.coberturaPct}%`, `${data.totales.departamentos} de ${data.totales.metaDepartamentos} departamentos`)}
        ${statCard('👨‍👩‍👧‍👦', 'Familias beneficiadas', data.totales.familiasBeneficiadas, `${data.totales.municipios} municipios alcanzados`)}
      </div>

      ${data.insights && data.insights.length ? `
      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Inteligencia del sistema</h3><p>Alertas y lecturas generadas automáticamente a partir de los datos cargados.</p></div>
        </div>
        <div class="insight-list">
          ${data.insights.map(insightHtml).join('')}
        </div>
      </section>` : ''}

      <div class="grid2">
        <section class="panel glass">
          <div class="panel-head">
            <div><h3>Semáforo de seguimiento</h3><p>Visión rápida del estado del portafolio.</p></div>
          </div>
          <div class="grid3" id="trafficGrid"></div>
          <p class="hint" style="margin-top:14px;" id="coverageHint"></p>
        </section>

        <section class="panel glass">
          <div class="panel-head">
            <div><h3>Validación técnica</h3><p>Revisión de calidad de la información cargada.</p></div>
          </div>
          <canvas id="chartValidacion" height="180"></canvas>
        </section>
      </div>

      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Mapa nacional de parcelas</h3><p>Ubicación georreferenciada de las parcelas con coordenadas registradas.</p></div>
          <span class="badge">${data.puntosMapa.length} puntos con GPS</span>
        </div>
        <div id="mapaParcelas"></div>
      </section>

      <div class="grid2">
        <section class="panel glass">
          <div class="panel-head"><div><h3>Parcelas por departamento</h3><p>Ranking según registros ingresados.</p></div></div>
          <div id="departmentBars" class="bar-chart"></div>
        </section>
        <section class="panel glass">
          <div class="panel-head"><div><h3>Estado del proceso</h3><p>Distribución por fase técnica.</p></div></div>
          <canvas id="chartEstadoProceso" height="220"></canvas>
        </section>
      </div>

      <section class="panel glass">
        <div class="panel-head">
          <div><h3>Diagnóstico físico del suelo</h3><p>Talpetate, encharcamiento, profundidad y bioindicadores.</p></div>
        </div>
        <div class="grid3" id="soilDiagnostic"></div>
      </section>

      <section class="panel glass">
        <div class="panel-head"><div><h3>Registros recientes</h3><p>Últimas parcelas cargadas por el equipo técnico.</p></div></div>
        <div class="table-wrap"><table>
          <thead><tr><th>Parcela</th><th>Código</th><th>Técnico</th><th>Departamento</th><th>Estado</th><th>Fecha</th></tr></thead>
          <tbody id="recentBody"></tbody>
        </table></div>
      </section>
    `;

    renderTraffic(data);
    renderMap(data.puntosMapa);
    renderDeptBars(data.porDepartamento);
    renderSoilDiagnostic(data.diagnosticoFisico, data.topBioindicadores, data.totales.parcelas);
    renderRecent(data.registrosRecientes);
    renderChartValidacion(data.totales);
    renderChartEstadoProceso(data.porEstadoProceso);
  }

  function statCard(icon, label, value, sub, tendencia, esArea) {
    let trendHtml = '';
    if (tendencia && tendencia.valor > 0) {
      const up = tendencia.cambioPct >= 0;
      trendHtml = `<div class="trend-badge ${up ? 'up' : 'down'}">${up ? '▲' : '▼'} ${Math.abs(tendencia.cambioPct)}% · últimos 30 días</div>`;
    }
    return `<div class="stat-card glass"><span class="s-icon">${icon}</span><div class="s-label">${label}</div><div class="s-value">${value}</div><div class="s-sub">${sub}</div>${trendHtml}</div>`;
  }

  function insightHtml(ins) {
    const icons = { alert: '⚠️', recommendation: '💡', trend: '📈' };
    const labels = { alert: 'Alerta', recommendation: 'Recomendación', trend: 'Tendencia' };
    return `<div class="insight-card ${ins.tipo}"><span class="ic-icon">${icons[ins.tipo] || '🔎'}</span><div><p class="ic-tag">${labels[ins.tipo] || ''}</p><p>${UI.escapeHtml(ins.texto)}</p></div></div>`;
  }

  function renderTraffic(data) {
    const s = data.semaforo;
    document.getElementById('trafficGrid').innerHTML = `
      <div class="traffic-card"><div class="traffic-head"><span class="light green"></span>Ejecutadas</div><div class="mini-value">${s.verde}</div><div class="hint">Parcelas implementadas.</div></div>
      <div class="traffic-card"><div class="traffic-head"><span class="light yellow"></span>En proceso</div><div class="mini-value">${s.amarillo}</div><div class="hint">Diseño y levantamiento en curso.</div></div>
      <div class="traffic-card"><div class="traffic-head"><span class="light red"></span>Pendientes</div><div class="mini-value">${s.rojo}</div><div class="hint">Requieren seguimiento.</div></div>
    `;
    document.getElementById('coverageHint').innerHTML = `El proyecto cubre <strong>${data.totales.departamentos} departamentos</strong> (${data.totales.coberturaPct}% de cobertura nacional) con <strong>${data.totales.validadas}</strong> parcelas validadas y <strong>${data.totales.pendientesValidacion}</strong> pendientes de revisión.`;
  }

  function renderMap(puntos) {
    const el = document.getElementById('mapaParcelas');
    if (mapInstance) { mapInstance.remove(); mapInstance = null; }
    const center = puntos.length ? [puntos[0].lat, puntos[0].lng] : [15.5, -90.25];
    mapInstance = L.map(el, { scrollWheelZoom: false }).setView(center, puntos.length ? 7 : 6);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; OpenStreetMap &copy; CARTO', maxZoom: 18,
    }).addTo(mapInstance);

    const colorFor = { Implementado: '#34d17c', Diseño: '#f5b93f', Levantamiento: '#3fb6e0', Pendiente: '#ef5757' };
    puntos.forEach((p) => {
      const marker = L.circleMarker([p.lat, p.lng], {
        radius: 8, color: colorFor[p.estado] || '#34d17c', fillColor: colorFor[p.estado] || '#34d17c', fillOpacity: 0.75, weight: 2,
      }).addTo(mapInstance);
      marker.bindPopup(`<strong>${UI.escapeHtml(p.nombre)}</strong><br>${UI.escapeHtml(p.codigo)}<br>${UI.escapeHtml(p.departamento)}<br>Estado: ${UI.escapeHtml(p.estado)}`);
    });
    if (!puntos.length) {
      L.popup().setLatLng(center).setContent('Aún no hay parcelas con coordenadas GPS registradas.').openOn(mapInstance);
    }
  }

  function renderDeptBars(items) {
    const el = document.getElementById('departmentBars');
    if (!items.length) { el.innerHTML = '<p class="hint">Aún no hay datos para mostrar el ranking por departamento.</p>'; return; }
    const max = items[0].cantidad || 1;
    el.innerHTML = items.map((it) => `
      <div class="bar-row">
        <div><strong>${UI.escapeHtml(it.departamento)}</strong><br><span class="hint">${it.area.toLocaleString('es-GT', { maximumFractionDigits: 1 })} ha</span></div>
        <div class="bar-track"><div class="bar-fill" style="width:${(it.cantidad / max) * 100}%"></div></div>
        <div><strong>${it.cantidad}</strong></div>
      </div>
    `).join('');
  }

  function renderSoilDiagnostic(d, topBio, total) {
    const t = total || 1;
    document.getElementById('soilDiagnostic').innerHTML = `
      <div class="traffic-card">
        <div class="traffic-head">🪨 Talpetate</div>
        <div class="mini-value">${d.conTalpetate}</div>
        <div class="hint">de ${total} parcelas (${Math.round((d.conTalpetate / t) * 100)}%). Sin talpetate: <strong>${d.sinTalpetate}</strong>.</div>
        <div class="bar-track" style="margin-top:10px;"><div class="bar-fill" style="width:${(d.conTalpetate / t) * 100}%; background: linear-gradient(90deg,#ef5757,#f5b93f);"></div></div>
      </div>
      <div class="traffic-card">
        <div class="traffic-head">💧 Encharcamiento</div>
        <div class="mini-value">${d.conEncharca}</div>
        <div class="hint">parcelas con encharcamiento (${Math.round((d.conEncharca / t) * 100)}%). Sin encharcamiento: <strong>${d.sinEncharca}</strong>.</div>
        <div class="bar-track" style="margin-top:10px;"><div class="bar-fill blue" style="width:${(d.conEncharca / t) * 100}%;"></div></div>
      </div>
      <div class="traffic-card">
        <div class="traffic-head">📏 Profundidad de suelo</div>
        <div class="mini-value">${d.profundidadProm.toFixed(1)} cm</div>
        <div class="hint">Promedio (${d.muestras} mediciones).</div>
      </div>
      <div class="traffic-card" style="grid-column: 1 / -1;">
        <div class="traffic-head">🌱 Bioindicadores de suelo</div>
        <div class="chip-list">${topBio.length ? topBio.map((b) => `<span class="chip">${UI.escapeHtml(b.nombre)} · ${b.conteo}</span>`).join('') : '<span class="hint">Aún no hay bioindicadores capturados.</span>'}</div>
      </div>
    `;
  }

  function renderRecent(items) {
    const body = document.getElementById('recentBody');
    if (!items.length) { body.innerHTML = `<tr><td colspan="6" class="empty-state">Sin registros aún.</td></tr>`; return; }
    body.innerHTML = items.map((p) => `
      <tr>
        <td><strong>${UI.escapeHtml(p.nombre)}</strong></td>
        <td>${UI.escapeHtml(p.codigo)}</td>
        <td>${UI.escapeHtml(p.tecnico)}</td>
        <td>${UI.escapeHtml(p.departamento)}</td>
        <td>${UI.estadoTagHtml(p.estado)}</td>
        <td>${UI.fmtFechaHora(p.fecha)}</td>
      </tr>
    `).join('');
  }

  function destroyChart(key) { if (charts[key]) { charts[key].destroy(); delete charts[key]; } }

  function renderChartValidacion(totales) {
    destroyChart('validacion');
    const ctx = document.getElementById('chartValidacion');
    charts.validacion = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Validadas', 'Pendientes de revisión', 'Otras'],
        datasets: [{
          data: [totales.validadas, totales.pendientesValidacion, Math.max(totales.parcelas - totales.validadas - totales.pendientesValidacion, 0)],
          backgroundColor: ['#34d17c', '#f5b93f', 'rgba(255,255,255,0.15)'],
          borderWidth: 0,
        }],
      },
      options: { plugins: { legend: { position: 'bottom', labels: { color: '#eef7f0', font: { size: 11 } } } }, cutout: '68%' },
    });
  }

  function renderChartEstadoProceso(items) {
    destroyChart('estadoProceso');
    const ctx = document.getElementById('chartEstadoProceso');
    const colors = { Levantamiento: '#3fb6e0', Diseño: '#f5b93f', Implementado: '#34d17c', Pendiente: '#ef5757' };
    charts.estadoProceso = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: items.map((i) => i.estado),
        datasets: [{ data: items.map((i) => i.cantidad), backgroundColor: items.map((i) => colors[i.estado] || '#34d17c'), borderRadius: 8 }],
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: '#eef7f0', font: { size: 11 } }, grid: { display: false } },
          y: { beginAtZero: true, ticks: { color: '#eef7f0', font: { size: 11 }, stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.08)' } },
        },
      },
    });
  }

  return { render };
})();
