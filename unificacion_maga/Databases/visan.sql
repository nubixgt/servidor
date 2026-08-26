CREATE TABLE IF NOT EXISTS visan_entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    tipo_asistencia ENUM('AA', 'APA', 'INSAN', 'MEDIDA TRANSITORIA', 'NDA NACIONAL', 'MEDIDA CAUTELAR', 'RESERVA ESTRATÉGICA') NOT NULL,
    raciones INT DEFAULT 0,
    familias INT DEFAULT 0,
    observaciones TEXT,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Metas y Techos (Opcional, para configuración de indicadores)
CREATE TABLE IF NOT EXISTS visan_metas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_asistencia VARCHAR(50) UNIQUE NOT NULL,
    techo_raciones INT NOT NULL,
    techo_familias INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO visan_metas (tipo_asistencia, techo_raciones, techo_familias) VALUES 
('AA', 691547, 691547),
('APA', 232508, 232508),
('RESERVA ESTRATÉGICA', 205302, 205302);

-- Initial data for testing
INSERT INTO visan_entregas (fecha, departamento, municipio, tipo_asistencia, raciones, familias) VALUES 
('2026-02-10', 'JALAPA', 'JALAPA', 'AA', 1500, 1500),
('2026-02-12', 'JALAPA', 'SAN PEDRO PINULA', 'APA', 800, 800),
('2026-02-15', 'ALTA VERAPAZ', 'TACTIC', 'INSAN', 300, 300),
('2026-02-18', 'EL PROGRESO', 'GUASTATOYA', 'RESERVA ESTRATÉGICA', 2000, 2000);

-- DAPCA (Departamento de Apoyo a la Producción Comunitaria de Alimentos)
CREATE TABLE IF NOT EXISTS visan_dapca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento VARCHAR(100) NOT NULL,
    intervencion VARCHAR(200) NOT NULL,
    meta INT DEFAULT 0,
    avance INT DEFAULT 0,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO visan_dapca (departamento, intervencion, meta, avance) VALUES 
('CHIMALTENANGO', 'Huertos Familiares', 500, 350),
('CHIMALTENANGO', 'Aves de Postura', 200, 120),
('GUATEMALA', 'Huertos Escolares', 300, 280);

-- Entregas Individuales / Solicitudes (Para SeguridadAlimentaria.vue)
CREATE TABLE IF NOT EXISTS visan_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud VARCHAR(20) UNIQUE NOT NULL,
    fecha DATE NOT NULL,
    productor_id INT,
    comunidad VARCHAR(200),
    programa ENUM('ASISTENCIA_ALIMENTARIA', 'ESTIPENDIO', 'RESERVAS_ESTRATEGICAS') NOT NULL,
    estado ENUM('PROGRAMADO', 'EVALUACION', 'ENTREGADO') DEFAULT 'PROGRAMADO',
    observaciones TEXT,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (productor_id) REFERENCES productores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO visan_solicitudes (id_solicitud, fecha, productor_id, comunidad, programa, estado) VALUES 
('REQ-2026-1042', '2026-01-28', 1, 'Caserío Los Pinos', 'ASISTENCIA_ALIMENTARIA', 'ENTREGADO'),
('REQ-2026-1055', '2026-02-05', 2, 'Aldea El Sinaca', 'ESTIPENDIO', 'PROGRAMADO');
