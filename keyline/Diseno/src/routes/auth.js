const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../db');
const { sign, requireAuth } = require('../middleware/auth');

const router = express.Router();

function publicUser(u) {
  const { passwordHash, ...rest } = u;
  return rest;
}

router.post('/login', async (req, res) => {
  const { email, password } = req.body || {};
  if (!email || !password) return res.status(400).json({ error: 'Correo y contraseña son requeridos.' });
  const user = db.state.users.find((u) => u.email.toLowerCase() === String(email).toLowerCase());
  if (!user || user.activo === false) return res.status(401).json({ error: 'Credenciales inválidas o usuario inactivo.' });
  const ok = await bcrypt.compare(password, user.passwordHash);
  if (!ok) return res.status(401).json({ error: 'Credenciales inválidas.' });

  const token = sign(user);
  res.cookie('keyline_token', token, {
    httpOnly: true,
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    maxAge: 12 * 60 * 60 * 1000,
  });
  user.ultimoAcceso = new Date().toISOString();
  db.save();
  db.logActivity({ tipo: 'login', usuarioId: user.id, usuarioNombre: user.nombre, detalle: 'Inició sesión en el sistema' });
  res.json({ user: publicUser(user), token });
});

router.post('/logout', (req, res) => {
  res.clearCookie('keyline_token');
  res.json({ ok: true });
});

router.get('/me', requireAuth, (req, res) => {
  res.json({ user: publicUser(req.user) });
});

router.post('/cambiar-password', requireAuth, async (req, res) => {
  const { actual, nueva } = req.body || {};
  if (!nueva || nueva.length < 6) return res.status(400).json({ error: 'La nueva contraseña debe tener al menos 6 caracteres.' });
  const user = db.state.users.find((u) => u.id === req.user.id);
  const ok = await bcrypt.compare(actual || '', user.passwordHash);
  if (!ok) return res.status(400).json({ error: 'La contraseña actual no es correcta.' });
  user.passwordHash = await bcrypt.hash(nueva, 10);
  db.save();
  res.json({ ok: true });
});

module.exports = router;
