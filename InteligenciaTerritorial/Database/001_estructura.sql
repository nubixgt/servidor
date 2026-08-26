-- Inteligencia Territorial - Estructura de BD

CREATE TABLE IF NOT EXISTS municipios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  departamento VARCHAR(100) NOT NULL,
  municipio VARCHAR(150) NOT NULL,
  alcalde VARCHAR(200) NOT NULL,
  partido_alcalde VARCHAR(200) NOT NULL,
  diputado_asignado TEXT,
  gpc VARCHAR(200),
  notas TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY (departamento, municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
