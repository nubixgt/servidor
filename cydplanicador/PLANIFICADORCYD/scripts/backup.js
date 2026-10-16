'use strict';
// Copia de respaldo consistente de la base de datos (API de respaldo de SQLite; puede ejecutarse con el sistema en uso).
// Uso: node scripts/backup.js [carpeta_destino]
const fs = require('fs');
const path = require('path');
const config = require('../src/config');
const Database = require('better-sqlite3');
const dir = process.argv[2] || config.backupDir;
fs.mkdirSync(dir, { recursive: true });
const stamp = new Date().toISOString().replace(/[:T]/g, '-').slice(0, 19);
const dest = path.join(dir, `cyd-${stamp}.db`);
const src = new Database(config.dbFile, { readonly: true });
src.backup(dest).then(() => {
  const chk = new Database(dest, { readonly: true });
  const ok = chk.pragma('integrity_check', { simple: true });
  chk.close(); src.close();
  if (ok !== 'ok') { console.error('El respaldo no pasó la verificación de integridad:', ok); process.exit(1); }
  console.log(`Respaldo creado y verificado: ${dest}`);
}).catch(e => { console.error('Error al respaldar:', e.message); process.exit(1); });
