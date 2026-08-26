CREATE TABLE IF NOT EXISTS productores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dpi VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    apellido VARCHAR(150),
    finca VARCHAR(200),
    departamento VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    tipo ENUM('AGRÍCOLA', 'GANADERO', 'MIXTO') DEFAULT 'AGRÍCOLA',
    estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    fecha_registro DATE,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data
INSERT INTO productores (dpi, nombre, apellido, finca, departamento, municipio, tipo, estado, fecha_registro) VALUES 
('1234 56789 0101', 'Juan', 'Pérez', 'Finca Los Girasoles', 'JALAPA', 'JALAPA', 'AGRÍCOLA', 'ACTIVO', '2025-01-15'),
('9876 54321 0201', 'María', 'Castillo', 'Hacienda La Virgen', 'EL PROGRESO', 'GUASTATOYA', 'GANADERO', 'ACTIVO', '2025-02-10'),
('4567 12345 0301', 'Carlos', 'Ruiz', 'Parcela 45', 'CHIMALTENANGO', 'CHIMALTENANGO', 'MIXTO', 'INACTIVO', '2025-03-05');
