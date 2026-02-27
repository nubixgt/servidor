-- Tabla de ventas (nueva_venta)
-- Almacena todas las ventas realizadas

CREATE TABLE IF NOT EXISTS nueva_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    vendedor ENUM('Felipe Machán', 'Jurandir Terreaux', 'Vinicio Arreaga') NOT NULL,
    cliente_id INT NOT NULL,
    nit VARCHAR(15) NOT NULL COMMENT 'Copiado del cliente',
    direccion TEXT NOT NULL COMMENT 'Copiado del cliente',
    tipo_venta ENUM('Contado', 'Crédito', 'Pruebas') NOT NULL,
    dias_credito INT NULL COMMENT 'Solo si tipo_venta = Crédito',
    producto VARCHAR(50) NOT NULL,
    presentacion VARCHAR(20) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL COMMENT 'Precio según producto+presentación',
    cantidad INT NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL COMMENT 'Calculado: (precio_unitario * cantidad) - descuento',
    usuario_id INT NOT NULL COMMENT 'Usuario que registró la venta',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    
    -- Índices
    INDEX idx_fecha (fecha),
    INDEX idx_vendedor (vendedor),
    INDEX idx_cliente (cliente_id),
    INDEX idx_tipo_venta (tipo_venta),
    INDEX idx_producto (producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos de ejemplo (opcional)
-- Asumiendo que existe cliente_id=1 y usuario_id=1
INSERT INTO nueva_venta (
    fecha, vendedor, cliente_id, nit, direccion, tipo_venta, dias_credito,
    producto, presentacion, precio_unitario, cantidad, descuento, total, usuario_id
) VALUES
(
    '2026-01-20',
    'Felipe Machán',
    1,
    '11652646-7',
    'Zona 1, 5ta Avenida 10-20',
    'Contado',
    NULL,
    'EM1',
    '1 litro',
    150.00,
    10,
    0.00,
    1500.00,
    1
),
(
    '2026-01-20',
    'Jurandir Terreaux',
    1,
    '11652646-7',
    'Zona 1, 5ta Avenida 10-20',
    'Crédito',
    30,
    'EMA',
    '20 litros',
    480.00,
    5,
    50.00,
    2350.00,
    1
);
