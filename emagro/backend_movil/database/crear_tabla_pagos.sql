-- Tabla para almacenar los registros de pagos de facturas a crédito
CREATE TABLE IF NOT EXISTS pagos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    fecha_pago DATE NOT NULL,
    banco ENUM('Banco G&T Continental', 'Banco Industrial', 'BAC Credomatic', 'Banrural', 'Bantrab') NOT NULL,
    monto_pago DECIMAL(10,2) NOT NULL,
    referencia_transaccion VARCHAR(100) NOT NULL,
    usuario_id INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES nota_envio(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_factura_id (factura_id),
    INDEX idx_fecha_pago (fecha_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
