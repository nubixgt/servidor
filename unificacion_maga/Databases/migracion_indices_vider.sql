-- Script para optimizar la velocidad de carga del módulo VIDER y Dashboard Global
-- Agrega índices a las columnas más utilizadas en los JOINs, WHERE y GROUP BY
-- Esto soluciona el problema de "tarda un poco en cargar los datos"

ALTER TABLE vider_datos 
ADD INDEX IF NOT EXISTS idx_departamento (departamento_id),
ADD INDEX IF NOT EXISTS idx_municipio (municipio_id),
ADD INDEX IF NOT EXISTS idx_dependencia (dependencia_id),
ADD INDEX IF NOT EXISTS idx_medida (medida_id),
ADD INDEX IF NOT EXISTS idx_actividad (actividad_id),
ADD INDEX IF NOT EXISTS idx_producto (producto_id),
ADD INDEX IF NOT EXISTS idx_intervencion (intervencion_id);
