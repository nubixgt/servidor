ALTER TABLE equipos 
ADD COLUMN rol ENUM('encargado', 'admin') DEFAULT 'encargado';

INSERT INTO equipos (nombre, representante, telefono, dpi, foto_ruta, usuario, password_hash, rol) 
VALUES ('Administración', 'Admin', '0000', 'admin_dpi', '', 'admin', '$2y$10$tZ2E6H2G4J.y2h/1Z7uK6.kX6fP0q8U0hQ3l.1D7Q8h6yK4x6G', 'admin');
