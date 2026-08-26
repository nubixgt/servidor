-- Dependencias del Viceministerio
CREATE TABLE IF NOT EXISTS vider_dependencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    siglas VARCHAR(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catálogos de Actividades, Productos e Intervenciones
CREATE TABLE IF NOT EXISTS vider_catalogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dependencia_id INT,
    tipo ENUM('ACTIVIDAD', 'PRODUCTO', 'INTERVENCION') NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    FOREIGN KEY (dependencia_id) REFERENCES vider_dependencias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beneficiarios y Ejecución (Física y Financiera)
CREATE TABLE IF NOT EXISTS vider_ejecucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    dependencia_id INT,
    actividad_id INT,
    producto_id INT,
    intervencion_id INT,
    genero ENUM('H', 'M', 'N/D') DEFAULT 'N/D',
    fisico_tipo ENUM('PERSONAS', 'HECTAREAS', 'METROS', 'M2') DEFAULT 'PERSONAS',
    fisico_planificado DECIMAL(15,2) DEFAULT 0,
    fisico_ejecutado DECIMAL(15,2) DEFAULT 0,
    financiero_vigente DECIMAL(15,2) DEFAULT 0,
    financiero_ejecutado DECIMAL(15,2) DEFAULT 0,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dependencia_id) REFERENCES vider_dependencias(id),
    FOREIGN KEY (actividad_id) REFERENCES vider_catalogos(id),
    FOREIGN KEY (producto_id) REFERENCES vider_catalogos(id),
    FOREIGN KEY (intervencion_id) REFERENCES vider_catalogos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tobanik (Crédito Cooperativo)
CREATE TABLE IF NOT EXISTS vider_tobanik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento VARCHAR(100) NOT NULL,
    nombre_cooperativa VARCHAR(200) NOT NULL,
    productores INT DEFAULT 0,
    monto_colocado DECIMAL(15,2) DEFAULT 0,
    monto_otorgado DECIMAL(15,2) DEFAULT 0,
    fecha_registro DATE,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data for Testing
INSERT INTO vider_dependencias (nombre, siglas) VALUES 
('Dirección de Extensión Rural', 'DICORER'),
('Fondo de Tierras', 'FONTIERRAS'),
('Desarrollo de Comunidades', 'DDC');

-- IDs would be 1, 2, 3
INSERT INTO vider_catalogos (dependencia_id, tipo, nombre) VALUES 
(1, 'ACTIVIDAD', 'Asistencia Técnica'),
(1, 'PRODUCTO', 'Semilla de Maíz'),
(1, 'INTERVENCION', 'Huertos Comunales'),
(2, 'ACTIVIDAD', 'Regularización de Tierras');

INSERT INTO vider_ejecucion (fecha, departamento, municipio, dependencia_id, actividad_id, producto_id, intervencion_id, genero, fisico_tipo, fisico_planificado, fisico_ejecutado, financiero_vigente, financiero_ejecutado) VALUES 
('2026-02-01', 'GUATEMALA', 'GUATEMALA', 1, 1, 2, 3, 'M', 'PERSONAS', 100, 85, 50000, 42000),
('2026-02-05', 'JALAPA', 'SAN PEDRO PINULA', 1, 1, 2, 3, 'H', 'PERSONAS', 50, 48, 20000, 19500);

INSERT INTO vider_tobanik (departamento, nombre_cooperativa, productores, monto_colocado, monto_otorgado) VALUES 
('HUEHUETENANGO', 'Cooperativa El Paraíso', 150, 500000, 450000),
('QUICHE', 'Cooperativa Flor de Maíz', 85, 250000, 250000);
