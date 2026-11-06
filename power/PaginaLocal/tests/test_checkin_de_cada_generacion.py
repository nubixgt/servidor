"""Segunda vuelta sobre la generación de Jessie (atleta 3), 2026-09-15.

1. Al volver a generar, el check-in traía marcado lo de la semana anterior (la
   nota de su activación, el viernes cerrado, el sábado abierto). Y "Saltar" no
   era "sin check-in": la semana usaba igual el de los dos últimos días. Ahora el
   modal empieza en blanco, enseña las fechas de la semana que se va a generar
   (antes, los 9 días desde hoy: un día marcado fuera de esa semana no hacía
   nada) y "Saltar" viaja hasta el servidor.
2. El macrociclo puso la fase específica de su trail B empezando la semana de
   la carrera. Ahora recibe cada carrera con su semana, una regla de dónde va la
   preparación de una carrera, y un aviso en el log si una fase empieza en ella.
3. El test de umbral va al reloj en dos pasos (10' + 20'): alguien puede leerlos
   como dos series con descanso. Los textos dicen "sin parar".

Correr con:  python -m unittest discover -s tests -v
"""

import inspect
import json
import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import importlib  # noqa: E402

from services.ai import test_de_umbral  # noqa: E402
from services.ai.langgraph.nodes.season_planner_node import (  # noqa: E402
    con_semana_del_macrociclo,
    fases_que_empiezan_en_su_carrera,
)
from web_app import server  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]

# Las fases reales del macrociclo de Jessie del 2026-09-15 (season_phases.json).
FASES_JESSIE = [
    {"name": "Competición 5K (B) + reenganche estable", "start_week": 1, "end_week": 3},
    {"name": "Base aeróbica estable + economía", "start_week": 4, "end_week": 9},
    {"name": "Trail corto (B): cuestas + tolerancia excéntrica", "start_week": 10, "end_week": 12},
    {"name": "Transición a Build 10K: umbral sostenido", "start_week": 13, "end_week": 16},
    {"name": "Build 10K (provisional): umbral + VO2max controlado", "start_week": 17, "end_week": 22},
    {"name": "Especificidad 10K (provisional)", "start_week": 23, "end_week": 27},
    {"name": "Peak 10K (provisional)", "start_week": 28, "end_week": 30},
    {"name": "Taper + Carrera A 10K", "start_week": 31, "end_week": 31},
]
CARRERAS_JESSIE = [
    {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B"},
    {"name": "12k UTG Trail Guatemala", "date": "2026-11-21", "priority": "B"},
    {"name": "10k Ciudad de Guatemala", "date": "2027-04-17", "priority": "A"},
]


class ElTestDeUmbralNoTieneDescanso(unittest.TestCase):
    def test_los_pasos_del_reloj_dicen_sin_parar(self):
        for deporte in ("run", "bike"):
            with self.subTest(deporte=deporte):
                pasos = test_de_umbral.sesion_del_test(deporte, "2026-09-25").steps
                self.assertIn("NO pares", pasos[1].description)
                self.assertIn("SIN PARAR", pasos[2].description)
                # Y siguen siendo dos pasos seguidos, sin recuperación entre medias.
                self.assertEqual([p.step_type for p in pasos], ["warmup", "interval", "interval", "cooldown"])

    def test_el_protocolo_de_la_pantalla_tambien(self):
        for deporte in ("run", "bike"):
            with self.subTest(deporte=deporte):
                paso = test_de_umbral.PROTOCOLOS[deporte]["pasos"][1]
                self.assertIn("SIN PARAR", paso)
                self.assertIn("no pares ni aflojes", paso)

    def test_el_json_del_frontend_esta_regenerado(self):
        publicado = json.loads((RAIZ / "Frontend/public/test_de_umbral.json").read_text(encoding="utf-8"))
        self.assertEqual(publicado["protocolos"]["run"]["pasos"], test_de_umbral.PROTOCOLOS["run"]["pasos"])


class SaltarEsSinCheckin(unittest.TestCase):
    """El cable entero: modal -> store -> PlanView -> PHP -> puente -> worker -> servidor."""

    def test_el_servidor_no_usa_ninguno(self):
        self.assertIn("sin_checkin", inspect.signature(server.run_week_generation_task).parameters)
        self.assertIn("latest = None if sin_checkin else latest_checkin(", inspect.getsource(server.run_week_generation_task))

    def test_cada_salto_lo_pasa(self):
        esperado = {
            "Frontend/src/components/modals/CheckinModal.vue": "store.closeCheckin('saltado')",
            "Frontend/src/views/admin/PlanView.vue": "sinCheckin = respuesta === 'saltado'",
            "Frontend/src/services/plan.js": "skip_checkin: skipCheckin",
            "Backend/src/Controllers/PlanController.php": "$body['skip_checkin']",
            "Backend/src/Services/PlanService.php": "'skip_checkin' => $sinCheckin",
            "AiService/bridge/schemas.py": "skip_checkin: bool = False",
            "AiService/main.py": '"skip_checkin": req.skip_checkin',
            "AiService/worker.py": 'sin_checkin=bool(SPEC.get("skip_checkin", False))',
        }
        for ruta, texto in esperado.items():
            with self.subTest(ruta=ruta):
                self.assertIn(texto, (RAIZ / ruta).read_text(encoding="utf-8"))


class ElModalEmpiezaEnBlanco(unittest.TestCase):
    def setUp(self):
        self.modal = (RAIZ / "Frontend/src/components/modals/CheckinModal.vue").read_text(encoding="utf-8")

    def test_no_se_rellena_con_el_anterior(self):
        for resto in ("c.week_note", "c.unavailable_dates", "c.open_dates", "seedFrom"):
            with self.subTest(resto=resto):
                self.assertNotIn(resto, self.modal)

    def test_ensena_las_fechas_de_la_semana_que_se_genera(self):
        self.assertIn("fechasDeLaProximaSemana", self.modal)
        self.assertNotIn("length: 9", self.modal)


class LasFechasSonLasDelServidor(unittest.TestCase):
    """`Frontend/src/utils/semana.js` repite `_next_week_start` y `dias_del_bloque`."""

    def setUp(self):
        self.js = (RAIZ / "Frontend/src/utils/semana.js").read_text(encoding="utf-8")

    def _constante(self, nombre):
        return int(re.search(rf"export const {nombre} = (\d+);", self.js).group(1))

    def test_mismas_constantes(self):
        self.assertEqual(self._constante("MIN_DIAS_DEL_BLOQUE"), server.MIN_DIAS_DEL_BLOQUE)
        self.assertEqual(self._constante("MAX_DIAS_ADELANTE_PLAUSIBLES"), server._MAX_PLAUSIBLE_DAYS_AHEAD)


class ElMacroSabeDondeVaCadaCarrera(unittest.TestCase):
    def test_avisa_de_la_fase_del_trail_de_jessie(self):
        avisos = fases_que_empiezan_en_su_carrera(FASES_JESSIE, con_semana_del_macrociclo(CARRERAS_JESSIE, "2026-09-15"))
        self.assertEqual(len(avisos), 1)
        self.assertIn("Trail corto (B)", avisos[0])
        self.assertIn("semana 10", avisos[0])

    def test_la_fase_de_afinamiento_y_carrera_de_la_A_no_es_un_aviso(self):
        fases = [{"name": "Peak", "start_week": 1, "end_week": 30}, {"name": "Taper + Carrera A", "start_week": 31,
                                                                     "end_week": 31}]
        self.assertEqual(fases_que_empiezan_en_su_carrera(fases, con_semana_del_macrociclo(CARRERAS_JESSIE[2:],
                                                                                           "2026-09-15")), [])

    def test_el_prompt_dice_donde_va_la_preparacion(self):
        # El paquete `nodes` exporta una FUNCIÓN con el nombre del módulo: se pide el módulo.
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("Each race is prepared in the weeks BEFORE it, not from its week.", prompt)
        self.assertIn("`semana_del_macrociclo` is the week of this plan the race falls in", prompt)
        # Y no le dice cuántas semanas: eso es criterio de ese atleta y esa carrera.
        self.assertIn("why that is enough for\n  this race and this athlete", prompt)


if __name__ == "__main__":
    unittest.main()
