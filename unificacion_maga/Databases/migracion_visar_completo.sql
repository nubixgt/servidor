-- Migración completa del módulo VISAR desde exportacionesmagaprueba
-- Prefijo usado: visar_

-- 1. Tabla: visar_exportaciones (SIIA)
CREATE TABLE IF NOT EXISTS visar_exportaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255),
    certificado VARCHAR(100),
    fecha_emision DATE,
    categoria_producto VARCHAR(100),
    pais_destino VARCHAR(100),
    producto VARCHAR(255),
    peso_neto DECIMAL(15, 2),
    valor_fob DECIMAL(15, 2),
    observaciones TEXT,
    destinatario VARCHAR(255),
    aduana VARCHAR(150),
    emisor VARCHAR(150),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla: visar_importaciones
CREATE TABLE IF NOT EXISTS visar_importaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_empresa VARCHAR(255),
    categoria_producto VARCHAR(100),
    producto VARCHAR(255),
    pais_origen VARCHAR(100),
    pais_procedencia VARCHAR(100),
    temperatura VARCHAR(50),
    no_bultos INT,
    no_lote VARCHAR(100),
    peso_neto DECIMAL(15, 2),
    valor_dolares DECIMAL(15, 2),
    tipo_valor VARCHAR(50),
    consignatario VARCHAR(255),
    aduana VARCHAR(150),
    observaciones TEXT,
    no_importacion VARCHAR(100),
    no_transaccion VARCHAR(100),
    no_recibo_electronico VARCHAR(100),
    fecha_emision DATE,
    sistema VARCHAR(100),
    emisor VARCHAR(150),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla: visar_licencias_transporte
CREATE TABLE IF NOT EXISTS visar_licencias_transporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_licencia VARCHAR(100),
    empresa VARCHAR(255),
    nit VARCHAR(50),
    placa VARCHAR(50),
    transporte_de VARCHAR(150),
    inspector VARCHAR(150),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla: visar_licencias_fitosanitarias
CREATE TABLE IF NOT EXISTS visar_licencias_fitosanitarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_recibo_osu VARCHAR(100),
    licencia VARCHAR(100),
    nombre_empresa VARCHAR(255),
    propietario VARCHAR(255),
    categoria VARCHAR(100),
    clasificacion VARCHAR(100),
    departamento VARCHAR(100),
    municipio VARCHAR(100),
    fecha_emision DATE,
    fecha_vencimiento DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla: visar_lv_siia (Libre Venta)
CREATE TABLE IF NOT EXISTS visar_lv_siia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa VARCHAR(255),
    numero_documento VARCHAR(100),
    producto VARCHAR(255),
    categoria_producto VARCHAR(100),
    pais_destino VARCHAR(100),
    emisor VARCHAR(150),
    fecha_emision DATE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

