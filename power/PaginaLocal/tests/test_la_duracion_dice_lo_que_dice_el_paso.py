"""La duración que va al reloj dice lo que dice el texto del paso.

Semana de la atleta 3 del 2026-09-14 (traza `reasoning/2026-09-14T12-53-47`),
a seis días de su 5K, validador con 0 violaciones:

- "15-20\" progresivos, acelerando suave" codificado con 90 s, el miércoles y
  en el calentamiento de la carrera.
- "200m trote muy suave Z1" codificado como time=200: los metros en el campo de
  los segundos.

La semana del atleta 2, diez minutos antes, codificó los mismos pasos con 20 s
y 120 s. Los casos "no se toca" salen de esas dos semanas reales.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import _duracion_como_dice_el_paso  # noqa: E402


def _paso(descripcion, valor, tipo="time", step_type="interval"):
    return WorkoutStep(step_order=1, step_type=step_type, duration_type=tipo, duration_value=valor,
                       description=descripcion)


def _semana(pasos, deporte="run"):
    dia = WorkoutDay(date="2026-09-16", day_name_es="Miércoles", is_rest=False, sport=deporte, focus="5K pace",
                     purpose="p", adaptation="a", intensity_class="repeticiones", discipline="carrera",
                     total_duration_minutes=45.0, steps=pasos)
    return WeeklyPlanPayload(athlete_name="Jessie", start_date="2026-09-14", zones_table="",
                             weeks=[WeekBlock(week_number=1, week_rationale="", days=[dia])])


def _corregir(*pasos, deporte="run"):
    plan, cambios = _duracion_como_dice_el_paso(_semana(list(pasos), deporte))
    return plan.weeks[0].days[0].steps, cambios


class LosDosFallosReales(unittest.TestCase):
    def test_progresivos_de_15_20_segundos_no_son_de_90(self):
        pasos, cambios = _corregir(_paso("15-20\" progresivos, acelerando suave", 90, step_type="warmup"))
        self.assertEqual(pasos[0].duration_value, 18.0)
        self.assertEqual(len(cambios), 1)

    def test_los_metros_copiados_como_segundos_vuelven_a_ser_metros(self):
        pasos, _ = _corregir(_paso("200m trote muy suave Z1", 200, step_type="recovery"))
        self.assertEqual((pasos[0].duration_type, pasos[0].duration_value), ("distance", 200.0))


class LoQueEstabaBienNoSeToca(unittest.TestCase):
    def test_pasos_bien_codificados_de_las_dos_semanas(self):
        casos = [
            ("17-18' calentamiento muy suave en Z1", 1050),
            ("3' drills: A-skip, ankling, butt kicks", 180),
            ("25' rodaje muy fácil en Z1", 1500),
            ("45\" muy suave entre cada progresivo", 45),
            ("1'20-2' muy suave entre cada stride", 100),
            ("20\" progresivos: entra suave, termina más rápido, 60\" suave", 20),
            ("20\" cadencia alta en ligero, 40\" suave", 20),
            ("6x20\" strides en llano, rápido pero relajado sin forzar", 20),
            ("Fuerza de mantenimiento: 30 min", 1800),
            ("2–3' suave para cerrar, deja que el pulso baje", 150),
        ]
        for texto, valor in casos:
            with self.subTest(texto=texto):
                pasos, cambios = _corregir(_paso(texto, valor))
                self.assertEqual(cambios, [])
                self.assertEqual(pasos[0].duration_value, valor)

    def test_una_aproximacion_del_entrenador_para_una_distancia_se_respeta(self):
        """Atleta 2: "200 m trote muy suave" con 120 s. No es una copia de
        unidades, es cuánto tarda en trotar 200 m."""
        pasos, cambios = _corregir(_paso("200 m trote muy suave", 120, step_type="recovery"))
        self.assertEqual(cambios, [])
        self.assertEqual(pasos[0].duration_type, "time")

    def test_un_ritmo_no_se_lee_como_un_tiempo(self):
        pasos, cambios = _corregir(_paso("400 m a 5:05–5:15/km, sensación 5K controlada", 400, tipo="distance"))
        self.assertEqual(cambios, [])
        pasos, cambios = _corregir(_paso("2' a 5:10-5:16/km", 120))
        self.assertEqual(cambios, [])

    def test_dentro_de_un_bloque_de_repeticion_tambien(self):
        bloque = WorkoutStep(step_order=3, step_type="repeat", repeat_count=3, description="3x progresivos",
                             substeps=[_paso("15-20\" progresivos, acelerando suave", 90, step_type="warmup"),
                                       _paso("45\" muy suave entre cada progresivo", 45, step_type="recovery")])
        pasos, cambios = _corregir(bloque)
        self.assertEqual([s.duration_value for s in pasos[0].substeps], [18.0, 45])
        self.assertEqual(len(cambios), 1)

    def test_las_horas_son_horas(self):
        """Prueba local del 2026-10-09 (atleta 2, trail de prueba de 15 km el sábado 10/10): el paso
        de la carrera decía "~1 h 50 min a tope" con 6.600 s, y la red sólo leía "50 min" y lo
        "corrigió" a 3.000 s. En el reloj la carrera se acababa a los 50'."""
        pasos, cambios = _corregir(_paso(
            "Trail de prueba (local): 15 km, ~600 m D+, ~1 h 50 min a tope, gestionando por esfuerzo y terreno",
            6600, step_type="main"))
        self.assertEqual((pasos[0].duration_value, cambios), (6600, []))
        for texto, segundos in (("2 h en Z2", 7200), ("1h50 suave", 6600), ("1,5 h rodaje", 5400),
                                ("2 horas y 15 min", 8100), ("1 h 50' de carrera", 6600)):
            with self.subTest(texto=texto):
                self.assertEqual(_corregir(_paso(texto, segundos))[1], [])
        # Y lo que sí estaba mal en horas se arregla en horas.
        pasos, _ = _corregir(_paso("2 h en Z2", 3000))
        self.assertEqual(pasos[0].duration_value, 7200.0)

    def test_la_fuerza_no_pasa_por_aqui(self):
        pasos, cambios = _corregir(_paso("Activación: 20 min", 900), deporte="strength")
        self.assertEqual(cambios, [])


if __name__ == "__main__":
    unittest.main()
