-- =====================================================================
-- 04 · TABLERO: la serie de fallecidos (KPI "Fallecidos", mini-gráfica y evolución por año)
--      pasa a calcularse con el CUR (resoluciones de fallecimiento, por año de mes_pago)
--      en lugar de core_expediente.fecha_fallecimiento.
--
-- Requiere 01..03 ya ejecutados. Solo redefine el procedimiento mart_refrescar() y lo ejecuta.
-- No modifica datos de expedientes. Re-ejecutable.
--   mariadb -u USUARIO -p NOMBRE_BASE < Database/actualizacion/04_fallecidos_tablero.sql
-- (puede tardar unos minutos: reconstruye los agregados del tablero)
-- =====================================================================
SET NAMES utf8mb4;
DELIMITER $$

DROP PROCEDURE IF EXISTS mart_refrescar$$
CREATE PROCEDURE mart_refrescar()
BEGIN
  DECLARE v_cohorte DATE;
  SELECT STR_TO_DATE(JSON_UNQUOTE(valor), '%Y-%m-%d') INTO v_cohorte FROM ref_parametro WHERE clave = 'cohorte_reciente_desde';
  SET v_cohorte = COALESCE(v_cohorte, '2023-01-01');

  DROP TABLE IF EXISTS mart_cubo_nuevo, mart_serie_nuevo, mart_tiempos_nuevo;
  CREATE TABLE mart_cubo_nuevo LIKE mart_cubo;
  CREATE TABLE mart_serie_nuevo LIKE mart_serie;
  CREATE TABLE mart_tiempos_nuevo LIKE mart_tiempos;

  -- Cubo: una fila por combinación de dimensiones.
  INSERT INTO mart_cubo_nuevo(municipio_id, e, a, anio, r, v, s, f, n, t, c, p, qd, qu, qx, qf, qe, qa, qs, qm)
  SELECT b.municipio_id, b.estado_orden, b.banda_edad, COALESCE(YEAR(b.fecha_solicitud), 0), COALESCE(f.orden, 9),
         b.visita_cod, (b.fecha_suspension IS NOT NULL), (b.fecha_fallecimiento IS NOT NULL), b.situacion_nomina, b.antiguedad,
         COUNT(*), SUM(b.rn = 1),
         SUM(b.q_doc), SUM(b.flag_dpi_duplicado), SUM(b.flag_exp_duplicado), SUM(b.q_fechas), SUM(b.q_edad),
         SUM(b.q_fall_activo), SUM(b.q_susp_activo), SUM(b.q_sin_tel)
  FROM (SELECT x.*, ROW_NUMBER() OVER (PARTITION BY x.persona_id ORDER BY x.fecha_solicitud IS NULL, x.fecha_solicitud DESC, x.id DESC) AS rn
        FROM core_v_expediente x) b
  LEFT JOIN ref_cat_familia_resolucion f ON f.id = b.ult_resolucion_familia_id
  GROUP BY b.municipio_id, b.estado_orden, b.banda_edad, COALESCE(YEAR(b.fecha_solicitud), 0), COALESCE(f.orden, 9),
           b.visita_cod, (b.fecha_suspension IS NOT NULL), (b.fecha_fallecimiento IS NOT NULL), b.situacion_nomina, b.antiguedad;

  -- Eventos por mes, municipio y estado actual del expediente.
  INSERT INTO mart_serie_nuevo(municipio_id, e, tipo, mes, n)
  SELECT x.municipio_id, ce.orden, ev.tipo, DATE_SUB(ev.fecha, INTERVAL DAYOFMONTH(ev.fecha) - 1 DAY), COUNT(*)
  FROM (
    SELECT id AS expediente_id, fecha_solicitud AS fecha, 'sol' AS tipo FROM core_expediente
    UNION ALL SELECT expediente_id, fecha, CASE WHEN resultado_id = 1 THEN 'vpos' ELSE 'vneg' END FROM core_visita
    UNION ALL SELECT expediente_id, fecha, 'res' FROM core_resolucion
    UNION ALL SELECT expediente_id, fecha, CASE WHEN tipo = 'ALTA' THEN 'ing' ELSE 'sal' END FROM core_movimiento_nomina
    UNION ALL SELECT id, fecha_suspension, 'susp' FROM core_expediente WHERE fecha_suspension IS NOT NULL
    -- Fallecidos: fuente oficial = resoluciones de fallecimiento del CUR, por mes de pago.
    UNION ALL SELECT expediente_id, mes_pago, 'fall' FROM core_cur_detalle
              WHERE es_fallecimiento = 1 AND expediente_id IS NOT NULL
    -- Respaldo: si aún no se ha cargado el CUR, se usa la fecha de fallecimiento del expediente.
    UNION ALL SELECT id, fecha_fallecimiento, 'fall' FROM core_expediente
              WHERE fecha_fallecimiento IS NOT NULL
                AND NOT EXISTS (SELECT 1 FROM core_cur_detalle WHERE es_fallecimiento = 1)) ev
  JOIN core_expediente x ON x.id = ev.expediente_id
  JOIN ref_cat_estado ce ON ce.id = x.estado_id
  WHERE ev.fecha >= '2006-01-01' AND ev.fecha <= CURDATE()
  GROUP BY x.municipio_id, ce.orden, ev.tipo, DATE_SUB(ev.fecha, INTERVAL DAYOFMONTH(ev.fecha) - 1 DAY);

  -- Tiempos del proceso (días): media, mediana y P90 por departamento (dep 0 = nacional).
  DROP TABLE IF EXISTS mart_t_base, mart_t_tramo, mart_t_in;
  CREATE TABLE mart_t_base(dep SMALLINT UNSIGNED, reciente TINYINT, fsol DATE, fvis DATE, fres DATE, fnom DATE) ENGINE=InnoDB;
  CREATE TABLE mart_t_tramo(dep SMALLINT UNSIGNED, reciente TINYINT, tramo VARCHAR(8), d INT) ENGINE=InnoDB;
  CREATE TABLE mart_t_in(dep SMALLINT UNSIGNED, cohorte VARCHAR(8), tramo VARCHAR(8), d INT, KEY (dep, cohorte, tramo)) ENGINE=InnoDB;

  INSERT INTO mart_t_base(dep, reciente, fsol, fvis, fres, fnom)
  SELECT m.departamento_id, COALESCE(e.fecha_solicitud >= v_cohorte, 0), e.fecha_solicitud,
         (SELECT MIN(v.fecha) FROM core_visita v
           WHERE v.expediente_id = e.id AND v.resultado_id = 1 AND v.fecha >= e.fecha_solicitud),
         (SELECT MIN(r.fecha) FROM core_resolucion r JOIN ref_cat_tipo_resolucion t ON t.id = r.tipo_id
           WHERE r.expediente_id = e.id AND t.familia_id = 1),
         e.fecha_nomina_desde
  FROM core_expediente e JOIN ref_municipio m ON m.id = e.municipio_id;

  INSERT INTO mart_t_tramo SELECT dep, reciente, 'sv', DATEDIFF(fvis, fsol) FROM mart_t_base WHERE DATEDIFF(fvis, fsol) >= 0;
  INSERT INTO mart_t_tramo SELECT dep, reciente, 'vr', DATEDIFF(fres, fvis) FROM mart_t_base WHERE DATEDIFF(fres, fvis) >= 0;
  INSERT INTO mart_t_tramo SELECT dep, reciente, 'rn', DATEDIFF(fnom, fres) FROM mart_t_base WHERE DATEDIFF(fnom, fres) >= 0;
  INSERT INTO mart_t_tramo SELECT dep, reciente, 'sn', DATEDIFF(fnom, fsol) FROM mart_t_base WHERE DATEDIFF(fnom, fsol) >= 0;

  INSERT INTO mart_t_in SELECT dep, 'all', tramo, d FROM mart_t_tramo;
  INSERT INTO mart_t_in SELECT dep, 'rec', tramo, d FROM mart_t_tramo WHERE reciente = 1;
  INSERT INTO mart_t_in SELECT 0, 'all', tramo, d FROM mart_t_tramo;
  INSERT INTO mart_t_in SELECT 0, 'rec', tramo, d FROM mart_t_tramo WHERE reciente = 1;

  -- mediana y P90 con interpolación lineal (equivale a percentile_cont)
  INSERT INTO mart_tiempos_nuevo(dep, cohorte, tramo, n, media, mediana, p90)
  SELECT z.dep, z.cohorte, z.tramo, MAX(z.cnt), ROUND(AVG(z.d)),
         ROUND(SUM(CASE WHEN z.rn = z.lo5 THEN z.d * (1 - z.f5) END) + COALESCE(SUM(CASE WHEN z.rn = z.lo5 + 1 THEN z.d * z.f5 END), 0)),
         ROUND(SUM(CASE WHEN z.rn = z.lo9 THEN z.d * (1 - z.f9) END) + COALESCE(SUM(CASE WHEN z.rn = z.lo9 + 1 THEN z.d * z.f9 END), 0))
  FROM (SELECT y.dep, y.cohorte, y.tramo, y.d, y.rn, y.cnt,
               FLOOR(1 + (y.cnt - 1) * 0.5) AS lo5, (y.cnt - 1) * 0.5 - FLOOR((y.cnt - 1) * 0.5) AS f5,
               FLOOR(1 + (y.cnt - 1) * 0.9) AS lo9, (y.cnt - 1) * 0.9 - FLOOR((y.cnt - 1) * 0.9) AS f9
        FROM (SELECT i.dep, i.cohorte, i.tramo, i.d,
                     ROW_NUMBER() OVER (PARTITION BY i.dep, i.cohorte, i.tramo ORDER BY i.d) AS rn,
                     COUNT(*) OVER (PARTITION BY i.dep, i.cohorte, i.tramo) AS cnt
              FROM mart_t_in i) y) z
  GROUP BY z.dep, z.cohorte, z.tramo;

  DROP TABLE IF EXISTS mart_t_base, mart_t_tramo, mart_t_in;

  -- Intercambio atómico
  DROP TABLE IF EXISTS mart_cubo_old, mart_serie_old, mart_tiempos_old;
  RENAME TABLE mart_cubo TO mart_cubo_old, mart_cubo_nuevo TO mart_cubo,
               mart_serie TO mart_serie_old, mart_serie_nuevo TO mart_serie,
               mart_tiempos TO mart_tiempos_old, mart_tiempos_nuevo TO mart_tiempos;
  DROP TABLE IF EXISTS mart_cubo_old, mart_serie_old, mart_tiempos_old;

  REPLACE INTO mart_meta(clave, valor) VALUES ('refrescado_en', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'));
END$$

DELIMITER ;

CALL mart_refrescar();

-- Verificación: fallecidos por año (esperado: total ~118,767; 2026 ~11,395; puede diferir en
-- unas pocas filas cuyo nounico no existe en el sistema)
SELECT YEAR(mes) AS anio, SUM(n) AS fallecidos FROM mart_serie WHERE tipo = 'fall' GROUP BY YEAR(mes) ORDER BY anio;
