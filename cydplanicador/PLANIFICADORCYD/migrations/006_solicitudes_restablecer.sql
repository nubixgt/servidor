-- Solicitudes de restablecimiento de contraseña hechas por el usuario desde «Recuperar acceso»
-- (cuando no hay correo configurado). La administración las atiende desde «Usuarios».
CREATE TABLE reset_requests (
  id          INTEGER PRIMARY KEY,
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  note        TEXT NOT NULL DEFAULT '',
  created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  resolved_at TEXT
);
CREATE INDEX idx_reset_requests_user ON reset_requests(user_id, resolved_at);
