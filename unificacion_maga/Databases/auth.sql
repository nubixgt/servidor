CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    rol ENUM('ADMIN', 'TECNICO', 'CONSULTOR') DEFAULT 'CONSULTOR',
    activo TINYINT(1) DEFAULT 1,
    ultimo_login DATETIME,
    creado_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial administrator (password: admin123)
-- In a real scenario, this hash should be generated via password_hash()
INSERT INTO usuarios (username, password, email, nombre_completo, rol) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@maga.gob.gt', 'Administrador General', 'ADMIN'),
('tecnico', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tecnico@maga.gob.gt', 'Técnico de Campo', 'TECNICO');
