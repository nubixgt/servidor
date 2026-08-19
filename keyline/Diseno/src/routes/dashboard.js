const express = require('express');
const db = require('../db');
const { requireAuth, requireRole } = require('../middleware/auth');
const { DEPARTAMENTOS } = require('../constants');

const router = express.Router();

function safeNum(v) { const n = parseFloat(v); return Number.isFinite(n) ? n : 0; }

function scopedParcelas(user) {
  if (user.role === 'administrador') return db.state.parcelas;
  if (user.role === 'supervisor') {
    return db.state.parcelas.filter((p) => !user.regionAsignada || p.departamento === user.regionAsignada);
  }
  return db.state.parcelas.filter((p) => p.tecnicoId === user.id);
}

router.get('/resumen', requireAuth, (req, res) => {
  const data = scopedParcelas(req.user);
  const totalArea = data.reduce((acc, p) => acc + safeNum(p.areaHa), 0);
  const totalDeptos = new Set(data.map((p) => p.departamento).filter(Boolean)).size;
  const municipios = new Set(data.map((p) => `${p.departamento}|${p.municipio}`)).size;
  const implementadas = data.filter((p) => p.estado === 'Implementado').length;
  const pendientesValidacion = data.filter((p) => p.estadoValidacion === 'Pendiente de revisión').length;
  const validadas = data.filter((p) => p.estadoValidacion === 'Validado').length;
  const familias = data.reduce((acc, p) => acc + safeNum(p.numFamiliasBeneficiadas), 0);

  const semaforo = {
    verde: data.filter((p) => p.estado === 'Implementado').length,
    amarillo: data.filter((p) => p.estado === 'Diseño' || p.estado === 'Levantamiento').length,
    rojo: data.filter((p) => p.estado === 'Pendiente').length,
  };

  const porDepartamento = DEPARTAMENTOS.map((dep) => {
    const items = data.filter((p) => p.departamento === dep);
    return {
      departamento: dep,
      cantidad: items.length,
      area: items.reduce((acc, p) => acc + safeNum(p.areaHa), 0),
      implementadas: items.filter((p) => p.estado === 'Implementado').length,
    };
  }).filter((d) => d.cantidad > 0).sort((a, b) => b.cantidad - a.cantidad || b.area - a.area);

  const conTalpetate = data.filter((p) => p.talpetate === 'Sí').length;
  const sinTalpetate = data.filter((p) => p.talpetate === 'No').length;
  const conEncharca = data.filter((p) => p.encharca === 'Sí').length;
  const sinEncharca = data.filter((p) => p.encharca === 'No').length;
  const profVals = data.map((p) => safeNum(p.profundidadSuelo)).filter((v) => v > 0);
  const profundidadProm = profVals.length ? profVals.reduce((a, b) => a + b, 0) / profVals.length : 0;

  const bioList = {};
  data.forEach((p) => (p.bioindicadores || '').split(/[,;/]+/).map((s) => s.trim().toLowerCase()).filter(Boolean)
    .forEach((k) => { bioList[k] = (bioList[k] || 0) + 1; }));
  const topBioindicadores = Object.entries(bioList).sort((a, b) => b[1] - a[1]).slice(0, 10).map(([k, v]) => ({ nombre: k, conteo: v }));

  const porEstadoProceso = ['Levantamiento', 'Diseño', 'Implementado', 'Pendiente'].map((estado) => ({
    estado, cantidad: data.filter((p) => p.estado === estado).length,
  }));

  const porUso = {};
  data.forEach((p) => { if (p.usoActual) porUso[p.usoActual] = (porUso[p.usoActual] || 0) + 1; });

  const puntosMapa = data.filter((p) => p.latitud !== '' && p.longitud !== '' && p.latitud !== undefined && p.longitud !== undefined)
    .map((p) => ({
      id: p.id, nombre: p.nombreParcela, lat: Number(p.latitud), lng: Number(p.longitud),
      estado: p.estado, departamento: p.departamento, codigo: p.codigo,
    }));

  const registrosRecientes = [...data].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)).slice(0, 8)
    .map((p) => ({ id: p.id, nombre: p.nombreParcela, codigo: p.codigo, tecnico: p.tecnicoNombre, fecha: p.createdAt, departamento: p.departamento, estado: p.estado }));

  // ===== Tendencias (últimos 30 días vs 30 días previos) =====
  const now = Date.now();
  const DAY = 24 * 60 * 60 * 1000;
  const dentroDe = (p, desdeMs, hastaMs) => {
    const t = new Date(p.createdAt).getTime();
    return t >= desdeMs && t < hastaMs;
  };
  const ultimos30 = data.filter((p) => dentroDe(p, now - 30 * DAY, now));
  const previos30 = data.filter((p) => dentroDe(p, now - 60 * DAY, now - 30 * DAY));
  const pctCambio = (actual, previo) => {
    if (!previo) return actual > 0 ? 100 : 0;
    return Math.round(((actual - previo) / previo) * 100);
  };
  const tendencias = {
    parcelas: { valor: ultimos30.length, cambioPct: pctCambio(ultimos30.length, previos30.length) },
    areaHa: {
      valor: ultimos30.reduce((a, p) => a + safeNum(p.areaHa), 0),
      cambioPct: pctCambio(ultimos30.reduce((a, p) => a + safeNum(p.areaHa), 0), previos30.reduce((a, p) => a + safeNum(p.areaHa), 0)),
    },
  };

  // ===== Inteligencia del sistema: alertas / recomendaciones / tendencias generadas de datos reales =====
  const insights = [];
  const totalParaPct = data.length || 1;
  const pctEncharca = Math.round((conEncharca / totalParaPct) * 100);
  if (conEncharca > 0 && pctEncharca >= 15) {
    insights.push({ tipo: 'alert', texto: `${conEncharca} parcela(s) (${pctEncharca}%) reportan encharcamiento y podrían requerir obras de drenaje o rediseño de canales keyline.` });
  } else if (conEncharca > 0) {
    insights.push({ tipo: 'alert', texto: `${conEncharca} parcela(s) presentan encharcamiento reportado; conviene dar seguimiento técnico.` });
  }
  if (porDepartamento.length) {
    const top = porDepartamento[0];
    insights.push({ tipo: 'recommendation', texto: `${top.departamento} concentra el mayor número de parcelas registradas (${top.cantidad}, ${top.area.toLocaleString('es-GT', { maximumFractionDigits: 1 })} ha acumuladas).` });
  }
  if (tendencias.parcelas.valor > 0) {
    const signo = tendencias.parcelas.cambioPct >= 0 ? 'aumentó' : 'disminuyó';
    insights.push({ tipo: 'trend', texto: `El registro de parcelas ${signo} ${Math.abs(tendencias.parcelas.cambioPct)}% en los últimos 30 días (${tendencias.parcelas.valor} parcelas nuevas) frente al periodo anterior.` });
  }
  if (pendientesValidacion > 0) {
    insights.push({ tipo: 'alert', texto: `${pendientesValidacion} parcela(s) están pendientes de revisión técnica por un supervisor.` });
  }
  if (conTalpetate > 0) {
    insights.push({ tipo: 'recommendation', texto: `${conTalpetate} parcela(s) presentan talpetate; priorizar diagnóstico de profundidad antes de diseñar obras de infiltración.` });
  }

  res.json({
    totales: {
      parcelas: data.length,
      areaHa: totalArea,
      departamentos: totalDeptos,
      metaDepartamentos: DEPARTAMENTOS.length,
      coberturaPct: Math.round((totalDeptos / DEPARTAMENTOS.length) * 100),
      municipios,
      implementadas,
      pendientesValidacion,
      validadas,
      familiasBeneficiadas: familias,
    },
    tendencias,
    insights: insights.slice(0, 5),
    semaforo,
    porDepartamento,
    porEstadoProceso,
    porUso,
    diagnosticoFisico: { conTalpetate, sinTalpetate, conEncharca, sinEncharca, profundidadProm, muestras: profVals.length },
    topBioindicadores,
    puntosMapa,
    registrosRecientes,
  });
});

router.get('/actividad', requireAuth, requireRole('administrador', 'supervisor'), (req, res) => {
  res.json({ actividad: db.state.activity.slice(0, 60) });
});

module.exports = router;
