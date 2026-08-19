/**
 * Almacén de datos en JSON (sin dependencias nativas de base de datos).
 * Pensado para equipos pequeños/medianos (cientos a pocos miles de parcelas).
 * Si el proyecto crece mucho, este módulo puede sustituirse por Postgres/SQLite
 * sin tener que tocar las rutas (todas usan las funciones exportadas aquí).
 */
const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '..', 'data');
const DB_FILE = path.join(DATA_DIR, 'db.json');

if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });

const EMPTY = {
  users: [],
  parcelas: [],
  photos: [],
  activity: [], // bitácora de acciones (auditoría ligera)
};

function load() {
  if (!fs.existsSync(DB_FILE)) {
    fs.writeFileSync(DB_FILE, JSON.stringify(EMPTY, null, 2));
    return JSON.parse(JSON.stringify(EMPTY));
  }
  try {
    const raw = fs.readFileSync(DB_FILE, 'utf-8');
    const parsed = JSON.parse(raw);
    return { ...EMPTY, ...parsed };
  } catch (e) {
    console.error('Error leyendo db.json, se restaura respaldo vacío temporal:', e.message);
    return JSON.parse(JSON.stringify(EMPTY));
  }
}

let state = load();
let writeQueue = Promise.resolve();

function persist() {
  // Escritura serializada para evitar condiciones de carrera en escrituras concurrentes.
  writeQueue = writeQueue.then(() => {
    const tmp = DB_FILE + '.tmp';
    fs.writeFileSync(tmp, JSON.stringify(state, null, 2));
    fs.renameSync(tmp, DB_FILE);
  }).catch((e) => console.error('Error guardando base de datos:', e));
  return writeQueue;
}

module.exports = {
  get state() {
    return state;
  },
  save: persist,
  reload() {
    state = load();
  },
  logActivity(entry) {
    state.activity.unshift({
      id: require('crypto').randomUUID(),
      fecha: new Date().toISOString(),
      ...entry,
    });
    state.activity = state.activity.slice(0, 500);
  },
};
