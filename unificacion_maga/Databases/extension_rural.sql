-- Extensionists (Staff)
CREATE TABLE IF NOT EXISTS extensionistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    puesto VARCHAR(100),
    region VARCHAR(100),
    activo TINYINT(1) DEFAULT 1,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Technical Visits and Activities
CREATE TABLE IF NOT EXISTS extension_visitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    fecha DATE NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    comunidad VARCHAR(200),
    tipo ENUM('CAPACITACION', 'ASISTENCIA', 'ENTREGA_INSUMOS') NOT NULL,
    extensionista_id INT,
    estado ENUM('PROGRAMADA', 'EN PROCESO', 'COMPLETADA', 'CANCELADA') DEFAULT 'PROGRAMADA',
    beneficiarios INT DEFAULT 0,
    observaciones TEXT,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (extensionista_id) REFERENCES extensionistas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data
INSERT INTO extensionistas (nombre, puesto, region) VALUES 
('Mario Pérez', 'Extensionista Municipal', 'Nororiente'),
('Luisa Fernanda', 'Técnico de Campo', 'Norte'),
('Carlos Ruiz', 'Coordinador Regional', 'Occidente');

INSERT INTO extension_visitas (codigo, fecha, departamento, municipio, comunidad, tipo, extensionista_id, estado, beneficiarios) VALUES 
('EXT-2026-001', '2026-02-12', 'CHIQUIMULA', 'JOCOTÁN', 'Aldea Pelillo Negro', 'ASISTENCIA', 1, 'COMPLETADA', 15),
('EXT-2026-002', '2026-02-14', 'PETEN', 'SAYAXCHÉ', 'Caserío El Rosario', 'CAPACITACION', 2, 'PROGRAMADA', 40);
