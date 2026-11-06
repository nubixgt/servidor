"""El enfriamiento va al final, o el reloj manda esfuerzos después de enfriar.

## El fallo, con la sesión real

Generación del atleta 2 del 2026-09-10 (tier `standard`, el de producción).
Sesión de umbral del viernes, tal como salió:

    1 warmup    10'  Z1
    2 repeat x3      3x(6' a 5:46-6:08/km, 2' rec Z1)
    3 cooldown  10'  Z1
    4 main      15'  "Si te sientes suelto: 4x15" strides en llano..."

Un bloque de TRABAJO detrás del enfriamiento. El reloj no lee "si te sientes
suelto": ejecuta los pasos en orden, así que el atleta enfría diez minutos y
entonces la pantalla le pide esfuerzos. Y el bloque reserva 15 minutos para un
contenido de 4x(15"+45") = cuatro.

Es el hueco exacto que este repo persigue: la sesión TIENE la forma de un
entrenador -- calentamiento, series, enfriamiento -- y no se puede correr así.
Ninguna regla del validador lo miraba, porque todas juzgaban los pasos uno a
uno y ésta es una propiedad del ORDEN.

## Por qué se arregla en código y no con el modelo

Porque no hay nada que decidir. El enfriamiento es, por definición, lo último.
Mover el paso no cambia ni una dosis; mandárselo a la reparación de tier-2
cuesta ~$0.11 y le da la oportunidad de reescribir una semana que por lo demás
estaba bien.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    _recolocar_enfriamientos,
)


def paso(orden, tipo, seg=600, desc=""):
    return WorkoutStep(step_order=orden, step_type=tipo, duration_type="time",
                       duration_value=seg, target_type="none", description=desc)


def series(orden, veces=3):
    """Un bloque de repeticiones DE VERDAD: el esquema exige `substeps`, y un
    fixture sin ellos probaria contra una forma que el sistema no acepta."""
    return WorkoutStep(
        step_order=orden, step_type="repeat", repeat_count=veces, target_type="none",
        description=f"{veces}x(6' a ritmo, 2' rec)",
        substeps=[
            WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                        duration_value=360, target_type="none", description="6'"),
            WorkoutStep(step_order=2, step_type="recovery", duration_type="time",
                        duration_value=120, target_type="hr_zone", target_zone=1,
                        description="2' rec"),
        ],
    )


def _dia(pasos):
    return WorkoutDay(
        date="2026-09-11", day_name_es="Viernes", is_rest=False, sport="run",
        focus="umbral 5K", purpose=".", adaptation=".", intensity_class="tempo_threshold",
        discipline="carrera", total_duration_minutes=55.0, steps=pasos,
    )


def _plan(pasos):
    return WeeklyPlanPayload(
        athlete_name="T", start_date="2026-09-10", zones_table="N/A",
        weeks=[{"week_number": 1, "week_rationale": "", "days": [_dia(pasos)]}],
    )


class ElEnfriamientoVaAlFinal(unittest.TestCase):
    def test_el_trabajo_de_despues_se_recoloca(self):
        """La sesión real que lo motivó."""
        p, cambiado = _recolocar_enfriamientos(_plan([
            paso(1, "warmup"), series(2), paso(3, "cooldown"),
            paso(4, "main", 900, "Si te sientes suelto: 4x15\" strides"),
        ]))
        self.assertTrue(cambiado)
        tipos = [s.step_type for s in p.weeks[0].days[0].steps]
        self.assertEqual(tipos, ["warmup", "repeat", "main", "cooldown"])

    def test_renumera_el_orden(self):
        """`step_order` alimenta la subida a Garmin: dejarlo desordenado
        cambiaría el arreglo por otro fallo."""
        p, _ = _recolocar_enfriamientos(_plan([
            paso(1, "warmup"), paso(2, "cooldown"), paso(3, "main"),
        ]))
        self.assertEqual([s.step_order for s in p.weeks[0].days[0].steps], [1, 2, 3])

    def test_una_sesion_bien_ordenada_no_se_toca(self):
        pasos = [paso(1, "warmup"), series(2), paso(3, "cooldown")]
        p, cambiado = _recolocar_enfriamientos(_plan(pasos))
        self.assertFalse(cambiado)
        self.assertEqual([s.step_type for s in p.weeks[0].days[0].steps],
                         ["warmup", "repeat", "cooldown"])

    def test_un_paso_facil_despues_del_enfriamiento_no_cuenta(self):
        """Sólo el TRABAJO obliga a recolocar. Una recuperación detrás del
        enfriamiento es redundante pero no es peligrosa, y tocarla sería
        reordenar por gusto."""
        pasos = [paso(1, "warmup"), paso(2, "cooldown"), paso(3, "recovery")]
        _, cambiado = _recolocar_enfriamientos(_plan(pasos))
        self.assertFalse(cambiado)

    def test_no_cambia_duraciones_ni_objetivos(self):
        """Recolocar no es reescribir: la dosis que decidió el entrenador se
        queda exactamente como estaba."""
        p, _ = _recolocar_enfriamientos(_plan([
            paso(1, "warmup", 600), paso(2, "cooldown", 300), paso(3, "main", 900),
        ]))
        pasos = p.weeks[0].days[0].steps
        self.assertEqual([s.duration_value for s in pasos], [600, 900, 300])
        self.assertEqual(len(pasos), 3)

    def test_una_sesion_sin_enfriamiento_no_revienta(self):
        _, cambiado = _recolocar_enfriamientos(_plan([paso(1, "main")]))
        self.assertFalse(cambiado)

    def test_un_dia_de_descanso_no_revienta(self):
        plan = _plan([paso(1, "warmup")])
        plan.weeks[0].days[0].is_rest = True
        plan.weeks[0].days[0].steps = []
        _, cambiado = _recolocar_enfriamientos(plan)
        self.assertFalse(cambiado)


if __name__ == "__main__":
    unittest.main()
