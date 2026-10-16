-- Esquema inicial del Sistema de Planificación Docente — Colegio CyD
-- Todas las tablas usan claves foráneas con ON DELETE CASCADE donde la información
-- depende de un registro padre. Las restricciones UNIQUE evitan duplicados aunque
-- dos usuarios operen al mismo tiempo.

CREATE TABLE users (
  id               INTEGER PRIMARY KEY,
  username         TEXT NOT NULL,
  username_key     TEXT NOT NULL UNIQUE,          -- minúsculas, sin espacios extremos
  password_hash    TEXT NOT NULL,                 -- scrypt$N$r$p$salt$hash
  full_name        TEXT NOT NULL,
  role             TEXT NOT NULL CHECK (role IN ('docente','admin')),
  active           INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  recovery_email   TEXT,
  email_verified   INTEGER NOT NULL DEFAULT 0 CHECK (email_verified IN (0,1)),
  must_change_password INTEGER NOT NULL DEFAULT 0 CHECK (must_change_password IN (0,1)),
  created_at       TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  updated_at       TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

CREATE TABLE sessions (
  id          TEXT PRIMARY KEY,                   -- SHA-256 del token de la cookie
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  expires_at  TEXT NOT NULL,
  user_agent  TEXT
);
CREATE INDEX idx_sessions_user ON sessions(user_id);

CREATE TABLE password_resets (
  id          INTEGER PRIMARY KEY,
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  token_hash  TEXT NOT NULL UNIQUE,
  kind        TEXT NOT NULL CHECK (kind IN ('email')),
  created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  expires_at  TEXT NOT NULL,
  used_at     TEXT
);

CREATE TABLE email_verifications (
  id          INTEGER PRIMARY KEY,
  user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  email       TEXT NOT NULL,
  token_hash  TEXT NOT NULL UNIQUE,
  created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  expires_at  TEXT NOT NULL,
  used_at     TEXT
);

-- Configuración institucional (fila única)
CREATE TABLE institution (
  id                 INTEGER PRIMARY KEY CHECK (id = 1),
  full_name          TEXT NOT NULL,
  short_name         TEXT NOT NULL,
  header_line1       TEXT NOT NULL DEFAULT '',
  header_line2       TEXT NOT NULL DEFAULT '',
  motto              TEXT NOT NULL DEFAULT '',
  sheet_title        TEXT NOT NULL DEFAULT 'HOJA DE ACTIVIDADES',
  contents_banner    TEXT NOT NULL DEFAULT 'CONTENIDOS',
  default_observations TEXT NOT NULL DEFAULT '',
  note_text          TEXT NOT NULL DEFAULT '',
  sign_teacher       TEXT NOT NULL DEFAULT '',
  sign_parent        TEXT NOT NULL DEFAULT '',
  sign_direction     TEXT NOT NULL DEFAULT '',
  logo               BLOB,
  logo_mime          TEXT,
  updated_at         TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

CREATE TABLE school_years (
  id         INTEGER PRIMARY KEY,
  name       TEXT NOT NULL UNIQUE,
  active     INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  created_at TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

CREATE TABLE bimesters (
  id             INTEGER PRIMARY KEY,
  school_year_id INTEGER NOT NULL REFERENCES school_years(id) ON DELETE CASCADE,
  number         INTEGER NOT NULL CHECK (number BETWEEN 1 AND 4),
  start_date     TEXT,
  end_date       TEXT,
  UNIQUE (school_year_id, number),
  UNIQUE (id, school_year_id),
  CHECK (start_date IS NULL OR end_date IS NULL OR start_date <= end_date)
);

CREATE TABLE grades (
  id         INTEGER PRIMARY KEY,
  name       TEXT NOT NULL,
  name_key   TEXT NOT NULL UNIQUE,
  level      TEXT NOT NULL DEFAULT '',
  sort_order INTEGER NOT NULL DEFAULT 0,
  active     INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1))
);

CREATE TABLE sections (
  id        INTEGER PRIMARY KEY,
  grade_id  INTEGER NOT NULL REFERENCES grades(id) ON DELETE CASCADE,
  name      TEXT NOT NULL,
  name_key  TEXT NOT NULL,
  active    INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  UNIQUE (grade_id, name_key),
  UNIQUE (id, grade_id)
);

CREATE TABLE courses (
  id          INTEGER PRIMARY KEY,
  name        TEXT NOT NULL,
  name_key    TEXT NOT NULL UNIQUE,               -- sin acentos, minúsculas, espacios normalizados
  active      INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
  created_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

-- Asignación: docente + ciclo + curso + grado + sección
CREATE TABLE assignments (
  id             INTEGER PRIMARY KEY,
  user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  school_year_id INTEGER NOT NULL REFERENCES school_years(id) ON DELETE RESTRICT,
  course_id      INTEGER NOT NULL REFERENCES courses(id) ON DELETE RESTRICT,
  grade_id       INTEGER NOT NULL,
  section_id     INTEGER NOT NULL,
  created_at     TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  FOREIGN KEY (section_id, grade_id) REFERENCES sections(id, grade_id) ON DELETE RESTRICT,
  UNIQUE (user_id, school_year_id, course_id, section_id),
  UNIQUE (id, school_year_id)
);

CREATE TABLE plans (
  id             INTEGER PRIMARY KEY,
  assignment_id  INTEGER NOT NULL,
  school_year_id INTEGER NOT NULL,
  bimester_id    INTEGER NOT NULL,
  status         TEXT NOT NULL DEFAULT 'borrador' CHECK (status IN ('borrador','listo')),
  version        INTEGER NOT NULL DEFAULT 1,
  include_pages  INTEGER NOT NULL DEFAULT 0 CHECK (include_pages IN (0,1)),
  observations   TEXT NOT NULL DEFAULT '',
  pdf_hash       TEXT,                          -- huella de la información usada en el PDF
  ready_at       TEXT,
  created_at     TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  updated_at     TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  updated_by     INTEGER REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (assignment_id, school_year_id) REFERENCES assignments(id, school_year_id) ON DELETE CASCADE,
  FOREIGN KEY (bimester_id, school_year_id) REFERENCES bimesters(id, school_year_id) ON DELETE RESTRICT,
  UNIQUE (assignment_id, bimester_id)
);

CREATE TABLE competencies (
  id       TEXT PRIMARY KEY,
  plan_id  INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  position INTEGER NOT NULL,
  text     TEXT NOT NULL DEFAULT ''
);
CREATE INDEX idx_comp_plan ON competencies(plan_id);

CREATE TABLE indicators (
  id       TEXT PRIMARY KEY,
  plan_id  INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  position INTEGER NOT NULL,
  text     TEXT NOT NULL DEFAULT ''
);
CREATE INDEX idx_ind_plan ON indicators(plan_id);

-- Bloque mensual flexible
CREATE TABLE blocks (
  id         TEXT PRIMARY KEY,
  plan_id    INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  position   INTEGER NOT NULL,
  label      TEXT NOT NULL DEFAULT '',
  start_date TEXT,
  end_date   TEXT,
  closed     INTEGER NOT NULL DEFAULT 0 CHECK (closed IN (0,1)),
  UNIQUE (plan_id, position),
  UNIQUE (id, plan_id)
);

-- Exactamente una evaluación por bloque: block_id es UNIQUE
CREATE TABLE evaluations (
  id            TEXT PRIMARY KEY,
  block_id      TEXT NOT NULL UNIQUE REFERENCES blocks(id) ON DELETE CASCADE,
  description   TEXT NOT NULL DEFAULT '',
  instructions  TEXT NOT NULL DEFAULT '',
  scoring_mode  TEXT NOT NULL DEFAULT 'criterios' CHECK (scoring_mode IN ('criterios','total')),
  total_points  REAL,                            -- solo se usa en modo 'total'
  due_date      TEXT
);

CREATE TABLE evaluation_criteria (
  id            TEXT PRIMARY KEY,
  evaluation_id TEXT NOT NULL REFERENCES evaluations(id) ON DELETE CASCADE,
  position      INTEGER NOT NULL,
  text          TEXT NOT NULL DEFAULT '',
  points        REAL
);
CREATE INDEX idx_crit_eval ON evaluation_criteria(evaluation_id);

CREATE TABLE weeks (
  id         TEXT PRIMARY KEY,
  plan_id    INTEGER NOT NULL,
  block_id   TEXT NOT NULL,
  number     INTEGER NOT NULL,                  -- consecutivo dentro del bimestre
  start_date TEXT,
  end_date   TEXT,
  FOREIGN KEY (block_id, plan_id) REFERENCES blocks(id, plan_id) ON DELETE CASCADE,
  UNIQUE (plan_id, number)
);
CREATE INDEX idx_weeks_block ON weeks(block_id);

CREATE TABLE contents (
  id        TEXT PRIMARY KEY,
  week_id   TEXT NOT NULL REFERENCES weeks(id) ON DELETE CASCADE,
  position  INTEGER NOT NULL,
  topic     TEXT NOT NULL DEFAULT '',
  pages     TEXT NOT NULL DEFAULT '',
  periods   INTEGER CHECK (periods IS NULL OR periods >= 0)
);
CREATE INDEX idx_contents_week ON contents(week_id);

CREATE TABLE learning_activities (
  id         TEXT PRIMARY KEY,
  content_id TEXT NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
  position   INTEGER NOT NULL,
  text       TEXT NOT NULL DEFAULT ''
);
CREATE INDEX idx_la_content ON learning_activities(content_id);

-- Apartados bimestrales de la hoja (no son evaluaciones mensuales)
CREATE TABLE extra_sections (
  id            TEXT PRIMARY KEY,
  plan_id       INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  kind          TEXT NOT NULL CHECK (kind IN ('mejoramiento','afectivo','asistencia','examen_final')),
  applies       INTEGER CHECK (applies IS NULL OR applies IN (0,1)),  -- NULL = sin definir
  detail        TEXT NOT NULL DEFAULT '',      -- texto junto al título (columna Contenido)
  activity      TEXT NOT NULL DEFAULT '',      -- columna Actividades de evaluación
  scoring_mode  TEXT NOT NULL DEFAULT 'total' CHECK (scoring_mode IN ('criterios','total')),
  total_points  REAL,
  aspects_note  TEXT NOT NULL DEFAULT '',      -- texto adicional en Aspectos a evaluar
  due_date      TEXT,
  due_text      TEXT NOT NULL DEFAULT '',      -- p. ej. una indicación de período en lugar de fecha
  observations  TEXT NOT NULL DEFAULT '',
  UNIQUE (plan_id, kind)
);

CREATE TABLE extra_criteria (
  id        TEXT PRIMARY KEY,
  extra_id  TEXT NOT NULL REFERENCES extra_sections(id) ON DELETE CASCADE,
  position  INTEGER NOT NULL,
  text      TEXT NOT NULL DEFAULT '',
  points    REAL
);

CREATE TABLE audit_log (
  id         INTEGER PRIMARY KEY,
  at         TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
  user_role  TEXT,
  action     TEXT NOT NULL,
  entity     TEXT NOT NULL,
  entity_id  TEXT,
  plan_id    INTEGER,
  details    TEXT
);
CREATE INDEX idx_audit_plan ON audit_log(plan_id);

CREATE TABLE generated_documents (
  id            INTEGER PRIMARY KEY,
  plan_id       INTEGER NOT NULL REFERENCES plans(id) ON DELETE CASCADE,
  plan_version  INTEGER NOT NULL,
  generated_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
  generated_at  TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now')),
  filename      TEXT NOT NULL,
  snapshot      TEXT NOT NULL,                 -- datos congelados usados para el PDF
  pdf           BLOB NOT NULL,
  sha256        TEXT NOT NULL
);
CREATE INDEX idx_docs_plan ON generated_documents(plan_id);

-- Un plan sólo puede usar un bimestre del mismo ciclo que su asignación (ya garantizado
-- por las claves foráneas compuestas). Las semanas deben pertenecer a un bloque del mismo plan
-- (clave foránea compuesta en weeks).
