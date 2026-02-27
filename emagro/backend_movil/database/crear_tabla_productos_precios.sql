-- Tabla de productos con precios
-- Almacena la relación producto-presentación-precio según la tabla de precios

CREATE TABLE IF NOT EXISTS productos_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto VARCHAR(50) NOT NULL,
    presentacion VARCHAR(20) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_producto_presentacion (producto, presentacion),
    INDEX idx_producto (producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de productos y precios según la tabla
-- EM1
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM1', '1 litro', 150.00, 0),
('EM1', '4 litros', 540.00, 0),
('EM1', '20 litros', 2400.00, 0),
('EM1', '200 litros', 21000.00, 0),
('EM1', '1000 litros', 90000.00, 0);

-- EMA
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EMA', '1 litro', 30.00, 0),
('EMA', '4 litros', 108.00, 0),
('EMA', '20 litros', 480.00, 0),
('EMA', '200 litros', 4200.00, 0),
('EMA', '1000 litros', 17000.00, 0);

-- EM SuperSuelo
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperSuelo', '1 litro', 35.00, 0),
('EM SuperSuelo', '4 litros', 120.00, 0),
('EM SuperSuelo', '20 litros', 550.00, 0),
('EM SuperSuelo', '200 litros', 5000.00, 0),
('EM SuperSuelo', '1000 litros', 21000.00, 0);

-- EM SuperAgua
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperAgua', '1 litro', 30.00, 0),
('EM SuperAgua', '4 litros', 110.00, 0),
('EM SuperAgua', '20 litros', 525.00, 0),
('EM SuperAgua', '200 litros', 4800.00, 0),
('EM SuperAgua', '1000 litros', 20000.00, 0);

-- EM SuperRaiz (no tiene 1000 litros)
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperRaiz', '1 litro', 90.00, 0),
('EM SuperRaiz', '4 litros', 325.00, 0),
('EM SuperRaiz', '20 litros', 1540.00, 0),
('EM SuperRaiz', '200 litros', 14600.00, 0);

-- EM SuperFoliar (no tiene 1000 litros)
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperFoliar', '1 litro', 170.00, 0),
('EM SuperFoliar', '4 litros', 660.00, 0),
('EM SuperFoliar', '20 litros', 3150.00, 0),
('EM SuperFoliar', '200 litros', 30000.00, 0);

-- EM SuperFruto (no tiene 1000 litros)
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperFruto', '1 litro', 170.00, 0),
('EM SuperFruto', '4 litros', 660.00, 0),
('EM SuperFruto', '20 litros', 3150.00, 0),
('EM SuperFruto', '200 litros', 30000.00, 0);

-- EM SuperCompost
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperCompost', '1 litro', 35.00, 0),
('EM SuperCompost', '4 litros', 120.00, 0),
('EM SuperCompost', '20 litros', 550.00, 0),
('EM SuperCompost', '200 litros', 5000.00, 0),
('EM SuperCompost', '1000 litros', 21000.00, 0);

-- EM SuperAnimal (no tiene 1000 litros)
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperAnimal', '1 litro', 170.00, 0),
('EM SuperAnimal', '4 litros', 660.00, 0),
('EM SuperAnimal', '20 litros', 3150.00, 0),
('EM SuperAnimal', '200 litros', 30000.00, 0);

-- EM SuperMelaza (no tiene 1000 litros)
INSERT INTO productos_precios (producto, presentacion, precio, cantidad) VALUES
('EM SuperMelaza', '1 litro', 15.00, 0),
('EM SuperMelaza', '4 litros', 54.00, 0),
('EM SuperMelaza', '20 litros', 230.00, 0),
('EM SuperMelaza', '200 litros', 1800.00, 0);
