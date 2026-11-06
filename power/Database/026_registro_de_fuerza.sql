-- 026: lo que el atleta LEVANTÓ de verdad en el gimnasio (Beta 3.0).
--
-- Run ONCE, after 025. Aditiva y repetible: comprueba la tabla antes de crearla.
--
-- ## Por qué
--
-- La app prescribe fuerza desde la Beta 1 y NUNCA ha sabido qué se hizo con ella.
-- Garmin no lo resuelve: medido el 2026-09-17 en la cuenta del atleta 2 (actividad
-- 24371594337), `get_activity_exercise_sets` devuelve repeticiones y descansos
-- reales, pero el ejercicio viene `UNKNOWN` o como probabilidad (BURPEE 53,9 %) y
-- el peso llega a 0.0. Sin saber qué levantó no hay progresión posible: el
-- programa se queda congelado en lo que el atleta confirmó el primer día, que es
-- justo lo que Hevy, Strong, Fitbod o Juggernaut sí hacen.
--
-- Una fila por SESIÓN (día + franja), con sus ejercicios dentro:
--   [{"exercise_id": "upper_a_g1_e1", "name": "Press de banca",
--     "sets": 4, "reps": "8", "weight_kg": 60, "rpe": 8, "done": true}]
--
-- El `exercise_id` es el del programa confirmado (Database/025), así que la
-- progresión puede seguir el mismo ejercicio semana a semana aunque cambie de
-- nombre. Un registro de un ejercicio AÑADIDO esa semana no tiene id del programa
-- y va con el suyo propio; se guarda igual.
--
-- La tolerancia a que esta migración todavía no esté es la de siempre:
-- `StrengthLogRepository::disponible()` comprueba la tabla y, si no está, la app
-- se comporta como hasta hoy (no se registra nada y nadie ve un error).

CREATE TABLE IF NOT EXISTS athlete_strength_logs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    -- UNSIGNED como `users.id` (int(10) unsigned): con INT a secas MySQL rechaza
    -- la clave ajena con errno 150, y la tabla no llega a crearse.
    user_id       INT UNSIGNED NOT NULL,
    fecha         DATE NOT NULL,
    session_slot  VARCHAR(8) NOT NULL DEFAULT 'unica',
    routine_session_id VARCHAR(64) NULL
        COMMENT 'La sesion del programa que tocaba ese dia, si venia de uno.',
    exercises     JSON NOT NULL
        COMMENT 'Lo que hizo, ejercicio a ejercicio: series, repeticiones, kilos y RPE.',
    note          TEXT NULL COMMENT 'Lo que el atleta quiera decir de esa sesion.',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_sesion (user_id, fecha, session_slot),
    KEY idx_usuario_fecha (user_id, fecha),
    CONSTRAINT fk_strength_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
