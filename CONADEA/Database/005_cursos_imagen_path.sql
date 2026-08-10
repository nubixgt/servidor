-- =====================================================================
-- CONADEA · MAGA AgroIA — Migración: cursos.imagen_url -> imagen_path
-- Ahora la imagen del curso se sube como archivo (Backend/uploads/cursos/{id}/),
-- ya no se pide una URL externa. La columna guarda la ruta relativa
-- (ej. "uploads/cursos/12/imagen.jpg"), no una URL completa.
-- Ejecutar después de 001, 002 y 003. Si la tabla `cursos` ya tiene datos
-- de prueba con URLs externas, ese valor se conserva tal cual en la
-- columna renombrada (no se borra nada).
-- =====================================================================

ALTER TABLE cursos
    CHANGE COLUMN imagen_url imagen_path VARCHAR(300) NOT NULL;
