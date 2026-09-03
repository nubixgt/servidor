-- ============================================================================
--  Liga de Baloncesto Sanarateca — Datos iniciales
--  Ejecutar DESPUÉS de schema.sql
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
--  Usuario administrador
--    usuario:    admin
--    contraseña: admin123
--  Hash bcrypt generado con: password_hash('admin123', PASSWORD_BCRYPT)
--  (cámbialo cuanto antes desde la base de datos)
-- ----------------------------------------------------------------------------
INSERT INTO usuarios (usuario, password_hash, nombre, rol, activo) VALUES
('admin', '$2y$10$Ttt.IdaWfJawWGWLeuXYEu2cP/R665c/.DGPtx28GDYaDuGuBtsWK', 'Administrador', 'admin', 1);

-- ----------------------------------------------------------------------------
--  Equipos de ejemplo (para que las consolas no salgan vacías)
-- ----------------------------------------------------------------------------
INSERT INTO equipos (nombre, sede, conferencia, rama, director_tecnico, telefono_delegado, color_hex, activo) VALUES
('Toros de Sanarate',  'Gimnasio Municipal Central',  'Norte', 'Masculina Mayor',  'Prof. Carlos Mendoza',  '+502 5541-8920', '#ccff00', 1),
('Halcones Dorados',    'Cancha Minerva Sur',          'Sur',   'Masculina Mayor',  'Lic. Walter Estrada',   '+502 5588-1122', '#0F172A', 1),
('Cobras del Valle',    'Complejo El Manantial',       'Norte', 'Femenina Libre',   'Entr. Roxana Morales',  '+502 4477-3311', '#84CC16', 1),
('Jaguares Oriente',    'Polideportivo Barrio Nuevo',  'Sur',   'Masculina Mayor',  'Prof. Hugo Quiñónez',   '+502 5123-4567', '#3B82F6', 1),
('Lobos de Jalapa',     'Domo Polideportivo Jalapa',   'Norte', 'Masculina Mayor',  'Prof. Erick Ramírez',   '+502 5999-0000', '#EF4444', 1),
('Águilas Sub-18',      'Cancha La Democracia',        'Sur',   'Juvenil Sub-18',   'Prof. Mynor Sandoval',  '+502 5010-2030', '#0F172A', 1);

-- Fila de clasificación por cada equipo (normalmente la crea EquipoService::create)
INSERT INTO clasificacion (equipo_id)
SELECT id FROM equipos;
