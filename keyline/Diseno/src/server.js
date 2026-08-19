require('dotenv').config();
const express = require('express');
const cors = require('cors');
const cookieParser = require('cookie-parser');
const morgan = require('morgan');
const path = require('path');
const fs = require('fs');

const { requireAuth } = require('./middleware/auth');
const constants = require('./constants');
const authRoutes = require('./routes/auth');
const usuariosRoutes = require('./routes/usuarios');
const parcelasRoutes = require('./routes/parcelas');
const dashboardRoutes = require('./routes/dashboard');
const { ensureSeed } = require('./utils/seed');

const app = express();
const PORT = process.env.PORT || 4000;

app.use(morgan('dev'));
app.use(cors({ origin: true, credentials: true }));
app.use(cookieParser());
app.use(express.json({ limit: '5mb' }));
app.use(express.urlencoded({ extended: true }));

// Carpeta de fotos subidas
const UPLOAD_DIR = path.join(__dirname, '..', 'uploads');
if (!fs.existsSync(UPLOAD_DIR)) fs.mkdirSync(UPLOAD_DIR, { recursive: true });
app.use('/uploads', requireAuth, express.static(UPLOAD_DIR));

// API
app.get('/api/constantes', (req, res) => res.json(constants));
app.use('/api/auth', authRoutes);
app.use('/api/usuarios', usuariosRoutes);
app.use('/api/parcelas', parcelasRoutes);
app.use('/api/dashboard', dashboardRoutes);

app.get('/api/salud', (req, res) => res.json({ ok: true, servicio: 'keyline-sistema', hora: new Date().toISOString() }));

// Frontend estático
const PUBLIC_DIR = path.join(__dirname, '..', 'public');
app.use(express.static(PUBLIC_DIR));
app.get('*', (req, res, next) => {
  if (req.path.startsWith('/api/') || req.path.startsWith('/uploads/')) return next();
  res.sendFile(path.join(PUBLIC_DIR, 'index.html'));
});

// Manejo de errores (incluye errores de multer)
app.use((err, req, res, next) => {
  console.error(err);
  res.status(err.status || 500).json({ error: err.message || 'Error interno del servidor.' });
});

ensureSeed().then(() => {
  app.listen(PORT, () => {
    console.log(`\n🌿 Sistema Keyline corriendo en http://localhost:${PORT}\n`);
  });
});
