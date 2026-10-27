-- =====================================================================
-- 004 · Núcleo: Persona → Expediente → Eventos
-- Las tablas de detalle conservan historia; el expediente guarda un
-- "resumen" (último estado, última visita, etc.) para consultas rápidas.
-- origen: MATRIZ (importado) | SISTEMA (registrado en la plataforma)
-- =====================================================================
CREATE TABLE core.persona(
  id bigserial PRIMARY KEY,
  clave text NOT NULL UNIQUE,              -- CUI:<13 dígitos> | NF:<nombre normalizado>|<fecha nac.>
  nombre text NOT NULL,
  nombre_norm text NOT NULL,
  documento_tipo text NOT NULL CHECK (documento_tipo IN ('CUI','CEDULA','SIN_DOCUMENTO')),
  documento_numero text,
  cui_valido boolean NOT NULL DEFAULT false,
  fecha_nacimiento date,
  telefono text,
  direccion text,
  municipio_id smallint REFERENCES ref.municipio(id),
  creado_en timestamptz NOT NULL DEFAULT now(),
  actualizado_en timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX persona_nombre_trgm ON core.persona USING gin (nombre_norm gin_trgm_ops);
CREATE INDEX persona_doc ON core.persona (documento_numero text_pattern_ops);
CREATE INDEX persona_tel ON core.persona (telefono text_pattern_ops) WHERE telefono IS NOT NULL;
CREATE INDEX persona_fnac ON core.persona (fecha_nacimiento);

CREATE TABLE core.apoderado(
  id bigserial PRIMARY KEY,
  clave text NOT NULL UNIQUE,
  tipo_id text,
  numero_id text,
  nombre text,
  nombre_norm text,
  creado_en timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX apoderado_num ON core.apoderado (numero_id text_pattern_ops);
CREATE INDEX apoderado_nombre_trgm ON core.apoderado USING gin (nombre_norm gin_trgm_ops);

CREATE TABLE core.expediente(
  id bigserial PRIMARY KEY,
  numero text,
  clave_origen text UNIQUE,                 -- llave estable de la fila de la matriz (para re-importar)
  persona_id bigint NOT NULL REFERENCES core.persona(id),
  apoderado_id bigint REFERENCES core.apoderado(id),
  municipio_id smallint NOT NULL REFERENCES ref.municipio(id),
  fecha_solicitud date,
  estado_id smallint NOT NULL REFERENCES ref.cat_estado(id),
  fecha_estado date,
  -- resumen (se recalcula con core.recalcular_resumen)
  fecha_nomina_desde date,
  fecha_nomina_hasta date,
  fecha_fallecimiento date,
  fecha_suspension date,
  ult_visita_resultado_id smallint REFERENCES ref.cat_resultado_visita(id),
  ult_visita_fecha date,
  ult_resolucion_tipo_id int REFERENCES ref.cat_tipo_resolucion(id),
  ult_resolucion_fecha date,
  ult_observacion text,
  ult_observacion_fecha date,
  -- banderas de calidad persistidas (las que requieren comparar contra toda la base)
  flag_dpi_duplicado boolean NOT NULL DEFAULT false,
  flag_exp_duplicado boolean NOT NULL DEFAULT false,
  origen text NOT NULL DEFAULT 'MATRIZ',
  lote_id int,
  creado_en timestamptz NOT NULL DEFAULT now(),
  actualizado_en timestamptz NOT NULL DEFAULT now(),
  version int NOT NULL DEFAULT 1
);
CREATE INDEX exp_persona ON core.expediente(persona_id);
CREATE INDEX exp_numero ON core.expediente(numero text_pattern_ops);
CREATE INDEX exp_mun_estado ON core.expediente(municipio_id, estado_id);
CREATE INDEX exp_estado_fsol ON core.expediente(estado_id, fecha_solicitud);
CREATE INDEX exp_fsol_id ON core.expediente(fecha_solicitud DESC, id DESC);
CREATE INDEX exp_activos ON core.expediente(municipio_id) WHERE estado_id = 1;
CREATE INDEX exp_apoderado ON core.expediente(apoderado_id) WHERE apoderado_id IS NOT NULL;

CREATE TABLE core.visita(
  id bigserial PRIMARY KEY,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  fecha date NOT NULL,
  resultado_id smallint NOT NULL REFERENCES ref.cat_resultado_visita(id),
  observacion text,
  tecnico text,
  usuario_id int REFERENCES seg.usuario(id),
  origen text NOT NULL DEFAULT 'SISTEMA',
  creado_en timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX ON core.visita(expediente_id, fecha DESC);
CREATE INDEX ON core.visita(fecha);

CREATE TABLE core.observacion(
  id bigserial PRIMARY KEY,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  fecha date NOT NULL,
  texto text NOT NULL,
  usuario_id int REFERENCES seg.usuario(id),
  origen text NOT NULL DEFAULT 'SISTEMA',
  creado_en timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX ON core.observacion(expediente_id, fecha DESC);

CREATE TABLE core.resolucion(
  id bigserial PRIMARY KEY,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  fecha date,
  tipo_id int NOT NULL REFERENCES ref.cat_tipo_resolucion(id),
  numero text,
  descripcion text,
  usuario_id int REFERENCES seg.usuario(id),
  origen text NOT NULL DEFAULT 'SISTEMA',
  creado_en timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX ON core.resolucion(expediente_id, fecha DESC);
CREATE INDEX ON core.resolucion(fecha);

CREATE TABLE core.movimiento_nomina(
  id bigserial PRIMARY KEY,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  tipo text NOT NULL CHECK (tipo IN ('ALTA','BAJA')),
  fecha date NOT NULL,
  motivo text,
  usuario_id int REFERENCES seg.usuario(id),
  origen text NOT NULL DEFAULT 'SISTEMA',
  creado_en timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX ON core.movimiento_nomina(expediente_id, fecha);
CREATE INDEX ON core.movimiento_nomina(tipo, fecha);

CREATE TABLE core.estado_expediente(
  id bigserial PRIMARY KEY,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  estado_id smallint NOT NULL REFERENCES ref.cat_estado(id),
  fecha date,
  vigente boolean NOT NULL DEFAULT true,
  motivo text,
  usuario_id int REFERENCES seg.usuario(id),
  origen text NOT NULL DEFAULT 'SISTEMA',
  creado_en timestamptz NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX estado_vigente_unico ON core.estado_expediente(expediente_id) WHERE vigente;
CREATE INDEX ON core.estado_expediente(expediente_id, creado_en);

-- Seguimiento de casos detectados por las reglas de alerta / calidad
CREATE TABLE core.caso_revision(
  id bigserial PRIMARY KEY,
  regla text NOT NULL,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  estado text NOT NULL DEFAULT 'EN_REVISION' CHECK (estado IN ('EN_REVISION','CONFIRMADO','DESCARTADO','CORREGIDO')),
  comentario text,
  usuario_id int REFERENCES seg.usuario(id),
  actualizado_en timestamptz NOT NULL DEFAULT now(),
  UNIQUE (regla, expediente_id)
);

-- Documentos adjuntos (almacenamiento externo compatible con S3 o disco)
CREATE TABLE core.documento(
  id bigserial PRIMARY KEY,
  expediente_id bigint NOT NULL REFERENCES core.expediente(id) ON DELETE CASCADE,
  nombre text NOT NULL,
  ruta text NOT NULL,
  tipo_mime text,
  sha256 text,
  usuario_id int REFERENCES seg.usuario(id),
  creado_en timestamptz NOT NULL DEFAULT now()
);

-- Línea de tiempo: vista (no se duplica información)
CREATE OR REPLACE VIEW core.v_evento AS
  SELECT e.id AS expediente_id, e.fecha_solicitud AS fecha, 'SOLICITUD'::text AS tipo, 'Solicitud recibida'::text AS titulo, ('Expediente ' || coalesce(e.numero,'s/n')) AS detalle, e.origen, e.creado_en
    FROM core.expediente e WHERE e.fecha_solicitud IS NOT NULL
  UNION ALL
  SELECT v.expediente_id, v.fecha, 'VISITA_' || r.codigo, 'Visita ' || lower(r.nombre), concat_ws(' · ', v.observacion, v.tecnico), v.origen, v.creado_en
    FROM core.visita v JOIN ref.cat_resultado_visita r ON r.id = v.resultado_id
  UNION ALL
  SELECT o.expediente_id, o.fecha, 'OBSERVACION', 'Observación', o.texto, o.origen, o.creado_en FROM core.observacion o
  UNION ALL
  SELECT s.expediente_id, s.fecha, 'RESOLUCION', 'Resolución ' || t.codigo, concat_ws(' · ', t.nombre, s.numero, s.descripcion), s.origen, s.creado_en
    FROM core.resolucion s JOIN ref.cat_tipo_resolucion t ON t.id = s.tipo_id
  UNION ALL
  SELECT m.expediente_id, m.fecha, 'NOMINA_' || m.tipo, CASE m.tipo WHEN 'ALTA' THEN 'Ingreso a nómina' ELSE 'Salida de nómina' END, m.motivo, m.origen, m.creado_en
    FROM core.movimiento_nomina m
  UNION ALL
  SELECT e.id, e.fecha_suspension, 'SUSPENSION', 'Suspensión registrada', NULL, e.origen, e.creado_en FROM core.expediente e WHERE e.fecha_suspension IS NOT NULL
  UNION ALL
  SELECT e.id, e.fecha_fallecimiento, 'FALLECIMIENTO', 'Fallecimiento registrado', NULL, e.origen, e.creado_en FROM core.expediente e WHERE e.fecha_fallecimiento IS NOT NULL
  UNION ALL
  SELECT x.expediente_id, x.fecha, 'ESTADO', 'Estado: ' || c.nombre, x.motivo, x.origen, x.creado_en
    FROM core.estado_expediente x JOIN ref.cat_estado c ON c.id = x.estado_id;

-- Recalcula el resumen del expediente desde las tablas de detalle (NULL = todos).
CREATE OR REPLACE FUNCTION core.recalcular_resumen(p_ids bigint[] DEFAULT NULL) RETURNS void
LANGUAGE sql AS $$
  WITH ids AS (
    SELECT id FROM core.expediente WHERE p_ids IS NULL
    UNION SELECT unnest(p_ids)),
  v AS (SELECT DISTINCT ON (expediente_id) expediente_id, resultado_id, fecha FROM core.visita
        WHERE expediente_id IN (SELECT id FROM ids) ORDER BY expediente_id, fecha DESC, id DESC),
  r AS (SELECT DISTINCT ON (expediente_id) expediente_id, tipo_id, fecha FROM core.resolucion
        WHERE expediente_id IN (SELECT id FROM ids) ORDER BY expediente_id, fecha DESC NULLS LAST, id DESC),
  o AS (SELECT DISTINCT ON (expediente_id) expediente_id, texto, fecha FROM core.observacion
        WHERE expediente_id IN (SELECT id FROM ids) ORDER BY expediente_id, fecha DESC, id DESC),
  s AS (SELECT expediente_id, estado_id, fecha FROM core.estado_expediente
        WHERE vigente AND expediente_id IN (SELECT id FROM ids)),
  n AS (SELECT expediente_id, min(fecha) FILTER (WHERE tipo = 'ALTA') AS alta, max(fecha) FILTER (WHERE tipo = 'BAJA') AS baja
        FROM core.movimiento_nomina WHERE expediente_id IN (SELECT id FROM ids) GROUP BY expediente_id),
  x AS (SELECT ids.id, v.resultado_id, v.fecha AS vf, r.tipo_id, r.fecha AS rf, o.texto, o.fecha AS ofe,
               s.estado_id, s.fecha AS sf, n.alta, n.baja
        FROM ids LEFT JOIN v ON v.expediente_id = ids.id LEFT JOIN r ON r.expediente_id = ids.id
        LEFT JOIN o ON o.expediente_id = ids.id LEFT JOIN s ON s.expediente_id = ids.id LEFT JOIN n ON n.expediente_id = ids.id)
  UPDATE core.expediente e SET
    ult_visita_resultado_id = x.resultado_id, ult_visita_fecha = x.vf,
    ult_resolucion_tipo_id = x.tipo_id, ult_resolucion_fecha = x.rf,
    ult_observacion = x.texto, ult_observacion_fecha = x.ofe,
    estado_id = coalesce(x.estado_id, e.estado_id), fecha_estado = coalesce(x.sf, e.fecha_estado),
    fecha_nomina_desde = x.alta,
    fecha_nomina_hasta = CASE WHEN coalesce(x.estado_id, e.estado_id) = 1 THEN NULL ELSE x.baja END,
    actualizado_en = now()
  FROM x WHERE e.id = x.id;
$$;

-- Banderas que dependen de toda la base (DPI y número de expediente repetidos).
CREATE OR REPLACE FUNCTION core.recalcular_banderas() RETURNS void
LANGUAGE sql AS $$
  WITH d AS (
    SELECT e.id,
           count(*) FILTER (WHERE p.documento_tipo = 'CUI') OVER (PARTITION BY p.documento_numero) AS n_dpi,
           count(*) OVER (PARTITION BY e.numero) AS n_exp
    FROM core.expediente e JOIN core.persona p ON p.id = e.persona_id)
  UPDATE core.expediente e SET
    flag_dpi_duplicado = coalesce(d.n_dpi, 0) > 1,
    flag_exp_duplicado = e.numero IS NOT NULL AND d.n_exp > 1
  FROM d WHERE d.id = e.id
    AND (e.flag_dpi_duplicado IS DISTINCT FROM (coalesce(d.n_dpi,0) > 1)
         OR e.flag_exp_duplicado IS DISTINCT FROM (e.numero IS NOT NULL AND d.n_exp > 1));
$$;

-- Vista plana con las dimensiones de análisis de cada expediente (usada por tableros, listados y reportes).
CREATE OR REPLACE VIEW core.v_expediente AS
SELECT e.id, e.numero, e.persona_id, p.nombre, p.nombre_norm, p.documento_tipo, p.documento_numero, p.cui_valido,
       p.fecha_nacimiento, p.telefono, p.direccion,
       e.municipio_id, m.departamento_id, m.nombre AS municipio, d.nombre AS departamento,
       e.fecha_solicitud, e.estado_id, ce.orden AS estado_orden, ce.nombre AS estado, e.fecha_estado,
       e.fecha_nomina_desde, e.fecha_nomina_hasta, e.fecha_fallecimiento, e.fecha_suspension,
       e.ult_visita_resultado_id, e.ult_visita_fecha, e.ult_resolucion_tipo_id, e.ult_resolucion_fecha,
       tr.codigo AS ult_resolucion_codigo, tr.familia_id AS ult_resolucion_familia_id,
       e.ult_observacion, e.ult_observacion_fecha, e.apoderado_id,
       e.flag_dpi_duplicado, e.flag_exp_duplicado, e.actualizado_en,
       CASE WHEN e.estado_id = 1 THEN 1 WHEN e.fecha_nomina_desde IS NOT NULL THEN 2 ELSE 0 END::smallint AS situacion_nomina,
       CASE e.ult_visita_resultado_id WHEN 1 THEN 1 WHEN 2 THEN 2 WHEN 3 THEN 2 ELSE 0 END::smallint AS visita_cod,
       ref.f_banda_edad(p.fecha_nacimiento) AS banda_edad,
       extract(year FROM age(current_date, p.fecha_nacimiento))::int AS edad,
       CASE WHEN e.estado_id = 2 AND e.fecha_solicitud IS NOT NULL THEN
         CASE WHEN current_date - e.fecha_solicitud <= 30 THEN 1 WHEN current_date - e.fecha_solicitud <= 60 THEN 2
              WHEN current_date - e.fecha_solicitud <= 90 THEN 3 WHEN current_date - e.fecha_solicitud <= 180 THEN 4
              WHEN current_date - e.fecha_solicitud <= 365 THEN 5 ELSE 6 END
       WHEN e.estado_id = 2 THEN 6 ELSE 0 END::smallint AS antiguedad,
       -- banderas de calidad por fila
       (NOT p.cui_valido) AS q_doc,
       (e.ult_visita_fecha < e.fecha_solicitud OR e.ult_resolucion_fecha < e.fecha_solicitud OR e.fecha_nomina_desde < e.fecha_solicitud
        OR e.fecha_fallecimiento < e.fecha_solicitud OR e.fecha_fallecimiento < p.fecha_nacimiento
        OR greatest(e.fecha_solicitud, e.ult_visita_fecha, e.ult_resolucion_fecha, e.fecha_nomina_desde, e.fecha_fallecimiento, e.fecha_suspension) > current_date)
        IS TRUE AS q_fechas,
       (p.fecha_nacimiento IS NULL OR age(current_date, p.fecha_nacimiento) > interval '110 years'
        OR age(e.fecha_solicitud, p.fecha_nacimiento) < interval '60 years') IS TRUE AS q_edad,
       (e.fecha_fallecimiento IS NOT NULL AND e.estado_id = 1) AS q_fall_activo,
       (e.fecha_suspension IS NOT NULL AND e.estado_id = 1) AS q_susp_activo,
       (p.telefono IS NULL) AS q_sin_tel
FROM core.expediente e
JOIN core.persona p ON p.id = e.persona_id
JOIN ref.municipio m ON m.id = e.municipio_id
JOIN ref.departamento d ON d.id = m.departamento_id
JOIN ref.cat_estado ce ON ce.id = e.estado_id
LEFT JOIN ref.cat_tipo_resolucion tr ON tr.id = e.ult_resolucion_tipo_id;
