-- Database schema for complaints and attachments
CREATE TABLE IF NOT EXISTS denuncias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_denunciante VARCHAR(255) NOT NULL,
    dpi_denunciante VARCHAR(20) NOT NULL,
    edad_denunciante INT,
    genero_denunciante VARCHAR(20),
    celular_denunciante VARCHAR(20),
    correo_denunciante VARCHAR(255),
    direccion_hecho TEXT NOT NULL,
    departamento_hecho VARCHAR(100) NOT NULL,
    municipio_hecho VARCHAR(100) NOT NULL,
    latitud DECIMAL(10, 8),
    longitud DECIMAL(11, 8),
    especie VARCHAR(50) NOT NULL,
    cantidad_animales INT DEFAULT 1,
    descripcion TEXT NOT NULL,
    infracciones TEXT, -- Almacenado como JSON o lista separada por comas
    acepto_declaracion TINYINT(1) DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS archivos_denuncia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    denuncia_id INT NOT NULL,
    tipo_archivo ENUM('dpi_frontal', 'dpi_reverso', 'fachada', 'evidencia_foto', 'evidencia_documento') NOT NULL,
    ruta_archivo VARCHAR(512) NOT NULL,
    nombre_original VARCHAR(255),
    mime_type VARCHAR(100),
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (denuncia_id) REFERENCES denuncias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
