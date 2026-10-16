'use strict';
/**
 * Importa grados, secciones, cursos, docentes y la carga académica desde el reporte
 * «Cursos y docentes por grado - sección» (Markdown exportado del sistema del colegio).
 *
 *   npm run import-catalog -- /ruta/grados-cursos-docentes.md
 *
 * Se puede ejecutar varias veces: no duplica registros (compara nombres sin acentos, mayúsculas ni espacios)
 * y no modifica lo que ya existe. Se omiten las filas de prueba y el grupo interno «Ciencia y Desarrollo Docentes».
 */
const fs = require('fs');
const db = require('../src/db');
const U = require('../src/util');

const file = process.argv[2];
if (!file || !fs.existsSync(file)) { console.error('Uso: npm run import-catalog -- archivo.md'); process.exit(1); }

const ligatures = s => s.replace(/ﬁ/g, 'fi').replace(/ﬀ/g, 'ff').replace(/ﬂ/g, 'fl').replace(/ﬃ/g, 'ffi');

// Corrige el texto que quedó pegado o mal escrito al extraerlo del PDF.
function cleanCourse(raw) {
  let s = ligatures(raw).trim();
  for (const [re, to] of [
    [/Ingl[eé]st[eé]cnico/gi, 'Inglés Técnico'], [/deplanos/g, 'de Planos'], [/Criminaly /g, 'Criminal y '],
    [/Ciudadanae /g, 'Ciudadana e '], [/TÉNICAS/g, 'TÉCNICAS'], [/INVESTIGACÍON/g, 'INVESTIGACIÓN'],
    [/Transferenca/g, 'Transferencia'], [/MERCADOCTENIA/g, 'MERCADOTECNIA'], [/Socieconomicos/g, 'Socioeconómicos'],
  ]) s = s.replace(re, to);
  s = s.replace(/([a-záéíóúñ])([A-ZÁÉÍÓÚÑ])/g, '$1 $2')   // «FormaciónCiudadana» → «Formación Ciudadana»
    .replace(/,(\S)/g, ', $1').replace(/(\S)\(/g, '$1 (')
    .replace(/\s+l$/, ' I')                                 // «Computación l» → «Computación I»
    .replace(/\.+$/, '');
  return U.cleanName(s);
}

function gradeLevel(name) {
  const n = U.nameKey(name);
  if (/pre-?primaria|parvulos/.test(n)) return ['Preprimaria', 1];
  if (/primaria/.test(n)) return ['Primaria', 2];
  if (/basico/.test(n)) return ['Básico', 3];
  if (/bachillerato|perito|secretariado|magisterio/.test(n)) return ['Diversificado', 4];
  return ['Otros', 5];
}
const ORDINAL = { primero: 1, segundo: 2, tercero: 3, cuarto: 4, quinto: 5, sexto: 6 };

// ---------------------------------------------------------------- lectura del reporte
const text = ligatures(fs.readFileSync(file, 'utf8'));
const blocks = text.split(/^### /m).slice(1);
const roster = [];   // { id, name } — «Apellidos y nombres»
const rows = [];     // { grade, section, course, teacher, periods }
let skipped = 0;
for (const b of blocks) {
  const [head, ...lines] = b.split('\n');
  const cells = l => l.replace(/^\s*\|/, '').replace(/\|\s*$/, '').split('|').map(x => x.trim());
  if (/^Listado de docentes/i.test(head)) {
    for (const l of lines) {
      const m = l.match(/^\|\s*(\d+)\s*\|\s*(.+?)\s*\|/);
      if (m && !/prueba/i.test(m[2])) roster.push({ id: m[1], name: U.cleanName(m[2].replace(/\(.*?\)/g, '')) });
    }
    continue;
  }
  const m = head.trim().match(/^(.*\S)\s+-\s+(\S+)$/);
  if (!m) continue;
  const grade = U.cleanName(m[1]), section = m[2].toUpperCase();
  for (const l of lines) {
    if (!l.startsWith('|') || l.includes('---') || l.includes('**')) continue;
    const c = cells(l);
    if (c.length !== 3 || !c[0] || !c[1]) continue;
    if (/docentes$/i.test(grade) || /prueba/i.test(c[0]) || /prueba/i.test(c[1])) { skipped++; continue; }
    rows.push({ grade, section, course: cleanCourse(c[0]), teacher: U.cleanName(c[1]), periods: Number.parseInt(c[2], 10) || null });
  }
}

// ---------------------------------------------------------------- docentes: listado + nombres de la carga académica
// El listado viene como «Apellidos Nombres» y la carga como «Nombres Apellidos» (a veces con palabras pegadas).
const squash = s => U.nameKey(s).replace(/ /g, '');
const people = new Map(); // personKey → { name, external_id }
const rosterBy = new Map(roster.map(r => [U.personKey(r.name), r]));
function matchRoster(name) {
  const exact = rosterBy.get(U.personKey(name));
  if (exact) return { r: exact, display: name };
  const target = squash(name);
  for (const r of roster) {
    const t = U.cleanName(r.name).split(' ');
    for (let k = 1; k < t.length; k++) {
      const rot = [...t.slice(k), ...t.slice(0, k)];
      if (squash(rot.join(' ')) === target) return { r, display: rot.join(' ') };
    }
  }
  return null;
}
const teacherKeyOf = new Map(); // nombre en la carga → personKey
for (const name of new Set(rows.map(r => r.teacher))) {
  const hit = matchRoster(name);
  const display = U.displayPersonName((hit ? hit.display : name.replace(/([a-záéíóúñ])([A-ZÁÉÍÓÚÑ])/g, '$1 $2')).replace(/\s*\(.*?\)/g, ''));
  const key = U.personKey(display);
  people.set(key, { name: display, external_id: hit ? hit.r.id : null });
  teacherKeyOf.set(name, key);
}
// Docentes sólo del listado: se pasan de «Apellido Apellido Nombres» a «Nombres Apellido Apellido».
const PARTICLES = new Set(['de', 'del', 'la', 'las', 'los', 'y']);
function givenFirst(name) {
  const t = name.split(' ');
  if (t.length < 3 || PARTICLES.has(t[0].toLowerCase()) || PARTICLES.has(t[1].toLowerCase()) || PARTICLES.has(t[2].toLowerCase())) return name;
  return [...t.slice(2), ...t.slice(0, 2)].join(' ');
}
for (const r of roster) {
  if ([...people.values()].some(p => p.external_id === r.id)) continue;
  const key = U.personKey(r.name);
  if (!people.has(key)) people.set(key, { name: U.displayPersonName(givenFirst(r.name)), external_id: r.id });
}

// ---------------------------------------------------------------- escritura
const d = db.open();
const stats = { grades: 0, sections: 0, courses: 0, teachers: 0, loads: 0 };

// Nombre de curso para mostrar: entre las variantes del mismo curso se prefiere la que tiene tildes y no está toda en mayúsculas.
const courseVariants = new Map();
for (const r of rows) {
  const k = U.nameKey(r.course);
  if (!courseVariants.has(k)) courseVariants.set(k, new Map());
  const v = courseVariants.get(k); v.set(r.course, (v.get(r.course) || 0) + 1);
}
const score = s => (s === s.toUpperCase() ? 0 : 1000) + (s.match(/[áéíóúñÁÉÍÓÚÑ]/g) || []).length * 10;
const courseName = new Map([...courseVariants].map(([k, v]) => [k, U.displayCourseName([...v].sort((a, b) => (score(b[0]) + b[1]) - (score(a[0]) + a[1]))[0][0])]));

d.transaction(() => {
  const gradeId = new Map(), sectionId = new Map(), courseId = new Map(), teacherId = new Map();
  for (const g of new Set(rows.map(r => r.grade))) {
    const [level, lv] = gradeLevel(g);
    const ord = ORDINAL[U.nameKey(g).split(' ')[0]] || 0;
    const info = d.prepare('INSERT INTO grades(name, name_key, level, sort_order) VALUES (?,?,?,?) ON CONFLICT(name_key) DO NOTHING').run(g, U.nameKey(g), level, lv * 100 + ord);
    stats.grades += info.changes;
    gradeId.set(g, d.prepare('SELECT id FROM grades WHERE name_key = ?').get(U.nameKey(g)).id);
  }
  for (const r of rows) {
    const gid = gradeId.get(r.grade), k = `${gid}|${r.section}`;
    if (sectionId.has(k)) continue;
    stats.sections += d.prepare('INSERT INTO sections(grade_id, name, name_key) VALUES (?,?,?) ON CONFLICT(grade_id, name_key) DO NOTHING').run(gid, r.section, U.nameKey(r.section)).changes;
    sectionId.set(k, d.prepare('SELECT id FROM sections WHERE grade_id = ? AND name_key = ?').get(gid, U.nameKey(r.section)).id);
  }
  for (const [k, name] of courseName) {
    stats.courses += d.prepare('INSERT INTO courses(name, name_key) VALUES (?,?) ON CONFLICT(name_key) DO NOTHING').run(name, k).changes;
    courseId.set(k, d.prepare('SELECT id FROM courses WHERE name_key = ?').get(k).id);
  }
  for (const [key, p] of people) {
    const ext = p.external_id && d.prepare('SELECT id FROM teachers WHERE external_id = ?').get(p.external_id);
    if (!ext) stats.teachers += d.prepare('INSERT INTO teachers(full_name, name_key, external_id) VALUES (?,?,?) ON CONFLICT(name_key) DO NOTHING').run(p.name, key, p.external_id).changes;
    teacherId.set(key, (ext || d.prepare('SELECT id FROM teachers WHERE name_key = ?').get(key)).id);
  }
  const ins = d.prepare('INSERT INTO teacher_loads(teacher_id, course_id, grade_id, section_id, periods) VALUES (?,?,?,?,?) ON CONFLICT(teacher_id, course_id, section_id) DO NOTHING');
  for (const r of rows) {
    const gid = gradeId.get(r.grade);
    stats.loads += ins.run(teacherId.get(teacherKeyOf.get(r.teacher)), courseId.get(U.nameKey(r.course)), gid, sectionId.get(`${gid}|${r.section}`), r.periods).changes;
  }
})();

console.log(`Filas leídas: ${rows.length} (omitidas de prueba o internas: ${skipped})`);
console.log(`Nuevos → grados: ${stats.grades}, secciones: ${stats.sections}, cursos: ${stats.courses}, docentes: ${stats.teachers}, cargas: ${stats.loads}`);
console.log(`Totales → grados: ${d.prepare('SELECT COUNT(*) n FROM grades').get().n}, cursos: ${d.prepare('SELECT COUNT(*) n FROM courses').get().n}, docentes: ${d.prepare('SELECT COUNT(*) n FROM teachers').get().n}`);
