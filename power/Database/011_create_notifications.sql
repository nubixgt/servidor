-- Avisos del administrador a los atletas.
--
-- La campana del topbar existía desde el principio y no servía para nada: el
-- popover traía dos avisos inventados escritos en el código ("Ciclismo Z2
-- exportado a Garmin Edge 1040") que se le mostraban igual a todo el mundo, y
-- un punto verde de "no leídas" que nunca se apagaba. Se vaciaron a propósito
-- en su momento -- un aviso falso sobre una sesión que el atleta no hizo es
-- peor que no tener avisos -- dejando el hueco a la espera de una fuente real.
-- Esta es esa fuente.
--
-- ============================================================================
-- POR QUÉ SON DOS TABLAS Y NO UNA
-- ============================================================================
--
-- Un aviso es UNO y lo ven TODOS. Si se guardara una fila por atleta, mandar
-- un aviso a doscientas cuentas escribiría doscientas filas que dicen lo
-- mismo, y editar una errata obligaría a tocarlas todas.
--
-- Pero entonces "eliminar" significa dos cosas distintas según quién lo pulse,
-- y confundirlas es el error que esta tabla existe para evitar:
--
--   - **El atleta** lo quita DE SU BANDEJA. Es un descarte personal: se anota
--     en `notification_dismissals` y el aviso sigue vivo para los demás. Si
--     esto borrara la fila del aviso, un atleta limpiando su bandeja dejaría
--     sin el anuncio a toda la plataforma.
--   - **El administrador** lo retira PARA TODOS. Eso sí borra la fila, y los
--     descartes se van con ella por CASCADE: no tiene sentido recordar quién
--     había escondido un aviso que ya no existe.
--
-- ============================================================================
-- ALCANCE
-- ============================================================================
--
-- Por ahora todo aviso es para TODOS los atletas. No hay destinatario porque
-- todavía no hace falta: es un canal de anuncios ("mañana no habrá servicio",
-- "ya está disponible X"). El día que haga falta mandarle algo a un atleta
-- concreto, se añade una columna `user_id` NULL -- NULL = para todos -- y la
-- consulta de la bandeja crece en un `OR`. No se añade hoy para no dejar una
-- columna que nadie rellena y que hay que explicar en cada lectura.

CREATE TABLE IF NOT EXISTS notifications (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,

    title       VARCHAR(120)  NOT NULL,
    body        VARCHAR(1000) NOT NULL,

    -- Quién lo mandó. ON DELETE SET NULL y no CASCADE: si mañana se borra la
    -- cuenta del administrador, el anuncio que mandó sigue siendo válido para
    -- los atletas que lo tienen delante. Se pierde el autor, no el aviso.
    created_by  INT UNSIGNED  NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_notifications_created (created_at),
    CONSTRAINT fk_notifications_author
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Quién ha quitado qué de SU bandeja. La ausencia de fila es el estado normal.
CREATE TABLE IF NOT EXISTS notification_dismissals (
    notification_id INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    dismissed_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- La clave primaria compuesta hace el trabajo: un atleta no puede
    -- descartar dos veces el mismo aviso, así que el INSERT es idempotente y
    -- pulsar la X dos veces (o con mala conexión) no es un error.
    PRIMARY KEY (notification_id, user_id),
    INDEX idx_dismissals_user (user_id),

    CONSTRAINT fk_dismissal_notification
        FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    CONSTRAINT fk_dismissal_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
