-- Script para actualizar la base de datos existente y agregar el prefijo 'votaciones_'
-- a las tablas del módulo de Votaciones, preservando los datos actuales.

-- 1. Renombrar las tablas base
RENAME TABLE eventos_votacion TO votaciones_eventos;
RENAME TABLE congresistas TO votaciones_congresistas;
RENAME TABLE bloques TO votaciones_bloques;
RENAME TABLE votos TO votaciones_votos;
RENAME TABLE resumen_eventos TO votaciones_resumen_eventos;
RENAME TABLE historial_bloques TO votaciones_historial_bloques;

-- 2. Eliminar vistas antiguas
DROP VIEW IF EXISTS vista_estadisticas_congresista;
DROP VIEW IF EXISTS vista_estadisticas_bloque;
DROP VIEW IF EXISTS vista_detalle_eventos;

-- 3. Crear las nuevas vistas actualizadas con las nuevas tablas
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
