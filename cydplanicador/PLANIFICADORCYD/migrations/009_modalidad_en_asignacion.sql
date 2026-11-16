-- La modalidad (plan diario / fin de semana) la elige el docente por grupo (asignación), no por curso:
-- un mismo curso (p. ej. Matemática) se da en ambas modalidades. courses.plan_type (migración 008) queda sin uso.
ALTER TABLE assignments ADD COLUMN plan_type TEXT NOT NULL DEFAULT 'diario' CHECK (plan_type IN ('diario','fds'));
-- Conserva lo que el administrador ya hubiera marcado en el curso.
UPDATE assignments SET plan_type = (SELECT c.plan_type FROM courses c WHERE c.id = assignments.course_id);
