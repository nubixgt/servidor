-- Teléfono de contacto de la reserva (formato 0000-0000).
-- Ejecutar una sola vez. Las reservas anteriores quedan con teléfono vacío (NULL).
ALTER TABLE reservations
    ADD COLUMN phone VARCHAR(9) NULL AFTER name;
