-- =====================================================================
-- 007 · Agregados del tablero (vistas materializadas)
-- El tablero nunca recorre el detalle: lee estos agregados.
-- =====================================================================
INSERT INTO ref.parametro VALUES ('cohorte_reciente_desde', '"2023-01-01"', 'Inicio de la cohorte "reciente" en tiempos del proceso')
ON CONFLICT (clave) DO NOTHING;

-- Cubo: una fila por combinación de dimensiones (≈150 mil filas para 800 mil expedientes).
CREATE MATERIALIZED VIEW mart.mv_cubo AS
WITH b AS (
  SELECT v.*, row_number() OVER (PARTITION BY v.persona_id ORDER BY v.fecha_solicitud DESC NULLS LAST, v.id DESC) AS rn
  FROM core.v_expediente v)
SELECT b.municipio_id,
       b.estado_orden::smallint AS e,
       b.banda_edad::smallint AS a,
       coalesce(extract(year FROM b.fecha_solicitud)::smallint, 0::smallint) AS anio,
       coalesce(f.orden, 9)::smallint AS r,
       b.visita_cod AS v,
       (b.fecha_suspension IS NOT NULL)::int::smallint AS s,
       (b.fecha_fallecimiento IS NOT NULL)::int::smallint AS f,
       b.situacion_nomina AS n,
       b.antiguedad AS t,
       count(*)::int AS c,
       count(*) FILTER (WHERE b.rn = 1)::int AS p,
       count(*) FILTER (WHERE b.q_doc)::int AS qd,
       count(*) FILTER (WHERE b.flag_dpi_duplicado)::int AS qu,
       count(*) FILTER (WHERE b.flag_exp_duplicado)::int AS qx,
       count(*) FILTER (WHERE b.q_fechas)::int AS qf,
       count(*) FILTER (WHERE b.q_edad)::int AS qe,
       count(*) FILTER (WHERE b.q_fall_activo)::int AS qa,
       count(*) FILTER (WHERE b.q_susp_activo)::int AS qs,
       count(*) FILTER (WHERE b.q_sin_tel)::int AS qm
FROM b LEFT JOIN ref.cat_familia_resolucion f ON f.id = b.ult_resolucion_familia_id
GROUP BY 1,2,3,4,5,6,7,8,9,10;
CREATE UNIQUE INDEX mv_cubo_pk ON mart.mv_cubo(municipio_id, e, a, anio, r, v, s, f, n, t);

-- Eventos por mes, municipio y estado actual del expediente.
CREATE MATERIALIZED VIEW mart.mv_serie AS
WITH ev AS (
  SELECT e.id AS expediente_id, e.fecha_solicitud AS fecha, 'sol'::text AS tipo FROM core.expediente e
  UNION ALL SELECT expediente_id, fecha, CASE WHEN resultado_id = 1 THEN 'vpos' ELSE 'vneg' END FROM core.visita
  UNION ALL SELECT expediente_id, fecha, 'res' FROM core.resolucion
  UNION ALL SELECT expediente_id, fecha, CASE WHEN tipo = 'ALTA' THEN 'ing' ELSE 'sal' END FROM core.movimiento_nomina
  UNION ALL SELECT id, fecha_suspension, 'susp' FROM core.expediente WHERE fecha_suspension IS NOT NULL
  UNION ALL SELECT id, fecha_fallecimiento, 'fall' FROM core.expediente WHERE fecha_fallecimiento IS NOT NULL)
SELECT x.municipio_id, ce.orden::smallint AS e, ev.tipo, date_trunc('month', ev.fecha)::date AS mes, count(*)::int AS n
FROM ev JOIN core.expediente x ON x.id = ev.expediente_id JOIN ref.cat_estado ce ON ce.id = x.estado_id
WHERE ev.fecha >= DATE '2006-01-01' AND ev.fecha <= current_date
GROUP BY 1,2,3,4;
CREATE UNIQUE INDEX mv_serie_pk ON mart.mv_serie(municipio_id, e, tipo, mes);

-- Tiempos del proceso (días): media, mediana y P90 por departamento y nacional.
CREATE MATERIALIZED VIEW mart.mv_tiempos AS
WITH base AS (
  SELECT e.id, m.departamento_id AS dep, e.fecha_solicitud AS fsol, e.fecha_nomina_desde AS fnom,
         (SELECT min(v.fecha) FROM core.visita v WHERE v.expediente_id = e.id AND v.resultado_id = 1 AND v.fecha >= e.fecha_solicitud) AS fvis,
         (SELECT min(r.fecha) FROM core.resolucion r JOIN ref.cat_tipo_resolucion t ON t.id = r.tipo_id
           WHERE r.expediente_id = e.id AND t.familia_id = 1) AS fres,
         e.fecha_solicitud >= (SELECT (valor #>> '{}')::date FROM ref.parametro WHERE clave = 'cohorte_reciente_desde') AS reciente
  FROM core.expediente e JOIN ref.municipio m ON m.id = e.municipio_id),
tramos AS (
  SELECT dep, reciente, 'sv'::text AS tramo, (fvis - fsol) AS d FROM base
  UNION ALL SELECT dep, reciente, 'vr', (fres - fvis) FROM base
  UNION ALL SELECT dep, reciente, 'rn', (fnom - fres) FROM base
  UNION ALL SELECT dep, reciente, 'sn', (fnom - fsol) FROM base),
validos AS (SELECT * FROM tramos WHERE d IS NOT NULL AND d >= 0),
cohortes AS (
  SELECT dep, 'all'::text AS cohorte, tramo, d FROM validos
  UNION ALL SELECT dep, 'rec', tramo, d FROM validos WHERE reciente)
SELECT coalesce(dep, 0)::smallint AS dep, cohorte, tramo, count(*)::int AS n, round(avg(d))::int AS media,
       round(percentile_cont(0.5) WITHIN GROUP (ORDER BY d))::int AS mediana,
       round(percentile_cont(0.9) WITHIN GROUP (ORDER BY d))::int AS p90
FROM cohortes GROUP BY GROUPING SETS ((dep, cohorte, tramo), (cohorte, tramo));
CREATE UNIQUE INDEX mv_tiempos_pk ON mart.mv_tiempos(dep, cohorte, tramo);

CREATE TABLE mart.meta(clave text PRIMARY KEY, valor text);

CREATE OR REPLACE FUNCTION mart.refrescar(p_concurrente boolean DEFAULT true) RETURNS void
LANGUAGE plpgsql AS $$
BEGIN
  IF p_concurrente THEN
    REFRESH MATERIALIZED VIEW CONCURRENTLY mart.mv_cubo;
    REFRESH MATERIALIZED VIEW CONCURRENTLY mart.mv_serie;
    REFRESH MATERIALIZED VIEW CONCURRENTLY mart.mv_tiempos;
  ELSE
    REFRESH MATERIALIZED VIEW mart.mv_cubo;
    REFRESH MATERIALIZED VIEW mart.mv_serie;
    REFRESH MATERIALIZED VIEW mart.mv_tiempos;
  END IF;
  INSERT INTO mart.meta(clave, valor) VALUES ('refrescado_en', now()::text)
  ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor;
END $$;
