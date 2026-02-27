-- Tabla para almacenar las notas de envío
CREATE TABLE IF NOT EXISTS nota_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_nota VARCHAR(10) NOT NULL UNIQUE,
    fecha DATE NOT NULL,
    vendedor VARCHAR(100) NOT NULL,
    cliente_id INT NOT NULL,
    cliente_nombre VARCHAR(150) NOT NULL,
    nit VARCHAR(15) NOT NULL,
    direccion TEXT NOT NULL,
    tipo_venta ENUM('Contado', 'Crédito', 'Pruebas', 'Bonificación') NOT NULL,
    dias_credito INT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    descuento_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    usuario_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para búsqueda rápida por número de nota
CREATE INDEX idx_numero_nota ON nota_envio(numero_nota);

-- Índice para búsqueda por fecha
CREATE INDEX idx_fecha ON nota_envio(fecha);
