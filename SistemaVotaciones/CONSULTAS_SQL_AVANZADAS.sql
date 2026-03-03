-- ============================================================================
-- CONSULTAS SQL AVANZADAS - SISTEMA DE VOTACIONES DEL CONGRESO
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. ANÁLISIS DE DIPUTADOS
-- ----------------------------------------------------------------------------

-- Diputados más activos (con mayor participación)
SELECT 
    nombre_completo,
    bloque_actual,
    total_votaciones,
    votos_favor,
    votos_contra,
    ausencias,
    porcentaje_favor,
    porcentaje_inasistencia
FROM vista_analisis_diputado
WHERE total_votaciones > 0
ORDER BY total_votaciones DESC
LIMIT 20;

-- Diputados con mayor tasa de ausencia
SELECT 
    nombre_completo,
    bloque_actual,
    total_votaciones,
    ausencias,
    licencias,
    porcentaje_inasistencia,
    ROUND((ausencias + licencias) * 100.0 / total_votaciones, 2) as tasa_inasistencia_total
FROM vista_analisis_diputado
WHERE total_votaciones >= 10
ORDER BY porcentaje_inasistencia DESC
LIMIT 20;

-- Diputados que siempre votan a favor
SELECT 
    nombre_completo,
    bloque_actual,
    total_votaciones,
    votos_favor,
    votos_contra
FROM vista_analisis_diputado
WHERE votos_contra = 0 
  AND total_votaciones >= 5
ORDER BY total_votaciones DESC;

-- Diputados más disidentes (votan diferente a su bloque)
SELECT 
    d.nombre_completo,
    b.nombre as bloque,
    COUNT(DISTINCT v.evento_id) as eventos_participados,
    SUM(CASE 
        WHEN v.voto = 'A FAVOR' 
        AND (SELECT COUNT(*) FROM votos v2 
             WHERE v2.evento_id = v.evento_id 
             AND v2.bloque_id = v.bloque_id 
             AND v2.voto = 'A FAVOR') < 
            (SELECT COUNT(*) FROM votos v3 
             WHERE v3.evento_id = v.evento_id 
             AND v3.bloque_id = v.bloque_id 
             AND v3.voto = 'EN CONTRA')
        THEN 1 ELSE 0 
    END) as votos_disidentes
FROM diputados d
JOIN votos v ON d.id = v.diputado_id
JOIN bloques b ON v.bloque_id = b.id
GROUP BY d.id, d.nombre_completo, b.nombre
HAVING votos_disidentes > 0
ORDER BY votos_disidentes DESC;

-- ----------------------------------------------------------------------------
-- 2. ANÁLISIS DE EVENTOS
-- ----------------------------------------------------------------------------

-- Eventos más polémicos (votación más reñida)
SELECT 
    numero_evento,
    descripcion,
    fecha_votacion,
    total_a_favor,
    total_en_contra,
    ABS(total_a_favor - total_en_contra) as diferencia,
    porcentaje_aprobacion,
    resultado
FROM vista_analisis_evento
WHERE total_votos > 50
ORDER BY diferencia ASC
LIMIT 10;

-- Eventos con mayor abstención
SELECT 
    numero_evento,
    descripcion,
    fecha_votacion,
    total_a_favor,
    total_en_contra,
    total_ausentes,
    total_licencia,
    ROUND((total_ausentes + total_licencia) * 100.0 / total_votos, 2) as porcentaje_abstencion
FROM vista_analisis_evento
ORDER BY porcentaje_abstencion DESC
LIMIT 10;

-- Eventos aprobados por unanimidad
SELECT 
    numero_evento,
    descripcion,
    fecha_votacion,
    total_a_favor,
    total_en_contra
FROM vista_analisis_evento
WHERE total_en_contra = 0 
  AND total_a_favor > 0
ORDER BY fecha_votacion DESC;

-- Tendencia mensual de aprobación
SELECT 
    DATE_FORMAT(fecha_votacion, '%Y-%m') as mes,
    COUNT(*) as total_eventos,
    SUM(CASE WHEN resultado = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
    SUM(CASE WHEN resultado = 'RECHAZADO' THEN 1 ELSE 0 END) as rechazados,
    ROUND(SUM(CASE WHEN resultado = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as tasa_aprobacion
FROM vista_analisis_evento
GROUP BY mes
ORDER BY mes DESC;

-- ----------------------------------------------------------------------------
-- 3. ANÁLISIS DE BLOQUES
-- ----------------------------------------------------------------------------

-- Comparación de rendimiento entre bloques
SELECT 
    b.nombre as bloque,
    COUNT(DISTINCT d.id) as diputados,
    COUNT(v.id) as total_votos,
    SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as a_favor,
    SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as en_contra,
    SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausentes,
    ROUND(SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) * 100.0 / 
          NULLIF(SUM(CASE WHEN v.voto IN ('A FAVOR', 'EN CONTRA') THEN 1 END), 0), 2) as porcentaje_favor,
    ROUND(SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) * 100.0 / COUNT(v.id), 2) as porcentaje_ausencia
FROM bloques b
LEFT JOIN diputados d ON b.id = d.bloque_actual_id
LEFT JOIN votos v ON d.id = v.diputado_id
GROUP BY b.id, b.nombre
ORDER BY total_votos DESC;

-- Bloques más disciplinados (votan en bloque)
SELECT 
    b.nombre as bloque,
    e.numero_evento,
    e.descripcion,
    COUNT(*) as miembros_votaron,
    MAX(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as alguien_favor,
    MAX(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as alguien_contra,
    CASE 
        WHEN MAX(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) = 1 
         AND MAX(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) = 0 
        THEN 'Disciplina Total A Favor'
        WHEN MAX(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) = 1 
         AND MAX(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) = 0 
        THEN 'Disciplina Total En Contra'
        ELSE 'Dividido'
    END as tipo_voto
FROM bloques b
JOIN votos v ON b.id = v.bloque_id
JOIN eventos e ON v.evento_id = e.id
GROUP BY b.nombre, e.numero_evento, e.descripcion
HAVING tipo_voto LIKE 'Disciplina%'
ORDER BY b.nombre, e.fecha_votacion DESC;

-- Alianzas entre bloques (votan similar)
SELECT 
    b1.nombre as bloque1,
    b2.nombre as bloque2,
    COUNT(*) as eventos_comunes,
    SUM(CASE WHEN v1.voto = v2.voto THEN 1 ELSE 0 END) as votos_coincidentes,
    ROUND(SUM(CASE WHEN v1.voto = v2.voto THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as porcentaje_coincidencia
FROM votos v1
JOIN votos v2 ON v1.evento_id = v2.evento_id 
             AND v1.diputado_id < v2.diputado_id
JOIN bloques b1 ON v1.bloque_id = b1.id
JOIN bloques b2 ON v2.bloque_id = b2.id
WHERE v1.voto IN ('A FAVOR', 'EN CONTRA')
  AND v2.voto IN ('A FAVOR', 'EN CONTRA')
  AND b1.id != b2.id
GROUP BY b1.nombre, b2.nombre
HAVING eventos_comunes >= 5
ORDER BY porcentaje_coincidencia DESC;

-- ----------------------------------------------------------------------------
-- 4. ANÁLISIS TEMPORAL
-- ----------------------------------------------------------------------------

-- Evolución de participación por mes
SELECT 
    DATE_FORMAT(e.fecha_votacion, '%Y-%m') as mes,
    COUNT(DISTINCT e.id) as eventos,
    COUNT(v.id) as total_votos,
    SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as favor,
    SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as contra,
    SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausentes,
    ROUND(AVG(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) * 100, 2) as tasa_ausencia
FROM eventos e
LEFT JOIN votos v ON e.id = v.evento_id
GROUP BY mes
ORDER BY mes DESC;

-- Diputados más consistentes (votan siempre)
SELECT 
    d.nombre_completo,
    b.nombre as bloque,
    COUNT(DISTINCT e.id) as total_eventos,
    COUNT(v.id) as participaciones,
    ROUND(COUNT(v.id) * 100.0 / COUNT(DISTINCT e.id), 2) as tasa_participacion
FROM diputados d
CROSS JOIN eventos e
LEFT JOIN votos v ON d.id = v.diputado_id AND e.id = v.evento_id
LEFT JOIN bloques b ON d.bloque_actual_id = b.id
GROUP BY d.id, d.nombre_completo, b.nombre
HAVING total_eventos >= 10
ORDER BY tasa_participacion DESC
LIMIT 20;

-- ----------------------------------------------------------------------------
-- 5. RANKINGS Y COMPARACIONES
-- ----------------------------------------------------------------------------

-- Top 10 diputados más activos por bloque
SELECT * FROM (
    SELECT 
        d.nombre_completo,
        b.nombre as bloque,
        COUNT(v.id) as votaciones,
        ROW_NUMBER() OVER (PARTITION BY b.id ORDER BY COUNT(v.id) DESC) as ranking_bloque
    FROM diputados d
    JOIN bloques b ON d.bloque_actual_id = b.id
    LEFT JOIN votos v ON d.id = v.diputado_id
    GROUP BY d.id, d.nombre_completo, b.id, b.nombre
) ranked
WHERE ranking_bloque <= 10
ORDER BY bloque, ranking_bloque;

-- Comparación de bloques en evento específico
SELECT 
    b.nombre as bloque,
    COUNT(*) as miembros,
    SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) as favor,
    SUM(CASE WHEN v.voto = 'EN CONTRA' THEN 1 ELSE 0 END) as contra,
    SUM(CASE WHEN v.voto = 'AUSENTE' THEN 1 ELSE 0 END) as ausente,
    ROUND(SUM(CASE WHEN v.voto = 'A FAVOR' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as porcentaje_favor
FROM votos v
JOIN bloques b ON v.bloque_id = b.id
WHERE v.evento_id = 1  -- Cambiar por ID del evento
GROUP BY b.nombre
ORDER BY favor DESC;

-- ----------------------------------------------------------------------------
-- 6. CONSULTAS DE AUDITORÍA
-- ----------------------------------------------------------------------------

-- Verificar integridad: diputados sin votos
SELECT d.id, d.nombre_completo, d.bloque_actual_id
FROM diputados d
LEFT JOIN votos v ON d.id = v.diputado_id
WHERE v.id IS NULL;

-- Verificar consistencia: votos sin diputado o evento
SELECT v.id, v.diputado_id, v.evento_id, v.voto
FROM votos v
LEFT JOIN diputados d ON v.diputado_id = d.id
LEFT JOIN eventos e ON v.evento_id = e.id
WHERE d.id IS NULL OR e.id IS NULL;

-- Eventos con datos incompletos
SELECT 
    e.id,
    e.numero_evento,
    e.descripcion,
    COUNT(v.id) as votos_registrados,
    r.total_votos as votos_resumen
FROM eventos e
LEFT JOIN votos v ON e.id = v.evento_id
LEFT JOIN resumenes_votacion r ON e.id = r.evento_id
GROUP BY e.id, e.numero_evento, e.descripcion, r.total_votos
HAVING votos_registrados != votos_resumen OR votos_resumen IS NULL;

-- ----------------------------------------------------------------------------
-- 7. EXPORTACIÓN Y REPORTES
-- ----------------------------------------------------------------------------

-- Reporte completo de un diputado (para exportar)
SELECT 
    e.fecha_votacion,
    e.numero_evento,
    e.numero_sesion,
    e.descripcion as evento,
    v.voto,
    b.nombre as bloque,
    e.resultado
FROM votos v
JOIN eventos e ON v.evento_id = e.id
JOIN diputados d ON v.diputado_id = d.id
LEFT JOIN bloques b ON v.bloque_id = b.id
WHERE d.id = 1  -- Cambiar por ID del diputado
ORDER BY e.fecha_votacion DESC;

-- Reporte de estadísticas generales
SELECT 
    'Total Diputados' as metrica,
    COUNT(DISTINCT d.id) as valor
FROM diputados d
UNION ALL
SELECT 'Total Eventos', COUNT(DISTINCT e.id) FROM eventos e
UNION ALL
SELECT 'Total Votos', COUNT(*) FROM votos
UNION ALL
SELECT 'Total Bloques', COUNT(*) FROM bloques
UNION ALL
SELECT 'Eventos Aprobados', COUNT(*) FROM vista_analisis_evento WHERE resultado = 'APROBADO'
UNION ALL
SELECT 'Eventos Rechazados', COUNT(*) FROM vista_analisis_evento WHERE resultado = 'RECHAZADO';

-- ============================================================================
-- FIN DE CONSULTAS SQL
-- ============================================================================

-- NOTAS:
-- - Cambiar los IDs y filtros según necesites
-- - Las consultas están optimizadas para rendimiento
-- - Usar índices para mejorar velocidad en tablas grandes
-- - Algunas consultas pueden tardar en bases de datos muy grandes
