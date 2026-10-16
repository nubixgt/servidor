'use strict';
// Uso: node scripts/create-admin.js <usuario> "<Nombre completo>"
// Crea un administrador con contraseña temporal (se muestra una sola vez y debe cambiarse al ingresar).
const crypto = require('crypto');
const db = require('../src/db');
const { hashPassword } = require('../src/util');
const [username, fullName] = process.argv.slice(2);
if (!username || !fullName || !/^[A-Za-z0-9._-]{3,40}$/.test(username)) {
  console.error('Uso: node scripts/create-admin.js <usuario> "<Nombre completo>"'); process.exit(1);
}
db.open();
const temp = `${crypto.randomBytes(4).toString('hex')}-${crypto.randomInt(1000, 9999)}`;
try {
  db.get().prepare("INSERT INTO users(username, username_key, password_hash, full_name, role, must_change_password) VALUES (?,?,?,?, 'admin', 1)")
    .run(username, username.toLowerCase(), hashPassword(temp), fullName);
} catch (e) { console.error('No se pudo crear: el usuario ya existe.'); process.exit(1); }
console.log(`Administrador creado: ${username}\nContraseña temporal (cámbiela al ingresar): ${temp}`);
