'use strict';
const path = require('path');
const express = require('express');
const config = require('./config');
const { get: db } = require('./db');
const auth = require('./auth');
const mail = require('./mail');
const P = require('./plan');
const pdf = require('./pdf');
const planPdf = require('./plan-pdf');
const U = require('./util');
const { HttpError } = U;

const wrap = fn => (req, res, next) => { try { const r = fn(req, res, next); if (r && r.catch) r.catch(next); } catch (e) { next(e); } };
const intParam = (v, name) => { const n = Number(v); if (!Number.isInteger(n) || n <= 0) throw new HttpError(400, `Parámetro ${name} no válido.`); return n; };
const str = (v, max = 200) => (typeof v === 'string' ? v : '').normalize('NFC').trim().slice(0, max);
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Ciclo vigente para el registro: el ciclo activo más reciente.
const currentYear = () => db().prepare('SELECT id, name FROM school_years WHERE active = 1 ORDER BY name DESC LIMIT 1').get() || null;

/**
 * Valida las filas de asignación del formulario: cada fila es un curso (existente por id o nuevo por nombre),
 * un grado y una o varias secciones de ese grado. Cada sección seleccionada será una relación independiente.
 * Los errores se devuelven por campo con la clave `rows.<índice>.<campo>`.
 */
function parseAssignmentRows(raw, { requireOne = false } = {}) {
  const rows = Array.isArray(raw) ? raw.slice(0, 60) : [];
  const fields = {};
  const items = [];
  const seen = new Map();
  const courseById = db().prepare('SELECT id, name, active FROM courses WHERE id = ?');
  const courseByKey = db().prepare('SELECT id, name, active FROM courses WHERE name_key = ?');
  const gradeById = db().prepare('SELECT id, name, active FROM grades WHERE id = ?');
  const sectionOf = db().prepare('SELECT id, name, active FROM sections WHERE id = ? AND grade_id = ?');
  rows.forEach((r, i) => {
    r = r && typeof r === 'object' ? r : {};
    const p = `rows.${i}.`;
    const courseId = Number.isInteger(Number(r.course_id)) && Number(r.course_id) > 0 ? Number(r.course_id) : null;
    const newName = courseId ? '' : U.displayCourseName(str(r.new_course_name, 100));
    const gradeId = Number.isInteger(Number(r.grade_id)) && Number(r.grade_id) > 0 ? Number(r.grade_id) : null;
    const sectionIds = Array.isArray(r.section_ids) ? [...new Set(r.section_ids.map(Number).filter(n => Number.isInteger(n) && n > 0))] : [];
    if (!courseId && !newName && !gradeId && !sectionIds.length) return; // fila vacía: se ignora
    const it = { row: i, courseId, newName: '', newKey: '', courseName: '', gradeId, gradeName: '', sections: [] };
    let courseKey = null;
    if (courseId) {
      const c = courseById.get(courseId);
      if (!c || !c.active) fields[p + 'course'] = 'El curso seleccionado no está disponible.';
      else { courseKey = `id:${c.id}`; it.courseName = c.name; }
    } else if (U.nameKey(newName).length < 2) {
      fields[p + 'course'] = 'Seleccione o cree el curso.';
    } else {
      const key = U.nameKey(newName);
      const ex = courseByKey.get(key);
      if (ex && !ex.active) fields[p + 'course'] = `El curso “${ex.name}” existe pero está desactivado. Solicite a la administración que lo active.`;
      else if (ex) { it.courseId = ex.id; it.courseName = ex.name; courseKey = `id:${ex.id}`; }
      else { it.newName = newName; it.newKey = key; it.courseName = newName; courseKey = `new:${key}`; }
    }
    const g = gradeId ? gradeById.get(gradeId) : null;
    if (!g || !g.active) fields[p + 'grade'] = gradeId ? 'El grado seleccionado no está disponible.' : 'Seleccione el grado.';
    else it.gradeName = g.name;
    if (!sectionIds.length) fields[p + 'sections'] = 'Seleccione una o varias secciones de este grado.';
    else if (g && g.active) {
      for (const sid of sectionIds) {
        const s = sectionOf.get(sid, gradeId);
        if (!s || !s.active) { fields[p + 'sections'] = 'Una de las secciones no pertenece al grado seleccionado.'; break; }
        if (courseKey) {
          const k = `${courseKey}|${sid}`;
          if (seen.has(k)) { fields[p + 'sections'] = `${it.courseName} — ${g.name} — sección ${s.name} ya está en la fila ${seen.get(k) + 1}.`; break; }
          seen.set(k, i);
        }
        it.sections.push(s);
      }
    }
    items.push(it);
  });
  if (requireOne && !items.length) fields.rows = 'Agregue al menos un curso con su grado y una sección.';
  return { items, fields };
}

/** Guarda las filas ya validadas (llamar dentro de una transacción). Crea los cursos nuevos una sola vez. */
function saveAssignmentRows(user, yearId, items) {
  const created = [], existing = [];
  const newIds = new Map();
  const find = db().prepare('SELECT id, active FROM assignments WHERE user_id = ? AND school_year_id = ? AND course_id = ? AND section_id = ?');
  for (const it of items) {
    let cid = it.courseId;
    if (!cid) {
      if (!newIds.has(it.newKey)) {
        const info = db().prepare('INSERT INTO courses(name, name_key, created_by) VALUES (?,?,?) ON CONFLICT(name_key) DO NOTHING').run(it.newName, it.newKey, user.id);
        const c = db().prepare('SELECT id FROM courses WHERE name_key = ?').get(it.newKey);
        if (info.changes) P.audit(user, 'crear_curso', 'curso', c.id, null, { name: it.newName });
        newIds.set(it.newKey, c.id);
      }
      cid = newIds.get(it.newKey);
    }
    for (const s of it.sections) {
      const label = `${it.courseName} — ${it.gradeName} — ${s.name}`;
      const prev = find.get(user.id, yearId, cid, s.id);
      if (prev && prev.active) { existing.push(label); continue; }
      if (prev) {
        db().prepare('UPDATE assignments SET active = 1 WHERE id = ?').run(prev.id);
        P.audit(user, 'reactivar_asignacion', 'asignacion', prev.id, null, null);
      } else {
        const info = db().prepare('INSERT INTO assignments(user_id, school_year_id, course_id, grade_id, section_id) VALUES (?,?,?,?,?)').run(user.id, yearId, cid, it.gradeId, s.id);
        P.audit(user, 'crear_asignacion', 'asignacion', info.lastInsertRowid, null, { courseId: cid, gradeId: it.gradeId, sectionId: s.id, yearId });
      }
      created.push(label);
    }
  }
  return { created, existing };
}

function createApp() {
  const app = express();
  if (config.trustProxy) app.set('trust proxy', 1);
  app.disable('x-powered-by');

  // Encabezados de seguridad. Vue compila las plantillas en el navegador (requiere 'unsafe-eval').
  app.use((req, res, next) => {
    res.setHeader('X-Content-Type-Options', 'nosniff');
    res.setHeader('Referrer-Policy', 'same-origin');
    res.setHeader('X-Frame-Options', 'SAMEORIGIN');
    res.setHeader('Content-Security-Policy',
      "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; " +
      "frame-src 'self' blob:; object-src 'self' blob:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
    next();
  });

  // Registro técnico mínimo (sin cuerpos de petición: nunca se registran contraseñas)
  app.use((req, res, next) => {
    const t = Date.now();
    res.on('finish', () => {
      if (req.path.startsWith('/api/')) console.log(`${new Date().toISOString()} ${req.method} ${req.path} ${res.statusCode} ${Date.now() - t}ms`);
    });
    next();
  });

  // Comprobación de salud (sin sesión): útil para el monitoreo y el healthcheck del contenedor
  app.get('/api/health', (req, res) => {
    try { db().prepare('SELECT 1').get(); res.json({ ok: true }); }
    catch { res.status(503).json({ ok: false }); }
  });

  app.use('/api', express.json({ limit: '4mb' }), auth.csrfGuard, auth.loadUser);
  app.use('/vendor/vue.js', (req, res) => res.sendFile(require.resolve('vue/dist/vue.global.prod.js')));
  // index.html se sirve con la subruta pública (BASE_PATH) incorporada en sus rutas absolutas
  const indexHtml = require('fs').readFileSync(path.join(__dirname, '..', 'public', 'index.html'), 'utf8')
    .replace(/(href|src)="\//g, `$1="${config.basePath}/`)
    .replace('content="__BASE__"', `content="${config.basePath}"`);
  app.get(['/', '/index.html'], (req, res) => { res.type('html').set('Cache-Control', 'no-cache').send(indexHtml); });
  app.use(express.static(path.join(__dirname, '..', 'public'), { index: false, maxAge: 0 }));

  // ================================================================ autenticación
  const api = express.Router();

  api.get('/auth/me', (req, res) => {
    const u = req.user;
    res.json({
      user: u ? { id: u.id, username: u.username, full_name: u.full_name, role: u.role, must_change_password: !!u.must_change_password,
        recovery_email: u.recovery_email, email_verified: !!u.email_verified } : null,
      mailEnabled: mail.enabled(),
      institution: db().prepare('SELECT short_name, full_name FROM institution WHERE id = 1').get(),
    });
  });

  // Datos públicos que necesita el formulario de registro: ciclo vigente, grados con sus secciones y catálogo de cursos.
  api.get('/auth/register-options', (req, res) => {
    const grades = db().prepare('SELECT id, name, level FROM grades WHERE active = 1 ORDER BY sort_order, name').all();
    const secs = db().prepare('SELECT id, name FROM sections WHERE grade_id = ? AND active = 1 ORDER BY name');
    grades.forEach(g => { g.sections = secs.all(g.id); });
    res.json({
      year: currentYear(), grades,
      courses: db().prepare('SELECT id, name FROM courses WHERE active = 1 ORDER BY name').all(),
      teachers: db().prepare('SELECT id, full_name, user_id IS NOT NULL AS taken FROM teachers WHERE active = 1 ORDER BY full_name').all(),
    });
  });

  // Carga académica registrada para un docente del listado: sirve para precargar sus filas en el registro.
  api.get('/auth/teachers/:id/load', wrap((req, res) => {
    const t = db().prepare('SELECT id, full_name, user_id FROM teachers WHERE id = ? AND active = 1').get(intParam(req.params.id, 'docente'));
    if (!t) throw new HttpError(404, 'El docente no está en el listado.');
    const rows = db().prepare(`SELECT l.course_id, c.name AS course_name, l.grade_id, g.name AS grade_name, l.section_id
      FROM teacher_loads l JOIN courses c ON c.id = l.course_id AND c.active = 1 JOIN grades g ON g.id = l.grade_id AND g.active = 1
      JOIN sections s ON s.id = l.section_id AND s.active = 1
      WHERE l.teacher_id = ? ORDER BY g.sort_order, g.name, c.name, s.name`).all(t.id);
    const groups = new Map();
    for (const r of rows) {
      const k = `${r.course_id}|${r.grade_id}`;
      if (!groups.has(k)) groups.set(k, { course_id: r.course_id, course_name: r.course_name, grade_id: r.grade_id, grade_name: r.grade_name, section_ids: [] });
      groups.get(k).section_ids.push(r.section_id);
    }
    res.json({ teacher: { id: t.id, full_name: t.full_name, taken: !!t.user_id }, rows: [...groups.values()] });
  }));

  /** Comprueba si un nombre de curso ya existe (coincidencia normalizada) y sugiere similares sin fusionar. */
  api.get('/courses/check', (req, res) => {
    const name = U.cleanName(str(req.query.name, 100));
    const key = U.nameKey(name);
    const exact = key ? db().prepare('SELECT id, name, active FROM courses WHERE name_key = ?').get(key) : null;
    const similar = key.length >= 3 ? db().prepare('SELECT id, name, name_key FROM courses WHERE active = 1').all()
      .filter(c => c.name_key !== key && (c.name_key.includes(key) || key.includes(c.name_key))).slice(0, 5).map(({ id, name }) => ({ id, name })) : [];
    res.json({ exact, similar, display: U.displayCourseName(name) });
  });

  // Registro completo en una sola operación: cuenta + cursos nuevos + relaciones curso/grado/sección.
  // Si algo falla no queda nada guardado (transacción).
  api.post('/auth/register', wrap((req, res) => {
    const full_name = U.cleanName(str(req.body.full_name, 150));
    const username = str(req.body.username, 60);
    const password = typeof req.body.password === 'string' ? req.body.password : '';
    const confirm = typeof req.body.password_confirm === 'string' ? req.body.password_confirm : '';
    const email = str(req.body.recovery_email, 200).toLowerCase();
    const teacherId = Number.isInteger(Number(req.body.teacher_id)) && Number(req.body.teacher_id) > 0 ? Number(req.body.teacher_id) : null;
    const fields = {};
    if (full_name.length < 5) fields.full_name = 'Escriba su nombre completo.';
    if (teacherId) {
      const t = db().prepare('SELECT user_id, active FROM teachers WHERE id = ?').get(teacherId);
      if (!t || !t.active) fields.full_name = 'El docente seleccionado no está en el listado.';
      else if (t.user_id) fields.full_name = 'Este docente ya tiene una cuenta. Inicie sesión o solicite ayuda a la administración.';
    }
    if (!/^[A-Za-z0-9._@-]{3,40}$/.test(username)) fields.username = 'Use de 3 a 40 caracteres: letras sin tilde, números, punto, arroba, guion o guion bajo.';
    else if (db().prepare('SELECT 1 FROM users WHERE username_key = ?').get(username.toLowerCase())) fields.username = 'Ese nombre de usuario ya está registrado. Si ya tiene cuenta, inicie sesión.';
    const pw = U.passwordProblems(password);
    if (pw.length) fields.password = pw.join(' ');
    if (password !== confirm) fields.password_confirm = 'La confirmación no coincide con la contraseña.';
    if (email && !EMAIL_RE.test(email)) fields.recovery_email = 'El correo no tiene un formato válido.';
    const year = currentYear();
    if (!year) throw new HttpError(422, 'La administración aún no ha configurado el ciclo escolar. Intente registrarse más tarde.');
    const parsed = parseAssignmentRows(req.body.assignments, { requireOne: true });
    Object.assign(fields, parsed.fields);
    if (Object.keys(fields).length) throw new HttpError(422, 'Revise los campos marcados.', { fields });
    const key = username.toLowerCase();
    let userId, saved;
    try {
      db().transaction(() => {
        userId = db().prepare(`INSERT INTO users(username, username_key, password_hash, full_name, role, recovery_email)
          VALUES (?,?,?,?, 'docente', ?)`).run(username, key, U.hashPassword(password), full_name, email || null).lastInsertRowid;
        const user = { id: userId, role: 'docente' };
        P.audit(user, 'registro', 'usuario', userId, null, teacherId ? { docente_listado: teacherId } : null);
        if (teacherId && !db().prepare('UPDATE teachers SET user_id = ? WHERE id = ? AND user_id IS NULL').run(userId, teacherId).changes) {
          throw new HttpError(422, 'Revise los campos marcados.', { fields: { full_name: 'Este docente ya tiene una cuenta. Inicie sesión o solicite ayuda a la administración.' } });
        }
        saved = saveAssignmentRows(user, year.id, parsed.items);
      })();
    } catch (e) {
      if (String(e.code).startsWith('SQLITE_CONSTRAINT') && /users/.test(e.message)) throw new HttpError(422, 'Revise los campos marcados.', { fields: { username: 'Ese nombre de usuario ya está registrado. Si ya tiene cuenta, inicie sesión.' } });
      throw e;
    }
    auth.createSession(res, userId, req.get('user-agent'));
    if (email && mail.enabled()) sendVerification(userId, email).catch(err => console.error('[correo] verificación no enviada:', err.message));
    res.status(201).json({ ok: true, school_year: year, assignments: saved.created });
  }));

  api.post('/auth/login', wrap((req, res) => {
    const username = str(req.body.username, 60);
    const password = typeof req.body.password === 'string' ? req.body.password : '';
    const key = `${username.toLowerCase()}|${req.ip}`;
    const wait = auth.loginBlocked(key);
    if (wait) throw new HttpError(429, `Demasiados intentos fallidos. Espere ${wait} minuto(s) e intente de nuevo.`);
    const u = db().prepare('SELECT id, password_hash, active, role FROM users WHERE username_key = ?').get(username.toLowerCase());
    const ok = U.verifyPassword(password, u ? u.password_hash : U.DUMMY_HASH);
    if (!u || !ok) { auth.loginFailed(key); throw new HttpError(401, 'Usuario o contraseña incorrectos.'); }
    if (!u.active) throw new HttpError(403, 'Su cuenta está desactivada. Comuníquese con la administración del colegio.');
    auth.loginOk(key);
    auth.createSession(res, u.id, req.get('user-agent'), { remember: req.body.remember === true });
    P.audit({ id: u.id, role: u.role }, 'inicio_sesion', 'usuario', u.id, null, null);
    res.json({ ok: true });
  }));

  api.post('/auth/logout', (req, res) => { auth.destroySession(req, res); res.json({ ok: true }); });

  api.post('/auth/forgot', wrap(async (req, res) => {
    if (!mail.enabled()) {
      return res.json({ mailEnabled: false, message: 'La recuperación por correo no está configurada en este servidor. Solicite a la administración del colegio el restablecimiento de su acceso.' });
    }
    const username = str(req.body.username, 60).toLowerCase();
    const u = db().prepare('SELECT id, recovery_email, email_verified, active FROM users WHERE username_key = ?').get(username);
    let sent = false;
    if (u && u.active && u.recovery_email && u.email_verified) {
      const token = U.randomToken(32);
      db().prepare(`INSERT INTO password_resets(user_id, token_hash, kind, expires_at) VALUES (?,?, 'email', ?)`)
        .run(u.id, U.sha256(token), new Date(Date.now() + 3600e3).toISOString());
      try {
        await mail.send(u.recovery_email, 'Restablecer contraseña — Planificación Docente CyD',
          `Recibimos una solicitud para restablecer su contraseña.\n\nAbra este enlace (válido por 1 hora):\n${config.publicUrl}/#/restablecer?token=${token}\n\nSi usted no lo solicitó, ignore este mensaje.`);
        sent = true;
      } catch (e) { console.error('[correo] error al enviar recuperación:', e.message); throw new HttpError(502, 'No se pudo enviar el correo en este momento. Intente más tarde o solicite el restablecimiento a la administración.'); }
    }
    // Respuesta uniforme para no revelar qué cuentas existen.
    res.json({ mailEnabled: true, message: 'Si la cuenta existe y tiene un correo de recuperación verificado, se envió un enlace para restablecer la contraseña. Si no recibe el correo, solicite el restablecimiento a la administración del colegio.', _sent: process.env.NODE_ENV === 'test' ? sent : undefined });
  }));

  api.post('/auth/reset', wrap((req, res) => {
    const token = str(req.body.token, 100);
    const password = typeof req.body.password === 'string' ? req.body.password : '';
    if (password !== req.body.password_confirm) throw new HttpError(422, 'La confirmación no coincide.', { fields: { password_confirm: 'La confirmación no coincide.' } });
    const pw = U.passwordProblems(password);
    if (pw.length) throw new HttpError(422, pw.join(' '), { fields: { password: pw.join(' ') } });
    const r = db().prepare('SELECT * FROM password_resets WHERE token_hash = ?').get(U.sha256(token));
    if (!r || r.used_at || r.expires_at < new Date().toISOString()) throw new HttpError(400, 'El enlace no es válido o ya expiró. Solicite uno nuevo.');
    db().transaction(() => {
      db().prepare("UPDATE password_resets SET used_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(r.id);
      db().prepare("UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(U.hashPassword(password), r.user_id);
      db().prepare('DELETE FROM sessions WHERE user_id = ?').run(r.user_id);
    })();
    P.audit({ id: r.user_id, role: 'docente' }, 'restablecer_contrasena_correo', 'usuario', r.user_id, null, null);
    res.json({ ok: true });
  }));

  api.get('/auth/verify-email', wrap((req, res) => {
    const token = str(req.query.token, 100);
    const r = db().prepare('SELECT * FROM email_verifications WHERE token_hash = ?').get(U.sha256(token));
    if (!r || r.used_at || r.expires_at < new Date().toISOString()) return res.redirect('/#/perfil?correo=invalido');
    db().transaction(() => {
      db().prepare("UPDATE email_verifications SET used_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(r.id);
      db().prepare('UPDATE users SET email_verified = 1 WHERE id = ? AND recovery_email = ?').run(r.user_id, r.email);
    })();
    res.redirect('/#/perfil?correo=verificado');
  }));

  async function sendVerification(userId, email) {
    const token = U.randomToken(32);
    db().prepare('INSERT INTO email_verifications(user_id, email, token_hash, expires_at) VALUES (?,?,?,?)')
      .run(userId, email, U.sha256(token), new Date(Date.now() + 48 * 3600e3).toISOString());
    await mail.send(email, 'Verifique su correo de recuperación — Planificación Docente CyD',
      `Para confirmar este correo como medio de recuperación de su cuenta, abra el enlace:\n${config.publicUrl}/api/auth/verify-email?token=${token}\n\nEl enlace vence en 48 horas.`);
  }

  // ================================================================ perfil
  api.put('/me/password', (req, _res, next) => { req.allowPendingPassword = true; next(); }, auth.requireAuth, wrap((req, res) => {
    const u = db().prepare('SELECT password_hash FROM users WHERE id = ?').get(req.user.id);
    if (!U.verifyPassword(req.body.current_password || '', u.password_hash)) throw new HttpError(422, 'La contraseña actual no es correcta.', { fields: { current_password: 'La contraseña actual no es correcta.' } });
    const password = typeof req.body.password === 'string' ? req.body.password : '';
    const pw = U.passwordProblems(password);
    if (pw.length) throw new HttpError(422, pw.join(' '), { fields: { password: pw.join(' ') } });
    if (password !== req.body.password_confirm) throw new HttpError(422, 'La confirmación no coincide.', { fields: { password_confirm: 'La confirmación no coincide.' } });
    if (U.verifyPassword(password, u.password_hash)) throw new HttpError(422, 'La nueva contraseña debe ser distinta a la actual.', { fields: { password: 'Debe ser distinta a la actual.' } });
    db().prepare("UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(U.hashPassword(password), req.user.id);
    db().prepare('DELETE FROM sessions WHERE user_id = ? AND id <> ?').run(req.user.id, req.user.sid);
    P.audit(req.user, 'cambio_contrasena', 'usuario', req.user.id, null, null);
    res.json({ ok: true });
  }));

  // Logo institucional: público (se muestra en la pantalla de ingreso)
  api.get('/institution/logo', (req, res) => {
    const r = db().prepare('SELECT logo, logo_mime FROM institution WHERE id = 1').get();
    if (!r || !r.logo) return res.status(404).end();
    res.setHeader('Content-Type', r.logo_mime);
    res.send(Buffer.from(r.logo));
  });

  api.use(auth.requireAuth);

  api.put('/me/profile', wrap((req, res) => {
    const full_name = U.cleanName(str(req.body.full_name, 150));
    if (full_name.length < 5) throw new HttpError(422, 'Escriba su nombre completo.', { fields: { full_name: 'Escriba su nombre completo.' } });
    db().prepare("UPDATE users SET full_name = ?, updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(full_name, req.user.id);
    P.audit(req.user, 'actualizar_perfil', 'usuario', req.user.id, null, { full_name });
    res.json({ ok: true });
  }));

  api.put('/me/email', wrap(async (req, res) => {
    const email = str(req.body.email, 200).toLowerCase();
    if (email && !EMAIL_RE.test(email)) throw new HttpError(422, 'El correo no tiene un formato válido.', { fields: { email: 'Formato no válido.' } });
    db().prepare('UPDATE users SET recovery_email = ?, email_verified = 0 WHERE id = ?').run(email || null, req.user.id);
    let verificationSent = false;
    if (email && mail.enabled()) {
      try { await sendVerification(req.user.id, email); verificationSent = true; }
      catch (e) { console.error('[correo]', e.message); throw new HttpError(502, 'El correo se guardó, pero no se pudo enviar el mensaje de verificación. Intente nuevamente más tarde.'); }
    }
    res.json({ ok: true, verificationSent, mailEnabled: mail.enabled() });
  }));

  // ================================================================ catálogos (lectura)
  api.get('/school-years', (req, res) => {
    const all = req.user.role === 'admin';
    const years = db().prepare(`SELECT id, name, active FROM school_years ${all ? '' : 'WHERE active = 1'} ORDER BY name DESC`).all();
    const bims = db().prepare('SELECT id, number, start_date, end_date FROM bimesters WHERE school_year_id = ? ORDER BY number');
    years.forEach(y => { y.bimesters = bims.all(y.id); });
    res.json(years);
  });

  api.get('/grades', (req, res) => {
    const all = req.user.role === 'admin';
    const grades = db().prepare(`SELECT id, name, level, sort_order, active FROM grades ${all ? '' : 'WHERE active = 1'} ORDER BY sort_order, name`).all();
    const secs = db().prepare(`SELECT id, name, active FROM sections WHERE grade_id = ? ${all ? '' : 'AND active = 1'} ORDER BY name`);
    grades.forEach(g => { g.sections = secs.all(g.id); });
    res.json(grades);
  });

  api.get('/courses', (req, res) => {
    const q = U.nameKey(str(req.query.q, 100));
    const rows = db().prepare(`SELECT c.id, c.name, c.name_key, c.created_by,
        (SELECT COUNT(DISTINCT user_id) FROM assignments a WHERE a.course_id = c.id) AS teachers
      FROM courses c WHERE c.active = 1 ORDER BY c.name`).all();
    res.json(rows.filter(r => !q || r.name_key.includes(q)).map(({ name_key, ...r }) => ({ ...r, mine: r.created_by === req.user.id })));
  });

  api.post('/courses', wrap((req, res) => {
    const name = U.displayCourseName(str(req.body.name, 100));
    const key = U.nameKey(name);
    if (key.length < 2) throw new HttpError(422, 'Escriba el nombre del curso.', { fields: { name: 'Escriba el nombre del curso.' } });
    // INSERT con conflicto ignorado: si dos docentes crean el mismo curso a la vez, ambos obtienen el mismo registro.
    const info = db().prepare('INSERT INTO courses(name, name_key, created_by) VALUES (?,?,?) ON CONFLICT(name_key) DO NOTHING').run(name, key, req.user.id);
    const course = db().prepare('SELECT id, name, active FROM courses WHERE name_key = ?').get(key);
    if (!course.active) throw new HttpError(422, `El curso “${course.name}” existe pero está desactivado. Solicite a la administración que lo active.`);
    if (info.changes) P.audit(req.user, 'crear_curso', 'curso', course.id, null, { name });
    res.status(info.changes ? 201 : 200).json({ course, created: info.changes > 0 });
  }));

  // El docente sólo puede corregir o eliminar un curso que él creó y que nadie más utiliza.
  function ownUnsharedCourse(user, id) {
    const c = db().prepare('SELECT * FROM courses WHERE id = ?').get(id);
    if (!c) throw new HttpError(404, 'El curso no existe.');
    const others = db().prepare('SELECT COUNT(*) n FROM assignments WHERE course_id = ? AND user_id <> ?').get(id, user.id).n;
    if (c.created_by !== user.id || others > 0) throw new HttpError(403, 'Este curso es compartido. Sólo la administración puede renombrarlo o eliminarlo.');
    return c;
  }
  api.put('/courses/:id', wrap((req, res) => {
    const c = ownUnsharedCourse(req.user, intParam(req.params.id, 'id'));
    const name = U.displayCourseName(str(req.body.name, 100));
    const key = U.nameKey(name);
    if (key.length < 2) throw new HttpError(422, 'Escriba el nombre del curso.');
    try { db().prepare('UPDATE courses SET name = ?, name_key = ? WHERE id = ?').run(name, key, c.id); }
    catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe un curso con ese nombre. Selecciónelo en lugar de renombrar.'); throw e; }
    P.audit(req.user, 'renombrar_curso', 'curso', c.id, null, { de: c.name, a: name });
    res.json({ ok: true });
  }));
  api.delete('/courses/:id', wrap((req, res) => {
    const c = ownUnsharedCourse(req.user, intParam(req.params.id, 'id'));
    const n = db().prepare('SELECT COUNT(*) n FROM assignments WHERE course_id = ?').get(c.id).n;
    if (n) throw new HttpError(409, 'Primero elimine sus asignaciones que usan este curso.');
    db().prepare('DELETE FROM courses WHERE id = ?').run(c.id);
    P.audit(req.user, 'eliminar_curso', 'curso', c.id, null, { name: c.name });
    res.json({ ok: true });
  }));

  // ================================================================ asignaciones del docente
  const teacherOnly = auth.requireRole('docente');

  api.get('/me/assignments', teacherOnly, wrap((req, res) => {
    const yearId = intParam(req.query.school_year_id, 'ciclo');
    res.json(db().prepare(`SELECT a.id, a.course_id, c.name AS course_name, a.grade_id, g.name AS grade_name, g.level AS grade_level,
        a.section_id, s.name AS section_name, a.active, (SELECT COUNT(*) FROM plans p WHERE p.assignment_id = a.id) AS plans
      FROM assignments a JOIN courses c ON c.id = a.course_id JOIN grades g ON g.id = a.grade_id JOIN sections s ON s.id = a.section_id
      WHERE a.user_id = ? AND a.school_year_id = ? ORDER BY c.name, g.sort_order, g.name, s.name`).all(req.user.id, yearId));
  }));

  api.post('/me/assignments', teacherOnly, wrap((req, res) => {
    const yearId = intParam(req.body.school_year_id, 'ciclo');
    const courseId = intParam(req.body.course_id, 'curso');
    const gradeId = intParam(req.body.grade_id, 'grado');
    const sectionIds = Array.isArray(req.body.section_ids) ? [...new Set(req.body.section_ids.map(Number))] : [];
    if (!sectionIds.length) throw new HttpError(422, 'Seleccione al menos una sección.', { fields: { sections: 'Seleccione al menos una sección.' } });
    const year = db().prepare('SELECT id, active FROM school_years WHERE id = ?').get(yearId);
    if (!year || !year.active) throw new HttpError(422, 'El ciclo escolar no está disponible.');
    const course = db().prepare('SELECT id, active FROM courses WHERE id = ?').get(courseId);
    if (!course || !course.active) throw new HttpError(422, 'El curso seleccionado no está disponible.');
    const grade = db().prepare('SELECT id, active FROM grades WHERE id = ?').get(gradeId);
    if (!grade || !grade.active) throw new HttpError(422, 'El grado seleccionado no está disponible.');
    const created = [], existing = [];
    db().transaction(() => {
      for (const sid of sectionIds) {
        const sec = db().prepare('SELECT id, name, active FROM sections WHERE id = ? AND grade_id = ?').get(sid, gradeId);
        if (!sec || !sec.active) throw new HttpError(422, 'Una de las secciones no pertenece al grado seleccionado.');
        const prev = db().prepare('SELECT id, active FROM assignments WHERE user_id = ? AND school_year_id = ? AND course_id = ? AND section_id = ?').get(req.user.id, yearId, courseId, sid);
        if (prev && prev.active) { existing.push(sec.name); continue; }
        if (prev) {
          db().prepare('UPDATE assignments SET active = 1 WHERE id = ?').run(prev.id);
          P.audit(req.user, 'reactivar_asignacion', 'asignacion', prev.id, null, null);
        } else {
          const info = db().prepare('INSERT INTO assignments(user_id, school_year_id, course_id, grade_id, section_id) VALUES (?,?,?,?,?)').run(req.user.id, yearId, courseId, gradeId, sid);
          P.audit(req.user, 'crear_asignacion', 'asignacion', info.lastInsertRowid, null, { courseId, gradeId, sectionId: sid, yearId });
        }
        created.push(sec.name);
      }
    })();
    res.status(201).json({ created, existing });
  }));

  // Varias filas (curso existente o nuevo + grado + secciones) en una sola operación, igual que en el registro.
  api.post('/me/assignments/batch', teacherOnly, wrap((req, res) => {
    const yearId = intParam(req.body.school_year_id, 'ciclo');
    const year = db().prepare('SELECT id, active FROM school_years WHERE id = ?').get(yearId);
    if (!year || !year.active) throw new HttpError(422, 'El ciclo escolar no está disponible.');
    const parsed = parseAssignmentRows(req.body.rows, { requireOne: true });
    if (Object.keys(parsed.fields).length) throw new HttpError(422, 'Revise los campos marcados.', { fields: parsed.fields });
    let saved;
    db().transaction(() => { saved = saveAssignmentRows(req.user, yearId, parsed.items); })();
    res.status(201).json(saved);
  }));

  // Quitar: si la asignación ya tiene planificaciones no se elimina; se desactiva y se conserva el historial.
  api.delete('/me/assignments/:id', teacherOnly, wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const a = db().prepare('SELECT id FROM assignments WHERE id = ? AND user_id = ?').get(id, req.user.id);
    if (!a) throw new HttpError(404, 'La asignación no existe.');
    const n = db().prepare('SELECT COUNT(*) n FROM plans WHERE assignment_id = ?').get(id).n;
    if (n) {
      db().prepare('UPDATE assignments SET active = 0 WHERE id = ?').run(id);
      P.audit(req.user, 'desactivar_asignacion', 'asignacion', id, null, { planes: n });
      return res.json({ ok: true, deactivated: true, plans: n });
    }
    db().prepare('DELETE FROM assignments WHERE id = ?').run(id);
    P.audit(req.user, 'eliminar_asignacion', 'asignacion', id, null, null);
    res.json({ ok: true, deactivated: false });
  }));

  api.put('/me/assignments/:id/active', teacherOnly, wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const a = db().prepare('SELECT id FROM assignments WHERE id = ? AND user_id = ?').get(id, req.user.id);
    if (!a) throw new HttpError(404, 'La asignación no existe.');
    const active = req.body.active ? 1 : 0;
    db().prepare('UPDATE assignments SET active = ? WHERE id = ?').run(active, id);
    P.audit(req.user, active ? 'reactivar_asignacion' : 'desactivar_asignacion', 'asignacion', id, null, null);
    res.json({ ok: true });
  }));

  // Resumen del docente: asignaciones × bimestres con estado
  api.get('/me/dashboard', teacherOnly, wrap((req, res) => {
    const yearId = intParam(req.query.school_year_id, 'ciclo');
    res.json(dashboardRows({ userId: req.user.id, yearId }));
  }));

  function dashboardRows({ userId, yearId }) {
    const rows = db().prepare(`SELECT a.id AS assignment_id, a.user_id, a.active, u.full_name AS teacher_name, u.active AS teacher_active,
        c.name AS course_name, g.name AS grade_name, s.name AS section_name
      FROM assignments a JOIN users u ON u.id = a.user_id JOIN courses c ON c.id = a.course_id JOIN grades g ON g.id = a.grade_id
      JOIN sections s ON s.id = a.section_id
      WHERE a.school_year_id = ? AND (a.active = 1 OR EXISTS (SELECT 1 FROM plans p WHERE p.assignment_id = a.id)) ${userId ? 'AND a.user_id = ?' : ''} ORDER BY u.full_name, c.name, g.sort_order, g.name, s.name`)
      .all(...(userId ? [yearId, userId] : [yearId]));
    const bims = db().prepare('SELECT id, number, start_date, end_date FROM bimesters WHERE school_year_id = ? ORDER BY number').all(yearId);
    const planStmt = db().prepare('SELECT id, status, version, ready_at, updated_at FROM plans WHERE assignment_id = ? AND bimester_id = ?');
    const docStmt = db().prepare('SELECT MAX(generated_at) AS last FROM generated_documents WHERE plan_id = ?');
    for (const r of rows) {
      r.bimesters = bims.map(b => {
        const p = planStmt.get(r.assignment_id, b.id);
        if (!p) return { bimester_id: b.id, number: b.number, status: 'sin_iniciar' };
        const ctx = P.planContext(p.id);
        const { completeness, errors } = P.validate(P.loadTree(p.id), ctx);
        return { bimester_id: b.id, number: b.number, plan_id: p.id, status: p.status, ready_at: p.ready_at, updated_at: p.updated_at,
          completeness, errors, last_document: docStmt.get(p.id).last };
      });
    }
    return { bimesters: bims, rows };
  }

  // ================================================================ planes
  api.post('/plans', teacherOnly, wrap((req, res) => {
    const assignmentId = intParam(req.body.assignment_id, 'asignación');
    const bimesterId = intParam(req.body.bimester_id, 'bimestre');
    const a = db().prepare('SELECT id, school_year_id, active FROM assignments WHERE id = ? AND user_id = ?').get(assignmentId, req.user.id);
    if (!a) throw new HttpError(404, 'La asignación no existe.');
    const b = db().prepare('SELECT id FROM bimesters WHERE id = ? AND school_year_id = ?').get(bimesterId, a.school_year_id);
    if (!b) throw new HttpError(422, 'El bimestre no pertenece al ciclo de la asignación.');
    const exists = db().prepare('SELECT id FROM plans WHERE assignment_id = ? AND bimester_id = ?').get(assignmentId, bimesterId);
    if (exists) return res.json({ id: exists.id, existed: true });
    if (!a.active) throw new HttpError(422, 'Esta asignación está desactivada. Reactívela en “Mis cursos” para crear planificaciones nuevas.');
    let id;
    db().transaction(() => {
      const info = db().prepare('INSERT INTO plans(assignment_id, school_year_id, bimester_id, updated_by) VALUES (?,?,?,?) ON CONFLICT(assignment_id, bimester_id) DO NOTHING')
        .run(assignmentId, a.school_year_id, bimesterId, req.user.id);
      id = info.changes ? info.lastInsertRowid : db().prepare('SELECT id FROM plans WHERE assignment_id = ? AND bimester_id = ?').get(assignmentId, bimesterId).id;
      if (info.changes) {
        const ins = db().prepare('INSERT INTO extra_sections(id, plan_id, kind) VALUES (?,?,?)');
        for (const k of P.EXTRA_KINDS) ins.run(U.uuid(), id, k);
        P.audit(req.user, 'crear', 'plan', id, id, null);
      }
    })();
    res.status(201).json({ id, existed: false });
  }));

  function planPayload(ctx, user) {
    const tree = P.loadTree(ctx.id);
    const v = P.validate(tree, ctx);
    const docs = db().prepare('SELECT id, plan_version, generated_at, filename FROM generated_documents WHERE plan_id = ? ORDER BY generated_at DESC, id DESC').all(ctx.id);
    docs.forEach((d, i) => { d.latest = i === 0; d.current = d.plan_version === ctx.version; });
    const { pdf_hash, ...general } = ctx;
    return { general, tree, validation: v, totals: P.periodTotals(tree), documents: docs, readonly: user.role !== 'docente', coordination: coordination(ctx.id) };
  }

  api.get('/plans/:id', wrap((req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'));
    if (req.user.role === 'admin') P.audit(req.user, 'consulta_admin', 'plan', ctx.id, ctx.id, null);
    res.json(planPayload(ctx, req.user));
  }));

  api.put('/plans/:id', teacherOnly, wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    P.assertAccess(req.user, id, { write: true });
    const version = Number(req.body.version);
    if (!Number.isInteger(version)) throw new HttpError(400, 'Falta la versión de la planificación.');
    const tree = P.sanitize(req.body.tree);
    const r = P.saveTree(id, tree, version, req.user, { allowReadyEdit: req.body.edit_ready === true });
    const ctx = P.planContext(id);
    res.json({ ...r, validation: P.validate(tree, ctx), totals: P.periodTotals(tree) });
  }));

  api.post('/plans/:id/edit-ready', teacherOnly, wrap((req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'), { write: true });
    if (ctx.status !== 'listo') return res.json({ ok: true });
    P.audit(req.user, 'habilitar_edicion', 'plan', ctx.id, ctx.id, { nota: 'El plan vuelve a borrador si cambia información de la hoja de actividades.' });
    res.json({ ok: true });
  }));

  api.post('/plans/:id/ready', teacherOnly, wrap((req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'), { write: true });
    if (Number(req.body.version) !== ctx.version) throw new HttpError(409, 'Hay cambios más recientes. Guarde o recargue antes de marcar como listo.', { code: 'VERSION_CONFLICT' });
    const tree = P.loadTree(ctx.id);
    const v = P.validate(tree, ctx);
    if (v.errors) throw new HttpError(422, 'La planificación tiene datos pendientes o incorrectos.', { validation: v });
    db().prepare(`UPDATE plans SET status = 'listo', ready_at = strftime('%Y-%m-%dT%H:%M:%fZ','now'), pdf_hash = ?, version = version + 1,
      updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now'), updated_by = ? WHERE id = ?`).run(P.pdfHash(tree), req.user.id, ctx.id);
    P.audit(req.user, 'cambio_estado', 'plan', ctx.id, ctx.id, { de: ctx.status, a: 'listo' });
    res.json(planPayload(P.planContext(ctx.id), req.user));
  }));

  api.get('/plans/:id/history', wrap((req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'));
    res.json(db().prepare(`SELECT l.at, l.action, l.user_role, l.details, u.full_name AS user_name FROM audit_log l
      LEFT JOIN users u ON u.id = l.user_id WHERE l.plan_id = ? ORDER BY l.id DESC LIMIT 200`).all(ctx.id));
  }));

  // Entrega institucional, observaciones y revisiones de coordinación (datos reales; nada se deduce del estado del plan)
  function coordination(planId) {
    const p = db().prepare(`SELECT p.delivered_at, p.delivered_version, p.coord_observations, p.coord_observations_at, u.full_name AS coord_observations_by
      FROM plans p LEFT JOIN users u ON u.id = p.coord_observations_by WHERE p.id = ?`).get(planId);
    const reviews = db().prepare('SELECT id, reviewer_name, result, plan_version, reviewed_at FROM plan_reviews WHERE plan_id = ? ORDER BY id DESC').all(planId);
    return { ...p, reviews };
  }

  // ---------------------------------------------------------------- PDF
  const safe = s => U.nameKey(s).replace(/[^a-z0-9]+/g, ' ').trim().split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join('_');
  const dmy = d => { if (!d) return ''; const [y, m, dd] = String(d).slice(0, 10).split('-'); return `${dd}/${m}/${y}`; };

  /** Datos del «Plan bimestral en PDF», tomados sólo de este plan y de sus registros institucionales. */
  function planSnapshot(ctx, tree) {
    const inst = db().prepare('SELECT full_name FROM institution WHERE id = 1').get();
    const co = coordination(ctx.id);
    const rev = co.reviews[0] || null;
    const filled = s => typeof s === 'string' && s.trim().length > 0;
    // Plan de mejoramiento: la primera acción es el apartado «Actividad de mejoramiento» (misma captura que la hoja)
    const m = tree.extras.mejoramiento;
    const improvement = [];
    if (m.applies === true && ([m.activity, m.strategies, m.students, m.due_text].some(filled) || !!m.due_date)) {
      improvement.push({ description: m.activity.trim(), strategies: m.strategies.trim(), students: m.students.trim(), date: m.due_date ? dmy(m.due_date) : m.due_text.trim() });
    }
    for (const a of tree.improvement_actions) {
      if ([a.description, a.strategies, a.students].some(filled) || a.action_date) improvement.push({ description: a.description.trim(), strategies: a.strategies.trim(), students: a.students.trim(), date: dmy(a.action_date) });
    }
    if (!improvement.length && m.applies === false) improvement.push({ description: 'No aplica', strategies: '', students: '', date: '' });
    // Resultado: sólo estados reales. «Sin entregar» porque no existe una entrega registrada.
    const result_label = rev ? planPdf.RESULT[rev.result] : co.delivered_at ? 'Entregado — pendiente de revisión' : 'Sin entregar';
    return {
      filename: `Plan_Bimestral_${safe(ctx.school_year_name)}_${safe(ctx.course_name)}_${safe(ctx.grade_name)}_${safe(ctx.section_name)}_Bim_${ctx.bimester_number}.pdf`,
      header: {
        institution_name: inst.full_name, teacher_name: ctx.teacher_name, course_name: ctx.course_name, grade_name: ctx.grade_name,
        section_name: ctx.section_name, bimester_number: ctx.bimester_number, school_year_name: ctx.school_year_name,
        bimester_start: ctx.bimester_start, bimester_end: ctx.bimester_end,
      },
      plan: {
        competencies: tree.competencies.map(c => c.text.trim()), indicators: tree.indicators.map(c => c.text.trim()),
        blocks: tree.blocks.map(b => ({
          label: b.label.trim(), start_date: b.start_date, end_date: b.end_date,
          evaluation: { description: b.evaluation.description.trim() },
          weeks: b.weeks.map(w => ({ number: w.number, start_date: w.start_date, end_date: w.end_date,
            contents: w.contents.map(c => ({ topic: c.topic.trim(), pages: c.pages.trim(), periods: c.periods, activities: c.activities.map(a => ({ text: a.text.trim() })) })) })),
        })),
        improvement,
      },
      review: {
        delivered_at: co.delivered_at, observations: co.coord_observations.trim(),
        reviewed_at: rev ? rev.reviewed_at : null, reviewer_name: rev ? rev.reviewer_name : '', result_label,
      },
    };
  }

  // Plan bimestral: la vista previa y la descarga son el mismo PDF, construido con la versión guardada del plan.
  api.get('/plans/:id/plan-bimestral.pdf', wrap(async (req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'));
    const snap = planSnapshot(ctx, P.loadTree(ctx.id));
    const buf = await planPdf.generate(snap);
    if (!buf || buf.length < 500) throw new HttpError(500, 'No se pudo generar el PDF del plan bimestral.');
    P.audit(req.user, req.user.role === 'admin' ? 'plan_bimestral_pdf_admin' : 'plan_bimestral_pdf', 'plan', ctx.id, ctx.id, { version: ctx.version });
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `${req.query.download ? 'attachment' : 'inline'}; filename="${snap.filename}"`);
    res.setHeader('Cache-Control', 'no-store');
    res.setHeader('X-Plan-Version', String(ctx.version));
    res.send(buf);
  }));

  // Entrega del plan a coordinación: acción explícita y opcional del docente (no es requisito para descargar).
  api.post('/plans/:id/deliver', teacherOnly, wrap((req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'), { write: true });
    if (ctx.status !== 'listo') throw new HttpError(422, 'Marque la planificación como lista antes de entregarla a coordinación.');
    db().prepare("UPDATE plans SET delivered_at = strftime('%Y-%m-%dT%H:%M:%fZ','now'), delivered_version = version WHERE id = ?").run(ctx.id);
    P.audit(req.user, 'entregar_plan', 'plan', ctx.id, ctx.id, { version: ctx.version });
    res.json({ ok: true, coordination: coordination(ctx.id) });
  }));

  function buildSnapshot(ctx, tree, draft) {
    const inst = db().prepare('SELECT * FROM institution WHERE id = 1').get();
    const filename = `Hoja_Actividades_${safe(ctx.course_name)}_${safe(ctx.grade_name)}_${safe(ctx.section_name)}_Bimestre_${ctx.bimester_number}_${safe(ctx.school_year_name)}.pdf`;
    return {
      filename, draft, plan_id: ctx.id, plan_version: ctx.version,
      header: {
        short_name: inst.short_name, full_name: inst.full_name, header_line1: inst.header_line1, header_line2: inst.header_line2,
        motto: inst.motto, sheet_title: inst.sheet_title, contents_banner: inst.contents_banner,
        default_observations: inst.default_observations, note_text: inst.note_text,
        sign_teacher: inst.sign_teacher, sign_parent: inst.sign_parent, sign_direction: inst.sign_direction,
        logo: inst.logo ? Buffer.from(inst.logo).toString('base64') : null, logo_mime: inst.logo_mime,
        teacher_name: ctx.teacher_name, course_name: ctx.course_name, grade_name: ctx.grade_name, section_name: ctx.section_name,
        bimester_number: ctx.bimester_number, school_year_name: ctx.school_year_name,
      },
      projection: P.pdfProjection(tree),
    };
  }

  // Vista previa: se genera con la información guardada (sin marcas de borrador en el documento).
  api.get('/plans/:id/preview.pdf', wrap(async (req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'));
    const tree = P.loadTree(ctx.id);
    const snap = buildSnapshot(ctx, tree, ctx.status !== 'listo');
    const buf = await pdf.generate(snap);
    if (req.user.role === 'admin') P.audit(req.user, 'vista_previa_admin', 'plan', ctx.id, ctx.id, null);
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `inline; filename="${snap.filename}"`);
    res.setHeader('Cache-Control', 'no-store');
    res.setHeader('X-Plan-Version', String(ctx.version));
    res.send(buf);
  }));

  // Documento final: sólo con plan listo, versión guardada y sin incidencias. Se conserva el PDF generado.
  api.post('/plans/:id/documents', wrap(async (req, res) => {
    const ctx = P.assertAccess(req.user, intParam(req.params.id, 'id'));
    if (Number(req.body.version) !== ctx.version) throw new HttpError(409, 'La planificación cambió desde que la abrió. Recargue antes de generar.', { code: 'VERSION_CONFLICT' });
    if (ctx.status !== 'listo') throw new HttpError(422, 'Marque la planificación como lista antes de generar la hoja final. Mientras tanto puede usar la vista previa de borrador.');
    const tree = P.loadTree(ctx.id);
    const v = P.validate(tree, ctx);
    if (v.errors) throw new HttpError(422, 'La planificación tiene datos pendientes.', { validation: v });
    if (P.pdfHash(tree) !== ctx.pdf_hash) throw new HttpError(409, 'La información de la hoja cambió después de marcarla como lista. Vuelva a validarla.');
    const snap = buildSnapshot(ctx, tree, false);
    const buf = await pdf.generate(snap);
    const info = db().prepare('INSERT INTO generated_documents(plan_id, plan_version, generated_by, filename, snapshot, pdf, sha256) VALUES (?,?,?,?,?,?,?)')
      .run(ctx.id, ctx.version, req.user.id, snap.filename, JSON.stringify({ ...snap, header: { ...snap.header, logo: snap.header.logo ? '[logo]' : null } }), buf, U.sha256(buf));
    P.audit(req.user, req.user.role === 'admin' ? 'generar_pdf_admin' : 'generar_pdf', 'documento', info.lastInsertRowid, ctx.id, { version: ctx.version });
    res.status(201).json({ id: info.lastInsertRowid, filename: snap.filename, plan_version: ctx.version });
  }));

  api.get('/documents/:id/pdf', wrap((req, res) => {
    const d = db().prepare('SELECT id, plan_id, filename, pdf FROM generated_documents WHERE id = ?').get(intParam(req.params.id, 'id'));
    if (!d) throw new HttpError(404, 'El documento no existe.');
    P.assertAccess(req.user, d.plan_id);
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `${req.query.download ? 'attachment' : 'inline'}; filename="${d.filename}"`);
    res.setHeader('Cache-Control', 'private, max-age=0');
    res.send(Buffer.from(d.pdf));
  }));

  // ================================================================ administración
  const admin = express.Router();
  admin.use(auth.requireRole('admin'));

  admin.get('/institution', (req, res) => {
    const { logo, ...r } = db().prepare('SELECT * FROM institution WHERE id = 1').get();
    res.json({ ...r, has_logo: !!logo });
  });
  admin.put('/institution', wrap((req, res) => {
    const f = ['full_name', 'short_name', 'header_line1', 'header_line2', 'motto', 'sheet_title', 'contents_banner', 'default_observations', 'note_text', 'sign_teacher', 'sign_parent', 'sign_direction'];
    const vals = {};
    for (const k of f) vals[k] = typeof req.body[k] === 'string' ? req.body[k].normalize('NFC').trim().slice(0, 600) : '';
    if (!vals.full_name || !vals.short_name) throw new HttpError(422, 'El nombre completo y el nombre corto son obligatorios.');
    db().prepare(`UPDATE institution SET ${f.map(k => `${k} = @${k}`).join(', ')}, updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = 1`).run(vals);
    P.audit(req.user, 'actualizar_institucion', 'institucion', 1, null, null);
    res.json({ ok: true });
  }));
  admin.post('/institution/logo', express.raw({ type: ['image/png', 'image/jpeg', 'image/webp'], limit: '3mb' }), wrap(async (req, res) => {
    if (!Buffer.isBuffer(req.body) || !req.body.length) throw new HttpError(422, 'Seleccione una imagen PNG, JPG o WEBP.');
    let png;
    try { png = await require('sharp')(req.body).resize({ width: 600, height: 600, fit: 'inside', withoutEnlargement: true }).png({ progressive: false }).toBuffer(); }
    catch { throw new HttpError(422, 'No se pudo leer la imagen. Use un archivo PNG, JPG o WEBP válido.'); }
    db().prepare("UPDATE institution SET logo = ?, logo_mime = 'image/png', updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = 1").run(png);
    P.audit(req.user, 'actualizar_logo', 'institucion', 1, null, null);
    res.json({ ok: true });
  }));
  admin.delete('/institution/logo', (req, res) => {
    db().prepare('UPDATE institution SET logo = NULL, logo_mime = NULL WHERE id = 1').run();
    P.audit(req.user, 'quitar_logo', 'institucion', 1, null, null);
    res.json({ ok: true });
  });

  // Ciclos y bimestres
  admin.post('/school-years', wrap((req, res) => {
    const name = U.cleanName(str(req.body.name, 40));
    if (!name) throw new HttpError(422, 'Escriba el nombre del ciclo (por ejemplo, el año).');
    let id;
    try {
      db().transaction(() => {
        id = db().prepare('INSERT INTO school_years(name) VALUES (?)').run(name).lastInsertRowid;
        const ins = db().prepare('INSERT INTO bimesters(school_year_id, number) VALUES (?,?)');
        for (let n = 1; n <= 4; n++) ins.run(id, n);
      })();
    } catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe un ciclo con ese nombre.'); throw e; }
    P.audit(req.user, 'crear_ciclo', 'ciclo', id, null, { name });
    res.status(201).json({ id });
  }));
  admin.put('/school-years/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const name = U.cleanName(str(req.body.name, 40));
    if (!name) throw new HttpError(422, 'Escriba el nombre del ciclo.');
    try { db().prepare('UPDATE school_years SET name = ?, active = ? WHERE id = ?').run(name, req.body.active ? 1 : 0, id); }
    catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe un ciclo con ese nombre.'); throw e; }
    P.audit(req.user, 'actualizar_ciclo', 'ciclo', id, null, { name, active: !!req.body.active });
    res.json({ ok: true });
  }));
  admin.put('/school-years/:id/bimesters', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const list = Array.isArray(req.body.bimesters) ? req.body.bimesters : [];
    const byNum = {};
    for (const b of list) {
      const n = Number(b.number);
      if (![1, 2, 3, 4].includes(n)) throw new HttpError(422, 'Número de bimestre no válido.');
      const s = b.start_date || null, e = b.end_date || null;
      if ((s && !U.isDate(s)) || (e && !U.isDate(e))) throw new HttpError(422, `Fechas no válidas en el bimestre ${n}.`);
      if (s && e && s > e) throw new HttpError(422, `Bimestre ${n}: la fecha de inicio es posterior a la de finalización.`);
      byNum[n] = { s, e };
    }
    for (let n = 2; n <= 4; n++) {
      const prev = byNum[n - 1], cur = byNum[n];
      if (prev && cur && prev.e && cur.s && cur.s <= prev.e) throw new HttpError(422, `El bimestre ${n} debe iniciar después de que termine el bimestre ${n - 1}.`);
    }
    db().transaction(() => {
      for (const [n, v] of Object.entries(byNum)) db().prepare('UPDATE bimesters SET start_date = ?, end_date = ? WHERE school_year_id = ? AND number = ?').run(v.s, v.e, id, Number(n));
    })();
    P.audit(req.user, 'actualizar_bimestres', 'ciclo', id, null, byNum);
    res.json({ ok: true });
  }));

  // Grados y secciones
  admin.post('/grades', wrap((req, res) => {
    const name = U.cleanName(str(req.body.name, 80));
    if (!name) throw new HttpError(422, 'Escriba el nombre del grado.');
    try {
      const info = db().prepare('INSERT INTO grades(name, name_key, level, sort_order) VALUES (?,?,?,?)').run(name, U.nameKey(name), U.cleanName(str(req.body.level, 80)), Number(req.body.sort_order) || 0);
      P.audit(req.user, 'crear_grado', 'grado', info.lastInsertRowid, null, { name });
      res.status(201).json({ id: info.lastInsertRowid });
    } catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe un grado con ese nombre.'); throw e; }
  }));
  admin.put('/grades/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const name = U.cleanName(str(req.body.name, 80));
    if (!name) throw new HttpError(422, 'Escriba el nombre del grado.');
    try { db().prepare('UPDATE grades SET name = ?, name_key = ?, level = ?, sort_order = ?, active = ? WHERE id = ?').run(name, U.nameKey(name), U.cleanName(str(req.body.level, 80)), Number(req.body.sort_order) || 0, req.body.active ? 1 : 0, id); }
    catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe un grado con ese nombre.'); throw e; }
    P.audit(req.user, 'actualizar_grado', 'grado', id, null, { name });
    res.json({ ok: true });
  }));
  admin.delete('/grades/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    if (db().prepare('SELECT COUNT(*) n FROM assignments WHERE grade_id = ?').get(id).n) throw new HttpError(409, 'El grado tiene asignaciones. Desactívelo en lugar de eliminarlo.');
    db().prepare('DELETE FROM grades WHERE id = ?').run(id);
    P.audit(req.user, 'eliminar_grado', 'grado', id, null, null);
    res.json({ ok: true });
  }));
  admin.post('/grades/:id/sections', wrap((req, res) => {
    const gid = intParam(req.params.id, 'id');
    const name = U.cleanName(str(req.body.name, 20)).toUpperCase();
    if (!name) throw new HttpError(422, 'Escriba el nombre de la sección.');
    try {
      const info = db().prepare('INSERT INTO sections(grade_id, name, name_key) VALUES (?,?,?)').run(gid, name, U.nameKey(name));
      P.audit(req.user, 'crear_seccion', 'seccion', info.lastInsertRowid, null, { gid, name });
      res.status(201).json({ id: info.lastInsertRowid });
    } catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'La sección ya existe en este grado.'); throw e; }
  }));
  admin.put('/sections/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const name = U.cleanName(str(req.body.name, 20)).toUpperCase();
    if (!name) throw new HttpError(422, 'Escriba el nombre de la sección.');
    try { db().prepare('UPDATE sections SET name = ?, name_key = ?, active = ? WHERE id = ?').run(name, U.nameKey(name), req.body.active ? 1 : 0, id); }
    catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'La sección ya existe en este grado.'); throw e; }
    P.audit(req.user, 'actualizar_seccion', 'seccion', id, null, { name });
    res.json({ ok: true });
  }));
  admin.delete('/sections/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    if (db().prepare('SELECT COUNT(*) n FROM assignments WHERE section_id = ?').get(id).n) throw new HttpError(409, 'La sección tiene asignaciones. Desactívela en lugar de eliminarla.');
    db().prepare('DELETE FROM sections WHERE id = ?').run(id);
    P.audit(req.user, 'eliminar_seccion', 'seccion', id, null, null);
    res.json({ ok: true });
  }));

  // Catálogo de cursos (administración)
  admin.get('/courses', (req, res) => {
    res.json(db().prepare(`SELECT c.id, c.name, c.active, c.created_at, u.full_name AS created_by_name,
      (SELECT COUNT(*) FROM assignments a WHERE a.course_id = c.id) AS assignments,
      (SELECT COUNT(DISTINCT user_id) FROM assignments a WHERE a.course_id = c.id) AS teachers
      FROM courses c LEFT JOIN users u ON u.id = c.created_by ORDER BY c.name`).all());
  });
  admin.put('/courses/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const name = U.cleanName(str(req.body.name, 100));
    if (U.nameKey(name).length < 2) throw new HttpError(422, 'Escriba el nombre del curso.');
    const old = db().prepare('SELECT name FROM courses WHERE id = ?').get(id);
    if (!old) throw new HttpError(404, 'El curso no existe.');
    try { db().prepare('UPDATE courses SET name = ?, name_key = ?, active = ? WHERE id = ?').run(name, U.nameKey(name), req.body.active ? 1 : 0, id); }
    catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe otro curso con ese nombre.'); throw e; }
    P.audit(req.user, 'actualizar_curso_admin', 'curso', id, null, { de: old.name, a: name, active: !!req.body.active });
    res.json({ ok: true });
  }));
  admin.delete('/courses/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    if (db().prepare('SELECT COUNT(*) n FROM assignments WHERE course_id = ?').get(id).n) throw new HttpError(409, 'El curso está asignado a docentes. Desactívelo en lugar de eliminarlo.');
    db().prepare('DELETE FROM courses WHERE id = ?').run(id);
    P.audit(req.user, 'eliminar_curso_admin', 'curso', id, null, null);
    res.json({ ok: true });
  }));

  // Listado institucional de docentes (se usa al registrarse para buscar el nombre)
  admin.get('/teachers', (req, res) => {
    res.json(db().prepare(`SELECT t.id, t.full_name, t.external_id, t.active, u.username,
        (SELECT COUNT(*) FROM teacher_loads l WHERE l.teacher_id = t.id) AS loads,
        (SELECT GROUP_CONCAT(DISTINCT c.name) FROM teacher_loads l JOIN courses c ON c.id = l.course_id WHERE l.teacher_id = t.id) AS courses
      FROM teachers t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.full_name`).all());
  });
  admin.post('/teachers', wrap((req, res) => {
    const name = U.displayPersonName(str(req.body.full_name, 150));
    if (name.length < 5) throw new HttpError(422, 'Escriba el nombre completo del docente.');
    const info = db().prepare('INSERT INTO teachers(full_name, name_key) VALUES (?,?) ON CONFLICT(name_key) DO NOTHING').run(name, U.personKey(name));
    if (!info.changes) throw new HttpError(422, 'Ese docente ya está en el listado.');
    P.audit(req.user, 'crear_docente_listado', 'docente', info.lastInsertRowid, null, { name });
    res.status(201).json({ id: info.lastInsertRowid });
  }));
  admin.put('/teachers/:id', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const name = U.cleanName(str(req.body.full_name, 150));
    if (name.length < 5) throw new HttpError(422, 'Escriba el nombre completo del docente.');
    try { db().prepare('UPDATE teachers SET full_name = ?, name_key = ?, active = ? WHERE id = ?').run(name, U.personKey(name), req.body.active ? 1 : 0, id); }
    catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ya existe otro docente con ese nombre.'); throw e; }
    P.audit(req.user, 'actualizar_docente_listado', 'docente', id, null, { name, active: !!req.body.active });
    res.json({ ok: true });
  }));
  // Libera el nombre del listado (por ejemplo, si se vinculó a una cuenta equivocada)
  admin.post('/teachers/:id/unlink', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    db().prepare('UPDATE teachers SET user_id = NULL WHERE id = ?').run(id);
    P.audit(req.user, 'desvincular_docente_listado', 'docente', id, null, null);
    res.json({ ok: true });
  }));

  // Usuarios
  admin.get('/users', (req, res) => {
    res.json(db().prepare(`SELECT id, username, full_name, role, active, recovery_email, email_verified, must_change_password, created_at,
      (SELECT COUNT(*) FROM assignments a WHERE a.user_id = users.id) AS assignments FROM users ORDER BY role, full_name`).all());
  });
  admin.post('/users', wrap((req, res) => {
    const full_name = U.cleanName(str(req.body.full_name, 150));
    const username = str(req.body.username, 60);
    const role = req.body.role === 'admin' ? 'admin' : 'docente';
    if (full_name.length < 5) throw new HttpError(422, 'Escriba el nombre completo.');
    if (!/^[A-Za-z0-9._-]{3,40}$/.test(username)) throw new HttpError(422, 'Nombre de usuario no válido (3 a 40 caracteres: letras sin tilde, números, punto, guion o guion bajo).');
    const temp = tempPassword();
    try {
      const info = db().prepare('INSERT INTO users(username, username_key, password_hash, full_name, role, must_change_password) VALUES (?,?,?,?,?,1)')
        .run(username, username.toLowerCase(), U.hashPassword(temp), full_name, role);
      P.audit(req.user, 'crear_usuario', 'usuario', info.lastInsertRowid, null, { username, role });
      res.status(201).json({ id: info.lastInsertRowid, temporary_password: temp });
    } catch (e) { if (String(e.code).startsWith('SQLITE_CONSTRAINT')) throw new HttpError(422, 'Ese nombre de usuario ya existe.'); throw e; }
  }));
  admin.put('/users/:id/active', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    if (id === req.user.id) throw new HttpError(422, 'No puede desactivar su propia cuenta.');
    const active = req.body.active ? 1 : 0;
    db().prepare('UPDATE users SET active = ? WHERE id = ?').run(active, id);
    if (!active) db().prepare('DELETE FROM sessions WHERE user_id = ?').run(id);
    P.audit(req.user, active ? 'activar_usuario' : 'desactivar_usuario', 'usuario', id, null, null);
    res.json({ ok: true });
  }));
  // Restablecimiento administrativo: genera una contraseña temporal de un solo uso (no revela la anterior).
  admin.post('/users/:id/reset-password', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const u = db().prepare('SELECT id FROM users WHERE id = ?').get(id);
    if (!u) throw new HttpError(404, 'El usuario no existe.');
    const temp = tempPassword();
    db().transaction(() => {
      db().prepare("UPDATE users SET password_hash = ?, must_change_password = 1, updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(U.hashPassword(temp), id);
      db().prepare('DELETE FROM sessions WHERE user_id = ?').run(id);
    })();
    P.audit(req.user, 'restablecer_contrasena_admin', 'usuario', id, null, null);
    res.json({ temporary_password: temp });
  }));
  function tempPassword() {
    const a = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
    const pick = () => a[require('crypto').randomInt(a.length)];
    return `${pick()}${pick()}${pick()}${pick()}-${require('crypto').randomInt(1000, 9999)}-${pick()}${pick()}${pick()}${pick()}`;
  }

  // Seguimiento de planificaciones
  admin.get('/plans', wrap((req, res) => {
    const yearId = intParam(req.query.school_year_id, 'ciclo');
    res.json(dashboardRows({ userId: null, yearId }));
  }));
  admin.post('/plans/:id/return-to-draft', wrap((req, res) => {
    const id = intParam(req.params.id, 'id');
    const ctx = P.planContext(id);
    if (!ctx) throw new HttpError(404, 'La planificación no existe.');
    const reason = str(req.body.reason, 500);
    if (!reason) throw new HttpError(422, 'Escriba el motivo; quedará registrado y visible para el docente.');
    if (ctx.status !== 'listo') throw new HttpError(422, 'La planificación ya está en borrador.');
    db().prepare("UPDATE plans SET status = 'borrador', ready_at = NULL, version = version + 1, updated_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(id);
    P.audit(req.user, 'cambio_estado_admin', 'plan', id, id, { de: 'listo', a: 'borrador', motivo: reason });
    res.json({ ok: true });
  }));
  // Observaciones del coordinador (sólo personal autorizado; no son notas del docente)
  admin.put('/plans/:id/coordination', wrap((req, res) => {
    const ctx = P.planContext(intParam(req.params.id, 'id'));
    if (!ctx) throw new HttpError(404, 'La planificación no existe.');
    const obs = typeof req.body.observations === 'string' ? req.body.observations.normalize('NFC').replace(/\r\n/g, '\n').trim().slice(0, 8000) : '';
    db().prepare("UPDATE plans SET coord_observations = ?, coord_observations_by = ?, coord_observations_at = strftime('%Y-%m-%dT%H:%M:%fZ','now') WHERE id = ?").run(obs, req.user.id, ctx.id);
    P.audit(req.user, 'observaciones_coordinador', 'plan', ctx.id, ctx.id, null);
    res.json({ ok: true, coordination: coordination(ctx.id) });
  }));
  // Revisión real del plan entregado: fecha, resultado y nombre de quien revisa
  admin.post('/plans/:id/reviews', wrap((req, res) => {
    const ctx = P.planContext(intParam(req.params.id, 'id'));
    if (!ctx) throw new HttpError(404, 'La planificación no existe.');
    const result = String(req.body.result || '');
    if (!planPdf.RESULT[result]) throw new HttpError(422, 'Seleccione el resultado de la revisión.');
    const p = db().prepare('SELECT delivered_at FROM plans WHERE id = ?').get(ctx.id);
    if (!p.delivered_at) throw new HttpError(422, 'El docente aún no ha entregado esta planificación a coordinación.');
    db().prepare('INSERT INTO plan_reviews(plan_id, reviewer_id, reviewer_name, result, plan_version) VALUES (?,?,?,?,?)').run(ctx.id, req.user.id, req.user.full_name, result, ctx.version);
    P.audit(req.user, 'revision_coordinacion', 'plan', ctx.id, ctx.id, { result });
    res.status(201).json({ ok: true, coordination: coordination(ctx.id) });
  }));

  admin.get('/audit', (req, res) => {
    res.json(db().prepare(`SELECT l.id, l.at, l.action, l.entity, l.entity_id, l.plan_id, l.user_role, l.details, u.full_name AS user_name
      FROM audit_log l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 300`).all());
  });

  api.use('/admin', admin);
  app.use('/api', api);
  app.use('/api', (req, res) => res.status(404).json({ error: 'Ruta no encontrada.' }));

  // Errores
  // eslint-disable-next-line no-unused-vars
  app.use((err, req, res, _next) => {
    if (err instanceof HttpError) return res.status(err.status).json({ error: err.message, ...(err.extra || {}) });
    if (err.type === 'entity.too.large') return res.status(413).json({ error: 'La información enviada es demasiado grande.' });
    if (err.type === 'entity.parse.failed') return res.status(400).json({ error: 'Datos con formato incorrecto.' });
    if (String(err.code || '').startsWith('SQLITE_CONSTRAINT')) {
      console.error('[restricción]', err.message);
      return res.status(409).json({ error: 'La operación viola una regla de integridad de los datos (registro duplicado o relación no válida).' });
    }
    console.error('[error]', err);
    res.status(500).json({ error: 'Ocurrió un error interno. Intente nuevamente.' });
  });

  // Con BASE_PATH la aplicación completa se monta bajo esa subruta
  if (!config.basePath) return app;
  const root = express();
  root.disable('x-powered-by');
  root.use(config.basePath, app);
  return root;
}

module.exports = { createApp };
