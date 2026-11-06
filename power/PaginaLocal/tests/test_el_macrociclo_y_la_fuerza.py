"""Beta 2.1, paso S7: el macrociclo sabe PARA QUÉ hace gimnasio el atleta.

Hasta aquí el planificador de temporada escribía "fuerza máxima" en Base sin saber si
el atleta tenía una barra, si buscaba hipertrofia o si sólo quería no lesionarse -- y
las ocho semanas archivadas de José y Jessie salieron con mancuernas y bandas pese a
esa frase. S7 le pasa lo que el atleta DECLARÓ, como una petición suya, y le deja
escribir qué busca la fuerza en cada fase (`SeasonPhase.strength_quality`).

Lo que fija:

- sin perfil de fuerza el prompt del macrociclo no cambia ni un carácter;
- lo que llega es lo que él dijo, con sus palabras, y dice lo que NO declaró;
- `strength_quality` es opcional: los macrociclos ya guardados siguen validando, y
  quien no contestó no recibe ninguna palabra sobre gimnasio en su plan;
- no se convierte en un número de sesiones: eso lo decide la semana con su tabla.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import SeasonPhase, SeasonPlanContent  # noqa: E402
from services.ai.perfil_de_fuerza import (  # noqa: E402
    leer_perfil_de_fuerza,
    leer_rutina_de_fuerza,
    render_para_el_macrociclo,
)

NODO = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
SEMANAL = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

CAMPOS = ("athlete_name", "current_date", "inicio_del_macrociclo", "competitions", "planning_context", "prescription_card",
          "volume_baseline", "load_state", "adherence", "goal_realism", "previous_season", "track_record",
          "phase_response", "training_habits", "coach_notebook", "metrics_insights", "activity_insights",
          "physiology_insights", "respuestas_del_coach")


def _perfil(**datos):
    return leer_perfil_de_fuerza({"strength_profile": {"mode": "coach", **datos}})


class SinPerfilDeFuerzaNoCambiaNada(unittest.TestCase):
    def test_el_bloque_es_vacio(self):
        self.assertEqual(render_para_el_macrociclo(None, None), "")
        self.assertEqual(render_para_el_macrociclo(leer_perfil_de_fuerza({}), None), "")

    def test_el_prompt_del_macrociclo_es_el_de_siempre(self):
        campos = dict.fromkeys(CAMPOS, "")
        campos = {k: v for k, v in campos.items() if "{" + k + "}" in NODO.SEASON_PLANNER_USER_PROMPT}
        sin = NODO.SEASON_PLANNER_USER_PROMPT.format(fuerza_declarada="", **campos)
        con = NODO.SEASON_PLANNER_USER_PROMPT.format(fuerza_declarada="\nX\n", **campos)
        self.assertNotIn("gimnasio", sin.lower())
        self.assertEqual(len(con) - len(sin), len("\nX\n"))


    def test_EL_FALLO_ni_una_linea_en_blanco_de_mas(self):
        """Auditoría del 2026-09-17: el hueco del bloque estaba en su propia línea, así
        que un atleta SIN fuerza declarada recibía un salto de línea de más que el
        prompt de siempre. Un carácter, pero la garantía era "idéntico"."""
        campos = dict.fromkeys(CAMPOS, "")
        campos = {k: v for k, v in campos.items() if "{" + k + "}" in NODO.SEASON_PLANNER_USER_PROMPT}
        sin = NODO.SEASON_PLANNER_USER_PROMPT.format(fuerza_declarada="", **campos)
        self.assertIn("```" + "\n"*2 + "## The athlete's real numbers", sin)
        self.assertNotIn("\n"*3 + "## The athlete's real numbers", sin)

    def test_el_bloque_se_pega_sin_romper_el_encabezado(self):
        campos = dict.fromkeys(CAMPOS, "")
        campos = {k: v for k, v in campos.items() if "{" + k + "}" in NODO.SEASON_PLANNER_USER_PROMPT}
        con = NODO.SEASON_PLANNER_USER_PROMPT.format(
            fuerza_declarada=render_para_el_macrociclo(_perfil(goal="hipertrofia"), None), **campos)
        self.assertIn("### Su fuerza, como la pidió", con)
        self.assertIn("\n" + "## The athlete's real numbers", con)


class LoQueLlegaEsLoQueDijoEl(unittest.TestCase):
    def test_su_objetivo_con_sus_palabras(self):
        bloque = render_para_el_macrociclo(_perfil(goal="hipertrofia"), None)
        self.assertIn("ganar músculo", bloque)
        self.assertIn("petición suya", bloque)

    def test_dice_lo_que_no_declaro(self):
        self.assertIn("No declaró ningún levantamiento con barra",
                      render_para_el_macrociclo(_perfil(goal="prevencion"), None))

    def test_un_programa_confirmado_se_menciona_con_su_origen(self):
        rutina = leer_rutina_de_fuerza({"strength_routine": {
            "source": "atleta", "confirmed_at": "2026-09-17T18:00:00Z", "original_text": "Upper A",
            "sessions": [{"id": "upper_a"}, {"id": "lower_a"}]}})
        bloque = render_para_el_macrociclo(_perfil(goal="hipertrofia"), rutina)
        self.assertIn("2 sesión(es)", bloque)
        self.assertIn("suya", bloque)

    def test_una_rutina_sin_confirmar_no_cuenta(self):
        rutina = leer_rutina_de_fuerza({"strength_routine": {"original_text": "Upper A", "sessions": []}})
        self.assertNotIn("programa de gimnasio confirmado",
                         render_para_el_macrociclo(_perfil(goal="hipertrofia"), rutina))

    def test_no_le_pone_un_numero_de_sesiones(self):
        bloque = render_para_el_macrociclo(_perfil(goal="rendimiento", equipment="gimnasio"), None)
        for prohibido in ("2 sesiones a la semana", "3 sesiones", "veces por semana"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, bloque)


class LaFaseDiceQueBuscaLaFuerza(unittest.TestCase):
    def _fase(self, **extra):
        datos = {"name": "Base", "start_week": 1, "end_week": 4,
                 "intensity_emphasis": "Aeróbico, con toques de calidad corta."}
        datos.update(extra)
        return SeasonPhase(**datos)

    def test_es_opcional_y_un_macrociclo_guardado_sigue_validando(self):
        """Los planes que ya están en la base se escribieron sin este campo."""
        self.assertIsNone(self._fase().strength_quality)
        guardado = {
            "season_start_date": "2026-01-05",
            "phases": [{"name": "Base", "start_week": 1, "end_week": 4,
                        "intensity_emphasis": "Aeróbico"}],
            "markdown": "# Plan",
        }
        plan = SeasonPlanContent.model_validate(guardado)
        self.assertIsNone(plan.phases[0].strength_quality)

    def test_cuando_lo_escribe_viaja_con_la_fase(self):
        fase = self._fase(strength_quality="Fuerza máxima con la sentadilla que ya domina")
        self.assertIn("sentadilla", SeasonPlanContent(
            season_start_date="2026-01-05", phases=[fase], markdown="# Plan",
        ).model_dump_json())


class LoQueLaFaseDiceLlegaALaSemana(unittest.TestCase):
    """Sin esto, `strength_quality` sería un campo que nadie lee: el fallo que más se
    repite en este repo."""

    FASES = {"season_start_date": "2026-09-14", "phases": [
        {"name": "Base", "start_week": 1, "end_week": 4, "intensity_emphasis": "Aeróbico",
         "strength_quality": "Fuerza máxima con la sentadilla que ya domina"}]}

    def test_la_posicion_de_la_semana_la_trae(self):
        posicion = SEMANAL.compute_mesocycle_position(self.FASES, "2026-09-21")
        self.assertEqual(posicion["strength_quality"], "Fuerza máxima con la sentadilla que ya domina")
        self.assertIn("La fuerza en esta fase busca: Fuerza máxima",
                      SEMANAL._format_mesocycle_position(posicion))

    def test_un_macrociclo_sin_ese_campo_no_dice_nada_de_fuerza(self):
        sin = {"season_start_date": "2026-09-14", "phases": [
            {"name": "Base", "start_week": 1, "end_week": 4, "intensity_emphasis": "Aeróbico"}]}
        posicion = SEMANAL.compute_mesocycle_position(sin, "2026-09-21")
        self.assertIsNone(posicion["strength_quality"])
        self.assertNotIn("fuerza", SEMANAL._format_mesocycle_position(posicion).lower())


if __name__ == "__main__":
    unittest.main()
