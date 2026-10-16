-- Listado institucional de docentes y su carga académica de referencia (importados del sistema del colegio).
-- Al registrarse, el docente busca su nombre en este listado; su carga académica precarga sus cursos,
-- grados y secciones, que él revisa antes de guardar. Una persona del listado sólo puede tener una cuenta.
CREATE TABLE teachers (
  id           INTEGER PRIMARY KEY,
  external_id  TEXT UNIQUE,                       -- ID del listado institucional, si existe
  full_name    TEXT NOT NULL,
  name_key     TEXT NOT NULL UNIQUE,              -- palabras normalizadas y ordenadas (no depende del orden nombre/apellido)
  active       INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0,1)),
  user_id      INTEGER UNIQUE REFERENCES users(id) ON DELETE SET NULL,
  created_at   TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

CREATE TABLE teacher_loads (
  id          INTEGER PRIMARY KEY,
  teacher_id  INTEGER NOT NULL REFERENCES teachers(id) ON DELETE CASCADE,
  course_id   INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
  grade_id    INTEGER NOT NULL REFERENCES grades(id) ON DELETE CASCADE,
  section_id  INTEGER NOT NULL REFERENCES sections(id) ON DELETE CASCADE,
  periods     INTEGER,
  UNIQUE (teacher_id, course_id, section_id)
);
CREATE INDEX idx_loads_teacher ON teacher_loads(teacher_id);
