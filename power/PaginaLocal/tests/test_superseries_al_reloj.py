"""Beta 2.1, paso S2: una superserie llega al reloj como UNA ronda con sus ejercicios.

Garmin admite rondas con varios ejercicios dentro (lo usa su propio constructor de
entrenos de fuerza). Hasta S2 la app subía siempre un grupo de repetición POR
EJERCICIO, así que la superserie de Fernando habría llegado como series sueltas:
4 de press, luego 4 de elevaciones, luego 3 de crunch.

Los nombres de gimnasio se verificaron subiendo el entreno 1701096031 a la cuenta
del atleta 2 y leyéndolo de vuelta (2026-09-17, con su permiso): 20 de 20.

Y la forma, subiendo la Upper A de Fernando tal como la arma este constructor
(entreno 1701100942, programado en el reloj del atleta 2 para el 2026-09-18, con
su permiso). Leído de vuelta: rondas 3, 4 y 3; la cuarta serie de press y
elevaciones suelta; los 11 ejercicios con nombre y categoría; 120 s al cerrar
cada ronda; stepOrder 1-20 sin huecos. El responsable lo miró y dio por buena
la cuarta serie suelta. Garmin también guarda una repetición de 1 (verificación
1701118922, borrada), por si se prefiere enmarcarla como ronda.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    StrengthBlock,
    StrengthExercise,
    StrengthGroup,
    WorkoutDay,
    WorkoutStep,
)
from services.garmin import garmin_vocabulary as vocab  # noqa: E402
from services.garmin.workout_builder import (  # noqa: E402
    MAX_PASOS_DE_UN_ENTRENO,
    AthleteZoneConfig,
    build_garmin_workout,
)

ZONAS = AthleteZoneConfig(hr_zones={1: (123, 139), 2: (140, 153), 3: (154, 167)})


def _ej(nombre, series, reps, categoria="", garmin="", descanso=None):
    return StrengthExercise(name=nombre, sets=series, reps=reps, garmin_category=categoria,
                            garmin_exercise=garmin, rest_seconds=descanso)


def _dia(bloque):
    return WorkoutDay(
        date="2026-09-18", day_name_es="Viernes", is_rest=False, sport="strength", focus="Upper A",
        purpose="Su rutina", adaptation="", total_duration_minutes=120, intensity_class="strength_freeform",
        discipline="fuerza", strength_block=bloque,
        steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time", duration_value=7200,
                           description="Upper A, 120 min")],
    )


def _upper_a(entre_ejercicios=None):
    return StrengthBlock(warmup="10' de movilidad", cooldown="Estiramiento", groups=[
        StrengthGroup(label="A", rest_between_rounds_s=120, rest_between_exercises_s=entre_ejercicios, exercises=[
            _ej("Press de banca", 4, "8", "BENCH_PRESS", "BARBELL_BENCH_PRESS"),
            _ej("Elevaciones laterales", 4, "12", "LATERAL_RAISE", "DUMBBELL_LATERAL_RAISE"),
            _ej("Crunch", 3, "15", "CRUNCH", "CRUNCH"),
        ]),
    ])


def _pasos(dia):
    return build_garmin_workout(dia, ZONAS)["workoutSegments"][0]["workoutSteps"]


class LaSuperserieEsUnaRonda(unittest.TestCase):
    def test_series_desiguales_se_parten_en_rondas_que_suman_las_suyas(self):
        """Press 4, elevaciones 4, crunch 3 → 3×(los tres) y la cuarta de press y elevaciones, suelta."""
        pasos = _pasos(_dia(_upper_a()))
        grupos = [p for p in pasos if p.get("workoutSteps")]
        self.assertEqual([g["numberOfIterations"] for g in grupos], [3])
        nombres = [s.get("exerciseName") for s in grupos[0]["workoutSteps"] if s["stepType"]["stepTypeKey"] != "rest"]
        self.assertEqual(nombres, ["BARBELL_BENCH_PRESS", "DUMBBELL_LATERAL_RAISE", "CRUNCH"])
        repeticiones = sum(3 for _ in nombres) + sum(1 for p in pasos if not p.get("workoutSteps") and p.get("category"))
        self.assertEqual(repeticiones, 4 + 4 + 3)

    def test_el_descanso_de_ronda_cierra_cada_ronda(self):
        for grupo in [p for p in _pasos(_dia(_upper_a())) if p.get("workoutSteps")]:
            ultimo = grupo["workoutSteps"][-1]
            self.assertEqual(ultimo["stepType"]["stepTypeKey"], "rest")
            self.assertEqual(ultimo["endConditionValue"], 120.0)

    def test_el_descanso_entre_ejercicios_va_entre_ellos_no_despues_del_ultimo(self):
        grupo = next(p for p in _pasos(_dia(_upper_a(entre_ejercicios=15))) if p.get("workoutSteps"))
        tipos = [s["stepType"]["stepTypeKey"] for s in grupo["workoutSteps"]]
        self.assertEqual(tipos, ["interval", "rest", "interval", "rest", "interval", "rest"])
        self.assertEqual([s["endConditionValue"] for s in grupo["workoutSteps"] if s["stepType"]["stepTypeKey"] == "rest"],
                         [15.0, 15.0, 120.0])

    def test_un_grupo_de_un_ejercicio_usa_su_descanso_entre_series(self):
        bloque = StrengthBlock(groups=[StrengthGroup(exercises=[_ej("Sentadilla", 4, "6", "SQUAT", descanso=150)])])
        grupo = next(p for p in _pasos(_dia(bloque)) if p.get("workoutSteps"))
        self.assertEqual(grupo["numberOfIterations"], 4)
        self.assertEqual(grupo["workoutSteps"][-1]["endConditionValue"], 150.0)

    def test_los_pasos_quedan_en_orden_global(self):
        orden = []

        def recorrer(pasos):
            for p in pasos:
                orden.append(p["stepOrder"])
                recorrer(p.get("workoutSteps") or [])
        recorrer(_pasos(_dia(_upper_a())))
        self.assertEqual(orden, list(range(1, len(orden) + 1)))


class UnaSolaSerieNoEsUnaRepeticion(unittest.TestCase):
    def test_la_cuarta_serie_de_press_y_elevaciones_va_suelta(self):
        pasos = _pasos(_dia(_upper_a()))
        grupos = [p for p in pasos if p.get("workoutSteps")]
        self.assertEqual(len(grupos), 1)
        sueltos = [p.get("exerciseName") for p in pasos if not p.get("workoutSteps") and p.get("category")]
        self.assertEqual(sueltos, ["BARBELL_BENCH_PRESS", "DUMBBELL_LATERAL_RAISE"])

    def test_un_plan_de_siempre_con_una_serie_ya_no_tumba_la_subida(self):
        bloque = StrengthBlock(exercises=[_ej("Farmer carry", 1, "40 m", "CARRY", "FARMERS_CARRY", descanso=60)])
        pasos = _pasos(_dia(bloque))
        self.assertEqual([p["stepType"]["stepTypeKey"] for p in pasos], ["interval", "rest"])


class ElTopeDeGarmin(unittest.TestCase):
    def test_por_encima_de_50_pasos_va_el_bloque_de_tiempo(self):
        """30 ejercicios seguidos con descanso entre ellos: 1 + 30 + 29 + 1 = 61 pasos."""
        muchos = StrengthBlock(groups=[
            StrengthGroup(rest_between_rounds_s=60, rest_between_exercises_s=10,
                          exercises=[_ej(f"Ejercicio {i}", 3, "10") for i in range(30)])])
        pasos = _pasos(_dia(muchos))
        self.assertEqual(len(pasos), 1)
        self.assertEqual(pasos[0]["endConditionValue"], 7200)
        self.assertEqual(MAX_PASOS_DE_UN_ENTRENO, 50)


class LosNombresDeGimnasioVerificados(unittest.TestCase):
    """Entreno 1701096031: 20 de 20 volvieron con nombre y categoría."""

    def test_categorias_nuevas(self):
        for categoria in ("LATERAL_RAISE", "CRUNCH", "FLYE", "LEG_CURL", "HIP_STABILITY"):
            with self.subTest(categoria=categoria):
                self.assertEqual(vocab.normalise_category(categoria), categoria)

    def test_nombres_de_la_rutina_de_fernando(self):
        pares = [("BENCH_PRESS", "INCLINE_DUMBBELL_BENCH_PRESS"), ("ROW", "SEATED_CABLE_ROW"),
                 ("PULL_UP", "LAT_PULLDOWN"), ("CURL", "EZ_BAR_PREACHER_CURL"), ("ROW", "T_BAR_ROW"),
                 ("SHOULDER_PRESS", "MILITARY_PRESS"), ("SQUAT", "LEG_PRESS"), ("DEADLIFT", "ROMANIAN_DEADLIFT"),
                 ("LEG_CURL", "LEG_CURL"), ("FLYE", "DUMBBELL_FLYE"), ("TRICEPS_EXTENSION", "ROPE_PRESSDOWN")]
        for categoria, nombre in pares:
            with self.subTest(nombre=nombre):
                self.assertEqual(vocab.verified_exercise(categoria, nombre), nombre)

    def test_los_de_antes_siguen(self):
        self.assertEqual(vocab.verified_exercise("SQUAT", "GOBLET_SQUAT"), "GOBLET_SQUAT")
        self.assertEqual(vocab.verified_exercise("ROW", "BENT_OVER_ROW_WITH_DUMBELL"), "BENT_OVER_ROW_WITH_DUMBELL")

    def test_garmin_no_tiene_extension_de_cuadriceps_de_maquina(self):
        self.assertIsNone(vocab.verified_exercise("SQUAT", "LEG_EXTENSION"))


if __name__ == "__main__":
    unittest.main()
