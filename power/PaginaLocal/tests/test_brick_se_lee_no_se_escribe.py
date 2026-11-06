"""El brick deja de emitirse sin romper los que ya están guardados.

## Por qué se retira

Un entreno multideporte no le llega bien a varios relojes: el atleta lo sube y
le aparece a medias o no le aparece. Y no hace falta — un brick de bici y
carrera son dos sesiones el mismo día, que es como el atleta las hace de todas
formas y como cualquier reloj las entiende. Lo que hay que decirle es que las
encadene, y eso cabe en el texto de la sesión.

## La asimetría que hay que probar

Quitar `"brick"` del `Literal` de `WorkoutDay` habría sido lo obvio y lo malo:
los cinco atletas de la beta pueden tener bricks dentro de su plan guardado, y
con eso no podrían ni abrir su semana ni volver a subirla al reloj. El fallo no
sería visible al desplegar — aparecería la primera vez que uno de ellos pulsara
"Subir a Garmin".

Así que la regla es **se lee, no se escribe**, y son dos garantías distintas:

- `DayIntent` y `FormattedWorkoutDay` NO lo admiten. Son los esquemas que el
  proveedor le impone al modelo, y es el único sitio donde una prohibición se
  cumple sola.
- `WorkoutDay` SÍ lo admite. Es el que valida lo que ya está en la base de datos.

Correr con:  python -m unittest discover -s tests
"""

import sys
import typing
import unittest
from pathlib import Path

from pydantic import ValidationError

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    DayIntent,
    FormattedWorkoutDay,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)


def _deportes(modelo) -> tuple[str, ...]:
    return typing.get_args(modelo.model_fields["sport"].annotation)


class NoSeEscribe(unittest.TestCase):
    def test_la_capa_de_decision_no_puede_proponer_un_brick(self):
        self.assertNotIn("brick", _deportes(DayIntent))

    def test_el_formateador_tampoco(self):
        """Hereda de WorkoutDay, que sí lo admite: hay que sobreescribirlo."""
        self.assertNotIn("brick", _deportes(FormattedWorkoutDay))

    def test_el_esquema_rechaza_un_brick_de_verdad(self):
        """No basta con que no esté en la lista: tiene que fallar al validar."""
        with self.assertRaises(ValidationError):
            FormattedWorkoutDay(
                date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="brick",
                focus="Brick", purpose="x", adaptation="x", steps=[],
            )

    def test_el_prompt_ya_no_lo_ofrece_y_dice_como_se_hace(self):
        from services.ai.langgraph.nodes.weekly_planner_node import (
            TRAINING_DECISION_USER_PROMPT,
            WORKOUT_FORMATTER_USER_PROMPT,
        )
        self.assertIn("DOS sesiones encadenadas", TRAINING_DECISION_USER_PROMPT)
        self.assertNotIn('`sport="brick"` y `discipline="brick"`', TRAINING_DECISION_USER_PROMPT)
        self.assertIn('No emitas `sport="brick"`', WORKOUT_FORMATTER_USER_PROMPT)


class SiSeLee(unittest.TestCase):
    """La mitad que protege a los cinco atletas de la beta."""

    def _dia_brick(self) -> WorkoutDay:
        return WorkoutDay(
            date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="brick",
            focus="Brick", purpose="Transición bici-carrera",
            adaptation="Si estás cansado: sólo la bici.",
            total_duration_minutes=70.0,
            steps=[
                WorkoutStep(step_order=1, step_type="interval", leg_sport="bike",
                            duration_type="time", duration_value=2400, description="40' bici"),
                WorkoutStep(step_order=2, step_type="interval", leg_sport="run",
                            duration_type="time", duration_value=1200, description="20' corriendo"),
            ],
        )

    def test_un_dia_con_brick_sigue_validando(self):
        self.assertEqual(self._dia_brick().sport, "brick")

    def test_una_semana_guardada_con_brick_se_puede_releer(self):
        """Es lo que hace `Subir a Garmin` con el plan que hay en la base."""
        crudo = WeeklyPlanPayload(
            athlete_name="Hugo", start_date="2026-09-14", zones_table="Z1 …",
            weeks=[WeekBlock(week_number=1, days=[self._dia_brick()])],
        ).model_dump()
        releido = WeeklyPlanPayload.model_validate(crudo)
        self.assertEqual(releido.weeks[0].days[0].sport, "brick")

    def test_sigue_siendo_exportable_al_reloj(self):
        """Si dejara de serlo, el plan guardado se volveria texto de un dia a otro."""
        from services.ai.langgraph.schemas.plan_outputs import is_garmin_exportable
        dia = self._dia_brick()
        self.assertTrue(is_garmin_exportable(dia.discipline, dia.sport, dia.is_rest, dia.steps))

    def test_el_constructor_sigue_montando_el_multideporte(self):
        from services.garmin.workout_builder import build_garmin_workout, compute_zone_config
        zonas = compute_zone_config(lactate_threshold_hr=176)
        dto = build_garmin_workout(self._dia_brick(), zonas)
        # 10, no 9: se mando 9 una vez -- deducido de la lista de deportes -- y
        # Garmin acepto la subida guardandola como un entreno de UN solo deporte.
        self.assertEqual(dto["sportType"]["sportTypeId"], 10)
        self.assertEqual(len(dto["workoutSegments"]), 2)


if __name__ == "__main__":
    unittest.main()
