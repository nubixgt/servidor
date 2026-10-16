'use strict';
// Restaura un respaldo. DETENGA la aplicación antes de ejecutarlo.
// Uso: node scripts/restore.js <archivo_respaldo.db> --confirmar
const fs = require('fs');
const config = require('../src/config');
const Database = require('better-sqlite3');
const [file, flag] = process.argv.slice(2);
if (!file || flag !== '--confirmar') { console.error('Uso: node scripts/restore.js <respaldo.db> --confirmar   (detenga la aplicación primero)'); process.exit(1); }
const chk = new Database(file, { readonly: true });
const ok = chk.pragma('integrity_check', { simple: true }); chk.close();
if (ok !== 'ok') { console.error('El archivo de respaldo está dañado:', ok); process.exit(1); }
if (fs.existsSync(config.dbFile)) {
  const prev = `${config.dbFile}.antes-de-restaurar-${Date.now()}`;
  fs.copyFileSync(config.dbFile, prev);
  console.log(`Base actual conservada en: ${prev}`);
}
for (const ext of ['-wal', '-shm']) if (fs.existsSync(config.dbFile + ext)) fs.unlinkSync(config.dbFile + ext);
fs.copyFileSync(file, config.dbFile);
console.log(`Restaurado desde ${file}. Inicie nuevamente la aplicación.`);
