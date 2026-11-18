-- Los grados "(fin de semana)" deben usar el calendario fijo de fin de semana. Las asignaciones nacieron con
-- plan_type='diario' por defecto, así que esos grupos veían fechas del plan diario y no podían ajustarlas.
UPDATE assignments SET plan_type = 'fds'
 WHERE plan_type = 'diario'
   AND grade_id IN (SELECT id FROM grades WHERE lower(name) LIKE '%(fin de semana)%');
