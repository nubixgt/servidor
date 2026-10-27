-- =====================================================================
-- PAM · Sistema Integral de Gestión y Monitoreo del Programa del Adulto Mayor
-- 001 · Extensiones, esquemas y funciones utilitarias
-- =====================================================================
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS unaccent;
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE SCHEMA IF NOT EXISTS ref;    -- catálogos y parámetros
CREATE SCHEMA IF NOT EXISTS seg;    -- usuarios, roles, permisos, sesiones
CREATE SCHEMA IF NOT EXISTS core;   -- datos operativos (persona, expediente, eventos)
CREATE SCHEMA IF NOT EXISTS audit;  -- bitácora inmutable
CREATE SCHEMA IF NOT EXISTS stg;    -- área de carga (importaciones)
CREATE SCHEMA IF NOT EXISTS mart;   -- agregados para tableros

-- Texto normalizado para búsqueda: minúsculas, sin tildes, espacios colapsados.
CREATE OR REPLACE FUNCTION ref.f_norm(t text) RETURNS text
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
  SELECT nullif(btrim(regexp_replace(lower(public.unaccent('public.unaccent'::regdictionary, coalesce(t,''))), '\s+', ' ', 'g')), '')
$$;

-- Fecha dd/mm/aaaa -> date. Devuelve NULL para vacíos, '#VALOR!', años de relleno (<=1900) y fechas imposibles.
CREATE OR REPLACE FUNCTION ref.f_fecha(t text) RETURNS date
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
  SELECT CASE WHEN t ~ '^\s*\d{1,2}/\d{1,2}/\d{4}\s*$' THEN (
    SELECT CASE WHEN y BETWEEN 1901 AND 2100 AND m BETWEEN 1 AND 12 AND d >= 1
                 AND d <= extract(day FROM (make_date(y, m, 1) + interval '1 month - 1 day'))::int
           THEN make_date(y, m, d) END
    FROM (SELECT split_part(btrim(t),'/',3)::int AS y, split_part(btrim(t),'/',2)::int AS m, split_part(btrim(t),'/',1)::int AS d) s)
  END
$$;

-- Validación del CUI/DPI de Guatemala (13 dígitos, depto 01-22, dígito verificador módulo 11).
CREATE OR REPLACE FUNCTION ref.f_cui_valido(t text) RETURNS boolean
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
  SELECT CASE WHEN t ~ '^\d{13}$' THEN
    substr(t,10,2)::int BETWEEN 1 AND 22
    AND substr(t,12,2)::int >= 1
    AND (SELECT sum(substr(t,i,1)::int * (i+1)) FROM generate_series(1,8) i) % 11 = substr(t,9,1)::int
  ELSE false END
$$;

-- Banda de edad (0: 65-69 … 5: 90+; las edades < 65 caen en la banda 0).
CREATE OR REPLACE FUNCTION ref.f_banda_edad(fnac date, ref date DEFAULT current_date) RETURNS smallint
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $$
  SELECT CASE WHEN fnac IS NULL THEN 0
              ELSE least(5, greatest(0, (extract(year FROM age(ref, fnac))::int - 65) / 5))::smallint END
$$;

CREATE TABLE IF NOT EXISTS public.schema_migrations(
  version text PRIMARY KEY,
  aplicada_en timestamptz NOT NULL DEFAULT now()
);
