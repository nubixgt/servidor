-- Algunos grados de fin de semana se nombran "(FS)" en lugar de "(fin de semana)" (p. ej. Cuarto/Quinto Bachillerato en
-- Computación ... y Diplomado en Administración de Empresas (FS)). La migración 010 no los detectó y quedaron en plan diario.
UPDATE assignments SET plan_type = 'fds'
 WHERE plan_type = 'diario'
   AND grade_id IN (SELECT id FROM grades WHERE upper(name) LIKE '%(FS)%');
