-- =====================================================================
-- 005 · Auditoría inmutable
-- La API fija en cada transacción: pam.usuario_id, pam.usuario, pam.ip, pam.request_id
-- con set_config(..., true). Las cargas masivas fijan pam.origen = 'IMPORTACION'
-- y se auditan como un solo evento por lote.
-- =====================================================================
CREATE TABLE audit.bitacora(
  id bigserial PRIMARY KEY,
  ts timestamptz NOT NULL DEFAULT now(),
  usuario_id int,
  usuario text,
  accion text NOT NULL,          -- INSERT | UPDATE | DELETE | LOGIN | LOGIN_FALLIDO | VER_DPI | EXPORTAR | IMPORTAR | ...
  modulo text,
  entidad text,
  entidad_id text,
  antes jsonb,
  despues jsonb,
  ip inet,
  user_agent text,
  request_id text
);
CREATE INDEX bitacora_ts ON audit.bitacora USING brin (ts);
CREATE INDEX bitacora_entidad ON audit.bitacora (entidad, entidad_id);
CREATE INDEX bitacora_usuario ON audit.bitacora (usuario_id, ts DESC);

-- Nadie puede modificar ni borrar la bitácora.
CREATE OR REPLACE FUNCTION audit.f_inmutable() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
  RAISE EXCEPTION 'La bitácora de auditoría es de solo inserción';
END $$;
CREATE TRIGGER bitacora_inmutable BEFORE UPDATE OR DELETE OR TRUNCATE ON audit.bitacora
  FOR EACH STATEMENT EXECUTE FUNCTION audit.f_inmutable();

-- Registro automático de cambios en tablas de negocio.
CREATE OR REPLACE FUNCTION audit.f_registrar() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE
  v_antes jsonb; v_despues jsonb; v_id text;
BEGIN
  IF coalesce(current_setting('pam.origen', true), '') = 'IMPORTACION' THEN
    RETURN NULL;
  END IF;
  IF TG_OP IN ('UPDATE','DELETE') THEN v_antes := to_jsonb(OLD); END IF;
  IF TG_OP IN ('INSERT','UPDATE') THEN v_despues := to_jsonb(NEW); END IF;
  v_antes := v_antes - 'hash' - 'refresh_hash';
  v_despues := v_despues - 'hash' - 'refresh_hash';
  IF TG_OP = 'UPDATE' THEN
    -- guardar solo las columnas que cambiaron
    SELECT jsonb_object_agg(k, v_antes -> k), jsonb_object_agg(k, v_despues -> k)
      INTO v_antes, v_despues
      FROM jsonb_object_keys(v_despues) k
     WHERE k NOT IN ('actualizado_en','version') AND (v_antes -> k) IS DISTINCT FROM (v_despues -> k);
    IF v_antes IS NULL THEN RETURN NULL; END IF;
  END IF;
  v_id := coalesce(v_despues ->> 'id', v_antes ->> 'id', (to_jsonb(coalesce(NEW, OLD)) ->> 'id'));
  INSERT INTO audit.bitacora(usuario_id, usuario, accion, modulo, entidad, entidad_id, antes, despues, ip, request_id)
  VALUES (nullif(current_setting('pam.usuario_id', true), '')::int, nullif(current_setting('pam.usuario', true), ''),
          TG_OP, TG_TABLE_SCHEMA, TG_TABLE_SCHEMA || '.' || TG_TABLE_NAME, coalesce(v_id, (to_jsonb(coalesce(NEW,OLD)) ->> 'id')),
          v_antes, v_despues, nullif(current_setting('pam.ip', true), '')::inet, nullif(current_setting('pam.request_id', true), ''));
  RETURN NULL;
END $$;

DO $$
DECLARE t text;
BEGIN
  FOREACH t IN ARRAY ARRAY['core.persona','core.expediente','core.visita','core.observacion','core.resolucion',
                           'core.movimiento_nomina','core.estado_expediente','core.caso_revision','core.documento',
                           'seg.usuario','seg.rol','seg.rol_permiso','ref.cat_tipo_resolucion','ref.parametro']
  LOOP
    EXECUTE format('CREATE TRIGGER auditoria AFTER INSERT OR UPDATE OR DELETE ON %s FOR EACH ROW EXECUTE FUNCTION audit.f_registrar()', t);
  END LOOP;
END $$;
