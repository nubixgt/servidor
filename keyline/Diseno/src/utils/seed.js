const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const db = require('../db');

async function ensureSeed() {
  if (db.state.users.length > 0) return;

  const adminPass = process.env.ADMIN_PASSWORD || 'Keyline2026!';
  const admin = {
    id: uuidv4(),
    nombre: 'Administrador Keyline',
    email: process.env.ADMIN_EMAIL || 'admin@keyline.gt',
    passwordHash: await bcrypt.hash(adminPass, 10),
    role: 'administrador',
    regionAsignada: '',
    telefono: '',
    activo: true,
    createdAt: new Date().toISOString(),
    ultimoAcceso: null,
  };

  const supervisorPass = 'Supervisor2026!';
  const supervisor = {
    id: uuidv4(),
    nombre: 'Supervisor Regional (demo)',
    email: 'supervisor@keyline.gt',
    passwordHash: await bcrypt.hash(supervisorPass, 10),
    role: 'supervisor',
    regionAsignada: 'Alta Verapaz',
    telefono: '',
    activo: true,
    createdAt: new Date().toISOString(),
    ultimoAcceso: null,
  };

  const tecnicoPass = 'Tecnico2026!';
  const tecnico = {
    id: uuidv4(),
    nombre: 'Técnico de Campo (demo)',
    email: 'tecnico@keyline.gt',
    passwordHash: await bcrypt.hash(tecnicoPass, 10),
    role: 'tecnico',
    regionAsignada: '',
    telefono: '',
    activo: true,
    createdAt: new Date().toISOString(),
    ultimoAcceso: null,
  };

  db.state.users.push(admin, supervisor, tecnico);
  db.save();

  console.log('\n============================================');
  console.log(' Usuarios iniciales creados (cámbialos luego):');
  console.log(` Administrador → ${admin.email} / ${adminPass}`);
  console.log(` Supervisor    → ${supervisor.email} / ${supervisorPass}`);
  console.log(` Técnico       → ${tecnico.email} / ${tecnicoPass}`);
  console.log('============================================\n');
}

module.exports = { ensureSeed };
