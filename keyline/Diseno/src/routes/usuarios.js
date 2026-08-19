const express = require('express');
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const db = require('../db');
const { requireAuth, requireRole } = require('../middleware/auth');
const { ROLES, DEPARTAMENTOS } = require('../constants');

const router = express.Router();

function publicUser(u) {
  const { passwordHash, ...rest } = u;
  return rest;
}

// Listar usuarios (admin y supervisor pueden ver el equipo)
router.get('/', requireAuth, requireRole('administrador', 'supervisor'), (req, res) => {
  let users = db.state.users;
  if (req.user.role === 'supervisor') {
    users = users.filter((u) => u.role === 'tecnico' && (u.regionAsignada === req.user.regionAsignada || !req.user.regionAsignada));
  }
  res.json({ users: users.map(publicUser) });
});

router.post('/', requireAuth, requireRole('administrador'), async (req, res) => {
  const { nombre, email, password, role, regionAsignada, telefono } = req.body || {};
  if (!nombre || !email || !password || !role) {
    return res.status(400).json({ error: 'Nombre, correo, contraseña y rol son requeridos.' });
  }
  if (!ROLES.includes(role)) return res.status(400).json({ error: 'Rol inválido.' });
  if (db.state.users.some((u) => u.email.toLowerCase() === email.toLowerCase())) {
    return res.status(409).json({ error: 'Ya existe un usuario con ese correo.' });
  }
  const passwordHash = await bcrypt.hash(password, 10);
  const user = {
    id: uuidv4(),
    nombre,
    email,
    passwordHash,
    role,
    regionAsignada: regionAsignada && DEPARTAMENTOS.includes(regionAsignada) ? regionAsignada : '',
    telefono: telefono || '',
    activo: true,
    createdAt: new Date().toISOString(),
    ultimoAcceso: null,
  };
  db.state.users.push(user);
  db.save();
  db.logActivity({ tipo: 'usuario_creado', usuarioId: req.user.id, usuarioNombre: req.user.nombre, detalle: `Creó usuario ${nombre} (${role})` });
  res.status(201).json({ user: publicUser(user) });
});

router.put('/:id', requireAuth, requireRole('administrador'), async (req, res) => {
  const user = db.state.users.find((u) => u.id === req.params.id);
  if (!user) return res.status(404).json({ error: 'Usuario no encontrado.' });
  const { nombre, role, regionAsignada, telefono, activo, password } = req.body || {};
  if (nombre) user.nombre = nombre;
  if (role && ROLES.includes(role)) user.role = role;
  if (regionAsignada !== undefined) user.regionAsignada = regionAsignada;
  if (telefono !== undefined) user.telefono = telefono;
  if (activo !== undefined) user.activo = !!activo;
  if (password) user.passwordHash = await bcrypt.hash(password, 10);
  db.save();
  res.json({ user: publicUser(user) });
});

router.delete('/:id', requireAuth, requireRole('administrador'), (req, res) => {
  if (req.params.id === req.user.id) return res.status(400).json({ error: 'No puedes eliminar tu propio usuario.' });
  const idx = db.state.users.findIndex((u) => u.id === req.params.id);
  if (idx === -1) return res.status(404).json({ error: 'Usuario no encontrado.' });
  db.state.users.splice(idx, 1);
  db.save();
  res.json({ ok: true });
});

module.exports = router;
