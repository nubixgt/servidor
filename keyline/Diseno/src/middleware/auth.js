const jwt = require('jsonwebtoken');
const db = require('../db');

const SECRET = process.env.JWT_SECRET || 'keyline-dev-secret-cambiar-en-produccion';

function sign(user) {
  return jwt.sign(
    { id: user.id, role: user.role, nombre: user.nombre, email: user.email },
    SECRET,
    { expiresIn: '12h' }
  );
}

function requireAuth(req, res, next) {
  const token = req.cookies?.keyline_token || (req.headers.authorization || '').replace('Bearer ', '');
  if (!token) return res.status(401).json({ error: 'No autenticado.' });
  try {
    const payload = jwt.verify(token, SECRET);
    const user = db.state.users.find((u) => u.id === payload.id && u.activo !== false);
    if (!user) return res.status(401).json({ error: 'Sesión inválida.' });
    req.user = user;
    next();
  } catch (e) {
    return res.status(401).json({ error: 'Sesión expirada o inválida.' });
  }
}

function requireRole(...roles) {
  return (req, res, next) => {
    if (!req.user || !roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'No tienes permisos para esta acción.' });
    }
    next();
  };
}

module.exports = { sign, requireAuth, requireRole, SECRET };
