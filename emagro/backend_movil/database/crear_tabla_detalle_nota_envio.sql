-- Tabla para almacenar los detalles (productos) de cada nota de envío
CREATE TABLE IF NOT EXISTS detalle_nota_envio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nota_envio_id INT NOT NULL,
    producto VARCHAR(100) NOT NULL,
    presentacion VARCHAR(100) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    cantidad INT NOT NULL,
    es_bonificacion ENUM('si', 'no') NOT NULL DEFAULT 'no',
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (nota_envio_id) REFERENCES nota_envio(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índice para búsqueda rápida por nota de envío
CREATE INDEX idx_nota_envio_id ON detalle_nota_envio(nota_envio_id);
