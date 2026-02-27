-- =====================================================
-- Tabla de Clientes para Emagro
-- =====================================================

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    nit VARCHAR(15) NOT NULL,
    telefono VARCHAR(10) NOT NULL,
    departamento VARCHAR(50) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    direccion TEXT NOT NULL,
    email VARCHAR(100) NULL,
    bloquear_ventas ENUM('si', 'no') NOT NULL DEFAULT 'no',
    usuario_id INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nit (nit),
    INDEX idx_nombre (nombre),
    INDEX idx_departamento (departamento),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Comentarios sobre los campos:
-- =====================================================
-- nombre: Nombre completo del cliente (texto libre)
-- nit: Formato 11652646-7 o "CF" (Consumidor Final)
-- telefono: Formato 4528-9012 (8 dígitos con guión)
-- departamento: Departamento de Guatemala seleccionado
-- municipio: Municipio del departamento seleccionado
-- direccion: Dirección completa (texto libre)
-- email: Correo electrónico (opcional)
-- bloquear_ventas: 'si' o 'no' - Solo visible en edición
-- usuario_id: ID del usuario que creó el cliente (relación con tabla usuarios)
-- =====================================================

-- Insertar algunos clientes de ejemplo (asumiendo que el usuario admin tiene id=1)
INSERT INTO clientes (nombre, nit, telefono, departamento, municipio, direccion, email, bloquear_ventas, usuario_id) 
VALUES 
('Juan Pérez López', '11652646-7', '4528-9012', 'Guatemala', 'Guatemala', 'Zona 1, 5ta Avenida 10-20', 'juan.perez@example.com', 'no', 1),
('María García', 'CF', '5512-3456', 'Sacatepéquez', 'Antigua Guatemala', 'Calle del Arco, Casa #15', 'maria.garcia@example.com', 'no', 1),
('Carlos Rodríguez', '98765432-1', '7789-4561', 'Escuintla', 'Escuintla', 'Barrio El Centro, 3ra Calle 8-45', NULL, 'no', 1);
