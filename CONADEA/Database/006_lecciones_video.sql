-- =====================================================================
-- CONADEA · MAGA AgroIA — Migración: video por lección
-- Cada lección puede tener 1 video propio (estilo Udemy: lección = video +
-- descripción). Se sube como archivo a
-- Backend/uploads/cursos/{cursoId}/lecciones/{orden}/video.<ext>, igual que
-- cursos.imagen_path — la columna guarda la ruta relativa, no una URL.
-- Es NULL-able a propósito: el video es opcional, para no romper las
-- lecciones ya creadas que solo tienen texto (contenido).
-- Ejecutar después de 001, 002, 003 y 005.
-- =====================================================================

ALTER TABLE lecciones
    ADD COLUMN video_path VARCHAR(300) NULL AFTER contenido;
