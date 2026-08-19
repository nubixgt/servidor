-- =====================================================================
-- Migración: login por "usuario" (nombre de usuario) en vez de correo.
-- Ejecutar UNA sola vez sobre la base de datos visionwe_Keyline ya existente.
-- Es seguro volver a correrlo si algo falla a medias: cada paso revisa
-- el estado antes de aplicarse.
-- =====================================================================

-- 1) Agregar la columna 'usuario' (aún nullable, para poder rellenarla)
ALTER TABLE usuarios
  ADD COLUMN usuario VARCHAR(50) NULL AFTER id;

-- 2) Rellenar usuario para los 3 usuarios demo creados por el sistema
UPDATE usuarios SET usuario = 'admin'      WHERE email = 'admin@keyline.gt'      AND usuario IS NULL;
UPDATE usuarios SET usuario = 'supervisor' WHERE email = 'supervisor@keyline.gt' AND usuario IS NULL;
UPDATE usuarios SET usuario = 'tecnico'    WHERE email = 'tecnico@keyline.gt'    AND usuario IS NULL;

-- 3) Si tienes otros usuarios creados después (vía la pantalla "Usuarios"),
--    esto les asigna un usuario provisional basado en su correo (parte antes de la @).
--    Revísalos después en la pantalla de Usuarios y ajústalos si quieres otro nombre.
UPDATE usuarios SET usuario = SUBSTRING_INDEX(email, '@', 1) WHERE usuario IS NULL AND email IS NOT NULL;

-- 4) Por si queda alguna fila sin correo ni usuario asignado (caso raro): usar user+id
UPDATE usuarios SET usuario = CONCAT('usuario', id) WHERE usuario IS NULL;

-- 5) Ya con todo relleno, hacer la columna obligatoria y única
ALTER TABLE usuarios
  MODIFY usuario VARCHAR(50) NOT NULL,
  ADD UNIQUE KEY uq_usuarios_usuario (usuario);

-- 6) El correo deja de ser el identificador de login: pasa a ser opcional
ALTER TABLE usuarios
  DROP INDEX uq_usuarios_email,
  MODIFY email VARCHAR(150) NULL;

-- =====================================================================
-- Verifica el resultado:
-- SELECT id, nombre, usuario, email, role FROM usuarios;
--
-- A partir de ahora el login es con estos usuarios (misma contraseña de antes):
--   admin      / Keyline2026!
--   supervisor / Supervisor2026!
--   tecnico    / Tecnico2026!
-- =====================================================================
