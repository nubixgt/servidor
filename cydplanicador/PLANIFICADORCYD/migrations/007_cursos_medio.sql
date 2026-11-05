-- Cursos solicitados por el colegio: Medio Natural y Medio Social.
INSERT INTO courses(name, name_key) VALUES ('Medio Natural', 'medio natural') ON CONFLICT(name_key) DO NOTHING;
INSERT INTO courses(name, name_key) VALUES ('Medio Social', 'medio social') ON CONFLICT(name_key) DO NOTHING;
