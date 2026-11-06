"""Lo que encontró la auditoría completa de la app del 2026-09-24 (navegador, José).

Cada prueba lleva lo que se vio en pantalla. Correr con:
python -m unittest tests.test_auditoria_del_24_de_septiembre -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.prescription import render_zones_table  # noqa: E402


class LaTablaDeZonasEnEspanol(unittest.TestCase):
    def test_fuerza_no_sale_en_ingles(self):
        """La cabecera del plan de José decía "FC Bici y Strength": la tabla de etiquetas no
        tenía `strength` y caía al nombre de Garmin con mayúscula."""
        zonas = {"1": [122, 139], "2": [140, 152]}
        tabla = render_zones_table({"hr_zones_by_sport": {"bike": zonas, "strength": zonas}})
        self.assertIn("FC Bici y Fuerza", tabla)
        self.assertNotIn("Strength", tabla)


if __name__ == "__main__":
    unittest.main()


def _dia(fecha, minutos=60, deporte="run", descanso=False):
    return {"date": fecha, "sport": deporte, "discipline": "carrera", "is_rest": descanso,
            "focus": "Rodaje", "total_duration_minutes": 0 if descanso else minutos}


class ElExpedienteSoloCuentaLoVivido(unittest.TestCase):
    """El expediente del atleta 2 cerró su semana del 21 al 27 de septiembre el miércoles 23 (la
    sustituyó por otra que empezaba ese día) con las 7,9 h de la semana ENTERA contra 0,45 h hechas en
    dos días: Analytics decía "2 h de 11,1 h · 18 %"."""

    def test_se_cierra_con_los_dias_antes_del_plan_nuevo(self):
        from services.ai.track_record import lo_vivido

        semana = {"week_number": 2, "days": [_dia(f"2026-09-{d}") for d in range(21, 28)]}
        vivida = lo_vivido(semana, "2026-09-23")
        self.assertEqual([d["date"] for d in vivida["days"]], ["2026-09-21", "2026-09-22"])
        self.assertIs(lo_vivido(semana, "2026-09-28"), semana)   # la normal: se cierra entera
        self.assertIsNone(lo_vivido(semana, "2026-09-21"))       # rehecha desde su primer día

    def test_la_semana_anterior_se_mide_entera_cuando_termina(self):
        """Se genera la siguiente hasta dos días antes: el sábado y el domingo de la que se cierra
        quedaban pendientes para siempre. En la generación siguiente se miden."""
        from datetime import date

        from tests.test_generation_window import _plan
        from web_app.server import semana_a_completar

        plan = _plan([f"2026-09-{d}" for d in range(21, 28)])
        plan.weeks.append(_plan([f"2026-09-{d}" for d in (28, 29, 30)] + ["2026-10-01"]).weeks[0])
        self.assertIsNone(semana_a_completar(plan, date(2026, 9, 27)))   # aún no terminó
        completa = semana_a_completar(plan, date(2026, 10, 3))
        self.assertEqual(completa.weeks[0].days[0].date, "2026-09-21")
        self.assertIsNone(semana_a_completar(plan, date(2026, 10, 3), rehacer=True))

    def test_medirla_otra_vez_actualiza_lo_hecho_y_conserva_la_foto(self):
        import tempfile

        from services.ai.track_record import read_all, record_week

        semana = {"week_number": 2, "days": [_dia(f"2026-09-{d}") for d in range(21, 28)]}
        pendiente = [{"date": f"2026-09-{d}", "status": "pending", "sport": "run"} for d in (26, 27)]
        hecho = [{"date": f"2026-09-{d}", "status": "completed", "sport": "run", "actual_minutes": 60}
                 for d in range(21, 26)]
        with tempfile.TemporaryDirectory() as tmp:
            from pathlib import Path as P
            record_week(P(tmp), semana, {"sessions": hecho + pendiente, "completed": 5, "executed_hours": 5.0},
                        marcadores={"fecha": "2026-09-25"})
            record_week(P(tmp), semana, {"completed": 7, "executed_hours": 7.0, "sessions": hecho + [
                {"date": f"2026-09-{d}", "status": "completed", "sport": "run", "actual_minutes": 60}
                for d in (26, 27)]})
            (linea,) = read_all(P(tmp))
        self.assertEqual((linea["completed_sessions"], linea["executed_hours"]), (7, 7.0))
        self.assertEqual(linea["marcadores"], {"fecha": "2026-09-25"})


class LasFechasYLosNumerosComoLosLeeElAtleta(unittest.TestCase):
    def test_los_prompts_lo_piden(self):
        from services.ai.coach_chat import SYSTEM_PROMPT
        from services.ai.langgraph.nodes.prompt_components import get_language_directive

        for texto in (get_language_directive(), SYSTEM_PROMPT):
            self.assertIn("en formato ISO (2026-10-24)", texto)
            self.assertIn("decimales con coma", texto)

    def test_la_ventana_de_generacion_no_dice_fechas_iso(self):
        from datetime import datetime

        from tests.test_generation_window import _plan
        from web_app.server import ventana_de_generacion

        motivo = ventana_de_generacion(_plan(["2026-09-27"]), datetime(2026, 9, 24, 12))["reason"]
        self.assertIn("lunes 28 de septiembre", motivo)
        self.assertNotRegex(motivo, r"\d{4}-\d{2}-\d{2}")


class BorrarUnaSemanaLaSacaDelExpediente(unittest.TestCase):
    def test_las_no_medidas_van_a_descartadas_y_lo_medido_se_queda(self):
        """Expediente real del atleta 2 el 2026-09-24: 15-20 sep medida (1,51 h hechas) y tres semanas
        de planes borrados abiertas y sin medir (23-sep, 28-sep, 5-oct)."""
        import json
        import tempfile
        from pathlib import Path as P

        from services.ai.track_record import DESCARTADAS_FILE, descartar_las_no_vividas, read_all

        lineas = [
            {"from": "2026-09-15", "to": "2026-09-20", "prescribed_hours": 3.17, "executed_hours": 1.51,
             "completed_sessions": 2},
            {"from": "2026-09-23", "to": "2026-09-27", "prescribed_hours": 6.5, "executed_hours": None},
            {"from": "2026-09-28", "to": "2026-10-04", "prescribed_hours": 11.95, "executed_hours": None},
            {"from": "2026-10-05", "to": "2026-10-11", "prescribed_hours": 10.62, "executed_hours": None},
        ]
        with tempfile.TemporaryDirectory() as tmp:
            (P(tmp) / "track_record.jsonl").write_text("".join(json.dumps(x) + "\n" for x in lineas), "utf-8")
            self.assertEqual(descartar_las_no_vividas(P(tmp), "2026-09-24"), 3)
            self.assertEqual([x["from"] for x in read_all(P(tmp))], ["2026-09-15"])
            descartadas = (P(tmp) / DESCARTADAS_FILE).read_text("utf-8").splitlines()
            self.assertEqual(len(descartadas), 3)
            self.assertEqual(json.loads(descartadas[0])["descartada"], "2026-09-24")
            self.assertEqual(descartar_las_no_vividas(P(tmp), "2026-09-24"), 0)


class ElDiaYLaFechaCuadran(unittest.TestCase):
    def test_el_racional_de_la_primera_semana(self):
        """Semana del atleta 2 generada el 2026-09-24 (bloque del viernes 25 al domingo 27): el racional
        decía "este bloque es corto (viernes 26/09 a domingo 27/09)". Se avisa en el log; no se corrige."""
        from datetime import date

        from services.ai.number_audit import dias_mal_nombrados

        texto = ("Este bloque es corto (viernes 26/09 a domingo 27/09), y viene 5 días después de tu 5K "
                 "del 20/09. Especificidad al XCM del sábado 24/10 y al 10K del martes 20/04.")
        self.assertEqual(dias_mal_nombrados(texto, date(2026, 9, 25)),
                         ["«viernes 26/09» (26/9 es sábado)"])


class LasFechasLlegaEscritasYNoSeCalculan(unittest.TestCase):
    """La causa del "viernes 26/09": el modelo recibía "2026-09-25" y el día aparte, y la directiva le pedía
    escribirla como "viernes 25/09": la conversión la hacía él, y se equivocó. Ahora la hace el código."""

    def test_cada_fecha_trae_su_forma_escrita(self):
        from services.ai.number_audit import con_fecha_escrita, fecha_escrita

        self.assertEqual(fecha_escrita("2026-09-25"), "viernes 25/09")
        self.assertEqual(fecha_escrita("2027-04-20"), "martes 20/04")
        self.assertEqual(con_fecha_escrita([{"date": "2026-10-24", "name": "Gravel"}, "x"]),
                         [{"date": "2026-10-24", "name": "Gravel", "se_escribe": "sábado 24/10"}, "x"])

    def test_la_directiva_prohibe_calcular_el_dia(self):
        from services.ai.coach_chat import SYSTEM_PROMPT
        from services.ai.langgraph.nodes.prompt_components import get_language_directive

        self.assertIn("Nunca calcules qué día de la semana es una fecha", get_language_directive())
        self.assertIn("`se_escribe`", get_language_directive())
        self.assertIn("No calcules qué día de la semana es una fecha", SYSTEM_PROMPT)

    def test_un_dia_mal_vuelve_a_la_decision(self):
        from types import SimpleNamespace

        from services.ai.langgraph.nodes.weekly_planner_node import (
            _dias_mal_en_la_decision,
            mensaje_de_correccion,
        )

        decision = SimpleNamespace(
            week_rationale="Este bloque es corto (viernes 26/09 a domingo 27/09).",
            days=[SimpleNamespace(purpose="", adaptation="", session_sketch="")])
        mal = _dias_mal_en_la_decision(decision, [{"date": "2026-09-25"}])
        self.assertEqual(mal, ["«viernes 26/09» (26/9 es sábado)"])
        texto = mensaje_de_correccion([], "{}", None, "", mal)
        self.assertIn("«viernes 26/09» (26/9 es sábado)", texto)
        self.assertIn("se_escribe", texto)


class ElObjetivoDeFuerzaVigenteManda(unittest.TestCase):
    """Jessie cambió su fuerza de "ganar músculo" con barras a "rendir más en su deporte" con mancuernas
    (2026-09-24). Su programa nuevo salió bien, pero su semana sólo leía el macrociclo viejo ("tu objetivo es
    ganar músculo, y ya dominas levantamientos con barra") y el paso de fuerza recibía los dos objetivos sin
    decirle cuál mandaba."""

    def test_la_decision_recibe_lo_que_declara_hoy(self):
        from types import SimpleNamespace

        from services.ai.langgraph.nodes import paso_de_fuerza as pf

        rutina = SimpleNamespace(origen="coach", confirmada=True, sesiones=[
            {"id": "fb1", "name": "Full Body 1", "minutes": 45, "groups": [{"exercises": [{"name": "Goblet"}]}]}])
        perfil = SimpleNamespace(objetivo="rendimiento", equipo="mancuernas_y_bandas")
        texto = pf.resumen_de_la_rutina(rutina, perfil)
        self.assertIn("**rendir más en su deporte**", texto)
        self.assertIn("mancuernas y bandas", texto)
        self.assertIn("manda esto", texto)
        self.assertNotIn("Lo que declara HOY", pf.resumen_de_la_rutina(rutina, None))

    def test_el_paso_de_fuerza_sabe_cual_manda(self):
        from services.ai.langgraph.nodes.paso_de_fuerza import AJUSTE_USER_PROMPT

        self.assertIn("si la fase del macrociclo dice otro objetivo, manda éste", AJUSTE_USER_PROMPT)
