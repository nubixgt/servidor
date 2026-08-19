const express = require('express');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { v4: uuidv4 } = require('uuid');
const db = require('../db');
const { requireAuth, requireRole } = require('../middleware/auth');
const { DEPARTAMENTOS, ESTADOS_PROCESO, ESTADOS_VALIDACION } = require('../constants');

const router = express.Router();

let sharp = null;
try { sharp = require('sharp'); } catch (e) { console.warn('sharp no disponible; se omitirán miniaturas.'); }

const UPLOAD_DIR = path.join(__dirname, '..', '..', 'uploads', 'parcelas');
if (!fs.existsSync(UPLOAD_DIR)) fs.mkdirSync(UPLOAD_DIR, { recursive: true });

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, UPLOAD_DIR),
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname || '').toLowerCase() || '.jpg';
    cb(null, `${Date.now()}-${uuidv4()}${ext}`);
  },
});
const upload = multer({
  storage,
  limits: { fileSize: 12 * 1024 * 1024, files: 8 },
  fileFilter: (req, file, cb) => {
    if (!/^image\/(jpeg|png|webp|heic|heif)$/i.test(file.mimetype)) {
      return cb(new Error('Solo se permiten imágenes (JPG, PNG, WEBP).'));
    }
    cb(null, true);
  },
});

function nextCodigo() {
  const year = new Date().getFullYear();
  const countThisYear = db.state.parcelas.filter((p) => (p.codigo || '').includes(`KL-${year}-`)).length;
  return `KL-${year}-${String(countThisYear + 1).padStart(5, '0')}`;
}

function safeNum(v) {
  if (v === '' || v === null || v === undefined) return '';
  const n = Number(v);
  return Number.isFinite(n) ? n : '';
}

function scopedParcelas(user) {
  if (user.role === 'administrador') return db.state.parcelas;
  if (user.role === 'supervisor') {
    return db.state.parcelas.filter((p) => !user.regionAsignada || p.departamento === user.regionAsignada);
  }
  return db.state.parcelas.filter((p) => p.tecnicoId === user.id);
}

router.get('/', requireAuth, (req, res) => {
  const { q, departamento, estado, estadoValidacion, talpetate, encharca, tecnicoId, desde, hasta } = req.query;
  let data = scopedParcelas(req.user);

  if (q) {
    const needle = String(q).toLowerCase();
    data = data.filter((p) => [p.nombreParcela, p.departamento, p.municipio, p.comunidad, p.propietario, p.tipoSuelo, p.bioindicadores]
      .join(' ').toLowerCase().includes(needle));
  }
  if (departamento) data = data.filter((p) => p.departamento === departamento);
  if (estado) data = data.filter((p) => p.estado === estado);
  if (estadoValidacion) data = data.filter((p) => p.estadoValidacion === estadoValidacion);
  if (talpetate) data = data.filter((p) => p.talpetate === talpetate);
  if (encharca) data = data.filter((p) => p.encharca === encharca);
  if (tecnicoId) data = data.filter((p) => p.tecnicoId === tecnicoId);
  if (desde) data = data.filter((p) => p.fechaRegistro >= desde);
  if (hasta) data = data.filter((p) => p.fechaRegistro <= hasta);

  const ordered = [...data].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
  res.json({ parcelas: ordered, total: ordered.length });
});

router.get('/:id', requireAuth, (req, res) => {
  const p = db.state.parcelas.find((x) => x.id === req.params.id);
  if (!p) return res.status(404).json({ error: 'Parcela no encontrada.' });
  if (req.user.role === 'tecnico' && p.tecnicoId !== req.user.id) return res.status(403).json({ error: 'No autorizado.' });
  if (req.user.role === 'supervisor' && req.user.regionAsignada && p.departamento !== req.user.regionAsignada) {
    return res.status(403).json({ error: 'No autorizado.' });
  }
  res.json({ parcela: p });
});

router.post('/', requireAuth, requireRole('tecnico', 'administrador', 'supervisor'), (req, res) => {
  const b = req.body || {};
  if (!b.nombreParcela || !b.departamento || !b.municipio || !b.areaHa || !b.estado) {
    return res.status(400).json({ error: 'Nombre de parcela, departamento, municipio, área y estado son obligatorios.' });
  }
  if (!DEPARTAMENTOS.includes(b.departamento)) return res.status(400).json({ error: 'Departamento inválido.' });

  const now = new Date().toISOString();
  const parcela = {
    id: uuidv4(),
    codigo: nextCodigo(),
    // Identificación
    nombreParcela: String(b.nombreParcela).trim(),
    departamento: b.departamento,
    municipio: String(b.municipio).trim(),
    comunidad: String(b.comunidad || '').trim(),
    propietario: String(b.propietario || '').trim(),
    telefono: String(b.telefono || '').trim(),
    tenenciaTierra: b.tenenciaTierra || '',
    numFamiliasBeneficiadas: safeNum(b.numFamiliasBeneficiadas),
    fechaRegistro: b.fechaRegistro || now.slice(0, 10),
    // Ubicación
    latitud: safeNum(b.latitud),
    longitud: safeNum(b.longitud),
    gpsPrecision: safeNum(b.gpsPrecision),
    altitud: safeNum(b.altitud),
    // Características generales
    areaHa: safeNum(b.areaHa) || 0,
    estado: ESTADOS_PROCESO.includes(b.estado) ? b.estado : 'Levantamiento',
    usoActual: b.usoActual || '',
    tipoSuelo: String(b.tipoSuelo || '').trim(),
    pendiente: safeNum(b.pendiente),
    agua: b.agua || '',
    fuenteAgua: b.fuenteAgua || '',
    sistemaRiego: b.sistemaRiego || '',
    riesgoErosion: b.riesgoErosion || '',
    cultivoPrincipal: String(b.cultivoPrincipal || '').trim(),
    // Diagnóstico físico del suelo
    profundidadSuelo: safeNum(b.profundidadSuelo),
    talpetate: b.talpetate || '',
    encharca: b.encharca || '',
    bioindicadores: String(b.bioindicadores || '').trim(),
    // Lluvia
    lluviaAnual: safeNum(b.lluviaAnual),
    lluviaFuente: String(b.lluviaFuente || '').trim(),
    // Intervención keyline
    intervenciones: String(b.intervenciones || '').trim(),
    especiesReforestacion: String(b.especiesReforestacion || '').trim(),
    fechaProximaVisita: b.fechaProximaVisita || '',
    consentimientoProductor: !!b.consentimientoProductor,
    observaciones: String(b.observaciones || '').trim(),
    // Flujo de trabajo / auditoría
    tecnicoId: req.user.id,
    tecnicoNombre: req.user.nombre,
    estadoValidacion: 'Pendiente de revisión',
    comentarioSupervisor: '',
    revisadoPor: '',
    fechaRevision: '',
    fotos: [],
    createdAt: now,
    updatedAt: now,
  };

  db.state.parcelas.push(parcela);
  db.save();
  db.logActivity({ tipo: 'parcela_creada', usuarioId: req.user.id, usuarioNombre: req.user.nombre, detalle: `Registró la parcela "${parcela.nombreParcela}" (${parcela.codigo})`, parcelaId: parcela.id });
  res.status(201).json({ parcela });
});

router.put('/:id', requireAuth, (req, res) => {
  const p = db.state.parcelas.find((x) => x.id === req.params.id);
  if (!p) return res.status(404).json({ error: 'Parcela no encontrada.' });
  if (req.user.role === 'tecnico' && p.tecnicoId !== req.user.id) return res.status(403).json({ error: 'No autorizado.' });

  const editable = [
    'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'tenenciaTierra',
    'numFamiliasBeneficiadas', 'fechaRegistro', 'latitud', 'longitud', 'gpsPrecision', 'altitud', 'areaHa',
    'estado', 'usoActual', 'tipoSuelo', 'pendiente', 'agua', 'fuenteAgua', 'sistemaRiego', 'riesgoErosion',
    'cultivoPrincipal', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual',
    'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'fechaProximaVisita', 'consentimientoProductor',
    'observaciones',
  ];
  const numericFields = new Set(['numFamiliasBeneficiadas', 'latitud', 'longitud', 'gpsPrecision', 'altitud', 'areaHa', 'pendiente', 'profundidadSuelo', 'lluviaAnual']);
  const b = req.body || {};
  editable.forEach((key) => {
    if (b[key] !== undefined) p[key] = numericFields.has(key) ? safeNum(b[key]) : b[key];
  });
  p.updatedAt = new Date().toISOString();
  // Si un técnico edita una parcela ya validada, vuelve a pedir revisión.
  if (req.user.role === 'tecnico' && p.estadoValidacion === 'Validado') {
    p.estadoValidacion = 'Pendiente de revisión';
  }
  db.save();
  res.json({ parcela: p });
});

// Revisión/validación (supervisor y administrador)
router.post('/:id/revision', requireAuth, requireRole('supervisor', 'administrador'), (req, res) => {
  const p = db.state.parcelas.find((x) => x.id === req.params.id);
  if (!p) return res.status(404).json({ error: 'Parcela no encontrada.' });
  const { estadoValidacion, comentario } = req.body || {};
  if (!ESTADOS_VALIDACION.includes(estadoValidacion)) return res.status(400).json({ error: 'Estado de validación inválido.' });
  p.estadoValidacion = estadoValidacion;
  p.comentarioSupervisor = comentario || '';
  p.revisadoPor = req.user.nombre;
  p.fechaRevision = new Date().toISOString();
  db.save();
  db.logActivity({ tipo: 'parcela_revisada', usuarioId: req.user.id, usuarioNombre: req.user.nombre, detalle: `${estadoValidacion} · "${p.nombreParcela}"`, parcelaId: p.id });
  res.json({ parcela: p });
});

router.delete('/:id', requireAuth, requireRole('administrador'), (req, res) => {
  const idx = db.state.parcelas.findIndex((x) => x.id === req.params.id);
  if (idx === -1) return res.status(404).json({ error: 'Parcela no encontrada.' });
  const [removed] = db.state.parcelas.splice(idx, 1);
  (removed.fotos || []).forEach((f) => {
    const filePath = path.join(UPLOAD_DIR, f.archivo);
    if (fs.existsSync(filePath)) fs.unlinkSync(filePath);
  });
  db.save();
  res.json({ ok: true });
});

// Fotos
router.post('/:id/fotos', requireAuth, upload.array('fotos', 8), async (req, res) => {
  const p = db.state.parcelas.find((x) => x.id === req.params.id);
  if (!p) return res.status(404).json({ error: 'Parcela no encontrada.' });
  if (req.user.role === 'tecnico' && p.tecnicoId !== req.user.id) return res.status(403).json({ error: 'No autorizado.' });

  const files = req.files || [];
  for (const f of files) {
    let thumb = null;
    if (sharp) {
      try {
        const thumbName = `thumb-${f.filename}`;
        await sharp(f.path).resize(480, 480, { fit: 'inside' }).toFile(path.join(UPLOAD_DIR, thumbName));
        thumb = thumbName;
      } catch (e) { /* si falla la miniatura, seguimos sin ella */ }
    }
    p.fotos.push({
      id: uuidv4(),
      archivo: f.filename,
      miniatura: thumb,
      caption: (req.body && req.body.caption) || '',
      subidoPor: req.user.nombre,
      fecha: new Date().toISOString(),
    });
  }
  p.updatedAt = new Date().toISOString();
  db.save();
  res.status(201).json({ parcela: p });
});

router.delete('/:id/fotos/:fotoId', requireAuth, (req, res) => {
  const p = db.state.parcelas.find((x) => x.id === req.params.id);
  if (!p) return res.status(404).json({ error: 'Parcela no encontrada.' });
  if (req.user.role === 'tecnico' && p.tecnicoId !== req.user.id) return res.status(403).json({ error: 'No autorizado.' });
  const idx = p.fotos.findIndex((f) => f.id === req.params.fotoId);
  if (idx === -1) return res.status(404).json({ error: 'Foto no encontrada.' });
  const [foto] = p.fotos.splice(idx, 1);
  [foto.archivo, foto.miniatura].filter(Boolean).forEach((name) => {
    const fp = path.join(UPLOAD_DIR, name);
    if (fs.existsSync(fp)) fs.unlinkSync(fp);
  });
  db.save();
  res.json({ parcela: p });
});

module.exports = router;
