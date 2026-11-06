-- Una lesión que se cura cambia de estado, no de tabla. Y la piscina tiene largo.
--
-- Run ONCE, after 013.
--
-- ## Lesiones: estado y observación
--
-- `athlete_injuries` sólo sabía guardar lesiones ACTUALES. Una que se curaba
-- únicamente se podía borrar, así que el historial del atleta desaparecía justo
-- cuando pasaba a ser historial — que es cuando empieza a valer para decidir
-- progresiones. Lo pasado vivía aparte, en `athlete_profiles
-- .past_injuries_surgeries`, un TEXT libre sin fecha, sin lado y sin dolor.
--
-- Dos tablas para lo mismo habrían sido dos sitios donde cambia lo mismo. Una
-- lesión pasada ES una lesión, con su ubicación, su lateralidad y su historia:
-- lo único que cambia es que ya no duele. Así que se queda donde está y se le
-- añade el estado.
--
-- `past_injuries_surgeries` NO se toca ni se migra: es lo que los cinco atletas
-- de la beta escribieron a mano y sigue llegando al prompt igual. Las nuevas se
-- registran estructuradas; aquélla se vacía sola con el tiempo.
--
-- `notes` es la "observación" que faltaba: lo que el atleta quiere que su
-- entrenador sepa de esa lesión y no cabe en los otros campos ("me pasa sólo en
-- bajada", "el fisio dice que es la cadera, no la rodilla").
--
-- ## Piscina: 25 ó 50
--
-- `pool_access` decía ninguno / limitado / completo, y nunca el LARGO. Una serie
-- de 8x100m son 4 largos en una piscina de 25 m y 2 en una de 50, y el sistema
-- no lo sabía, no lo decía y no podía escribirlo en la descripción. El atleta lo
-- cuenta de cabeza en el agua.
--
-- NULL a propósito: no sabemos el largo de nadie todavía, y un DEFAULT 25
-- inventaría el dato para los nueve. Sin él, la prescripción sigue escribiéndose
-- en metros como hasta ahora.

ALTER TABLE athlete_injuries
    ADD COLUMN status ENUM('activa','resuelta') NOT NULL DEFAULT 'activa'
        COMMENT 'Una lesion curada se queda en la tabla, cambiando de estado.'
        AFTER pain_scale,
    ADD COLUMN notes TEXT NULL
        COMMENT 'Observacion libre del atleta sobre esta lesion.'
        AFTER treatment;

ALTER TABLE athlete_profiles
    ADD COLUMN pool_length_m SMALLINT UNSIGNED NULL
        COMMENT 'Largo de la piscina en metros (25, 50, u otro). NULL = sin declarar.'
        AFTER pool_access;
