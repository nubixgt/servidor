"""Lo que manda es lo que el atleta ha hecho y cómo respondió; Garmin opina.

## El planteamiento, del propio atleta

Su barra de Garmin dice que le falta trabajo aeróbico alto y anaeróbico. Pero
Garmin reparte la carga según la FC máxima y las zonas del reloj, que a menudo
están mal puestas, y un atleta puede no tolerar más intensidad aunque la barra
diga que le falta. Un entrenador de verdad no decide por la barra: mira cómo
entrenó y cómo reaccionó.

Hasta aquí el pipeline hacía lo contrario en tres sitios, y los tres los metió
la primera versión de la v1.2:

- el macrociclo tenía escrito "si le falta aeróbico alto y le sobra bajo, una
  fase que añada horas suaves es la fase equivocada" -- una etiqueta de Garmin
  convertida en orden;
- la semana recibía "Techo: **220**" y "planifica en este orden: 1. las
  sesiones clave según lo que el Load Focus dice que falta";
- el validador acortaba sesiones cuando la carga estimada pasaba ese techo.

Y el cruce que un entrenador hace -- esta sesión dura, estas mañanas después --
no lo hacía nadie: los expertos están partidos por dominio y ninguno ve las dos
cosas a la vez.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date, timedelta
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.season_planner_node import SEASON_PLANNER_USER_PROMPT  # noqa: E402
from services.ai.langgraph.nodes.weekly_planner_node import TRAINING_DECISION_USER_PROMPT  # noqa: E402
from services.ai.load_state import LoadState, render_load_state  # noqa: E402
from services.ai.training_response import build_training_response  # noqa: E402

HOY = date(2026, 9, 11)


def _dia(n, hrv, rhr, sueno=80):
    return SimpleNamespace(
        date=(HOY - timedelta(days=n)).isoformat(),
        sleep={"avg_overnight_hrv": hrv, "resting_heart_rate": rhr, "quality": {"overall_score": sueno}},
        stress={},
    )


def _act(n, nombre, minutos, carga, vig):
    return SimpleNamespace(
        start_time=f"{(HOY - timedelta(days=n)).isoformat()} 17:00:00", activity_type="running",
        activity_name=nombre,
        summary=SimpleNamespace(duration=minutos * 60, activity_training_load=carga,
                                vigorous_intensity_minutes=vig),
    )


def _garmin(dias, actividades):
    return SimpleNamespace(recovery_indicators=dias, recent_activities=actividades)


# Catorce mañanas con HRV ~60 y pulso ~50, salvo las dos que siguen a las series
# de hace 2 días: ahí el HRV cae y el pulso sube. Es el patrón que un
# entrenador busca, y lo que el bloque tiene que poner delante.
MANANAS = [_dia(n, 60, 50) for n in range(3, 15)] + [_dia(1, 48, 55, sueno=62), _dia(0, 53, 53)]
SESIONES = [
    _act(2, "Series 8x2'", 56, 91, vig=25),
    _act(5, "Rodaje", 40, 25, vig=1),
    _act(8, "Rodaje", 45, 28, vig=1),
    _act(10, "Rodaje", 40, 24, vig=0),
]


class ElCruceQueNoHaciaNadie(unittest.TestCase):
    def setUp(self):
        self.bloque = build_training_response(_garmin(MANANAS, SESIONES), HOY)

    def test_pone_la_sesion_dura_al_lado_de_las_mananas_siguientes(self):
        self.assertIn("Series 8x2'", self.bloque)
        self.assertIn("HRV 48 (-20%)", self.bloque)
        self.assertIn("pulso en reposo 55 (+5)", self.bloque)

    def test_compara_contra_la_referencia_PROPIA_del_atleta(self):
        self.assertIn("HRV mediana 60 ms", self.bloque)

    def test_no_saca_conclusiones_por_el_entrenador(self):
        """Un HRV bajo tiene muchas causas; leerlo es del entrenador."""
        self.assertIn("No es un diagnóstico", self.bloque)
        for veredicto in ("le sentó mal", "no tolera", "sobreentren"):
            with self.subTest(veredicto=veredicto):
                self.assertNotIn(veredicto, self.bloque)

    def test_los_rodajes_suaves_no_cuentan_como_exigentes(self):
        self.assertNotIn("Rodaje", self.bloque)

    def test_dos_sesiones_del_mismo_dia_son_una_linea(self):
        """La traza real del 2026-09-11 repetía las mismas mañanas para la
        natación y las series del 9-sep, como si fueran dos respuestas."""
        sesiones = SESIONES + [_act(2, "Natación", 35, 34, vig=20)]
        bloque = build_training_response(_garmin(MANANAS, sesiones), HOY)
        linea = next(x for x in bloque.splitlines() if "Series 8x2'" in x)
        self.assertIn("Natación", linea)
        self.assertEqual(bloque.count("HRV 48 (-20%)"), 1)

    def test_sin_serie_diaria_se_abstiene(self):
        self.assertEqual(build_training_response(_garmin([], SESIONES), HOY), "")

    def test_sin_sesiones_exigentes_se_abstiene(self):
        self.assertEqual(build_training_response(_garmin(MANANAS, []), HOY), "")


class GarminEsUnaSegundaOpinion(unittest.TestCase):
    def test_el_reparto_de_carga_lleva_su_advertencia(self):
        estado = LoadState(load_balance={"aerobic_high": {"load": 227, "target_min": 275,
                                                          "target_max": 538, "status": "below"}},
                           load_balance_feedback="AEROBIC_HIGH_SHORTAGE")
        bloque = render_load_state(estado)
        self.assertIn("mal configuradas", bloque)
        self.assertIn("nunca decide por sí sola", bloque)

    def test_la_semana_pone_la_historia_por_delante(self):
        self.assertIn("what this athlete has actually done and how their body responded",
                      TRAINING_DECISION_USER_PROMPT)
        self.assertIn("the history wins", TRAINING_DECISION_USER_PROMPT)
        self.assertIn("second opinion", TRAINING_DECISION_USER_PROMPT)

    def test_la_semana_ya_no_ordena_partir_del_load_focus(self):
        self.assertNotIn("from what Garmin's Load Focus says", TRAINING_DECISION_USER_PROMPT)

    def test_el_macrociclo_ya_no_convierte_la_etiqueta_en_orden(self):
        self.assertNotIn("is the wrong phase", SEASON_PLANNER_USER_PROMPT)
        self.assertIn("follow the history", SEASON_PLANNER_USER_PROMPT)


if __name__ == "__main__":
    unittest.main()
