-- Lo que cada cuenta ha costado en APIs, para poder cobrarlo.
--
-- ============================================================================
-- LA REGLA QUE NO SE PUEDE COPIAR DE LAS OTRAS MIGRACIONES
-- ============================================================================
--
-- Las siete tablas anteriores llevan `ON DELETE CASCADE` sobre `users(id)`, y
-- para ellas está bien: si se va el usuario, se van su perfil, su cuaderno y
-- sus planes.
--
-- Aquí NO. Esta tabla es un libro contable, y un libro contable que se borra
-- cuando el cliente borra sus datos no es un libro contable. El dinero ya se le
-- pagó a Anthropic, a OpenAI y a Google; la factura llega igual aunque el
-- atleta borre su plan, su perfil o su cuenta entera. Por eso no hay clave
-- foránea: la fila sobrevive al usuario a propósito, y `user_id` se queda como
-- el número al que se le imputó el gasto.
--
-- Es la misma regla que ya sigue el libro de origen en el AiService
-- (`PaginaLocal/core/usage_ledger.py`), que vive fuera del árbol que se borra y
-- al que sólo se le añaden líneas, nunca se le reescribe ni se le quita una.
--
-- ============================================================================
-- QUÉ ES Y QUÉ NO ES
-- ============================================================================
--
-- Es **costo de proveedor**: lo que la app pagó por cuenta de este atleta.
-- No es una factura, no lleva impuestos y no sabe de precios de suscripción.
-- Convertir esto en lo que se le cobra al usuario es una decisión de negocio
-- que se toma con estos números delante, no dentro de la base de datos.
--
-- Y no es la fuente de la verdad: lo es el `.jsonl` del AiService, que guarda
-- una línea por corrida con sus tokens. Esto es un espejo agregado por mes,
-- para poder consultarlo y facturarlo desde el backend sin depender de que el
-- servicio de Python esté arriba. Si las dos cifras discrepan, manda el
-- `.jsonl` — y que discrepen es señal de que la sincronización se rompió.

CREATE TABLE IF NOT EXISTS athlete_usage (
    user_id     INT UNSIGNED NOT NULL,
    -- 'YYYY-MM'. El corte por mes es el que se factura.
    month       CHAR(7)      NOT NULL,

    -- DECIMAL y no FLOAT: sumar dinero en coma flotante acumula error, y aquí
    -- el error es una diferencia entre lo cobrado y lo pagado. Seis decimales
    -- porque una corrida barata cuesta $0.0004.
    cost_usd    DECIMAL(12,6) NOT NULL DEFAULT 0,
    runs        INT UNSIGNED  NOT NULL DEFAULT 0,
    tokens      BIGINT UNSIGNED NOT NULL DEFAULT 0,

    -- Desglose por tipo (semana / temporada / chat) tal cual lo da el puente.
    -- Se guarda entero para no tener que migrar la tabla cada vez que aparezca
    -- un tipo de gasto nuevo.
    by_kind     JSON          NULL,

    synced_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, month),
    INDEX idx_usage_month (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
