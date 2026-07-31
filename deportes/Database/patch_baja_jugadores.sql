ALTER TABLE jugadores 
ADD COLUMN estado ENUM('activo', 'inactivo') DEFAULT 'activo',
ADD COLUMN razon_baja TEXT NULL,
ADD COLUMN fecha_baja DATETIME NULL;
