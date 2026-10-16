'use strict';
/**
 * Pruebas de aceptación con datos de prueba en una base de datos temporal (separada de la real).
 * Uso: npm test        (requiere pdftotext — poppler-utils — para las comprobaciones de texto del PDF)
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const assert = require('assert');
const { execFileSync } = require('child_process');

process.env.NODE_ENV = 'test';
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'cyd-test-'));
process.env.DB_FILE = path.join(tmp, 'prueba.db');
for (const k of ['SMTP_HOST', 'SMTP_FROM', 'PUBLIC_URL']) delete process.env[k];

const db = require('../src/db');
db.open(process.env.DB_FILE);
const { createApp } = require('../src/app');
const U = require('../src/util');

let base;
const results = [];
let current = '';
function section(t) { current = t; }
async function test(name, fn) {
  try { await fn(); results.push({ section: current, name, ok: true }); process.stdout.write(`  ✓ ${name}\n`); }
  catch (e) { results.push({ section: current, name, ok: false, err: e.message }); process.stdout.write(`  ✗ ${name}\n      ${e.message}\n`); }
}

class Client {
  constructor() { this.cookie = ''; }
  async req(method, url, body, { raw = false, noCsrf = false } = {}) {
    const headers = {};
    if (!noCsrf) headers['X-Requested-With'] = 'cyd-app';
    if (this.cookie) headers.Cookie = this.cookie;
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    const res = await fetch(base + url, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined, redirect: 'manual' });
    const sc = res.headers.get('set-cookie');
    if (sc) { const m = sc.match(/cyd_sid=([^;]*)/); if (m) this.cookie = m[1] ? `cyd_sid=${m[1]}` : ''; }
    if (raw) return { status: res.status, headers: res.headers, buf: Buffer.from(await res.arrayBuffer()) };
    let data = null;
    try { data = await res.json(); } catch { /* sin cuerpo */ }
    return { status: res.status, data };
  }
  get(u) { return this.req('GET', u); }
  post(u, b) { return this.req('POST', u, b ?? {}); }
  put(u, b) { return this.req('PUT', u, b); }
}

const hasPdftotext = (() => { try { execFileSync('pdftotext', ['-v'], { stdio: 'ignore' }); return true; } catch { return false; } })();
function pdfText(buf, layout = false) {
  const f = path.join(tmp, `t-${Date.now()}-${Math.random()}.pdf`);
  fs.writeFileSync(f, buf);
  return execFileSync('pdftotext', [...(layout ? ['-layout'] : []), f, '-'], { encoding: 'utf8' });
}
function pdfPages(buf) {
  const f = path.join(tmp, `p-${Date.now()}.pdf`); fs.writeFileSync(f, buf);
  const info = execFileSync('pdfinfo', [f], { encoding: 'utf8' });
  return { pages: Number(info.match(/Pages:\s+(\d+)/)[1]), size: info.match(/Page size:\s+([^\n]+)/)[1] };
}
function pdfWords(buf) {
  const f = path.join(tmp, `b-${Date.now()}.pdf`); fs.writeFileSync(f, buf);
  const html = execFileSync('pdftotext', ['-bbox', f, '-'], { encoding: 'utf8' });
  return [...html.matchAll(/<word xMin="([\d.]+)" yMin="([\d.]+)" xMax="([\d.]+)" yMax="([\d.]+)">/g)].map(m => m.slice(1).map(Number));
}

const id = () => U.uuid();
function content(topic, periods = 2, acts = ['Ejercicios en clase']) {
  return { id: id(), topic, pages: '15–18', periods, activities: acts.map(t => ({ id: id(), text: t })) };
}
function extras(over = {}) {
  const base = {
    mejoramiento: { id: id(), applies: true, detail: '', activity: 'Actividad de mejoramiento de prueba', scoring_mode: 'total', total_points: 20, aspects_note: 'Nota de prueba', due_date: '2027-03-26', due_text: '', observations: '', criteria: [{ id: id(), text: 'Aspecto de prueba', points: null }] },
    afectivo: { id: id(), applies: true, detail: 'desglose de prueba', activity: '', scoring_mode: 'total', total_points: 5, aspects_note: '', due_date: null, due_text: 'Durante el bimestre', observations: '', criteria: [] },
    asistencia: { id: id(), applies: true, detail: '', activity: '', scoring_mode: 'total', total_points: 5, aspects_note: '', due_date: null, due_text: 'Durante el bimestre', observations: '', criteria: [] },
    examen_final: { id: id(), applies: false, detail: '', activity: '', scoring_mode: 'total', total_points: null, aspects_note: '', due_date: null, due_text: '', observations: '', criteria: [] },
  };
  return { ...base, ...over };
}

(async () => {
  const server = createApp().listen(0);
  await new Promise(r => server.once('listening', r));
  base = `http://127.0.0.1:${server.address().port}`;
  const d = db.get();

  // Administrador de prueba
  d.prepare("INSERT INTO users(username, username_key, password_hash, full_name, role) VALUES ('admin.prueba','admin.prueba',?, 'Administración de Prueba','admin')").run(U.hashPassword('Admin12345'));
  const admin = new Client();
  const A = new Client(), B = new Client(), anon = new Client();

  // ---------------------------------------------------------------- configuración
  section('Configuración institucional');
  let yearId, bims, gQuinto, gCuarto, secA, secB, secC4;
  await test('El administrador inicia sesión y crea un ciclo con cuatro bimestres', async () => {
    assert.strictEqual((await admin.post('/api/auth/login', { username: 'ADMIN.prueba', password: 'Admin12345' })).status, 200);
    const r = await admin.post('/api/admin/school-years', { name: '2027' });
    assert.strictEqual(r.status, 201); yearId = r.data.id;
    const years = (await admin.get('/api/school-years')).data;
    bims = years.find(y => y.id === yearId).bimesters;
    assert.strictEqual(bims.length, 4);
    assert.deepStrictEqual(bims.map(b => b.number), [1, 2, 3, 4]);
  });
  await test('Fechas de los bimestres configurables (y se rechazan bimestres superpuestos)', async () => {
    const bad = await admin.put(`/api/admin/school-years/${yearId}/bimesters`, { bimesters: [{ number: 1, start_date: '2027-01-15', end_date: '2027-03-31' }, { number: 2, start_date: '2027-03-20', end_date: '2027-05-31' }] });
    assert.strictEqual(bad.status, 422);
    const r = await admin.put(`/api/admin/school-years/${yearId}/bimesters`, { bimesters: [
      { number: 1, start_date: '2027-01-11', end_date: '2027-03-26' }, { number: 2, start_date: '2027-04-05', end_date: '2027-06-11' },
      { number: 3, start_date: '2027-06-21', end_date: '2027-08-27' }, { number: 4, start_date: '2027-08-30', end_date: '2027-10-15' }] });
    assert.strictEqual(r.status, 200);
  });
  await test('Grados con secciones propias', async () => {
    gQuinto = (await admin.post('/api/admin/grades', { name: 'Quinto primaria', level: 'Primaria', sort_order: 5 })).data.id;
    gCuarto = (await admin.post('/api/admin/grades', { name: 'Cuarto primaria', level: 'Primaria', sort_order: 4 })).data.id;
    secA = (await admin.post(`/api/admin/grades/${gQuinto}/sections`, { name: 'a' })).data.id;
    secB = (await admin.post(`/api/admin/grades/${gQuinto}/sections`, { name: 'B' })).data.id;
    secC4 = (await admin.post(`/api/admin/grades/${gCuarto}/sections`, { name: 'A' })).data.id;
    const dup = await admin.post(`/api/admin/grades/${gQuinto}/sections`, { name: 'A' });
    assert.strictEqual(dup.status, 422, 'sección duplicada en el mismo grado');
    const grades = (await admin.get('/api/grades')).data;
    assert.deepStrictEqual(grades.find(g => g.id === gQuinto).sections.map(s => s.name), ['A', 'B']);
    assert.deepStrictEqual(grades.find(g => g.id === gCuarto).sections.map(s => s.name), ['A']);
  });

  // ---------------------------------------------------------------- registro y permisos
  section('Registro y permisos');
  let comp;
  await test('Registro completo: cuenta + curso nuevo + grado + secciones en una sola operación', async () => {
    const r1 = await A.post('/api/auth/register', { full_name: 'Docente Prueba Uno Ñúñez', username: 'docente.uno', password: 'Clave1234', password_confirm: 'Clave1234',
      assignments: [{ new_course_name: '  computación ', grade_id: gQuinto, section_ids: [secA] }] });
    assert.strictEqual(r1.status, 201, JSON.stringify(r1.data));
    assert.deepStrictEqual(r1.data.assignments, ['Computación — Quinto primaria — A']);
    comp = d.prepare("SELECT id FROM courses WHERE name_key = 'computacion'").get().id;
    // El segundo docente selecciona el curso existente; “COMPUTACIÓN” escrito como nuevo no se duplica.
    const r2 = await B.post('/api/auth/register', { full_name: 'Docente Prueba Dos', username: 'docente.dos', password: 'Clave5678', password_confirm: 'Clave5678', recovery_email: 'dos@ejemplo.test',
      assignments: [{ course_id: comp, grade_id: gQuinto, section_ids: [secB] }, { new_course_name: 'COMPUTACIÓN', grade_id: gCuarto, section_ids: [secC4] }] });
    assert.strictEqual(r2.status, 201, JSON.stringify(r2.data));
    assert.strictEqual(d.prepare("SELECT COUNT(*) n FROM courses WHERE name_key = 'computacion'").get().n, 1);
    assert.strictEqual(d.prepare('SELECT COUNT(*) n FROM assignments WHERE course_id = ?').get(comp).n, 3);
  });
  await test('Cada fila genera una relación por sección (sin combinar cursos con todos los grados)', async () => {
    const C = new Client();
    const r = await C.post('/api/auth/register', { full_name: 'Docente Prueba Tres', username: 'docente.tres', password: 'Clave9999', password_confirm: 'Clave9999',
      assignments: [
        { new_course_name: 'Física Fundamental', grade_id: gQuinto, section_ids: [secA, secB] },
        { new_course_name: 'Química Básica', grade_id: gCuarto, section_ids: [secC4] },
        { new_course_name: 'física fundamental', grade_id: gCuarto, section_ids: [secC4] },
        { course_id: null, new_course_name: '', grade_id: null, section_ids: [] }] });
    assert.strictEqual(r.status, 201, JSON.stringify(r.data));
    const uid = d.prepare("SELECT id FROM users WHERE username_key = 'docente.tres'").get().id;
    const rows = d.prepare(`SELECT c.name || '|' || g.name || '|' || s.name AS k FROM assignments a JOIN courses c ON c.id = a.course_id
      JOIN grades g ON g.id = a.grade_id JOIN sections s ON s.id = a.section_id WHERE a.user_id = ? ORDER BY k`).all(uid).map(x => x.k);
    assert.deepStrictEqual(rows, ['Física Fundamental|Cuarto primaria|A', 'Física Fundamental|Quinto primaria|A', 'Física Fundamental|Quinto primaria|B', 'Química Básica|Cuarto primaria|A']);
    assert.strictEqual(d.prepare("SELECT COUNT(*) n FROM courses WHERE name_key = 'fisica fundamental'").get().n, 1);
  });
  await test('Registro sin asignación completa o con errores: no se crea nada y se señalan los campos', async () => {
    const before = d.prepare('SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM courses) c, (SELECT COUNT(*) FROM assignments) a').get();
    const r0 = await new Client().post('/api/auth/register', { full_name: 'Persona Sin Cursos', username: 'sin.cursos', password: 'Clave1234', password_confirm: 'Clave1234', assignments: [] });
    assert.strictEqual(r0.status, 422); assert.ok(r0.data.fields.rows);
    const r1 = await new Client().post('/api/auth/register', { full_name: 'Persona Incompleta', username: 'incompleta', password: 'Clave1234', password_confirm: 'Clave1234',
      assignments: [{ new_course_name: 'Curso Que No Debe Existir', grade_id: gQuinto, section_ids: [] }, { new_course_name: 'Otro', grade_id: gQuinto, section_ids: [secC4] }] });
    assert.strictEqual(r1.status, 422);
    assert.ok(r1.data.fields['rows.0.sections'] && r1.data.fields['rows.1.sections']);
    const r2 = await new Client().post('/api/auth/register', { full_name: 'Persona Duplicada', username: 'duplicada', password: 'Clave1234', password_confirm: 'Clave1234',
      assignments: [{ course_id: comp, grade_id: gQuinto, section_ids: [secA] }, { new_course_name: 'Computacion', grade_id: gQuinto, section_ids: [secA] }] });
    assert.strictEqual(r2.status, 422); assert.ok(r2.data.fields['rows.1.sections'], 'combinación repetida en otra fila');
    const after = d.prepare('SELECT (SELECT COUNT(*) FROM users) u, (SELECT COUNT(*) FROM courses) c, (SELECT COUNT(*) FROM assignments) a').get();
    assert.deepStrictEqual(after, before);
  });
  await test('Un nombre de usuario duplicado se rechaza (sin distinguir mayúsculas)', async () => {
    const r = await new Client().post('/api/auth/register', { full_name: 'Otra Persona', username: 'Docente.Uno', password: 'Clave1234', password_confirm: 'Clave1234',
      assignments: [{ course_id: comp, grade_id: gQuinto, section_ids: [secA] }] });
    assert.strictEqual(r.status, 422); assert.ok(r.data.fields.username);
  });
  await test('El formulario de registro obtiene ciclo, grados con secciones y catálogo sin iniciar sesión', async () => {
    const r = await anon.get('/api/auth/register-options');
    assert.strictEqual(r.status, 200);
    assert.strictEqual(r.data.year.id, yearId);
    assert.deepStrictEqual(r.data.grades.find(g => g.id === gQuinto).sections.map(s => s.name), ['A', 'B']);
    assert.ok(r.data.courses.some(c => c.id === comp));
    const chk = await anon.get('/api/courses/check?name=' + encodeURIComponent(' COMPUTACION '));
    assert.strictEqual(chk.data.exact.id, comp);
  });
  let tAna;
  await test('Importación del listado del colegio: grados, cursos, docentes y carga académica (sin duplicar al repetir)', async () => {
    const md = path.join(tmp, 'listado.md');
    fs.writeFileSync(md, [
      '### Quinto Primaria - A', '', '| **Curso** | **Docente** | **Períodos porsemana** |', '| --- | --- | ---: |',
      '| GEOGRAFIA | ANA LUCIA HERNANDEZ LOPEZ | 4 |', '| Física Fundamental | ANA LUCIA HERNANDEZ LOPEZ | 3 |', '| CATEDRA PRUEBA | CATEDRATICO PRUEBA | 1 |', '',
      '### Quinto Primaria - B', '', '| **Curso** | **Docente** | **Períodos porsemana** |', '| --- | --- | ---: |',
      '| Geografía | Ana Lucía Hernández López | 4 |', '',
      '### Listado de docentes activos', '', '| **ID** | **Apellidos y nombres** |', '| ---: | --- |',
      '| 467 | Hernández López Ana Lucía |', '| 500 | Ramírez Gómez Pedro Pablo |'].join('\n'));
    const run = () => execFileSync(process.execPath, [path.join(__dirname, '..', 'scripts', 'import-catalog.js'), md], { env: process.env, encoding: 'utf8' });
    run(); const again = run();
    assert.ok(/docentes: 0, cargas: 0/.test(again), again);
    assert.strictEqual(d.prepare("SELECT COUNT(*) n FROM grades WHERE name_key = 'quinto primaria'").get().n, 1, 'usa el grado existente');
    assert.strictEqual(d.prepare("SELECT COUNT(*) n FROM courses WHERE name_key = 'fisica fundamental'").get().n, 1, 'no duplica cursos');
    assert.ok(!d.prepare("SELECT 1 FROM teachers WHERE name_key LIKE '%prueba%'").get(), 'omite filas de prueba');
    const opts = (await anon.get('/api/auth/register-options')).data;
    const ana = opts.teachers.find(t => t.full_name === 'Ana Lucía Hernández López');
    assert.ok(ana && !ana.taken, 'nombre en orden «Nombres Apellidos»');
    assert.ok(opts.teachers.some(t => t.full_name === 'Pedro Pablo Ramírez Gómez'), 'docente del listado sin carga');
    tAna = ana.id;
  });
  await test('Al elegir su nombre se precargan sus cursos agrupados por curso y grado', async () => {
    const r = (await anon.get(`/api/auth/teachers/${tAna}/load`)).data;
    const geo = r.rows.find(x => x.course_name === 'Geografía');
    assert.deepStrictEqual(geo.section_ids.sort(), [secA, secB].sort());
    assert.strictEqual(r.rows.length, 2);
  });
  await test('Registro vinculado al listado con cualquier contraseña; el nombre no puede usarse dos veces', async () => {
    const load = (await anon.get(`/api/auth/teachers/${tAna}/load`)).data.rows;
    const r = await new Client().post('/api/auth/register', { full_name: 'Ana Lucía Hernández López', username: 'ahernandez', password: '1', password_confirm: '1', teacher_id: tAna,
      assignments: load.map(x => ({ course_id: x.course_id, grade_id: x.grade_id, section_ids: x.section_ids })) });
    assert.strictEqual(r.status, 201, JSON.stringify(r.data));
    assert.strictEqual(r.data.assignments.length, 3);
    assert.ok(d.prepare('SELECT user_id FROM teachers WHERE id = ?').get(tAna).user_id);
    assert.strictEqual((await anon.get('/api/auth/register-options')).data.teachers.find(t => t.id === tAna).taken, 1);
    const dup = await new Client().post('/api/auth/register', { full_name: 'Ana Lucía Hernández López', username: 'otra.ana', password: 'x', password_confirm: 'x', teacher_id: tAna,
      assignments: [{ course_id: comp, grade_id: gQuinto, section_ids: [secA] }] });
    assert.strictEqual(dup.status, 422); assert.ok(dup.data.fields.full_name);
    const empty = await new Client().post('/api/auth/register', { full_name: 'Persona Sin Clave', username: 'sin.clave', password: '', password_confirm: '',
      assignments: [{ course_id: comp, grade_id: gQuinto, section_ids: [secA] }] });
    assert.strictEqual(empty.status, 422); assert.ok(empty.data.fields.password, 'sólo se exige que no esté vacía');
    const list = (await admin.get('/api/admin/teachers')).data;
    assert.strictEqual(list.find(t => t.id === tAna).username, 'ahernandez', 'la administración ve quién tiene cuenta');
    assert.strictEqual((await A.get('/api/admin/teachers')).status, 403);
  });
  await test('Las contraseñas se guardan con hash, nunca en texto legible', async () => {
    const rows = d.prepare('SELECT password_hash FROM users').all();
    assert.ok(rows.every(r => r.password_hash.startsWith('scrypt$') && !r.password_hash.includes('Clave1234')));
    const users = (await admin.get('/api/admin/users')).data;
    assert.ok(users.every(u => !('password_hash' in u)), 'el listado no expone hashes');
  });
  await test('Rutas privadas protegidas: sin sesión 401; sin encabezado CSRF 403', async () => {
    assert.strictEqual((await anon.get('/api/me/dashboard?school_year_id=1')).status, 401);
    assert.strictEqual((await A.req('POST', '/api/courses', { name: 'X' }, { noCsrf: true })).status, 403);
  });
  await test('Un docente no puede usar rutas de administración', async () => {
    assert.strictEqual((await A.get('/api/admin/users')).status, 403);
  });

  // ---------------------------------------------------------------- cursos
  section('Cursos y asignaciones');
  let mat;
  await test('“Matemáticas”, “ MATEMÁTICAS ” y “Matematicas” no crean cursos duplicados', async () => {
    const r1 = await A.post('/api/courses', { name: 'Matemáticas' });
    const r2 = await B.post('/api/courses', { name: '  MATEMÁTICAS  ' });
    const r3 = await B.post('/api/courses', { name: 'Matematicas' });
    const r4 = await A.post('/api/courses', { name: 'matemáticas   ' });
    assert.strictEqual(r1.data.created, true);
    assert.ok([r2, r3, r4].every(r => r.data.course.id === r1.data.course.id && r.data.created === false));
    assert.strictEqual(r1.data.course.name, 'Matemáticas');
    mat = r1.data.course.id;
  });
  await test('“Matemáticas financieras” se crea como curso diferente', async () => {
    const r = await A.post('/api/courses', { name: 'Matemáticas financieras' });
    assert.strictEqual(r.data.created, true); assert.notStrictEqual(r.data.course.id, mat);
  });
  await test('Creación simultánea del mismo curso por varios docentes produce un único registro', async () => {
    const rs = await Promise.all([...Array(10)].map((_, i) => (i % 2 ? A : B).post('/api/courses', { name: i % 3 ? 'Ciencias Sociales' : 'ciencias  sociales' })));
    const ids = new Set(rs.map(r => r.data.course.id));
    assert.strictEqual(ids.size, 1);
    assert.strictEqual(rs.filter(r => r.data.created).length, 1);
    assert.strictEqual(d.prepare("SELECT COUNT(*) n FROM courses WHERE name_key = 'ciencias sociales'").get().n, 1);
  });
  let esp;
  await test('Un docente puede tener varios cursos, grados y secciones (una asignación por sección)', async () => {
    esp = (await A.post('/api/courses', { name: 'Idioma Español' })).data.course.id;
    const r1 = await A.post('/api/me/assignments', { school_year_id: yearId, course_id: mat, grade_id: gQuinto, section_ids: [secA, secB] });
    assert.deepStrictEqual(r1.data.created, ['A', 'B']);
    const r2 = await A.post('/api/me/assignments', { school_year_id: yearId, course_id: esp, grade_id: gCuarto, section_ids: [secC4] });
    assert.strictEqual(r2.status, 201);
    const list = (await A.get(`/api/me/assignments?school_year_id=${yearId}`)).data;
    assert.strictEqual(list.length, 4); // 3 nuevas + la registrada al crear la cuenta
  });
  await test('Las secciones dependen del grado (sección de otro grado rechazada)', async () => {
    const r = await A.post('/api/me/assignments', { school_year_id: yearId, course_id: esp, grade_id: gQuinto, section_ids: [secC4] });
    assert.strictEqual(r.status, 422);
  });
  await test('No se duplican asignaciones', async () => {
    const r = await A.post('/api/me/assignments', { school_year_id: yearId, course_id: mat, grade_id: gQuinto, section_ids: [secA] });
    assert.deepStrictEqual(r.data.existing, ['A']); assert.deepStrictEqual(r.data.created, []);
    assert.strictEqual(d.prepare('SELECT COUNT(*) n FROM assignments WHERE section_id = ? AND course_id = ?').get(secA, mat).n, 1);
  });
  await test('Un docente no puede renombrar un curso compartido que usan otras cuentas', async () => {
    await B.post('/api/me/assignments', { school_year_id: yearId, course_id: mat, grade_id: gCuarto, section_ids: [secC4] });
    const r = await A.put(`/api/courses/${mat}`, { name: 'Mate' });
    assert.strictEqual(r.status, 403);
  });

  await test('Desde «Mis cursos» se agregan varias filas a la vez (curso existente y curso nuevo)', async () => {
    const r = await A.post('/api/me/assignments/batch', { school_year_id: yearId, rows: [
      { course_id: esp, grade_id: gQuinto, section_ids: [secA] }, { new_course_name: 'Robótica', grade_id: gCuarto, section_ids: [secC4] }] });
    assert.strictEqual(r.status, 201, JSON.stringify(r.data));
    assert.deepStrictEqual(r.data.created, ['Idioma Español — Quinto primaria — A', 'Robótica — Cuarto primaria — A']);
  });
  await test('Quitar una asignación sin planes la elimina sin borrar el curso del catálogo', async () => {
    const a = (await A.get(`/api/me/assignments?school_year_id=${yearId}`)).data.find(x => x.course_name === 'Robótica');
    const r = await A.req('DELETE', `/api/me/assignments/${a.id}`);
    assert.strictEqual(r.data.deactivated, false);
    assert.ok(d.prepare("SELECT id FROM courses WHERE name_key = 'robotica'").get());
  });

  // ---------------------------------------------------------------- planificación
  section('Planificación');
  const asgs = (await A.get(`/api/me/assignments?school_year_id=${yearId}`)).data;
  const asgA = asgs.find(a => a.course_id === mat && a.section_name === 'A');
  const asgB = asgs.find(a => a.course_id === mat && a.section_name === 'B');
  const bim1 = bims.find(b => b.number === 1).id;
  let planA, planB, v;
  await test('Crear plan; un segundo intento abre el existente (sin duplicar)', async () => {
    const r = await A.post('/api/plans', { assignment_id: asgA.id, bimester_id: bim1 });
    assert.strictEqual(r.status, 201); planA = r.data.id;
    const again = await A.post('/api/plans', { assignment_id: asgA.id, bimester_id: bim1 });
    assert.strictEqual(again.data.id, planA); assert.strictEqual(again.data.existed, true);
    planB = (await A.post('/api/plans', { assignment_id: asgB.id, bimester_id: bim1 })).data.id;
    assert.notStrictEqual(planA, planB);
  });
  await test('Un borrador incompleto puede guardarse', async () => {
    const p = (await A.get(`/api/plans/${planA}`)).data;
    v = p.general.version;
    const tree = { ...p.tree, competencies: [{ id: id(), text: 'Competencia parcial' }] };
    const r = await A.put(`/api/plans/${planA}`, { version: v, tree });
    assert.strictEqual(r.status, 200); assert.strictEqual(r.data.status, 'borrador'); assert.ok(r.data.validation.errors > 0);
    v = r.data.version;
  });

  // Plan completo: bloque 1 cruza enero-febrero con 1 semana larga + bloque 2 con 3 semanas, 35 contenidos en una semana
  const b1 = { id: id(), label: 'Enero', start_date: '2027-01-11', end_date: '2027-02-05', closed: true,
    evaluation: { id: id(), description: 'Evaluación escrita del bloque de enero con texto extenso. '.repeat(3).trim(), instructions: 'Indicación complementaria: (en casa)', scoring_mode: 'criterios', total_points: null, due_date: '2027-02-05',
      criteria: [{ id: id(), text: 'Claridad de ideas', points: 6 }, { id: id(), text: 'Creatividad', points: 5 }, { id: id(), text: 'Ortografía', points: 4 }] },
    weeks: [{ id: id(), start_date: '2027-01-25', end_date: '2027-02-05', contents: [content('Números naturales — tema A1', 3, ['Lluvia de ideas sobre números', 'Ejercicios del libro']), content('Valor posicional — tema A2', 2)] }] };
  const b2 = { id: id(), label: 'Febrero', start_date: '2027-02-08', end_date: '2027-03-05', closed: false,
    evaluation: { id: id(), description: 'Evaluación parcial en línea del bloque de febrero', instructions: '', scoring_mode: 'total', total_points: 20, due_date: '2027-03-05',
      criteria: [{ id: id(), text: 'Respuesta correcta 1 punto c/u', points: null }] },
    weeks: [
      { id: id(), start_date: '2027-02-08', end_date: '2027-02-12', contents: [...Array(35)].map((_, i) => content(`Contenido B semana uno número ${i + 1}`, 1)) },
      { id: id(), start_date: '2027-02-15', end_date: '2027-02-19', contents: [content('Fracciones — tema B36', 2)] },
      { id: id(), start_date: '2027-02-22', end_date: '2027-03-05', contents: [content('Decimales — tema B37', 4)] },
    ] };
  const fullTree = () => ({
    include_pages: false, observations: '',
    competencies: [{ id: id(), text: 'Resuelve problemas con números naturales.' }],
    indicators: [{ id: id(), text: 'Aplica el valor posicional.' }, { id: id(), text: 'Opera con fracciones.' }],
    blocks: [reId(b1), reId(b2)],
    extras: extras(),
  });
  // Copia con identificadores nuevos (cada plan tiene sus propios registros)
  const reId = o => JSON.parse(JSON.stringify(o), (k, val) => (k === 'id' ? id() : val));
  let saved;
  await test('Bloques flexibles (1 y 3 semanas), bloque que cruza de enero a febrero, 35 contenidos en una semana', async () => {
    const r = await A.put(`/api/plans/${planA}`, { version: v, tree: fullTree() });
    assert.strictEqual(r.status, 200, JSON.stringify(r.data));
    v = r.data.version;
    assert.strictEqual(r.data.validation.errors, 0, JSON.stringify(r.data.validation.issues));
    saved = (await A.get(`/api/plans/${planA}`)).data;
    assert.strictEqual(saved.tree.blocks[0].weeks.length, 1);
    assert.strictEqual(saved.tree.blocks[1].weeks.length, 3);
    assert.strictEqual(saved.tree.blocks[1].weeks[0].contents.length, 35);
  });
  await test('La numeración de semanas continúa dentro del bimestre (no reinicia por bloque)', async () => {
    const nums = d.prepare('SELECT w.number FROM weeks w JOIN blocks b ON b.id = w.block_id WHERE w.plan_id = ? ORDER BY b.position, w.number').all(planA).map(r => r.number);
    assert.deepStrictEqual(nums, [1, 2, 3, 4]);
  });
  await test('Un contenido admite varias actividades de aprendizaje y los períodos se guardan por contenido', async () => {
    const c = saved.tree.blocks[0].weeks[0].contents[0];
    assert.strictEqual(c.activities.length, 2); assert.strictEqual(c.periods, 3);
    assert.strictEqual(saved.totals.blocks[0].block, 5); assert.strictEqual(saved.totals.blocks[1].block, 35 + 2 + 4);
    assert.strictEqual(saved.totals.plan, 46);
  });
  await test('Cada bloque tiene exactamente una evaluación; varios criterios no crean varias evaluaciones', async () => {
    assert.strictEqual(d.prepare('SELECT COUNT(*) n FROM evaluations e JOIN blocks b ON b.id = e.block_id WHERE b.plan_id = ?').get(planA).n, 2);
    assert.strictEqual(d.prepare('SELECT COUNT(*) n FROM evaluation_criteria c JOIN evaluations e ON e.id = c.evaluation_id WHERE e.block_id = ?').get(saved.tree.blocks[0].id).n, 3);
    assert.throws(() => d.prepare("INSERT INTO evaluations(id, block_id) VALUES ('x-dup', ?)").run(saved.tree.blocks[0].id), /UNIQUE/);
  });
  await test('Suma de criterios sin doble conteo (criterios 6+5+4 = 15; modo total = 20)', async () => {
    const P = require('../src/plan');
    const proj = P.pdfProjection(saved.tree);
    assert.strictEqual(proj.blocks[0].evaluation.total, 15);
    assert.strictEqual(proj.blocks[1].evaluation.total, 20);
  });
  await test('Los planes de las secciones A y B permanecen separados', async () => {
    const pb = (await A.get(`/api/plans/${planB}`)).data;
    assert.strictEqual(pb.tree.blocks.length, 0);
    assert.strictEqual(pb.general.section_name, 'B');
  });
  await test('Fechas inválidas se detectan antes de marcar como listo', async () => {
    const pb = (await A.get(`/api/plans/${planB}`)).data;
    const t = fullTree();
    t.blocks[1].weeks[1].start_date = '2027-02-20'; t.blocks[1].weeks[1].end_date = '2027-02-16';     // inicio > fin
    t.blocks[1].weeks[2].start_date = '2027-02-10';                                                    // superpuesta y desordenada
    t.blocks[0].weeks[0].end_date = '2027-02-10';                                                      // fuera del bloque
    const r = await A.put(`/api/plans/${planB}`, { version: pb.general.version, tree: t });
    assert.strictEqual(r.status, 200);
    const ready = await A.post(`/api/plans/${planB}/ready`, { version: r.data.version });
    assert.strictEqual(ready.status, 422);
    const msgs = ready.data.validation.issues.map(i => i.message).join(' | ');
    assert.match(msgs, /posterior a la de finalización/); assert.match(msgs, /se superponen|anteriores/); assert.match(msgs, /dentro del bloque/);
    assert.ok(ready.data.validation.issues.every(i => i.target), 'cada incidencia indica el campo');
  });
  await test('Una pestaña antigua no sobrescribe cambios más recientes (conflicto de versión 409)', async () => {
    const r = await A.put(`/api/plans/${planA}`, { version: v - 1, tree: fullTree() });
    assert.strictEqual(r.status, 409); assert.strictEqual(r.data.code, 'VERSION_CONFLICT');
  });
  await test('Cada cuenta recupera sus planes después de cerrar sesión', async () => {
    await A.post('/api/auth/logout');
    assert.strictEqual((await A.get(`/api/plans/${planA}`)).status, 401);
    assert.strictEqual((await A.post('/api/auth/login', { username: 'docente.uno', password: 'Clave1234' })).status, 200);
    const p = (await A.get(`/api/plans/${planA}`)).data;
    assert.strictEqual(p.tree.blocks[1].weeks[0].contents.length, 35);
  });
  await test('Un docente no puede acceder a planes ajenos modificando URL o petición', async () => {
    assert.strictEqual((await B.get(`/api/plans/${planA}`)).status, 404);
    assert.strictEqual((await B.put(`/api/plans/${planA}`, { version: v, tree: fullTree() })).status, 404);
    assert.strictEqual((await B.req('GET', `/api/plans/${planA}/preview.pdf`, undefined, { raw: true })).status, 404);
    assert.strictEqual((await B.post(`/api/plans/${planA}/ready`, { version: v })).status, 404);
    assert.strictEqual((await B.post('/api/plans', { assignment_id: asgA.id, bimester_id: bim1 })).status, 404);
    assert.strictEqual((await B.get(`/api/plans/${planA}/history`)).status, 404);
  });

  // ---------------------------------------------------------------- listo / PDF
  section('Estado “Listo” y hoja de actividades en PDF');
  let docId;
  await test('No se genera la hoja final mientras el plan es borrador; la vista previa no lleva marcas de borrador', async () => {
    const r = await A.post(`/api/plans/${planA}/documents`, { version: v });
    assert.strictEqual(r.status, 422);
    const pv = await A.req('GET', `/api/plans/${planA}/preview.pdf`, undefined, { raw: true });
    assert.strictEqual(pv.status, 200); assert.strictEqual(pv.headers.get('content-type'), 'application/pdf');
    if (hasPdftotext) assert.doesNotMatch(pdfText(pv.buf), /BORRADOR|versión final|VISTA PREVIA/i);
    assert.ok(!/BORRADOR/.test(pv.headers.get('content-disposition')), 'el nombre del archivo no indica borrador');
  });
  await test('Marcar como listo registra la fecha', async () => {
    const r = await A.post(`/api/plans/${planA}/ready`, { version: v });
    assert.strictEqual(r.status, 200, JSON.stringify(r.data));
    assert.strictEqual(r.data.general.status, 'listo'); assert.ok(r.data.general.ready_at);
    v = r.data.general.version;
  });
  await test('Editar un plan listo requiere acción explícita', async () => {
    const p = (await A.get(`/api/plans/${planA}`)).data;
    const r = await A.put(`/api/plans/${planA}`, { version: v, tree: p.tree });
    assert.strictEqual(r.status, 409); assert.strictEqual(r.data.code, 'PLAN_READY');
  });
  await test('Cambiar sólo una actividad de aprendizaje (no aparece en el PDF) conserva el estado Listo', async () => {
    const p = (await A.get(`/api/plans/${planA}`)).data;
    p.tree.blocks[0].weeks[0].contents[0].activities[0].text = 'Lluvia de ideas modificada';
    const r = await A.put(`/api/plans/${planA}`, { version: v, tree: p.tree, edit_ready: true });
    assert.strictEqual(r.status, 200); assert.strictEqual(r.data.status, 'listo'); assert.strictEqual(r.data.reverted_to_draft, false);
    v = r.data.version;
  });
  await test('Generar la hoja final (plan listo) y descargarla', async () => {
    const r = await A.post(`/api/plans/${planA}/documents`, { version: v });
    assert.strictEqual(r.status, 201, JSON.stringify(r.data)); docId = r.data.id;
    assert.strictEqual(r.data.filename, 'Hoja_Actividades_Matematicas_Quinto_Primaria_A_Bimestre_1_2027.pdf');
    const dl = await A.req('GET', `/api/documents/${docId}/pdf?download=1`, undefined, { raw: true });
    assert.strictEqual(dl.status, 200); assert.match(dl.headers.get('content-disposition'), /attachment/);
    const row = d.prepare('SELECT sha256, plan_version FROM generated_documents WHERE id = ?').get(docId);
    assert.strictEqual(U.sha256(dl.buf), row.sha256); assert.strictEqual(row.plan_version, v);
    assert.strictEqual((await B.req('GET', `/api/documents/${docId}/pdf`, undefined, { raw: true })).status, 404, 'otro docente no puede descargarlo');
  });
  if (hasPdftotext) {
    let text, buf;
    await test('Formato de la plantilla: papel oficio vertical (612 × 1008 pt) y texto seleccionable', async () => {
      buf = (await A.req('GET', `/api/documents/${docId}/pdf`, undefined, { raw: true })).buf;
      const info = pdfPages(buf);
      assert.match(info.size, /^612 x 1008 pts/);
      text = pdfText(buf).replace(/\s+/g, ' ');
      assert.ok(text.length > 500);
    });
    await test('Recupera docente, curso, grado, sección, ciclo y bimestre correctos (con tildes y ñ)', async () => {
      for (const s of ['HOJA DE ACTIVIDADES PRIMER BIMESTRE', 'Docente Prueba Uno Ñúñez', 'Matemáticas', 'Quinto primaria', '“A”', 'Ciclo escolar 2027', 'NOMBRE DEL ALUMNO', 'CLAVE', 'CONTENIDOS']) assert.ok(text.includes(s) || text.replace(/\s+/g, '').includes(s.replace(/\s+/g, '')), `falta “${s}”`);
      assert.ok(!text.includes('“B”'));
    });
    await test('Columnas de la plantilla, criterios, punteos y fechas conservados', async () => {
      for (const s of ['Contenido', 'Actividades de evaluación', 'Aspectos a evaluar', 'Fecha de', 'Recepción de', 'Tareas', 'Claridad de ideas', '6 puntos', 'Total', '15 puntos', 'TOTAL 20 puntos', '05-02-2027', '05-03-2027', 'Respuesta correcta 1 punto c/']) assert.ok(text.includes(s) || text.replace(/\s+/g, '').includes(s.replace(/\s+/g, '')), `falta “${s}”`);
    });
    await test('Incluye apartados adicionales registrados (“No aplica” explícito) y textos institucionales', async () => {
      for (const s of ['ACTIVIDAD DE MEJORAMIENTO', 'Actividad de mejoramiento de prueba', 'Afectivo', 'desglose de prueba', 'Asistencia', 'Durante el bimestre', 'Examen final.', 'No aplica', 'Observaciones:', 'NOTA:', 'CATEDRÁTICO', 'PADRE DE FAMILIA', 'DIRECCIÓN']) assert.ok(text.includes(s) || text.replace(/\s+/g, '').includes(s.replace(/\s+/g, '')), `falta “${s}”`);
    });
    await test('No confunde aprendizaje con evaluación: las actividades de aprendizaje no aparecen en la hoja', async () => {
      assert.ok(!text.includes('Lluvia de ideas'), 'una actividad de aprendizaje apareció en el PDF');
      assert.ok(!text.includes('Ejercicios del libro'));
    });
    await test('Agrupa los contenidos con la evaluación de su bloque', async () => {
      const lay = pdfText(buf, true);
      const iEvalEnero = lay.indexOf('Evaluación escrita'), iEvalFeb = lay.indexOf('Evaluación parcial en línea');
      assert.ok(iEvalEnero >= 0 && iEvalFeb > iEvalEnero, 'las evaluaciones aparecen en el orden de sus bloques');
      const snap = JSON.parse(d.prepare('SELECT snapshot FROM generated_documents WHERE id = ?').get(docId).snapshot);
      assert.strictEqual(snap.projection.blocks[0].contents.length, 2); assert.strictEqual(snap.projection.blocks[1].contents.length, 37);
    });
    await test('No agrega valores que nadie registró (sin undefined/null/NaN ni datos de ejemplo de la plantilla)', async () => {
      for (const s of ['undefined', 'null', 'NaN', '[object', 'Miguel', 'Ciencias Naturales', '00-00-2026', '30 puntos']) assert.ok(!text.includes(s), `apareció “${s}”`);
    });
    await test('La vista previa y el PDF descargado corresponden a la misma información guardada', async () => {
      const pv = await A.req('GET', `/api/plans/${planA}/preview.pdf`, undefined, { raw: true });
      assert.strictEqual(pdfText(pv.buf).replace(/\s+/g, ' '), text);
    });
    await test('Documento extenso: varios meses y textos largos se distribuyen en varias páginas, sin recortes ni letra diminuta', async () => {
      // Nuevo plan muy extenso para el grupo B
      const pb = (await A.get(`/api/plans/${planB}`)).data;
      const t = fullTree();
      t.include_pages = true;
      const long = 'Este es un contenido con una descripción larga que debe ajustarse dentro de la celda sin cortarse ni salir del área imprimible. ';
      t.blocks.push({ id: id(), label: 'Marzo', start_date: '2027-03-08', end_date: '2027-03-26', closed: false,
        evaluation: { id: id(), description: ('Proyecto final del bloque de marzo con instrucciones muy detalladas. ').repeat(40), instructions: 'Entregar en grupos.', scoring_mode: 'criterios', total_points: null, due_date: '2027-03-26',
          criteria: [...Array(8)].map((_, i) => ({ id: id(), text: `Criterio extenso número ${i + 1} con descripción`, points: 2.5 })) },
        weeks: [{ id: id(), start_date: '2027-03-08', end_date: '2027-03-26', contents: [...Array(80)].map((_, i) => content(`${i + 1}. ${long}${i % 5 === 0 ? long : ''}`, 1)) }] });
      const r = await A.put(`/api/plans/${planB}`, { version: pb.general.version, tree: t });
      assert.strictEqual(r.status, 200);
      const ready = await A.post(`/api/plans/${planB}/ready`, { version: r.data.version });
      assert.strictEqual(ready.status, 200, JSON.stringify(ready.data.validation && ready.data.validation.issues.slice(0, 3)));
      const gen = await A.post(`/api/plans/${planB}/documents`, { version: ready.data.general.version });
      assert.strictEqual(gen.status, 201);
      const big = (await A.req('GET', `/api/documents/${gen.data.id}/pdf`, undefined, { raw: true })).buf;
      const info = pdfPages(big);
      assert.ok(info.pages >= 4, `páginas: ${info.pages}`);
      const bt = pdfText(big).replace(/\s+/g, ' ');
      for (let i = 1; i <= 80; i++) assert.ok(bt.includes(`${i}. Este es un contenido`), `falta el contenido ${i}`);
      for (let i = 1; i <= 35; i++) assert.ok(bt.includes(`número ${i}`), `falta contenido B ${i}`);
      assert.ok(bt.includes('(continuación — Marzo)'), 'las continuaciones indican el bloque mensual');
      assert.ok((bt.match(/Actividades de evaluación/g) || []).length >= info.pages - 1, 'el encabezado de la tabla se repite en cada página');
      const words = pdfWords(big);
      const minH = Math.min(...words.map(w => w[3] - w[1]));
      assert.ok(minH >= 6, `texto demasiado pequeño: ${minH.toFixed(2)} pt`);
      const out = words.filter(w => w[0] < 18 || w[2] > 594 || w[1] < 10 || w[3] > 998);
      assert.strictEqual(out.length, 0, `palabras fuera del área imprimible: ${out.length}`);
      assert.ok(bt.includes('(pág. 15–18)'), 'páginas del libro incluidas cuando se elige');
    });
    await test('Consistencia histórica: renombrar un curso o el perfil no cambia hojas ya generadas', async () => {
      assert.strictEqual((await admin.put(`/api/admin/courses/${mat}`, { name: 'Matemática', active: true })).status, 200);
      assert.strictEqual((await A.put('/api/me/profile', { full_name: 'Docente Uno Renombrado' })).status, 200);
      const old = pdfText((await A.req('GET', `/api/documents/${docId}/pdf`, undefined, { raw: true })).buf).replace(/\s+/g, ' ');
      assert.ok(old.includes('Matemáticas') && old.includes('Docente Prueba Uno Ñúñez'));
      const pv = pdfText((await A.req('GET', `/api/plans/${planA}/preview.pdf`, undefined, { raw: true })).buf).replace(/\s+/g, ' ');
      assert.ok(pv.includes('ASIGNATURA: Matemática '), 'la nueva vista previa usa el nombre actual');
      assert.ok(pv.includes('Docente Uno Renombrado'));
      await admin.put(`/api/admin/courses/${mat}`, { name: 'Matemáticas', active: true });
    });
  }
  await test('Cambiar un tema (información del PDF) devuelve el plan a borrador', async () => {
    const p = (await A.get(`/api/plans/${planA}`)).data;
    p.tree.blocks[0].weeks[0].contents[0].topic = 'Tema cambiado';
    const r = await A.put(`/api/plans/${planA}`, { version: p.general.version, tree: p.tree, edit_ready: true });
    assert.strictEqual(r.data.status, 'borrador'); assert.strictEqual(r.data.reverted_to_draft, true);
    const docs = (await A.get(`/api/plans/${planA}`)).data.documents;
    assert.strictEqual(docs[0].current, false, 'el documento anterior queda identificado como desactualizado');
  });
  await test('Se conserva el registro de creación, modificaciones y cambios de estado', async () => {
    const h = (await A.get(`/api/plans/${planA}/history`)).data.map(x => x.action);
    for (const a of ['crear', 'guardar', 'cambio_estado', 'generar_pdf']) assert.ok(h.includes(a), `falta ${a}`);
  });

  // ---------------------------------------------------------------- plan bimestral en PDF
  section('Plan bimestral en PDF');
  if (hasPdftotext) {
    const planPdf = (C, pid) => C.req('GET', `/api/plans/${pid}/plan-bimestral.pdf`, undefined, { raw: true });
    const flat = buf => pdfText(buf).replace(/\s+/g, ' ');
    let shortTxt;
    await test('Plan corto: papel legal, título y datos del plan seleccionado, cinco secciones en orden', async () => {
      const r = await planPdf(A, planA);
      assert.strictEqual(r.status, 200);
      assert.ok(r.headers.get('content-type').includes('application/pdf'));
      assert.ok(/Plan_Bimestral_2027_Matematicas_Quinto_Primaria_A_Bim_1/.test(r.headers.get('content-disposition')), r.headers.get('content-disposition'));
      const info = pdfPages(r.buf);
      assert.ok(info.size.startsWith('612 x 1008'), info.size);
      const t = shortTxt = flat(r.buf);
      assert.ok(t.includes('PLAN BIMESTRAL 2027 - BIMESTRE 1'));
      for (const x of ['Establecimiento: Colegio Particular Mixto Ciencia y Desarrollo', 'Docente: Docente Uno Renombrado', 'Bimestre: Primero', 'Curso: Matemáticas', 'Grado: Quinto primaria', 'Sección: A', '11/01/2027 al 26/03/2027']) assert.ok(t.includes(x), `falta «${x}»`);
      const order = ['1. COMPETENCIA E INDICADORES DE LOGRO', '2. DOSIFICACIÓN Y DESARROLLO DEL BIMESTRE', '3. PLAN DE MEJORAMIENTO', '4. OBSERVACIONES DEL COORDINADOR', '5. CONTROL DE ENTREGA Y REVISIÓN'].map(x => t.indexOf(x));
      assert.ok(order.every((x, i) => x >= 0 && (i === 0 || x > order[i - 1])), `orden de secciones: ${order}`);
      for (const x of ['SEMANA', 'FECHAS', 'CONTENIDOS DE APRENDIZAJE', 'PÁG. LIBRO', 'ACTIVIDADES DE APRENDIZAJE', 'ACTIVIDADES DE EVALUACIÓN', 'PERÍODOS']) assert.ok(t.includes(x), `falta columna ${x}`);
      assert.ok(t.includes('Resuelve problemas con números naturales.') && t.includes('Opera con fracciones.'), 'competencias e indicadores completos');
      assert.ok(t.includes('BIM 1') && /Página 1 de \d+/.test(t), 'pie de página');
      assert.ok(!/BORRADOR|versión final/i.test(t), 'el documento no lleva marcas de borrador');
      assert.ok(!/BORRADOR/.test(r.headers.get('content-disposition')), 'el nombre del archivo no indica borrador');
    });
    await test('Contenidos, páginas, actividades por contenido y períodos guardados (no numeración automática)', async () => {
      const t = shortTxt;
      for (const x of ['Tema cambiado', 'Valor posicional — tema A2', 'Lluvia de ideas modificada', 'Ejercicios del libro', 'Páginas: 15–18', 'Contenido B semana uno número 35', 'Decimales — tema B37']) assert.ok(t.includes(x), `falta «${x}»`);
      // El número de períodos va en la columna PERÍODOS (x ≥ 505 pt), a la altura de su contenido
      const f = path.join(tmp, `bb-${Date.now()}.pdf`); fs.writeFileSync(f, (await planPdf(A, planA)).buf);
      const pageXml = execFileSync('pdftotext', ['-bbox', f, '-'], { encoding: 'utf8' }).split('<page ').find(pg => pg.includes('>B37<'));
      const bb = [...pageXml.matchAll(/<word xMin="([\d.]+)" yMin="([\d.]+)" xMax="([\d.]+)" yMax="([\d.]+)">([^<]*)<\/word>/g)]
        .map(m => ({ x: +m[1], y: +m[2], t: m[5] }));
      const anchor = bb.find(w => w.t === 'B37');
      const per = bb.filter(w => w.x >= 505 && w.y >= anchor.y - 3 && w.y < anchor.y + 30).map(w => w.t);
      assert.deepStrictEqual(per, ['4'], `períodos guardados junto a «Decimales — tema B37»: ${per}`);
      const weeks = d.prepare('SELECT number FROM weeks WHERE plan_id = ? ORDER BY number').all(planA).map(w => w.number);
      for (const n of weeks) assert.ok(new RegExp(`\\b${n}\\b`).test(t));
    });
    await test('Una sola evaluación por bloque: se imprime su descripción y no los criterios de la hoja', async () => {
      const t = shortTxt;
      assert.ok(t.includes('Evaluación parcial en línea del bloque de febrero'));
      assert.ok(!t.includes('Claridad de ideas'), 'los criterios pertenecen a la hoja de actividades');
      const n = d.prepare('SELECT COUNT(*) n FROM evaluations e JOIN blocks b ON b.id = e.block_id WHERE b.plan_id = ?').get(planA).n;
      assert.strictEqual(n, d.prepare('SELECT COUNT(*) n FROM blocks WHERE plan_id = ?').get(planA).n);
    });
    await test('No aparecen #REF!, undefined, null ni datos del ejemplo; sin entrega ni revisión inventadas', async () => {
      const t = shortTxt;
      for (const bad of ['#REF!', 'undefined', 'null', 'NaN', 'Rubi', 'Infografía: estructura', 'Cronología ilustrada', 'Aprobado']) assert.ok(!t.includes(bad), `aparece «${bad}»`);
      assert.ok(t.includes('Sin entregar'), 'estado real: el plan no se ha entregado');
      assert.ok(t.includes('Firma del docente: Docente Uno Renombrado') && t.includes('Firma del coordinador(a) ') && t.includes('Vo.Bo. Dirección / Sello'));
    });
    await test('Entrega y revisión reales: entregar requiere plan listo; la revisión la registra coordinación', async () => {
      assert.strictEqual((await A.post(`/api/plans/${planA}/deliver`, {})).status, 422, 'un borrador no se entrega');
      assert.strictEqual((await admin.post(`/api/admin/plans/${planB}/reviews`, { result: 'aprobado' })).status, 422, 'no se revisa lo no entregado');
      assert.strictEqual((await A.post(`/api/plans/${planB}/deliver`, {})).status, 200);
      assert.strictEqual((await A.put(`/api/admin/plans/${planB}/coordination`, { observations: 'x' })).status, 403, 'el docente no escribe observaciones del coordinador');
      assert.strictEqual((await admin.put(`/api/admin/plans/${planB}/coordination`, { observations: 'Revisar la secuencia de fracciones. '.repeat(30).trim() })).status, 200);
      assert.strictEqual((await admin.post(`/api/admin/plans/${planB}/reviews`, { result: 'con_observaciones' })).status, 201);
      const t = flat((await planPdf(A, planB)).buf);
      const [y, m, dd] = new Date().toISOString().slice(0, 10).split('-');
      assert.ok(t.includes(`${dd}/${m}/${y}`), 'fechas de entrega y revisión');
      assert.ok(t.includes('Aprobado con observaciones') && t.includes('Administración de Prueba'));
      assert.ok(t.includes('Revisar la secuencia de fracciones.'), 'observaciones del coordinador (texto extenso)');
      assert.ok(t.includes('Firma del coordinador(a): Administración de Prueba'));
      assert.ok(!t.includes('Sin entregar'));
    });
    await test('Plan extenso: varias páginas legibles, encabezados repetidos, continuaciones y todos los contenidos', async () => {
      // Datos sólo del plan bimestral (mejoramiento): no cambian la hoja, el plan sigue «Listo»
      const p = (await A.get(`/api/plans/${planB}`)).data;
      p.tree.extras.mejoramiento.strategies = 'Tutorías en grupos pequeños. '.repeat(20).trim();
      p.tree.extras.mejoramiento.students = 'Estudiantes con nota menor a 60';
      p.tree.improvement_actions = [{ id: id(), description: 'Segunda acción de refuerzo', strategies: 'Guías de práctica', students: '8', action_date: '2027-03-20' }];
      const sv = await A.put(`/api/plans/${planB}`, { version: p.general.version, tree: p.tree, edit_ready: true });
      assert.strictEqual(sv.status, 200); assert.strictEqual(sv.data.status, 'listo', 'el plan de mejoramiento no altera la hoja de actividades');
      const r = await planPdf(A, planB);
      const info = pdfPages(r.buf);
      assert.ok(info.pages >= 4, `páginas: ${info.pages}`);
      const t = flat(r.buf);
      for (let i = 1; i <= 80; i++) assert.ok(t.includes(`${i}. Este es un contenido`), `falta el contenido ${i}`);
      assert.ok(t.includes(`Página ${info.pages} de ${info.pages}`), 'numeración final correcta');
      assert.ok(t.includes('(continuación)'), 'continuaciones identificadas');
      assert.ok((t.match(/ACTIVIDADES DE EVALUACIÓN/g) || []).length >= 3, 'encabezados de tabla repetidos');
      assert.ok(t.includes('Tutorías en grupos pequeños.') && t.includes('Segunda acción de refuerzo') && t.includes('20/03/2027'), 'plan de mejoramiento completo');
      assert.ok(!t.includes('BORRADOR'), 'un plan listo no lleva marca de borrador');
      const words = pdfWords(r.buf);
      assert.ok(Math.min(...words.map(w => w[3] - w[1])) >= 6, 'texto legible');
      assert.strictEqual(words.filter(w => w[0] < 18 || w[2] > 594 || w[1] < 10 || w[3] > 998).length, 0, 'sin texto fuera del área imprimible');
      assert.strictEqual(d.prepare('SELECT COUNT(*) n FROM evaluations e JOIN blocks b ON b.id = e.block_id WHERE b.plan_id = ?').get(planB).n, 3, 'la repetición visual no duplica evaluaciones');
    });
    await test('Otro docente no puede obtener el plan bimestral ajeno', async () => {
      assert.strictEqual((await planPdf(B, planA)).status, 404);
    });
  }

  // ---------------------------------------------------------------- administración
  section('Administración');
  await test('El administrador consulta planes (registrado) pero no modifica contenido académico', async () => {
    assert.strictEqual((await admin.get(`/api/plans/${planA}`)).status, 200);
    const p = (await admin.get(`/api/plans/${planA}`)).data;
    assert.strictEqual(p.readonly, true);
    assert.strictEqual((await admin.put(`/api/plans/${planA}`, { version: p.general.version, tree: p.tree })).status, 403);
    assert.ok(d.prepare("SELECT COUNT(*) n FROM audit_log WHERE action = 'consulta_admin' AND plan_id = ?").get(planA).n >= 1);
  });
  await test('Devolver a borrador por la administración queda registrado con motivo', async () => {
    const pb = (await admin.get(`/api/plans/${planB}`)).data;
    assert.strictEqual(pb.general.status, 'listo');
    assert.strictEqual((await admin.post(`/api/admin/plans/${planB}/return-to-draft`, {})).status, 422, 'exige motivo');
    assert.strictEqual((await admin.post(`/api/admin/plans/${planB}/return-to-draft`, { reason: 'Revisión de fechas' })).status, 200);
    const row = d.prepare("SELECT details FROM audit_log WHERE action = 'cambio_estado_admin' AND plan_id = ?").get(planB);
    assert.match(row.details, /Revisión de fechas/);
  });
  await test('Seguimiento: estados por docente y bimestre', async () => {
    const r = (await admin.get(`/api/admin/plans?school_year_id=${yearId}`)).data;
    const row = r.rows.find(x => x.assignment_id === asgA.id);
    assert.strictEqual(row.bimesters.length, 4);
    assert.strictEqual(row.bimesters[1].status, 'sin_iniciar');
  });
  await test('Quitar una asignación con planes la desactiva y conserva el historial', async () => {
    const r = await A.req('DELETE', `/api/me/assignments/${asgB.id}`);
    assert.strictEqual(r.status, 200); assert.strictEqual(r.data.deactivated, true);
    assert.ok(d.prepare('SELECT id FROM plans WHERE id = ?').get(planB), 'el plan se conserva');
    const dash = (await A.get(`/api/me/dashboard?school_year_id=${yearId}`)).data;
    assert.strictEqual(dash.rows.find(x => x.assignment_id === asgB.id).active, 0);
    const bim2 = bims.find(b => b.number === 2).id;
    assert.strictEqual((await A.post('/api/plans', { assignment_id: asgB.id, bimester_id: bim2 })).status, 422, 'no se crean planes nuevos');
    assert.strictEqual((await A.get(`/api/plans/${planB}`)).status, 200, 'el plan existente sigue disponible');
    assert.strictEqual((await A.put(`/api/me/assignments/${asgB.id}/active`, { active: true })).status, 200);
    assert.strictEqual(d.prepare('SELECT active FROM assignments WHERE id = ?').get(asgB.id).active, 1);
  });

  // ---------------------------------------------------------------- acceso
  section('Recuperación y restablecimiento de acceso');
  await test('Sin correo configurado no se simula ningún envío de recuperación', async () => {
    const r = await anon.post('/api/auth/forgot', { username: 'docente.dos' });
    assert.strictEqual(r.data.mailEnabled, false);
  });
  await test('Restablecimiento administrativo: contraseña temporal de un solo uso y cambio obligatorio', async () => {
    const uid = d.prepare("SELECT id FROM users WHERE username_key = 'docente.dos'").get().id;
    const r = await admin.post(`/api/admin/users/${uid}/reset-password`, {});
    assert.ok(r.data.temporary_password);
    assert.strictEqual((await B.get('/api/me/dashboard?school_year_id=' + yearId)).status, 401, 'sesiones anteriores cerradas');
    assert.strictEqual((await B.post('/api/auth/login', { username: 'docente.dos', password: 'Clave5678' })).status, 401, 'la contraseña anterior ya no funciona');
    assert.strictEqual((await B.post('/api/auth/login', { username: 'docente.dos', password: r.data.temporary_password })).status, 200);
    const blocked = await B.get('/api/me/dashboard?school_year_id=' + yearId);
    assert.strictEqual(blocked.status, 403); assert.strictEqual(blocked.data.code, 'MUST_CHANGE_PASSWORD');
    assert.strictEqual((await B.put('/api/me/password', { current_password: r.data.temporary_password, password: 'NuevaClave9', password_confirm: 'NuevaClave9' })).status, 200);
    assert.strictEqual((await B.get('/api/me/dashboard?school_year_id=' + yearId)).status, 200);
  });
  await test('Cuenta desactivada no puede ingresar', async () => {
    const uid = d.prepare("SELECT id FROM users WHERE username_key = 'docente.dos'").get().id;
    await admin.put(`/api/admin/users/${uid}/active`, { active: false });
    assert.strictEqual((await new Client().post('/api/auth/login', { username: 'docente.dos', password: 'NuevaClave9' })).status, 403);
    await admin.put(`/api/admin/users/${uid}/active`, { active: true });
  });

  server.close();
  // ---------------------------------------------------------------- resumen
  const ok = results.filter(r => r.ok).length;
  console.log(`\n${ok}/${results.length} pruebas aprobadas${hasPdftotext ? '' : ' (pruebas de texto del PDF omitidas: falta pdftotext)'}`);
  const md = ['# Resultado de las pruebas de aceptación', '', `Fecha: ${new Date().toISOString()}`, '', `**${ok} de ${results.length} pruebas aprobadas.**`, ''];
  let sec = '';
  for (const r of results) {
    if (r.section !== sec) { sec = r.section; md.push('', `## ${sec}`, ''); }
    md.push(`- ${r.ok ? '✅' : '❌'} ${r.name}${r.ok ? '' : ` — ${r.err}`}`);
  }
  fs.writeFileSync(path.join(__dirname, 'RESULTADOS.md'), md.join('\n') + '\n');
  fs.rmSync(tmp, { recursive: true, force: true });
  process.exit(ok === results.length ? 0 : 1);
})().catch(e => { console.error(e); process.exit(1); });
