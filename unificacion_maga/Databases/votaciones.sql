-- Limpieza de tablas previas (Mocks o versiones anteriores) para evitar conflictos de columnas
DROP VIEW IF EXISTS votaciones_vista_estadisticas_congresista;
DROP VIEW IF EXISTS votaciones_vista_estadisticas_bloque;
DROP VIEW IF EXISTS votaciones_vista_detalle_eventos;
DROP TABLE IF EXISTS votaciones_historial_bloques;
DROP TABLE IF EXISTS votaciones_resumen_eventos;
DROP TABLE IF EXISTS votaciones_votos;
DROP TABLE IF EXISTS votaciones_eventos;
DROP TABLE IF EXISTS votaciones_congresistas;
DROP TABLE IF EXISTS votaciones_bloques;

-- Tabla de eventos de votación
CREATE TABLE IF NOT EXISTS votaciones_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_evento VARCHAR(50) NOT NULL,
    titulo TEXT NOT NULL,
    descripcion TEXT,
    sesion_numero VARCHAR(50),
    fecha_hora DATETIME NOT NULL,
    archivo_origen VARCHAR(255),
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento (numero_evento),
    INDEX idx_fecha (fecha_hora),
    INDEX idx_sesion (sesion_numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de congresistas
CREATE TABLE IF NOT EXISTS votaciones_congresistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    nombre_normalizado VARCHAR(255),
    foto VARCHAR(255) DEFAULT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_nombre (nombre_normalizado),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de bloques políticos
CREATE TABLE IF NOT EXISTS votaciones_bloques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    nombre_corto VARCHAR(100),
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    UNIQUE KEY idx_nombre (nombre),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de votos
CREATE TABLE IF NOT EXISTS votaciones_votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    congresista_id INT NOT NULL,
    bloque_id INT,
    voto ENUM('A FAVOR', 'EN CONTRA', 'AUSENTE', 'LICENCIA', 'ABSTENCION') NOT NULL,
    numero_orden INT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES votaciones_eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (congresista_id) REFERENCES votaciones_congresistas(id) ON DELETE CASCADE,
    FOREIGN KEY (bloque_id) REFERENCES votaciones_bloques(id) ON DELETE SET NULL,
    UNIQUE KEY idx_voto_unico (evento_id, congresista_id),
    INDEX idx_evento (evento_id),
    INDEX idx_congresista (congresista_id),
    INDEX idx_voto (voto),
    INDEX idx_bloque (bloque_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de resumen de eventos
CREATE TABLE IF NOT EXISTS votaciones_resumen_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    total_votos INT DEFAULT 0,
    votos_favor INT DEFAULT 0,
    votos_contra INT DEFAULT 0,
    votos_ausentes INT DEFAULT 0,
    votos_licencia INT DEFAULT 0,
    votos_abstencion INT DEFAULT 0,
    resultado ENUM('APROBADO', 'RECHAZADO', 'PENDIENTE', 'EMPATE') DEFAULT 'PENDIENTE',
    fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES votaciones_eventos(id) ON DELETE CASCADE,
    UNIQUE KEY idx_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de bloques (para congresistas que cambian de bloque)
CREATE TABLE IF NOT EXISTS votaciones_historial_bloques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    congresista_id INT NOT NULL,
    bloque_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (congresista_id) REFERENCES votaciones_congresistas(id) ON DELETE CASCADE,
    FOREIGN KEY (bloque_id) REFERENCES votaciones_bloques(id) ON DELETE CASCADE,
    INDEX idx_congresista (congresista_id),
    INDEX idx_bloque (bloque_id),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vista para estadísticas por congresista
CREATE OR REPLACE VIEW votaciones_vista_estadisticas_congresista AS
SELECT 
    c.id,
    c.nombre,
    COUNT(v.id) as total_votaciones,
    SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as votos_favor,
    SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as votos_contra,
    SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausencias,
    SUM(CASE WHEN v.voto = 'LICENCIA' THEN 1 ELSE 0 END) as licencias,
    ROUND(SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) * 100.0 / 
          NULLIF(SUM(CASE WHEN v.voto IN ('A FAVOR', 'EN CONTRA') THEN 1 ELSE 0 END), 0), 2) as porcentaje_favor,
    ROUND(SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) * 100.0 / 
          COUNT(v.id), 2) as porcentaje_ausencias
FROM votaciones_congresistas c
LEFT JOIN votaciones_votos v ON c.id = v.congresista_id
GROUP BY c.id, c.nombre;

-- Vista para estadísticas por bloque
CREATE OR REPLACE VIEW votaciones_vista_estadisticas_bloque AS
SELECT 
    b.id,
    b.nombre,
    COUNT(DISTINCT v.congresista_id) as total_congresistas,
    COUNT(v.id) as total_votos,
    SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as votos_favor,
    SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as votos_contra,
    SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausencias,
    ROUND(SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) * 100.0 / 
          NULLIF(SUM(CASE WHEN v.voto IN ('A FAVOR', 'EN CONTRA') THEN 1 ELSE 0 END), 0), 2) as porcentaje_favor
FROM votaciones_bloques b
LEFT JOIN votaciones_votos v ON b.id = v.bloque_id
GROUP BY b.id, b.nombre;

-- Vista para estadísticas por evento
CREATE OR REPLACE VIEW votaciones_vista_detalle_eventos AS
SELECT 
    e.id,
    e.numero_evento,
    e.titulo,
    e.sesion_numero,
    e.fecha_hora,
    r.total_votos,
    r.votos_favor,
    r.votos_contra,
    r.votos_ausentes,
    r.votos_licencia,
    r.resultado,
    e.archivo_origen
FROM votaciones_eventos e
LEFT JOIN votaciones_resumen_eventos r ON e.id = r.evento_id
ORDER BY e.fecha_hora DESC;
