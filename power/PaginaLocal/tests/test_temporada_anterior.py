"""El macrociclo nuevo tiene que saber del anterior.

## Lo que faltaba

El planificador de temporada recibía la ADHERENCIA -- qué se le prescribió al
atleta y qué cumplió, semana a semana -- pero no el plan del que salían esas
prescripciones. Auditado sobre el prompt real de una generación buena, 34.948
caracteres: ni el nombre de una fase anterior, ni sus horas objetivo, ni una
línea de su narrativa.

Con eso, el macrociclo número dos podía:

  - devolver a "Base Aeróbica, semanas 1-4" a un atleta que iba por la semana
    20 de un bloque de construcción;
  - contradecir al anterior sin decirlo, dejando al atleta sin saber cuál de
    los dos creer;
  - repetir una recomendación que el atleta ya había hecho, o tirar una que
    seguía vigente.

## Lo que NO se manda, y por qué está probado

La narrativa entera son ~20.000 caracteres que ya resumen lo que las fases
dicen en cuatro columnas. Se manda la ESTRUCTURA más un extracto corto. Un
bloque que se comiera medio prompt desplazaría los expertos y la prescripción,
que es de donde salen los números.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.previous_season import (  # noqa: E402
    NARRATIVA_MAX,
    render_previous_season,
    semana_alcanzada,
)

FASES = {
    "season_start_date": "2026-09-01",
    "phases": [
        {"name": "Arranque + test 5K", "start_week": 1, "end_week": 4,
         "weekly_hours_target": 5, "intensity_emphasis": "frescura", "deload_weeks": [4]},
        {"name": "Base 10K I", "start_week": 5, "end_week": 8,
         "weekly_hours_target": 6, "intensity_emphasis": "Z1-Z2", "deload_weeks": [8]},
        {"name": "Build 10K II", "start_week": 17, "end_week": 20,
         "weekly_hours_target": 9, "intensity_emphasis": "umbral", "deload_weeks": [20]},
    ],
    "constraints": [
        {"id": "sin-brick", "kind": "logistica",
         "statement": "No prescribir sesiones brick ni encadenar bici+carrera sin pausa."},
        {"id": "hombro", "kind": "medica",
         "statement": "Limitar palas si hay molestia en hombro."},
    ],
}
NARRATIVA = "## Resumen\nEste macrociclo prioriza carrera 5K/10K.\n" + ("relleno. " * 400)


class ElPlanNuevoSabeDondeIbaElAtleta(unittest.TestCase):
    def test_EL_FALLO_el_bloque_existe_y_lleva_las_fases_anteriores(self):
        bloque = render_previous_season(NARRATIVA, FASES, date(2027, 1, 15))
        self.assertIn("Macrociclo ANTERIOR", bloque)
        for nombre in ("Arranque + test 5K", "Base 10K I", "Build 10K II"):
            self.assertIn(nombre, bloque)

    def test_senala_en_que_fase_estaba_el_atleta(self):
        """Es lo que impide reiniciar una progresión que ya vivió."""
        bloque = render_previous_season(NARRATIVA, FASES, date(2027, 1, 15))
        self.assertIn("semana 20 de 20", bloque)
        marca = next(l for l in bloque.splitlines() if "aquí estaba" in l)
        self.assertIn("Build 10K II", marca)

    def test_dice_cuando_el_macrociclo_ya_se_habia_acabado(self):
        bloque = render_previous_season(NARRATIVA, FASES, date(2027, 3, 1))
        self.assertIn("terminado", bloque)
        self.assertIn("sin fase ni objetivo de horas", bloque)

    def test_lleva_las_restricciones_con_su_texto(self):
        """El campo es `statement`. Con `text` o `description` la cabecera
        salía puesta y las once restricciones invisibles debajo, que es peor
        que no ponerla."""
        bloque = render_previous_season(NARRATIVA, FASES, date(2026, 10, 1))
        self.assertIn("No prescribir sesiones brick", bloque)
        self.assertIn("Limitar palas", bloque)

    def test_dice_que_es_contexto_y_no_una_orden(self):
        """El atleta pudo cambiar de objetivo o lesionarse, y eso pesa más que
        la continuidad. Sin esta frase el modelo puede leer el bloque como una
        plantilla a copiar."""
        bloque = render_previous_season(NARRATIVA, FASES, date(2026, 10, 1))
        self.assertIn("no una restricción", bloque)
        self.assertIn("no reiniciar una progresión", bloque)

    def test_exige_explicar_cuando_se_aparta_del_anterior(self):
        bloque = render_previous_season(NARRATIVA, FASES, date(2026, 10, 1))
        self.assertIn("dilo en la narrativa y explica por qué", bloque)


class NoSeComeElPrompt(unittest.TestCase):
    def test_la_narrativa_se_recorta_y_se_dice(self):
        bloque = render_previous_season(NARRATIVA, FASES, date(2026, 10, 1))
        self.assertIn("resto de la narrativa anterior omitido", bloque)
        self.assertLess(len(bloque), NARRATIVA_MAX + 4000)

    def test_una_narrativa_corta_entra_entera_sin_aviso_de_recorte(self):
        bloque = render_previous_season("## Resumen\nPlan corto.", FASES, date(2026, 10, 1))
        self.assertIn("Plan corto.", bloque)
        self.assertNotIn("omitido", bloque)


class SinPlanAnteriorNoSeInventaNada(unittest.TestCase):
    def test_la_primera_temporada_no_produce_bloque(self):
        """Un bloque que dijera "no hay plan anterior" gasta tokens para decir
        nada y le da al modelo una ausencia sobre la que razonar."""
        self.assertEqual(render_previous_season(None, None), "")
        self.assertEqual(render_previous_season("", {}), "")
        self.assertEqual(render_previous_season("  ", {"phases": []}), "")

    def test_con_fases_pero_sin_narrativa_sigue_habiendo_bloque(self):
        bloque = render_previous_season(None, FASES, date(2026, 10, 1))
        self.assertIn("Base 10K I", bloque)

    def test_sin_fecha_de_arranque_no_se_inventa_una_semana(self):
        sin_fecha = {k: v for k, v in FASES.items() if k != "season_start_date"}
        bloque = render_previous_season(None, sin_fecha, date(2026, 10, 1))
        self.assertNotIn("llegó a la", bloque)
        self.assertIn("Base 10K I", bloque)


class LaSemanaSeCuentaIgualQueEnElPrompt(unittest.TestCase):
    """Misma fórmula que `compute_mesocycle_position`: si divergieran, el
    bloque diría una semana y el planificador semanal otra."""

    def test_el_dia_del_arranque_es_la_semana_1(self):
        self.assertEqual(semana_alcanzada("2026-09-01", date(2026, 9, 1)), 1)
        self.assertEqual(semana_alcanzada("2026-09-01", date(2026, 9, 7)), 1)
        self.assertEqual(semana_alcanzada("2026-09-01", date(2026, 9, 8)), 2)

    def test_antes_del_arranque_no_da_semanas_negativas(self):
        self.assertEqual(semana_alcanzada("2026-09-01", date(2026, 8, 20)), 1)

    def test_una_fecha_ilegible_devuelve_nada(self):
        self.assertIsNone(semana_alcanzada("ayer"))
        self.assertIsNone(semana_alcanzada(None))


if __name__ == "__main__":
    unittest.main()


class AlRefinarNoSeRepite(unittest.TestCase):
    def test_el_plan_vigente_no_va_dos_veces(self):
        """Macrociclo del atleta 2 del 2026-09-23 (semana 1 de 31, refinando): el plan vigente iba
        como "Existing Season Plan -- conserva sus fases" y otra vez como "Macrociclo ANTERIOR (el que
        este plan reemplaza) -- es contexto, apártate si hace falta". Dos órdenes contrarias y ~10.000
        caracteres pagados dos veces. Al refinar sólo va el primero (auditoría del 2026-09-24)."""
        fuente = (Path(__file__).resolve().parents[1]
                  / "services/ai/langgraph/nodes/season_planner_node.py").read_text(encoding="utf-8")
        self.assertIn('previous_season="" if refinando else _format_previous_season(state)', fuente)


if __name__ == "__main__":
    unittest.main()
