-- EleccionCYD - Esquema inicial: tabla de usuarios (login del jurado)
-- Ejecutar directamente en la base de datos configurada en Backend/config/database.php

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol VARCHAR(30) NOT NULL DEFAULT 'admin',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario administrador inicial.
-- usuario: admin
-- contraseña: admin123  (hash bcrypt, verificable con password_verify() en PHP)
INSERT INTO usuarios (usuario, password, nombre, rol) VALUES
('admin', '$2b$10$R6zmnnEIzXDrrxLRBbCYNOm22urniQw4Hf1ucpBa/tp4SttmMWhM.', 'Jurado Oficial', 'admin');
