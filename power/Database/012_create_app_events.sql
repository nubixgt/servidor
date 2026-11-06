-- Cómo se usa la app, que hasta ahora no se medía en ninguna parte.
--
-- ============================================================================
-- QUÉ FALTABA Y POR QUÉ IMPORTA AHORA
-- ============================================================================
--
-- La app ya sabía dos cosas: lo que cada atleta ha COSTADO (`athlete_usage`,
-- migración 009) y cuánto CUMPLIÓ de lo que se le prescribió
-- (`track_record.jsonl`, en el AiService). Las dos son sobre el entrenamiento.
--
-- Ninguna respondía la pregunta de un MVP: **¿esto le importa a alguien?**
-- No se sabía si un atleta volvió a entrar, cuántos terminan el perfil,
-- cuántos llegan a generar su primer plan, ni cuánto habla con el coach. Sin
-- eso, la decisión de seguir o pivotar se toma con la impresión que dejan las
-- conversaciones, que siempre es más optimista que la realidad.
--
-- ============================================================================
-- POR QUÉ AQUÍ Y NO EN GOOGLE ANALYTICS
-- ============================================================================
--
-- Porque lo que se mide son personas identificables usando una app de SALUD:
-- su entrenamiento, sus lesiones, su fisiología. Mandar eso a un tercero para
-- responder "¿cuántos volvieron?" es un intercambio que no vale la pena, y en
-- beta con amigos menos todavía. Se queda en el mismo MySQL que el resto.
--
-- Por eso tampoco se guarda ni la IP ni el user-agent: no hacen falta para
-- ninguna de las preguntas de arriba, y un dato que no se recoge no se puede
-- filtrar.
--
-- ============================================================================
-- LA REGLA QUE SE COPIA DE LA 009, Y LA QUE NO
-- ============================================================================
--
-- SIN clave foránea, igual que `athlete_usage`: si el atleta borra su cuenta,
-- el hecho de que usó la app doce veces en marzo siguió pasando, y es
-- justamente lo que hace falta para entender si el producto funciona. La fila
-- sobrevive al usuario a propósito y `user_id` se queda como el número al que
-- se le atribuyó.
--
-- A diferencia de la 009, esto SÍ se puede podar: no es un libro contable, es
-- telemetría. Cuando pese, se borran los eventos crudos viejos y se guardan
-- los agregados. Hoy, con diez atletas, no pesa.
--
-- ============================================================================
-- LO QUE NO ES
-- ============================================================================
--
-- No es una auditoría de seguridad ni un registro legal. Se anota en un
-- `try/catch` vacío desde el router: si el INSERT falla, **se pierde el evento
-- y la petición del atleta continúa**. Una analítica que puede tumbar una
-- petición es peor que no tener analítica.

CREATE TABLE IF NOT EXISTS app_events (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- NULL en lo que pase sin sesión (el login mismo). Sin FK: ver arriba.
    user_id     INT UNSIGNED NULL,

    -- Qué pasó, en vocabulario de producto y no de HTTP: 'login',
    -- 'plan_generado', 'chat_mensaje'... Lo decide EventService, no el router,
    -- para que la lista de eventos no dependa de cómo estén nombradas las
    -- rutas hoy.
    event       VARCHAR(64)  NOT NULL,

    -- La ruta que lo produjo, tal cual. Se guarda además del evento porque es
    -- lo que permite responder preguntas que no se anticiparon al diseñar la
    -- lista de eventos de arriba.
    route       VARCHAR(191) NULL,

    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    -- Las tres consultas del panel: actividad de un atleta, embudo por tipo de
    -- evento, y series por día.
    INDEX idx_events_user_time  (user_id, created_at),
    INDEX idx_events_event_time (event, created_at),
    INDEX idx_events_time       (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- La retención: la señal más honesta de un MVP y la que no se podía calcular.
--
-- Va en `users` y no en `app_events` porque se consulta en cada listado del
-- panel ("¿quién sigue entrando?") y sacarla de una tabla de eventos que crece
-- sin límite obligaría a un MAX() por atleta cada vez.
--
-- Es idempotente para poder correrla dos veces sin miedo: MySQL 8 y MariaDB
-- 10.5 no aceptan `ADD COLUMN IF NOT EXISTS` de la misma forma, así que se
-- comprueba en information_schema.
SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'last_login_at'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL DEFAULT NULL AFTER approved_at',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
