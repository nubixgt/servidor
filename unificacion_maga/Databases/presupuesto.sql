CREATE TABLE IF NOT EXISTS presupuesto_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo ENUM('PROGRAMA', 'UNIDAD_EJECUTORA', 'GRUPO_GASTO', 'MINISTERIO', 'FUENTE_FINANCIAMIENTO') NOT NULL,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Budget Items (Records)
CREATE TABLE IF NOT EXISTS presupuesto_ejecucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT,
    ejercicio_fiscal INT NOT NULL,
    asignado DECIMAL(18, 2) DEFAULT 0.00,
    modificado DECIMAL(18, 2) DEFAULT 0.00,
    vigente DECIMAL(18, 2) DEFAULT 0.00,
    devengado DECIMAL(18, 2) DEFAULT 0.00,
    saldo DECIMAL(18, 2) DEFAULT 0.00,
    pct_ejec DECIMAL(8, 4) DEFAULT 0.00,
    fecha_corte DATE NOT NULL,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES presupuesto_categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Budget Logs / Audit
CREATE TABLE IF NOT EXISTS presupuesto_bitacora (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100),
    accion VARCHAR(255),
    detalles TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial data
INSERT INTO presupuesto_categorias (codigo, nombre, tipo) VALUES 
('201', 'Programa 201', 'PROGRAMA'),
('204', 'Programa 204', 'PROGRAMA'),
('205', 'Programa 205', 'PROGRAMA'),
('31', 'Código 31', 'GRUPO_GASTO'),
('11', 'Unidad Ejecutora 11', 'UNIDAD_EJECUTORA');

INSERT INTO presupuesto_ejecucion (categoria_id, ejercicio_fiscal, asignado, modificado, vigente, devengado, saldo, pct_ejec, fecha_corte) VALUES 
(1, 2026, 545007427.00, 157602682.00, 702610109.00, 670379957.77, 32230151.23, 95.41, '2026-02-28'),
(2, 2026, 766139591.00, -346245304.00, 419894287.00, 411374043.93, 8520243.07, 97.97, '2026-02-28');
