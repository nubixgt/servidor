-- 036: los pagos de la suscripción (decisión del responsable, 2026-10-20).
--
-- Run ONCE, after 035. Aditiva y repetible.
--
-- ## Por qué
--
-- «El precio del producto es Q60 (8 USD) al mes», y las semanas gratis terminan en un muro de pago:
-- sin acceso no se generan semanas ni macrociclo (el plan, el reloj y el chat siguen). El pago se hace
-- FUERA de la app (transferencia o lo que se acuerde) y el administrador lo registra en «Usuarios»;
-- cada fila alarga el acceso desde donde terminaba. Es contabilidad: sin clave foránea a `users`, para
-- que borrar una cuenta no borre lo que se cobró, y nunca se borra una fila (un error se corrige con
-- otra, con su nota).
--
-- Tolerancia: `AccessService` sin esta tabla (o sin la 035) no pone muro: todo sigue como antes.

CREATE TABLE IF NOT EXISTS athlete_payments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL COMMENT 'Sin FK: es contabilidad y sobrevive al borrado.',
    months       SMALLINT UNSIGNED NOT NULL,
    amount_gtq   DECIMAL(8,2) NOT NULL,
    paid_from    DATE NOT NULL COMMENT 'Desde cuándo vale este pago.',
    paid_until   DATE NOT NULL COMMENT 'Hasta cuándo, inclusive.',
    recorded_by  INT UNSIGNED NULL COMMENT 'El administrador que lo registró.',
    note         VARCHAR(191) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_payments_user (user_id, paid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
