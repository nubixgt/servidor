-- La NOTA al pie de la hoja de actividades pasa a ser la indicación de entrega con fotografía firmada y sellada.
-- Sólo se reemplaza si el texto sigue siendo el original (no se pisa un texto que la administración haya personalizado).
UPDATE institution
   SET note_text = 'Subir fotografía de la hoja firmada y sellada por cada tarea entregada. Si no sube se le restarán 3 pts. de la tarea.'
 WHERE id = 1
   AND note_text = 'Las fichas de calificación deberán subirse a la APP del CyD, únicamente en formato PDF.';
