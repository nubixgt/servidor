-- =====================================================================
-- 006 · Importación masiva (Excel / CSV)
-- Flujo: archivo → stg.matriz (COPY, sin transformar) → validación →
--        stg.procesar_lote() → core.* → conciliación → mart.refrescar()
-- =====================================================================
CREATE TABLE stg.import_lote(
  id serial PRIMARY KEY,
  archivo text NOT NULL,
  ruta text,
  sha256 text,
  formato text,
  codificacion text,
  modo text NOT NULL DEFAULT 'INCREMENTAL' CHECK (modo IN ('INCREMENTAL','VALIDAR')),
  estado text NOT NULL DEFAULT 'PENDIENTE'
    CHECK (estado IN ('PENDIENTE','LEYENDO','VALIDANDO','TRANSFORMANDO','REFRESCANDO','COMPLETADO','VALIDADO','ERROR','CANCELADO')),
  progreso numeric(5,2) NOT NULL DEFAULT 0,
  mensaje text,
  total_filas int,
  filas_insertadas int,
  filas_actualizadas int,
  filas_sin_cambio int,
  filas_rechazadas int,
  advertencias int,
  reporte jsonb,
  usuario_id int REFERENCES seg.usuario(id),
  creado_en timestamptz NOT NULL DEFAULT now(),
  iniciado_en timestamptz,
  finalizado_en timestamptz
);

-- Copia fiel de la matriz (34 columnas, todo como texto). UNLOGGED: es área temporal.
CREATE UNLOGGED TABLE stg.matriz(
  lote_id int NOT NULL,
  linea int NOT NULL,
  departamento text, municipio text, no_expediente text, nombre text, dpi text,
  fecha_nacimiento text, fecha_solicitud text, anio_solicitud text, telefono text, direccion text,
  fecha_observacion text, ultima_observacion text, id_apoderado text, no_id_apoderado text, apoderado text,
  visita_positiva text, anio_visita_positiva text, mes_visita text, visita_negativa text, anio_visita_negativa text,
  mes_visita_negativa text, nomina_desde text, anio_ingreso_nomina text, nomina_hasta text, fallecio text,
  anio_fallecido text, suspendido text, fecha_ultima_resolucion text, anio_resolucion text, tipo_resolucion text,
  resolucion text, fecha_estado_actual text, anio_estado_actual text, estado_actual text
);
CREATE INDEX ON stg.matriz(lote_id);

CREATE TABLE stg.import_error(
  id bigserial PRIMARY KEY,
  lote_id int NOT NULL REFERENCES stg.import_lote(id) ON DELETE CASCADE,
  linea int,
  nivel text NOT NULL CHECK (nivel IN ('ERROR','ADVERTENCIA')),
  regla text NOT NULL,
  campo text,
  valor text,
  mensaje text
);
CREATE INDEX ON stg.import_error(lote_id, nivel, regla);

-- ---------------------------------------------------------------------
-- Procesa un lote ya cargado en stg.matriz. Idempotente: re-importar la
-- misma matriz no duplica expedientes (llave clave_origen) y los cambios
-- de estado generan historial en core.estado_expediente.
-- ---------------------------------------------------------------------
CREATE OR REPLACE FUNCTION stg.procesar_lote(p_lote int, p_solo_validar boolean DEFAULT false)
RETURNS jsonb LANGUAGE plpgsql AS $$
DECLARE
  v_total int; v_rech int; v_adv int; v_ins int := 0; v_upd int := 0; v_sin int := 0;
  v_rep jsonb;
BEGIN
  PERFORM set_config('pam.origen', 'IMPORTACION', true);
  UPDATE stg.import_lote SET estado = 'VALIDANDO', progreso = 40 WHERE id = p_lote;

  -- 1) Normalizar
  DROP TABLE IF EXISTS t_norm;
  CREATE TEMP TABLE t_norm ON COMMIT DROP AS
  SELECT s.linea,
         nullif(btrim(s.no_expediente), '') AS numero,
         nullif(regexp_replace(btrim(s.nombre), '\s+', ' ', 'g'), '') AS nombre,
         ref.f_norm(s.nombre) AS nombre_norm,
         nullif(regexp_replace(coalesce(s.dpi,''), '[^0-9]', '', 'g'), '') AS dpi,
         ref.f_fecha(s.fecha_nacimiento) AS fnac,
         ref.f_fecha(s.fecha_solicitud) AS fsol,
         nullif(btrim(s.telefono), '') AS telefono,
         nullif(btrim(s.direccion), '') AS direccion,
         ref.f_fecha(s.fecha_observacion) AS fobs,
         CASE WHEN btrim(coalesce(s.ultima_observacion,'')) IN ('', '.', '..', '...') THEN NULL ELSE btrim(s.ultima_observacion) END AS obs,
         nullif(btrim(s.id_apoderado), '') AS ap_tipo,
         nullif(btrim(s.no_id_apoderado), '') AS ap_num,
         CASE WHEN length(btrim(coalesce(s.apoderado,''))) <= 1 THEN NULL ELSE btrim(s.apoderado) END AS ap_nombre,
         ref.f_fecha(s.visita_positiva) AS vpos,
         ref.f_fecha(s.visita_negativa) AS vneg,
         ref.f_fecha(s.nomina_desde) AS ndesde,
         ref.f_fecha(s.nomina_hasta) AS nhasta,
         ref.f_fecha(s.fallecio) AS ffall,
         ref.f_fecha(s.suspendido) AS fsusp,
         ref.f_fecha(s.fecha_ultima_resolucion) AS fres,
         CASE WHEN btrim(coalesce(s.tipo_resolucion,'')) IN ('', '0') THEN NULL ELSE upper(btrim(s.tipo_resolucion)) END AS tres,
         nullif(btrim(s.resolucion), '') AS res_txt,
         ref.f_fecha(s.fecha_estado_actual) AS fest,
         upper(btrim(s.estado_actual)) AS estado_txt,
         substring(btrim(s.municipio) FROM '^(\d{3,4})') AS mun_txt,
         btrim(s.municipio) AS municipio_raw
  FROM stg.matriz s WHERE s.lote_id = p_lote;

  ALTER TABLE t_norm ADD COLUMN municipio_id smallint, ADD COLUMN estado_id smallint,
    ADD COLUMN es_cui boolean, ADD COLUMN cui_ok boolean, ADD COLUMN pclave text, ADD COLUMN clave_origen text, ADD COLUMN rechazo text;

  UPDATE t_norm t SET
    municipio_id = m.id,
    estado_id = ce.id,
    es_cui = (t.dpi ~ '^\d{13}$'),
    cui_ok = ref.f_cui_valido(t.dpi),
    pclave = CASE WHEN t.dpi ~ '^\d{13}$' THEN 'CUI:' || t.dpi
                  ELSE 'NF:' || coalesce(t.nombre_norm,'?') || '|' || coalesce(t.fnac::text,'') END
  FROM t_norm t2
  LEFT JOIN ref.municipio m ON m.id = nullif(t2.mun_txt,'')::int
  LEFT JOIN ref.cat_estado ce ON ce.texto_origen = t2.estado_txt
  WHERE t.linea = t2.linea;

  -- llave estable por fila; repetidos exactos reciben sufijo #n
  UPDATE t_norm t SET clave_origen = k.base || CASE WHEN k.rn > 1 THEN '#' || k.rn ELSE '' END
  FROM (SELECT linea, md5(concat_ws('|', numero, dpi, fsol, nombre_norm, municipio_id)) AS base,
               row_number() OVER (PARTITION BY numero, dpi, fsol, nombre_norm, municipio_id ORDER BY linea) AS rn
        FROM t_norm) k
  WHERE k.linea = t.linea;

  -- 2) Validar: errores (fila rechazada) y advertencias (se importa y se marca)
  DELETE FROM stg.import_error WHERE lote_id = p_lote;
  UPDATE t_norm SET rechazo = 'MUNICIPIO_DESCONOCIDO' WHERE municipio_id IS NULL;
  UPDATE t_norm SET rechazo = coalesce(rechazo, 'ESTADO_DESCONOCIDO') WHERE estado_id IS NULL;
  UPDATE t_norm SET rechazo = coalesce(rechazo, 'SIN_NOMBRE') WHERE nombre IS NULL;
  INSERT INTO stg.import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ERROR', rechazo,
         CASE rechazo WHEN 'MUNICIPIO_DESCONOCIDO' THEN 'Municipio' WHEN 'ESTADO_DESCONOCIDO' THEN 'Estado Actual' ELSE 'Nombre' END,
         CASE rechazo WHEN 'MUNICIPIO_DESCONOCIDO' THEN municipio_raw WHEN 'ESTADO_DESCONOCIDO' THEN estado_txt ELSE NULL END,
         'Fila rechazada: ' || rechazo
  FROM t_norm WHERE rechazo IS NOT NULL;

  INSERT INTO stg.import_error(lote_id, linea, nivel, regla, campo, valor, mensaje)
  SELECT p_lote, linea, 'ADVERTENCIA', r.regla, r.campo, r.valor, r.msg FROM t_norm t
  CROSS JOIN LATERAL (VALUES
     (CASE WHEN t.dpi IS NULL THEN 'SIN_DOCUMENTO' WHEN NOT t.es_cui THEN 'DOCUMENTO_NO_CUI' WHEN NOT t.cui_ok THEN 'CUI_VERIFICADOR' END, 'DPI', t.dpi, 'Documento sin CUI válido'),
     (CASE WHEN t.fsol IS NULL THEN 'SIN_FECHA_SOLICITUD' END, 'Fecha Solicitud', NULL, 'Fecha de solicitud vacía o inválida'),
     (CASE WHEN t.fnac IS NULL THEN 'SIN_FECHA_NACIMIENTO' END, 'Fecha Nacimiento', NULL, 'Fecha de nacimiento vacía o inválida'),
     (CASE WHEN t.vpos < t.fsol OR t.fres < t.fsol OR t.ndesde < t.fsol OR t.ffall < t.fsol OR t.ffall < t.fnac THEN 'FECHAS_INCONSISTENTES' END, NULL, NULL, 'Evento anterior a la solicitud o al nacimiento'),
     (CASE WHEN t.ffall IS NOT NULL AND t.estado_id = 1 THEN 'FALLECIDO_ACTIVO' END, 'Fallecio', NULL, 'Fecha de fallecimiento con estado EN NOMINA ACTIVA'),
     (CASE WHEN t.tres IS NOT NULL AND NOT EXISTS (SELECT 1 FROM ref.cat_tipo_resolucion c WHERE c.codigo = t.tres) THEN 'TIPO_RESOLUCION_NUEVO' END, 'Tipo Resolucion', t.tres, 'Código de resolución no catalogado: se agrega a la familia "Otras"')
  ) r(regla, campo, valor, msg)
  WHERE t.rechazo IS NULL AND r.regla IS NOT NULL;

  SELECT count(*), count(*) FILTER (WHERE rechazo IS NOT NULL) INTO v_total, v_rech FROM t_norm;
  SELECT count(*) INTO v_adv FROM stg.import_error WHERE lote_id = p_lote AND nivel = 'ADVERTENCIA';

  IF p_solo_validar THEN
    v_rep := stg.reporte_lote(p_lote, true);
    UPDATE stg.import_lote SET estado = 'VALIDADO', progreso = 100, total_filas = v_total, filas_rechazadas = v_rech,
           advertencias = v_adv, reporte = v_rep, finalizado_en = now(), mensaje = 'Validación terminada; no se modificó la base.' WHERE id = p_lote;
    RETURN v_rep;
  END IF;

  UPDATE stg.import_lote SET estado = 'TRANSFORMANDO', progreso = 55 WHERE id = p_lote;
  DELETE FROM t_norm WHERE rechazo IS NOT NULL;

  -- 3) Catálogo de resoluciones: códigos nuevos
  INSERT INTO ref.cat_tipo_resolucion(codigo, nombre, familia_id)
  SELECT DISTINCT ON (tres) tres, coalesce(res_txt, tres), 9 FROM t_norm
  WHERE tres IS NOT NULL ORDER BY tres, res_txt
  ON CONFLICT (codigo) DO NOTHING;

  -- 4) Personas (los datos de contacto se toman de la solicitud más reciente)
  INSERT INTO core.persona AS p (clave, nombre, nombre_norm, documento_tipo, documento_numero, cui_valido, fecha_nacimiento, telefono, direccion, municipio_id)
  SELECT DISTINCT ON (pclave) pclave, nombre, coalesce(nombre_norm,''),
         CASE WHEN es_cui THEN 'CUI' WHEN dpi IS NOT NULL THEN 'CEDULA' ELSE 'SIN_DOCUMENTO' END,
         dpi, coalesce(cui_ok,false), fnac, telefono, direccion, municipio_id
  FROM t_norm ORDER BY pclave, fsol DESC NULLS LAST, linea DESC
  ON CONFLICT (clave) DO UPDATE SET
    nombre = EXCLUDED.nombre, nombre_norm = EXCLUDED.nombre_norm,
    fecha_nacimiento = coalesce(EXCLUDED.fecha_nacimiento, p.fecha_nacimiento),
    telefono = coalesce(EXCLUDED.telefono, p.telefono), direccion = coalesce(EXCLUDED.direccion, p.direccion),
    municipio_id = EXCLUDED.municipio_id, actualizado_en = now()
  WHERE (p.nombre, p.fecha_nacimiento, p.telefono, p.direccion, p.municipio_id) IS DISTINCT FROM
        (EXCLUDED.nombre, coalesce(EXCLUDED.fecha_nacimiento, p.fecha_nacimiento), coalesce(EXCLUDED.telefono, p.telefono),
         coalesce(EXCLUDED.direccion, p.direccion), EXCLUDED.municipio_id);

  -- 5) Apoderados
  INSERT INTO core.apoderado(clave, tipo_id, numero_id, nombre, nombre_norm)
  SELECT DISTINCT ON (k) k, ap_tipo, ap_num, ap_nombre, ref.f_norm(ap_nombre)
  FROM (SELECT concat_ws('|', ap_tipo, ap_num, ref.f_norm(ap_nombre)) AS k, * FROM t_norm
        WHERE ap_num IS NOT NULL OR ap_nombre IS NOT NULL) a
  ORDER BY k
  ON CONFLICT (clave) DO NOTHING;

  UPDATE stg.import_lote SET progreso = 65 WHERE id = p_lote;

  -- 6) Expedientes (upsert por clave_origen)
  DROP TABLE IF EXISTS t_exp;
  CREATE TEMP TABLE t_exp ON COMMIT DROP AS
  SELECT t.*, p.id AS persona_id, a.id AS apoderado_id
  FROM t_norm t
  JOIN core.persona p ON p.clave = t.pclave
  LEFT JOIN core.apoderado a ON a.clave = concat_ws('|', t.ap_tipo, t.ap_num, ref.f_norm(t.ap_nombre)) AND (t.ap_num IS NOT NULL OR t.ap_nombre IS NOT NULL);

  WITH up AS (
    INSERT INTO core.expediente AS e (numero, clave_origen, persona_id, apoderado_id, municipio_id, fecha_solicitud, estado_id, fecha_estado,
                                      fecha_nomina_desde, fecha_nomina_hasta, fecha_fallecimiento, fecha_suspension, origen, lote_id)
    SELECT numero, clave_origen, persona_id, apoderado_id, municipio_id, fsol, estado_id, fest,
           ndesde, CASE WHEN estado_id = 1 THEN NULL ELSE nhasta END, ffall, fsusp, 'MATRIZ', p_lote
    FROM t_exp
    ON CONFLICT (clave_origen) DO UPDATE SET
      persona_id = EXCLUDED.persona_id, apoderado_id = EXCLUDED.apoderado_id, municipio_id = EXCLUDED.municipio_id,
      fecha_solicitud = EXCLUDED.fecha_solicitud, fecha_estado = EXCLUDED.fecha_estado,
      fecha_fallecimiento = EXCLUDED.fecha_fallecimiento, fecha_suspension = EXCLUDED.fecha_suspension,
      lote_id = EXCLUDED.lote_id, actualizado_en = now(), version = e.version + 1
    RETURNING (xmax = 0) AS insertado)
  SELECT count(*) FILTER (WHERE insertado), count(*) FILTER (WHERE NOT insertado) INTO v_ins, v_upd FROM up;

  ALTER TABLE t_exp ADD COLUMN expediente_id bigint;
  UPDATE t_exp t SET expediente_id = e.id FROM core.expediente e WHERE e.clave_origen = t.clave_origen;
  CREATE INDEX ON t_exp(expediente_id);

  UPDATE stg.import_lote SET progreso = 75 WHERE id = p_lote;

  -- 7) Detalle de origen MATRIZ: se reemplaza (los registros hechos en el sistema no se tocan)
  DELETE FROM core.visita v USING t_exp t WHERE v.expediente_id = t.expediente_id AND v.origen = 'MATRIZ';
  DELETE FROM core.observacion v USING t_exp t WHERE v.expediente_id = t.expediente_id AND v.origen = 'MATRIZ';
  DELETE FROM core.resolucion v USING t_exp t WHERE v.expediente_id = t.expediente_id AND v.origen = 'MATRIZ';
  DELETE FROM core.movimiento_nomina v USING t_exp t WHERE v.expediente_id = t.expediente_id AND v.origen = 'MATRIZ';

  INSERT INTO core.visita(expediente_id, fecha, resultado_id, origen)
  SELECT expediente_id, vpos, 1, 'MATRIZ' FROM t_exp WHERE vpos IS NOT NULL
  UNION ALL SELECT expediente_id, vneg, 2, 'MATRIZ' FROM t_exp WHERE vneg IS NOT NULL;

  INSERT INTO core.observacion(expediente_id, fecha, texto, origen)
  SELECT expediente_id, coalesce(fobs, fest, fsol, current_date), obs, 'MATRIZ' FROM t_exp WHERE obs IS NOT NULL;

  INSERT INTO core.resolucion(expediente_id, fecha, tipo_id, descripcion, origen)
  SELECT t.expediente_id, t.fres, c.id, t.res_txt, 'MATRIZ' FROM t_exp t JOIN ref.cat_tipo_resolucion c ON c.codigo = t.tres;

  INSERT INTO core.movimiento_nomina(expediente_id, tipo, fecha, origen)
  SELECT expediente_id, 'ALTA', ndesde, 'MATRIZ' FROM t_exp WHERE ndesde IS NOT NULL
  UNION ALL
  SELECT expediente_id, 'BAJA', nhasta, 'MATRIZ' FROM t_exp WHERE nhasta IS NOT NULL AND estado_id <> 1 AND ndesde IS NOT NULL;

  -- 8) Estado: si cambió respecto al vigente, se cierra el anterior y se abre uno nuevo (historial)
  DROP TABLE IF EXISTS t_cambio;
  CREATE TEMP TABLE t_cambio ON COMMIT DROP AS
    SELECT t.expediente_id, t.estado_id, t.fest FROM t_exp t
    LEFT JOIN core.estado_expediente x ON x.expediente_id = t.expediente_id AND x.vigente
    WHERE x.id IS NULL OR x.estado_id <> t.estado_id;
  UPDATE core.estado_expediente x SET vigente = false FROM t_cambio c WHERE x.expediente_id = c.expediente_id AND x.vigente;
  INSERT INTO core.estado_expediente(expediente_id, estado_id, fecha, vigente, motivo, origen)
  SELECT expediente_id, estado_id, fest, true, 'Importación lote ' || p_lote, 'MATRIZ' FROM t_cambio;

  UPDATE stg.import_lote SET progreso = 85 WHERE id = p_lote;

  -- 9) Resumen y banderas
  PERFORM core.recalcular_resumen(array_agg(expediente_id)) FROM t_exp;
  -- los datos de nómina de la matriz mandan sobre el cálculo cuando no hay movimientos del sistema
  PERFORM core.recalcular_banderas();

  v_sin := 0;
  v_rep := stg.reporte_lote(p_lote, false);
  UPDATE stg.import_lote SET estado = 'REFRESCANDO', progreso = 92, total_filas = v_total, filas_rechazadas = v_rech,
         advertencias = v_adv, filas_insertadas = v_ins, filas_actualizadas = v_upd, reporte = v_rep WHERE id = p_lote;

  INSERT INTO audit.bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, despues)
  SELECT l.usuario_id, u.usuario, 'IMPORTAR', 'importacion', 'stg.import_lote', l.id::text,
         jsonb_build_object('archivo', l.archivo, 'total', v_total, 'insertadas', v_ins, 'actualizadas', v_upd, 'rechazadas', v_rech, 'advertencias', v_adv)
  FROM stg.import_lote l LEFT JOIN seg.usuario u ON u.id = l.usuario_id WHERE l.id = p_lote;
  RETURN v_rep;
END $$;

-- Reporte de conciliación: conteos de la matriz vs. base, por departamento × estado.
CREATE OR REPLACE FUNCTION stg.reporte_lote(p_lote int, p_solo_matriz boolean DEFAULT false) RETURNS jsonb
LANGUAGE sql AS $$
  WITH m AS (
    SELECT substring(btrim(s.municipio) FROM '^(\d{2})')::int AS dep, upper(btrim(s.estado_actual)) AS estado, count(*) AS n
    FROM stg.matriz s WHERE s.lote_id = p_lote GROUP BY 1, 2),
  b AS (
    SELECT mu.departamento_id AS dep, ce.texto_origen AS estado, count(*) AS n
    FROM core.expediente e JOIN ref.municipio mu ON mu.id = e.municipio_id JOIN ref.cat_estado ce ON ce.id = e.estado_id
    WHERE NOT p_solo_matriz AND e.lote_id = p_lote GROUP BY 1, 2),
  j AS (
    SELECT coalesce(m.dep, b.dep) AS dep, coalesce(m.estado, b.estado) AS estado, coalesce(m.n, 0) AS matriz, coalesce(b.n, 0) AS base
    FROM m FULL JOIN b ON b.dep = m.dep AND b.estado = m.estado)
  SELECT jsonb_build_object(
    'total_matriz', (SELECT coalesce(sum(matriz),0) FROM j),
    'total_base_lote', (SELECT coalesce(sum(base),0) FROM j),
    'diferencias', (SELECT coalesce(jsonb_agg(jsonb_build_object('departamento', d.nombre, 'estado', j.estado, 'matriz', j.matriz, 'base', j.base)), '[]'::jsonb)
                    FROM j LEFT JOIN ref.departamento d ON d.id = j.dep WHERE NOT p_solo_matriz AND j.matriz <> j.base),
    'por_estado', (SELECT jsonb_object_agg(estado, n) FROM (SELECT estado, sum(matriz) AS n FROM j GROUP BY estado) x),
    'errores', (SELECT coalesce(jsonb_object_agg(regla, n), '{}'::jsonb) FROM (SELECT regla, count(*) AS n FROM stg.import_error WHERE lote_id = p_lote AND nivel = 'ERROR' GROUP BY regla) e),
    'advertencias', (SELECT coalesce(jsonb_object_agg(regla, n), '{}'::jsonb) FROM (SELECT regla, count(*) AS n FROM stg.import_error WHERE lote_id = p_lote AND nivel = 'ADVERTENCIA' GROUP BY regla) a),
    'personas_en_base', (SELECT count(*) FROM core.persona),
    'expedientes_en_base', (SELECT count(*) FROM core.expediente))
$$;
