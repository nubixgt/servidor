-- Si el atleta SE CONSIDERA propenso a lesionarse.
--
-- Es lo único de la ficha de salud que no se puede deducir de ningún dato. Las
-- lesiones activas se declaran una por una, el PAR-Q pregunta por riesgo
-- cardiovascular, y el historial de Garmin cuenta lo que entrenó -- pero
-- ninguno responde "a este cuerpo se le rompe algo cada vez que subo el
-- volumen". Eso sólo lo sabe él, y cambia cómo debería progresar la carga.
--
-- Deliberadamente AUTOPERCIBIDO, y así se le dice al modelo: no es un
-- diagnóstico ni una medida, es la lectura que el atleta hace de su propio
-- cuerpo. Vale precisamente por eso, y por eso el prompt lo enmarca como
-- cautela y nunca como una razón para recortarle el plan por su cuenta.
--
-- NOT NULL DEFAULT 0, y no un tercer estado "sin contestar": las columnas
-- booleanas del perfil pasan por ProfileFields::BOOLS, que escribe 0 ó 1 y
-- lee `(bool)(int)($row[$c] ?? 0)`. Un NULL acabaría leyéndose como `false` de
-- todas formas, así que declararlo NULL prometería un matiz que ninguna capa
-- sabe transportar. Es una casilla: marcada o no.
ALTER TABLE athlete_profiles
    ADD COLUMN injury_prone TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'El atleta se considera propenso a lesionarse (autopercepcion).'
        AFTER par_q_flags;
