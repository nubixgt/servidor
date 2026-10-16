'use strict';
const fs = require('fs');
const path = require('path');
const Database = require('better-sqlite3');
const config = require('./config');

let db;

function open(file = config.dbFile) {
  if (file !== ':memory:') fs.mkdirSync(path.dirname(file), { recursive: true });
  db = new Database(file);
  db.pragma('journal_mode = WAL');
  db.pragma('foreign_keys = ON');
  db.pragma('busy_timeout = 5000');
  migrate(db);
  seed(db);
  return db;
}

function migrate(d) {
  d.exec(`CREATE TABLE IF NOT EXISTS schema_migrations (
    name TEXT PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')))`);
  const dir = path.join(__dirname, '..', 'migrations');
  const done = new Set(d.prepare('SELECT name FROM schema_migrations').all().map(r => r.name));
  for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.sql')).sort()) {
    if (done.has(f)) continue;
    const sql = fs.readFileSync(path.join(dir, f), 'utf8');
    d.transaction(() => {
      d.exec(sql);
      d.prepare('INSERT INTO schema_migrations(name) VALUES (?)').run(f);
    })();
    console.log(`[db] migración aplicada: ${f}`);
  }
}

// Textos institucionales fijos identificados en la plantilla HOJA DE ACTIVIDADES.docx.
// El administrador puede modificarlos desde la configuración institucional.
function seed(d) {
  const row = d.prepare('SELECT id FROM institution WHERE id = 1').get();
  if (row) return;
  let logo = null;
  const logoFile = path.join(__dirname, '..', 'assets', 'logo-plantilla.png');
  if (fs.existsSync(logoFile)) logo = fs.readFileSync(logoFile);
  d.prepare(`INSERT INTO institution (id, full_name, short_name, header_line1, header_line2, motto,
      sheet_title, contents_banner, default_observations, note_text, sign_teacher, sign_parent, sign_direction, logo, logo_mime)
    VALUES (1, @full_name, @short_name, @l1, @l2, @motto, @title, @banner, @obs, @note, @s1, @s2, @s3, @logo, @mime)`).run({
    full_name: 'Colegio Particular Mixto Ciencia y Desarrollo',
    short_name: 'Colegio CyD',
    l1: 'Colegio Particular Mixto',
    l2: 'CIENCIA Y DESARROLLO',
    motto: '“Educar es Transformar Vidas”',
    title: 'HOJA DE ACTIVIDADES',
    banner: 'CONTENIDOS',
    obs: 'las fechas están sujetas a cambios, debido a imprevistos que puedan surgir.',
    note: 'Las fichas de calificación deberán subirse a la APP del CyD, únicamente en formato PDF.',
    s1: 'CATEDRÁTICO',
    s2: 'PADRE DE FAMILIA',
    s3: 'DIRECCIÓN',
    logo,
    mime: logo ? 'image/png' : null,
  });
}

function get() {
  if (!db) throw new Error('Base de datos no inicializada');
  return db;
}

module.exports = { open, get };
