'use strict';
/**
 * Lógica de planificación bimestral: carga, saneamiento, guardado transaccional,
 * validación para "Listo", completitud y proyección de datos para el PDF.
 */
const crypto = require('crypto');
const { get: db } = require('./db');
const { isDate, HttpError, uuid } = require('./util');

const EXTRA_KINDS = ['mejoramiento', 'afectivo', 'asistencia', 'examen_final'];
const EXTRA_LABELS = {
  mejoramiento: 'Actividad de mejoramiento',
  afectivo: 'Afectivo',
  asistencia: 'Asistencia',
  examen_final: 'Examen final',
};
const MAX_TEXT = 8000;
const MAX_ITEMS = 2000; // límite de seguridad por lista (no es un límite pedagógico)
const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

// ------------------------------------------------------------------ contexto
/** Datos generales de un plan (asignación, curso, grado, sección, bimestre, docente). */
function planContext(planId) {
  return db().prepare(`
    SELECT p.id, p.status, p.version, p.include_pages, p.observations, p.ready_at, p.created_at,
           p.updated_at, p.pdf_hash, p.assignment_id, p.bimester_id, p.school_year_id,
           a.user_id AS teacher_id, u.full_name AS teacher_name,
           c.id AS course_id, c.name AS course_name, g.id AS grade_id, g.name AS grade_name, g.level AS grade_level,
           s.id AS section_id, s.name AS section_name,
           b.number AS bimester_number, b.start_date AS bimester_start, b.end_date AS bimester_end,
           y.name AS school_year_name
    FROM plans p
    JOIN assignments a ON a.id = p.assignment_id
    JOIN users u ON u.id = a.user_id
    JOIN courses c ON c.id = a.course_id
    JOIN grades g ON g.id = a.grade_id
    JOIN sections s ON s.id = a.section_id
    JOIN bimesters b ON b.id = p.bimester_id
    JOIN school_years y ON y.id = p.school_year_id
    WHERE p.id = ?`).get(planId);
}

/** Verifica acceso: el docente sólo a sus planes; el administrador a todos (lectura/seguimiento). */
function assertAccess(user, planId, { write = false } = {}) {
  const ctx = planContext(planId);
  if (!ctx) throw new HttpError(404, 'La planificación no existe.');
  if (user.role === 'docente' && ctx.teacher_id !== user.id) throw new HttpError(404, 'La planificación no existe.');
  if (write && user.role !== 'docente') throw new HttpError(403, 'El contenido académico sólo puede ser modificado por el docente autor.');
  return ctx;
}

// ------------------------------------------------------------------ carga
function loadTree(planId) {
  const d = db();
  const plan = d.prepare('SELECT include_pages, observations FROM plans WHERE id = ?').get(planId);
  const competencies = d.prepare('SELECT id, text FROM competencies WHERE plan_id = ? ORDER BY position').all(planId);
  const indicators = d.prepare('SELECT id, text FROM indicators WHERE plan_id = ? ORDER BY position').all(planId);
  const blocks = d.prepare('SELECT id, label, start_date, end_date, closed FROM blocks WHERE plan_id = ? ORDER BY position').all(planId);
  const evalStmt = d.prepare('SELECT id, description, instructions, scoring_mode, total_points, due_date FROM evaluations WHERE block_id = ?');
  const critStmt = d.prepare('SELECT id, text, points FROM evaluation_criteria WHERE evaluation_id = ? ORDER BY position');
  const weekStmt = d.prepare('SELECT id, number, start_date, end_date FROM weeks WHERE block_id = ? ORDER BY number');
  const contStmt = d.prepare('SELECT id, topic, pages, periods FROM contents WHERE week_id = ? ORDER BY position');
  const actStmt = d.prepare('SELECT id, text FROM learning_activities WHERE content_id = ? ORDER BY position');
  for (const b of blocks) {
    b.closed = !!b.closed;
    let ev = evalStmt.get(b.id);
    if (!ev) ev = { id: uuid(), description: '', instructions: '', scoring_mode: 'criterios', total_points: null, due_date: null };
    ev.criteria = ev.id ? critStmt.all(ev.id) : [];
    b.evaluation = ev;
    b.weeks = weekStmt.all(b.id);
    for (const w of b.weeks) {
      w.contents = contStmt.all(w.id);
      for (const c of w.contents) c.activities = actStmt.all(c.id);
    }
  }
  const extras = {};
  const exStmt = d.prepare('SELECT * FROM extra_sections WHERE plan_id = ? AND kind = ?');
  const exCrit = d.prepare('SELECT id, text, points FROM extra_criteria WHERE extra_id = ? ORDER BY position');
  for (const k of EXTRA_KINDS) {
    const r = exStmt.get(planId, k);
    extras[k] = r ? {
      id: r.id, applies: r.applies === null ? null : !!r.applies, detail: r.detail, activity: r.activity,
      scoring_mode: r.scoring_mode, total_points: r.total_points, aspects_note: r.aspects_note,
      due_date: r.due_date, due_text: r.due_text, observations: r.observations, criteria: exCrit.all(r.id),
      strategies: r.strategies || '', students: r.students || '',
    } : emptyExtra();
  }
  // Acciones adicionales del plan de mejoramiento (la primera acción es el apartado «Actividad de mejoramiento»)
  const improvement_actions = d.prepare('SELECT id, description, strategies, students, action_date FROM improvement_actions WHERE plan_id = ? ORDER BY position').all(planId);
  return {
    include_pages: !!plan.include_pages, observations: plan.observations,
    competencies, indicators, blocks, extras, improvement_actions,
  };
}

function emptyExtra() {
  return { id: uuid(), applies: null, detail: '', activity: '', scoring_mode: 'total', total_points: null,
    aspects_note: '', due_date: null, due_text: '', observations: '', criteria: [], strategies: '', students: '' };
}

// ------------------------------------------------------------------ saneamiento
function txt(v, field) {
  if (v === null || v === undefined) return '';
  if (typeof v !== 'string' && typeof v !== 'number') throw new HttpError(400, `Valor no válido en ${field}.`);
  const s = String(v).normalize('NFC').replace(/\r\n/g, '\n');
  if (s.length > MAX_TEXT) throw new HttpError(400, `El texto de ${field} supera ${MAX_TEXT} caracteres.`);
  return s;
}
function date(v, field) {
  if (v === null || v === undefined || v === '') return null;
  if (!isDate(v)) throw new HttpError(400, `Fecha no válida en ${field}.`);
  return v;
}
function num(v, field, { integer = false } = {}) {
  if (v === null || v === undefined || v === '') return null;
  const n = Number(v);
  if (!isFinite(n) || n < 0 || n > 100000) throw new HttpError(400, `Número no válido en ${field}.`);
  if (integer && !Number.isInteger(n)) throw new HttpError(400, `${field} debe ser un número entero.`);
  return Math.round(n * 100) / 100;
}
function id(v, seen) {
  const s = typeof v === 'string' && UUID_RE.test(v) ? v.toLowerCase() : uuid();
  if (seen.has(s)) throw new HttpError(400, 'Identificador repetido en la planificación.');
  seen.add(s);
  return s;
}
function list(v, field) {
  if (v === undefined || v === null) return [];
  if (!Array.isArray(v)) throw new HttpError(400, `Lista no válida en ${field}.`);
  if (v.length > MAX_ITEMS) throw new HttpError(400, `La lista ${field} es demasiado extensa.`);
  return v;
}

/** Convierte la entrada del cliente en un árbol limpio y tipado. */
function sanitize(input) {
  if (!input || typeof input !== 'object') throw new HttpError(400, 'Datos de planificación no válidos.');
  const seen = new Set();
  const out = {
    include_pages: !!input.include_pages,
    observations: txt(input.observations, 'observaciones'),
    competencies: list(input.competencies, 'competencias').map(c => ({ id: id(c && c.id, seen), text: txt(c && c.text, 'competencia') })),
    indicators: list(input.indicators, 'indicadores').map(c => ({ id: id(c && c.id, seen), text: txt(c && c.text, 'indicador') })),
    blocks: list(input.blocks, 'bloques').map((b, bi) => {
      b = b || {};
      const ev = b.evaluation || {};
      const mode = ev.scoring_mode === 'total' ? 'total' : 'criterios';
      return {
        id: id(b.id, seen),
        label: txt(b.label, `bloque ${bi + 1}`).trim(),
        start_date: date(b.start_date, 'fecha de inicio del bloque'),
        end_date: date(b.end_date, 'fecha de finalización del bloque'),
        closed: !!b.closed,
        evaluation: {
          id: id(ev.id, seen),
          description: txt(ev.description, 'evaluación'),
          instructions: txt(ev.instructions, 'indicaciones'),
          scoring_mode: mode,
          total_points: mode === 'total' ? num(ev.total_points, 'punteo total') : null,
          due_date: date(ev.due_date, 'fecha de entrega'),
          criteria: list(ev.criteria, 'criterios').map(c => ({
            id: id(c && c.id, seen), text: txt(c && c.text, 'criterio'),
            points: mode === 'criterios' ? num(c && c.points, 'punteo del criterio') : null,
          })),
        },
        weeks: list(b.weeks, 'semanas').map(w => ({
          id: id(w && w.id, seen),
          start_date: date(w && w.start_date, 'fecha de inicio de semana'),
          end_date: date(w && w.end_date, 'fecha de finalización de semana'),
          contents: list(w && w.contents, 'contenidos').map(c => ({
            id: id(c && c.id, seen),
            topic: txt(c && c.topic, 'contenido'),
            pages: txt(c && c.pages, 'páginas').trim().slice(0, 200),
            periods: num(c && c.periods, 'períodos', { integer: true }),
            activities: list(c && c.activities, 'actividades').map(a => ({ id: id(a && a.id, seen), text: txt(a && a.text, 'actividad de aprendizaje') })),
          })),
        })),
      };
    }),
    extras: {},
    improvement_actions: list(input.improvement_actions, 'plan de mejoramiento').map(a => ({
      id: id(a && a.id, seen),
      description: txt(a && a.description, 'plan de mejoramiento'),
      strategies: txt(a && a.strategies, 'acciones o estrategias'),
      students: txt(a && a.students, 'estudiantes atendidos'),
      action_date: date(a && a.action_date, 'fecha del plan de mejoramiento'),
    })),
  };
  const ex = input.extras || {};
  for (const k of EXTRA_KINDS) {
    const e = ex[k] || {};
    const mode = e.scoring_mode === 'criterios' ? 'criterios' : 'total';
    out.extras[k] = {
      id: id(e.id, seen),
      applies: e.applies === true ? true : e.applies === false ? false : null,
      detail: txt(e.detail, EXTRA_LABELS[k]),
      activity: txt(e.activity, EXTRA_LABELS[k]),
      scoring_mode: mode,
      total_points: mode === 'total' ? num(e.total_points, `punteo de ${EXTRA_LABELS[k]}`) : null,
      aspects_note: txt(e.aspects_note, EXTRA_LABELS[k]),
      due_date: date(e.due_date, `fecha de ${EXTRA_LABELS[k]}`),
      due_text: txt(e.due_text, EXTRA_LABELS[k]).trim().slice(0, 200),
      observations: txt(e.observations, EXTRA_LABELS[k]),
      strategies: txt(e.strategies, 'acciones o estrategias'),
      students: txt(e.students, 'estudiantes atendidos'),
      criteria: list(e.criteria, 'criterios').map(c => ({
        id: id(c && c.id, seen), text: txt(c && c.text, 'criterio'),
        points: mode === 'criterios' ? num(c && c.points, 'punteo') : null,
      })),
    };
  }
  return out;
}

// ------------------------------------------------------------------ guardado
/** Reemplaza el contenido del plan dentro de una transacción con control de versión. */
function saveTree(planId, tree, expectedVersion, user, { allowReadyEdit = false } = {}) {
  const d = db();
  let result;
  d.transaction(() => {
    const p = d.prepare('SELECT version, status, pdf_hash FROM plans WHERE id = ?').get(planId);
    if (!p) throw new HttpError(404, 'La planificación no existe.');
    if (p.version !== expectedVersion) {
      throw new HttpError(409, 'Esta planificación fue modificada en otra pestaña o sesión. Recargue para ver la versión más reciente antes de continuar.', { code: 'VERSION_CONFLICT', currentVersion: p.version });
    }
    if (p.status === 'listo' && !allowReadyEdit) {
      throw new HttpError(409, 'La planificación está marcada como lista. Use “Editar planificación” para modificarla.', { code: 'PLAN_READY' });
    }
    // Borrar y reinsertar hijos (las claves foráneas eliminan en cascada)
    d.prepare('DELETE FROM competencies WHERE plan_id = ?').run(planId);
    d.prepare('DELETE FROM indicators WHERE plan_id = ?').run(planId);
    d.prepare('DELETE FROM blocks WHERE plan_id = ?').run(planId);
    d.prepare('DELETE FROM extra_sections WHERE plan_id = ?').run(planId);

    const insC = d.prepare('INSERT INTO competencies(id, plan_id, position, text) VALUES (?,?,?,?)');
    tree.competencies.forEach((c, i) => insC.run(c.id, planId, i, c.text));
    const insI = d.prepare('INSERT INTO indicators(id, plan_id, position, text) VALUES (?,?,?,?)');
    tree.indicators.forEach((c, i) => insI.run(c.id, planId, i, c.text));

    const insB = d.prepare('INSERT INTO blocks(id, plan_id, position, label, start_date, end_date, closed) VALUES (?,?,?,?,?,?,?)');
    const insE = d.prepare('INSERT INTO evaluations(id, block_id, description, instructions, scoring_mode, total_points, due_date) VALUES (?,?,?,?,?,?,?)');
    const insEC = d.prepare('INSERT INTO evaluation_criteria(id, evaluation_id, position, text, points) VALUES (?,?,?,?,?)');
    const insW = d.prepare('INSERT INTO weeks(id, plan_id, block_id, number, start_date, end_date) VALUES (?,?,?,?,?,?)');
    const insCt = d.prepare('INSERT INTO contents(id, week_id, position, topic, pages, periods) VALUES (?,?,?,?,?,?)');
    const insA = d.prepare('INSERT INTO learning_activities(id, content_id, position, text) VALUES (?,?,?,?)');
    let weekNo = 0;
    tree.blocks.forEach((b, bi) => {
      insB.run(b.id, planId, bi, b.label, b.start_date, b.end_date, b.closed ? 1 : 0);
      const e = b.evaluation;
      insE.run(e.id, b.id, e.description, e.instructions, e.scoring_mode, e.total_points, e.due_date);
      e.criteria.forEach((c, i) => insEC.run(c.id, e.id, i, c.text, c.points));
      b.weeks.forEach(w => {
        weekNo += 1;
        insW.run(w.id, planId, b.id, weekNo, w.start_date, w.end_date);
        w.contents.forEach((c, ci) => {
          insCt.run(c.id, w.id, ci, c.topic, c.pages, c.periods);
          c.activities.forEach((a, ai) => insA.run(a.id, c.id, ai, a.text));
        });
      });
    });
    const insX = d.prepare(`INSERT INTO extra_sections(id, plan_id, kind, applies, detail, activity, scoring_mode, total_points,
      aspects_note, due_date, due_text, observations, strategies, students) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`);
    const insXC = d.prepare('INSERT INTO extra_criteria(id, extra_id, position, text, points) VALUES (?,?,?,?,?)');
    for (const k of EXTRA_KINDS) {
      const x = tree.extras[k];
      insX.run(x.id, planId, k, x.applies === null ? null : (x.applies ? 1 : 0), x.detail, x.activity, x.scoring_mode,
        x.total_points, x.aspects_note, x.due_date, x.due_text, x.observations, x.strategies, x.students);
      x.criteria.forEach((c, i) => insXC.run(c.id, x.id, i, c.text, c.points));
    }
    d.prepare('DELETE FROM improvement_actions WHERE plan_id = ?').run(planId);
    const insIA = d.prepare('INSERT INTO improvement_actions(id, plan_id, position, description, strategies, students, action_date) VALUES (?,?,?,?,?,?,?)');
    tree.improvement_actions.forEach((a, i) => insIA.run(a.id, planId, i, a.description, a.strategies, a.students, a.action_date));

    // Si el plan estaba listo y cambió información usada en el PDF, vuelve a borrador.
    const newHash = pdfHash(tree);
    let status = p.status, revertido = false;
    if (p.status === 'listo' && newHash !== p.pdf_hash) { status = 'borrador'; revertido = true; }
    const newVersion = p.version + 1;
    d.prepare(`UPDATE plans SET include_pages = ?, observations = ?, version = ?, status = ?,
        ready_at = CASE WHEN ? = 'borrador' THEN NULL ELSE ready_at END,
        updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now'), updated_by = ? WHERE id = ?`)
      .run(tree.include_pages ? 1 : 0, tree.observations, newVersion, status, status, user.id, planId);
    audit(user, 'guardar', 'plan', planId, planId, { version: newVersion });
    if (revertido) audit(user, 'cambio_estado', 'plan', planId, planId, { de: 'listo', a: 'borrador', motivo: 'Cambió información utilizada en la hoja de actividades' });
    const row = d.prepare('SELECT version, status, updated_at, ready_at FROM plans WHERE id = ?').get(planId);
    result = { ...row, reverted_to_draft: revertido };
  })();
  return result;
}

function audit(user, action, entity, entityId, planId, details) {
  db().prepare('INSERT INTO audit_log(user_id, user_role, action, entity, entity_id, plan_id, details) VALUES (?,?,?,?,?,?,?)')
    .run(user ? user.id : null, user ? user.role : null, action, entity, entityId == null ? null : String(entityId), planId || null,
      details ? JSON.stringify(details) : null);
}

// ------------------------------------------------------------------ cálculos
function evaluationTotal(ev) {
  if (ev.scoring_mode === 'total') return ev.total_points;
  const pts = ev.criteria.map(c => c.points).filter(p => p !== null && p !== undefined);
  if (!pts.length) return null;
  return Math.round(pts.reduce((a, b) => a + b, 0) * 100) / 100;
}

function periodTotals(tree) {
  let plan = 0;
  const blocks = tree.blocks.map(b => {
    let bt = 0;
    const weeks = b.weeks.map(w => {
      const wt = w.contents.reduce((a, c) => a + (c.periods || 0), 0);
      bt += wt;
      return wt;
    });
    plan += bt;
    return { block: bt, weeks };
  });
  return { plan, blocks };
}

// ------------------------------------------------------------------ validación
/**
 * Devuelve { issues, completeness } donde issues = [{level, message, target}]
 * target permite a la interfaz llevar al docente al campo exacto.
 */
function validate(tree, ctx) {
  const issues = [];
  let checks = 0, passed = 0;
  const req = (ok, message, target) => { checks++; if (ok) passed++; else issues.push({ level: 'error', message, target }); return ok; };
  const err = (message, target) => issues.push({ level: 'error', message, target });
  const warn = (message, target) => issues.push({ level: 'warning', message, target });
  const filled = s => typeof s === 'string' && s.trim().length > 0;

  const bStart = ctx.bimester_start, bEnd = ctx.bimester_end;
  if (!bStart || !bEnd) err('El administrador aún no ha configurado las fechas de este bimestre. No es posible validar las fechas del plan.', { section: 'generales' });

  // Competencias e indicadores
  req(tree.competencies.some(c => filled(c.text)), 'Registre al menos una competencia.', { section: 'competencias', field: 'competencias' });
  tree.competencies.forEach((c, i) => { if (!filled(c.text)) err(`La competencia ${i + 1} está vacía. Complétela o elimínela.`, { section: 'competencias', id: c.id }); });
  req(tree.indicators.some(c => filled(c.text)), 'Registre al menos un indicador de logro.', { section: 'competencias', field: 'indicadores' });
  tree.indicators.forEach((c, i) => { if (!filled(c.text)) err(`El indicador de logro ${i + 1} está vacío. Complételo o elimínelo.`, { section: 'competencias', id: c.id }); });

  // Bloques
  req(tree.blocks.length > 0, 'Agregue al menos un bloque mensual.', { section: 'bloques' });
  const allWeeks = [];
  let weekNo = 0;
  tree.blocks.forEach((b, bi) => {
    const name = filled(b.label) ? `Bloque “${b.label}”` : `Bloque ${bi + 1}`;
    const tB = { section: 'bloques', blockId: b.id };
    req(filled(b.label), `${name}: escriba el nombre o etiqueta del mes.`, { ...tB, field: 'label' });
    const hasDates = req(!!b.start_date && !!b.end_date, `${name}: indique fecha de inicio y de finalización.`, { ...tB, field: 'dates' });
    if (hasDates) {
      if (b.start_date > b.end_date) err(`${name}: la fecha de inicio es posterior a la de finalización.`, { ...tB, field: 'dates' });
      if (bStart && bEnd && (b.start_date < bStart || b.end_date > bEnd)) err(`${name}: sus fechas deben estar dentro del bimestre (${fmt(bStart)} al ${fmt(bEnd)}).`, { ...tB, field: 'dates' });
      const prev = tree.blocks[bi - 1];
      if (prev && prev.end_date && prev.start_date && b.start_date <= prev.end_date) err(`${name}: se superpone o está antes del bloque anterior. Revise el orden y las fechas.`, { ...tB, field: 'dates' });
    }
    // Evaluación única
    const ev = b.evaluation;
    const tE = { ...tB, part: 'evaluacion' };
    req(filled(ev.description), `${name}: describa la actividad de evaluación del bloque.`, { ...tE, field: 'description' });
    const hasDue = req(!!ev.due_date, `${name}: indique la fecha de entrega de la evaluación.`, { ...tE, field: 'due_date' });
    if (hasDue && bStart && bEnd && (ev.due_date < bStart || ev.due_date > bEnd)) warn(`${name}: la fecha de entrega (${fmt(ev.due_date)}) está fuera del bimestre.`, { ...tE, field: 'due_date' });
    req(ev.criteria.some(c => filled(c.text)), `${name}: registre al menos un aspecto o criterio a evaluar.`, { ...tE, field: 'criteria' });
    ev.criteria.forEach((c, ci) => { if (!filled(c.text)) err(`${name}: el criterio ${ci + 1} está vacío. Complételo o elimínelo.`, { ...tE, field: 'criteria' }); });
    if (ev.scoring_mode === 'criterios') {
      req(ev.criteria.length > 0 && ev.criteria.every(c => c.points !== null && c.points !== undefined),
        `${name}: indique el punteo de cada criterio, o cambie a “Solo punteo total”.`, { ...tE, field: 'criteria' });
    } else {
      req(ev.total_points !== null && ev.total_points !== undefined, `${name}: indique el punteo total de la evaluación.`, { ...tE, field: 'total_points' });
    }
    // Semanas
    req(b.weeks.length > 0, `${name}: agregue al menos una semana.`, { ...tB, part: 'semanas' });
    b.weeks.forEach(w => {
      weekNo += 1;
      const wn = `${name}, semana ${weekNo}`;
      const tW = { ...tB, weekId: w.id };
      const wDates = req(!!w.start_date && !!w.end_date, `${wn}: indique fechas de inicio y finalización.`, { ...tW, field: 'dates' });
      if (wDates) {
        if (w.start_date > w.end_date) err(`${wn}: la fecha de inicio es posterior a la de finalización.`, { ...tW, field: 'dates' });
        if (b.start_date && b.end_date && (w.start_date < b.start_date || w.end_date > b.end_date)) err(`${wn}: sus fechas deben estar dentro del bloque.`, { ...tW, field: 'dates' });
        if (bStart && bEnd && (w.start_date < bStart || w.end_date > bEnd)) err(`${wn}: sus fechas deben estar dentro del bimestre.`, { ...tW, field: 'dates' });
        allWeeks.push({ no: weekNo, start: w.start_date, end: w.end_date, target: tW });
      }
      req(w.contents.length > 0, `${wn}: agregue al menos un contenido.`, { ...tW, part: 'contenidos' });
      w.contents.forEach((c, ci) => {
        const cn = `${wn}, contenido ${ci + 1}`;
        const tC = { ...tW, contentId: c.id };
        req(filled(c.topic), `${cn}: escriba el tema o descripción.`, { ...tC, field: 'topic' });
        req(c.periods !== null && c.periods !== undefined && c.periods >= 1, `${cn}: indique la cantidad de períodos (1 o más).`, { ...tC, field: 'periods' });
        req(c.activities.some(a => filled(a.text)), `${cn}: agregue al menos una actividad de aprendizaje.`, { ...tC, field: 'activities' });
        c.activities.forEach((a, ai) => { if (!filled(a.text)) err(`${cn}: la actividad de aprendizaje ${ai + 1} está vacía.`, { ...tC, field: 'activities' }); });
      });
    });
  });
  // Superposición y orden de semanas en todo el plan
  for (let i = 0; i < allWeeks.length; i++) {
    for (let j = i + 1; j < allWeeks.length; j++) {
      const a = allWeeks[i], b = allWeeks[j];
      if (a.start <= b.end && b.start <= a.end) err(`Las semanas ${a.no} y ${b.no} se superponen en sus fechas.`, b.target);
      else if (b.start < a.start) err(`La semana ${b.no} tiene fechas anteriores a la semana ${a.no}. Reordénela o corrija las fechas.`, b.target);
    }
  }

  // Apartados adicionales
  for (const k of EXTRA_KINDS) {
    const x = tree.extras[k];
    const n = EXTRA_LABELS[k];
    const tX = { section: 'apartados', kind: k };
    if (!req(x.applies !== null, `${n}: indique si aplica o márquelo como “No aplica”.`, { ...tX, field: 'applies' })) continue;
    if (!x.applies) continue;
    if (k === 'mejoramiento') req(filled(x.activity), `${n}: describa la actividad.`, { ...tX, field: 'activity' });
    if (x.scoring_mode === 'criterios') {
      req(x.criteria.length > 0 && x.criteria.every(c => filled(c.text) && c.points !== null && c.points !== undefined),
        `${n}: complete el texto y punteo de cada criterio.`, { ...tX, field: 'criteria' });
    } else {
      req(x.total_points !== null && x.total_points !== undefined, `${n}: indique el punteo.`, { ...tX, field: 'total_points' });
    }
    if (k !== 'examen_final') req(!!x.due_date || filled(x.due_text), `${n}: indique la fecha de entrega o aplicación (o el período en texto).`, { ...tX, field: 'due' });
    else if (!x.due_date && !filled(x.due_text)) warn(`${n}: no tiene fecha de aplicación; la celda de fecha quedará vacía.`, { ...tX, field: 'due' });
  }

  const completeness = checks ? Math.round((passed / checks) * 100) : 0;
  return { issues, completeness, errors: issues.filter(i => i.level === 'error').length };
}

function fmt(d) { if (!d) return ''; const [y, m, dd] = d.split('-'); return `${dd}-${m}-${y}`; }

// ------------------------------------------------------------------ proyección PDF
/** Sólo la información que aparece en la hoja de actividades. */
function pdfProjection(tree) {
  return {
    include_pages: tree.include_pages,
    observations: tree.observations.trim(),
    blocks: tree.blocks.map(b => ({
      id: b.id,
      label: b.label,
      start_date: b.start_date, end_date: b.end_date,
      contents: b.weeks.flatMap(w => w.contents.map(c => ({ topic: c.topic.trim(), pages: c.pages }))),
      evaluation: {
        id: b.evaluation.id,
        description: b.evaluation.description.trim(),
        instructions: b.evaluation.instructions.trim(),
        scoring_mode: b.evaluation.scoring_mode,
        criteria: b.evaluation.criteria.map(c => ({ text: c.text.trim(), points: c.points })),
        total: evaluationTotal(b.evaluation),
        due_date: b.evaluation.due_date,
      },
    })),
    extras: EXTRA_KINDS.map(k => {
      const x = tree.extras[k];
      return {
        kind: k, applies: x.applies, detail: x.detail.trim(), activity: x.activity.trim(),
        scoring_mode: x.scoring_mode,
        criteria: x.criteria.map(c => ({ text: c.text.trim(), points: c.points })),
        total: x.scoring_mode === 'total' ? x.total_points
          : (x.criteria.length ? Math.round(x.criteria.reduce((a, c) => a + (c.points || 0), 0) * 100) / 100 : null),
        aspects_note: x.aspects_note.trim(), due_date: x.due_date, due_text: x.due_text,
      };
    }),
  };
}

function pdfHash(tree) {
  return crypto.createHash('sha256').update(JSON.stringify(pdfProjection(tree))).digest('hex');
}

module.exports = {
  EXTRA_KINDS, EXTRA_LABELS, planContext, assertAccess, loadTree, sanitize, saveTree, validate,
  periodTotals, evaluationTotal, pdfProjection, pdfHash, audit, fmt,
};
