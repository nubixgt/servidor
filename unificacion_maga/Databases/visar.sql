-- Inspecciones Sanitarias (Fito/Zoo/Inocuidad)
CREATE TABLE IF NOT EXISTS visar_inspecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    area ENUM('FITOSANITARIO', 'ZOOSANITARIO', 'INOCUIDAD') NOT NULL,
    productor VARCHAR(200) NOT NULL,
    ubicacion VARCHAR(255),
    fecha DATE NOT NULL,
    motivo VARCHAR(255),
    estado ENUM('APROBADO', 'EN REVISIÓN', 'EN INSPECCIÓN', 'AGENDA', 'RECHAZADO') DEFAULT 'AGENDA',
    riesgo ENUM('ALTO', 'MEDIO', 'BAJO') DEFAULT 'BAJO',
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Licencias y Permisos
CREATE TABLE IF NOT EXISTS visar_licencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento VARCHAR(50) NOT NULL UNIQUE,
    tipo ENUM('Exportación', 'Importación', 'Transporte', 'Funcionamiento') NOT NULL,
    subtipo VARCHAR(100),
    titular VARCHAR(200) NOT NULL,
    identificacion VARCHAR(50), -- NIT or DPI
    fecha_emision DATE,
    fecha_vencimiento DATE,
    estado ENUM('VIGENTE', 'EN TRAMITE', 'POR VENCER', 'VENCIDA') DEFAULT 'EN TRAMITE',
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data for testing
INSERT INTO visar_inspecciones (codigo, area, productor, ubicacion, fecha, motivo, estado, riesgo) VALUES 
('CERT-FITO-2026-001', 'FITOSANITARIO', 'Finca Los Girasoles', 'Jalapa, Jalapa', '2026-02-15', 'Exportación Melón', 'APROBADO', 'BAJO'),
('INSP-ZOO-2026-042', 'ZOOSANITARIO', 'Hacienda La Virgen', 'El Progreso', '2026-02-20', 'Brote Sospechoso', 'EN INSPECCIÓN', 'ALTO');

INSERT INTO visar_licencias (documento, tipo, subtipo, titular, identificacion, fecha_emision, fecha_vencimiento, estado) VALUES 
('LIC-EXP-2026-0453', 'Exportación', 'Productos Pecuarios', 'Agrícola San Juan, S.A.', '4589230-1', '2026-01-10', '2027-01-10', 'VIGENTE'),
('PER-IMP-2026-1102', 'Importación', 'Fertilizantes Orgánicos', 'Distribuidora Agro Central', '8910234-5', NULL, NULL, 'EN TRAMITE');
