"""Las semanas del macrociclo son las semanas del atleta.

Atleta 2, Beta 2.0 publicada, 2026-09-21: macrociclo generado el martes
2026-09-15, su semana empieza el lunes. El lunes 21 empezó su segunda semana y
la pantalla seguía en "Semana 1 de 31" -- y la decisión semanal, con la misma
cuenta (`compute_mesocycle_position`), también creía estar en la 1. Ver
services/ai/semanas_del_macrociclo.py.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / "PaginaLocal"))
sys.path.insert(0, str(RAIZ / "AiService"))

from services.ai.langgraph.nodes.weekly_planner_node import compute_mesocycle_position  # noqa: E402
from services.ai.semanas_del_macrociclo import (  # noqa: E402
    DIAS,
    arranque_de_sus_semanas,
    con_arranque_de_sus_semanas,
)

# Las fases del macrociclo real del atleta 2 que importan aquí (semanas 1-2 y 3-7).
FASES = {
    "season_start_date": "2026-09-15",
    "phases": [
        {"name": "Puesta a punto + test 5K (B)", "start_week": 1, "end_week": 2, "deload_weeks": []},
        {"name": "Base consistente + especificidad MTB", "start_week": 3, "end_week": 7, "deload_weeks": [7]},
    ],
}


class ElArranqueEsElDeSuSemana(unittest.TestCase):
    def test_el_caso_real(self):
        self.assertEqual(arranque_de_sus_semanas("2026-09-15", "Lunes"), "2026-09-14")

    def test_generado_el_mismo_dia_en_que_empieza_no_cambia(self):
        self.assertEqual(arranque_de_sus_semanas("2026-09-14", "Lunes"), "2026-09-14")

    def test_sin_dia_elegido_no_cambia_nada(self):
        """Sin `week_start_day` sus semanas son bloques de 7 días desde el primero."""
        self.assertEqual(arranque_de_sus_semanas("2026-09-15", ""), "2026-09-15")
        self.assertEqual(arranque_de_sus_semanas("2026-09-15", None), "2026-09-15")
        self.assertIs(con_arranque_de_sus_semanas(FASES, ""), FASES)

    def test_nunca_hacia_delante(self):
        for dia in DIAS:
            with self.subTest(dia=dia):
                self.assertLessEqual(arranque_de_sus_semanas("2026-09-15", dia), "2026-09-15")

    def test_no_toca_las_fases(self):
        alineado = con_arranque_de_sus_semanas(FASES, "Lunes")
        self.assertEqual(alineado["phases"], FASES["phases"])
        self.assertEqual(FASES["season_start_date"], "2026-09-15")  # copia, no muta


class LaSemanaQueVeLaDecision(unittest.TestCase):
    def test_el_lunes_21_es_la_semana_2(self):
        antes = compute_mesocycle_position(FASES, "2026-09-21")
        self.assertEqual(antes["week_number"], 1)  # EL FALLO: la cuenta sin alinear
        despues = compute_mesocycle_position(con_arranque_de_sus_semanas(FASES, "Lunes"), "2026-09-21")
        self.assertEqual(despues["week_number"], 2)

    def test_la_descarga_cae_en_su_semana_7(self):
        """Semana 7 del atleta: la que empieza el lunes 2026-10-26."""
        fases = con_arranque_de_sus_semanas(FASES, "Lunes")
        self.assertTrue(compute_mesocycle_position(fases, "2026-10-26")["is_deload"])
        self.assertFalse(compute_mesocycle_position(fases, "2026-10-25")["is_deload"])


class ElEspejoLoAlinea(unittest.TestCase):
    """Los macrociclos ya guardados se corrigen al leerlos, sin tocar la base."""

    def test_storage_adapter(self):
        sa = importlib.import_module("bridge.storage_adapter")
        with tempfile.TemporaryDirectory() as d:
            sa.write_season_plan(Path(d), "# plan", FASES, "Lunes")
            escrito = json.loads((Path(d) / "season_phases.json").read_text(encoding="utf-8"))
        self.assertEqual(escrito["season_start_date"], "2026-09-14")


class LaTemporadaNaceAlineada(unittest.TestCase):
    def test_el_macrociclo_nuevo_guarda_el_arranque_de_su_semana(self):
        spn = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
        src = Path(spn.__file__).read_text(encoding="utf-8")
        self.assertIn("arranque_de_sus_semanas(", src)
        self.assertEqual(src.count("_fijar_arranque(contenido, arranque_de_sus_semanas("), 1)


@unittest.skipUnless(shutil.which("node"), "sin node no se puede ejecutar el espejo JS")
class ElEspejoJSDaLoMismo(unittest.TestCase):
    def test_mismos_resultados(self):
        js = (RAIZ / "Frontend/src/utils/macrociclo.js").as_uri()
        casos = [(f"2026-09-{d:02d}", dia) for d in range(10, 22) for dia in (*DIAS, "")]
        script = (
            f"import {{ alinearArranque, posicionEnLaTemporada }} from '{js}';\n"
            f"const casos = {json.dumps(casos)};\n"
            "const fases = " + json.dumps(FASES) + ";\n"
            "console.log(JSON.stringify({\n"
            "  arranques: casos.map(([f, d]) => alinearArranque(f, d)),\n"
            "  semana: posicionEnLaTemporada(fases, '2026-09-21', 'Lunes').semana,\n"
            "}));\n"
        )
        with tempfile.NamedTemporaryFile("w", suffix=".mjs", delete=False, encoding="utf-8") as f:
            f.write(script)
        try:
            salida = subprocess.run(["node", f.name], capture_output=True, text=True, check=True, timeout=60)
        finally:
            Path(f.name).unlink(missing_ok=True)
        datos = json.loads(salida.stdout)
        self.assertEqual(datos["arranques"], [arranque_de_sus_semanas(f, d) for f, d in casos])
        self.assertEqual(datos["semana"], 2)

    def _ejecutar(self, cuerpo: str) -> dict:
        js = (RAIZ / "Frontend/src/utils/macrociclo.js").as_uri()
        script = f"import * as m from '{js}';\n" + cuerpo
        with tempfile.NamedTemporaryFile("w", suffix=".mjs", delete=False, encoding="utf-8") as f:
            f.write(script)
        try:
            salida = subprocess.run(["node", f.name], capture_output=True, text=True, check=True, timeout=60)
        finally:
            Path(f.name).unlink(missing_ok=True)
        return json.loads(salida.stdout)

    def test_la_antelacion_vale_lo_mismo(self):
        """«Tu mes» dice desde cuándo se puede generar cada semana con DIAS_DE_ANTELACION del
        espejo JS; si no vale lo mismo que el servidor, la pantalla promete un día que el
        servidor rechaza (2026-09-24)."""
        from web_app import server

        datos = self._ejecutar("console.log(JSON.stringify({d: m.DIAS_DE_ANTELACION}));\n")
        self.assertEqual(datos["d"], server.DIAS_DE_ANTELACION)

    def test_el_tope_de_la_pantalla_es_el_del_servidor(self):
        """Plan avisa del tope de dos semanas ANTES de pedir el check-in (auditoría del
        2026-09-24): su cuenta tiene que dar lo mismo que `ventana_de_generacion`. Plan real del
        atleta 2 hasta el domingo 2026-10-11: hoy (24-sep) choca; desde el lunes 5 de octubre ya
        no. Y quien se quedó atrás nunca choca."""
        from datetime import datetime

        from tests.test_generation_window import _plan
        from web_app import server

        casos = [("2026-10-11", f"2026-{m:02d}-{d:02d}") for m, d in
                 [(9, 24), (9, 28), (10, 3), (10, 4), (10, 5), (10, 10), (10, 20)]]
        casos += [("2026-09-27", "2026-09-19"), ("2026-09-27", "2026-09-20"), ("2026-09-27", "2026-09-21")]
        datos = self._ejecutar(
            f"const casos = {json.dumps(casos)};\n"
            "console.log(JSON.stringify({s: m.SEMANAS_POR_DELANTE, "
            "t: casos.map(([u, h]) => m.topeDeSemanas(u, h) !== null)}));\n"
        )
        self.assertEqual(datos["s"], server.SEMANAS_POR_DELANTE)

        def limite(ultimo: str, hoy: str) -> bool:
            plan = _plan([ultimo])
            return bool(server.ventana_de_generacion(plan, datetime.fromisoformat(hoy + "T12:00:00")).get("limit"))

        self.assertEqual(datos["t"], [limite(u, h) for u, h in casos])
        self.assertEqual(datos["t"][:5], [True, True, True, True, False])

    def test_la_antelacion_de_la_pantalla_es_la_del_servidor(self):
        """Plan dice "todavía no toca" ANTES del check-in (auditoría del 2026-09-24): Jessie, con su
        semana hasta el 27-sep, pedía el 24 la del lunes 28, contestaba el check-in y DESPUÉS le decían
        que era desde el sábado 26. Misma cuenta que `ventana_de_generacion` en cada caso."""
        from datetime import datetime

        from tests.test_generation_window import _plan
        from web_app import server

        casos = [("2026-09-27", f"2026-09-{d:02d}") for d in range(22, 30)] + [("2026-09-20", "2026-09-24")]
        datos = self._ejecutar(
            f"const casos = {json.dumps(casos)};\n"
            "console.log(JSON.stringify(casos.map(([u, h]) => m.antelacionDeLaSemana(u, h) !== null)));\n"
        )
        servidor = [not server.ventana_de_generacion(_plan([u]), datetime.fromisoformat(h + "T12:00:00"))["allowed"]
                    for u, h in casos]
        self.assertEqual(datos, servidor)
        self.assertEqual(datos[:5], [True, True, True, True, False])   # 22-25 todavía no; 26 ya

    def test_tu_mes_cuenta_las_semanas_del_macrociclo(self):
        """El macrociclo real del atleta 2 arranca el martes 2026-09-15 y su semana empieza el
        lunes: la semana 1 es la del lunes 14. El jueves 24 está en la 2 (21-27), la 3 empieza
        el 28 con su fase de base, y la descarga de la 7 cae del 26-oct al 1-nov. La semana
        generada es la en curso y cada una se puede generar dos días antes de empezar."""
        fases = {
            "season_start_date": "2026-09-15",
            "phases": [
                {"name": "Puesta a punto", "start_week": 1, "end_week": 2, "deload_weeks": []},
                {"name": "Base", "start_week": 3, "end_week": 7, "deload_weeks": [7]},
            ],
        }
        cuerpo = (
            "const fases = " + json.dumps(fases) + ";\n"
            "console.log(JSON.stringify(m.semanasDelMes(fases, {hoy: '2026-09-24', weekStartDay: 'Lunes',"
            " competiciones: [{name: 'Gravel', date: '2026-10-24', priority: 'B'}],"
            " diasGenerados: ['2026-09-21', '2026-09-27'], cuantas: 6})));\n"
        )
        semanas = self._ejecutar(cuerpo)
        self.assertEqual([s["numero"] for s in semanas], [2, 3, 4, 5, 6, 7])
        self.assertEqual((semanas[0]["desde"], semanas[0]["hasta"]), ("2026-09-21", "2026-09-27"))
        self.assertTrue(semanas[0]["actual"] and semanas[0]["generada"])
        self.assertFalse(semanas[1]["generada"])
        self.assertEqual(semanas[1]["generableDesde"], "2026-09-26")
        self.assertEqual(semanas[1]["fase"], "Base")
        # La gravel del sábado 24-oct cae en la semana 6 (19-25 oct) y en ninguna otra.
        self.assertEqual([s["numero"] for s in semanas if s["carreras"]], [6])
        self.assertEqual([s["numero"] for s in semanas if s["descarga"]], [7])
        self.assertEqual(semanas[-1]["hasta"], "2026-11-01")


if __name__ == "__main__":
    unittest.main()
