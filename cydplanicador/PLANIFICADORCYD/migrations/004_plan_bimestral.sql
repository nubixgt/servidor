-- Datos que necesita el «Plan bimestral en PDF» y que no existían.
-- 1) Plan de mejoramiento: se reutiliza el apartado «Actividad de mejoramiento» (descripción y fecha) y sólo se
--    agregan las columnas que faltan; las acciones adicionales se guardan en su propia tabla.
ALTER TABLE extra_sections ADD COLUMN strategies TEXT NOT NULL DEFAULT '';
ALTER TABLE extra_sections ADD COLUMN students   TEXT NOT NULL DEFAULT '';

CREATE TABLE improvement_actions (
  id           TEXT PRIMARY KEY,
  plan_id      INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  position     INTEGER NOT NULL,
  description  TEXT NOT NULL DEFAULT '',
  strategies   TEXT NOT NULL DEFAULT '',
  students     TEXT NOT NULL DEFAULT '',
  action_date  TEXT
);
CREATE INDEX idx_improvement_plan ON improvement_actions(plan_id);

-- 2) Entrega institucional del plan (acción explícita del docente; guardar, marcar listo o generar PDF no es entregar).
ALTER TABLE plans ADD COLUMN delivered_at TEXT;
ALTER TABLE plans ADD COLUMN delivered_version INTEGER;

-- 3) Observaciones y revisión de coordinación (registradas por un usuario autorizado).
ALTER TABLE plans ADD COLUMN coord_observations TEXT NOT NULL DEFAULT '';
ALTER TABLE plans ADD COLUMN coord_observations_by INTEGER REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE plans ADD COLUMN coord_observations_at TEXT;

CREATE TABLE plan_reviews (
  id            INTEGER PRIMARY KEY,
  plan_id       INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  reviewer_id   INTEGER REFERENCES users(id) ON DELETE SET NULL,
  reviewer_name TEXT NOT NULL,
  result        TEXT NOT NULL CHECK (result IN ('aprobado','con_observaciones','requiere_correcciones')),
  plan_version  INTEGER NOT NULL,
  reviewed_at   TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);
CREATE INDEX idx_reviews_plan ON plan_reviews(plan_id);
