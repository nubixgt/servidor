"""Cinco fallos de una sesión de pruebas real, y lo que los cierra.

Ninguno es un caso límite inventado: los cinco los encontró el atleta usando la
app una noche, y tres de ellos le costaron dinero.

  1. El experto de actividad se dejó un campo de su esquema. El error se
     clasificó como no-reintentable, el nodo murió y la semana salió vacía
     después de cobrar $0.22. Dos veces.
  2. El planificador puso sólo natación en un bloque de 05:00-07:00 que
     permite `run, swim`, justificándose con una regla que nadie escribió.
  3. La subida a Garmin no reintentaba: un pico de latencia dejaba una sesión
     sin subir con un aspa roja junto a un entreno perfectamente válido.
  4. El desplegable de distancia borraba del PERFIL la distancia declarada.
  5. El doctor encadenó cinco rondas de preguntas hasta que el atleta escribió
     "por qué sigues preguntando y no generas el informe".

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from pydantic import BaseModel, ValidationError  # noqa: E402

RAIZ = Path(__file__).resolve().parents[1]


class UnCampoQueElModeloSeDejaSeVuelveAPedir(unittest.TestCase):
    """Fallo 1. `ReceiverOutputs.for_weekly_planner.evidence` faltaba.

    No es que el modelo NO PUEDA rellenarlo: es que esa vez no lo hizo. Volver
    a pedirlo es exactamente lo que arregla esto, y no enmascara un esquema
    imposible -- si de verdad no puede cumplirlo, falla las tres veces.
    """

    def test_una_validacion_fallida_es_reintentable(self):
        from services.ai.utils.retry_handler import RetryConfig
        self.assertIn(ValidationError, RetryConfig().retryable_exceptions)

    def test_y_se_reintenta_de_verdad(self):
        from services.ai.utils.retry_handler import RetryConfig, retry_with_backoff

        class _Payload(BaseModel):
            evidence: list[str]

        intentos = []

        async def flojea_una_vez():
            intentos.append(1)
            if len(intentos) == 1:
                _Payload()  # le falta `evidence`, como en la corrida real
            return "bien"

        r = asyncio.run(retry_with_backoff(
            flojea_una_vez, RetryConfig(max_retries=2, base_delay=0.01), "prueba"))
        self.assertEqual(r, "bien")
        self.assertEqual(len(intentos), 2)

    def test_los_tres_campos_obligatorios_van_explicados(self):
        """Un campo requerido que nadie explica es el que se cae."""
        from services.ai.langgraph.schemas.expert_outputs import ReceiverPayload
        for campo in ("signals", "evidence", "implications"):
            with self.subTest(campo=campo):
                info = ReceiverPayload.model_fields[campo]
                self.assertTrue(info.is_required(), f"{campo} dejó de ser obligatorio")
                self.assertTrue((info.description or "").strip(),
                                f"{campo} no tiene descripción")


class DosSesionesCabenEnUnBloque(unittest.TestCase):
    """Fallo 2. El viernes del atleta: 05:00-07:00 (2h) con `run, swim`.

    El planificador puso sólo natación, dos generaciones seguidas, y escribió
    en su propio razonamiento "con la regla operativa de 'una sola entrada en
    días de un bloque'". Esa regla no existe en ningún sitio.

    Comprobado en el prompt archivado: recibió la tabla correcta Y la nota que
    dice que un bloque admite las dos cosas. La causa estaba en el esquema --
    `session_slot` no tenía descripción, y `am`/`pm` leídos como HORAS hacen
    imposibles dos sesiones en una misma mañana.
    """

    def test_el_prompt_lo_dice(self):
        """Lo dice el contexto de un perfil CON franjas, que es donde existen los
        bloques. El prompt genérico de decisión lo decía también, y desde la
        rejilla de la v1.1 hablaba de bloques a atletas que no tienen ninguno."""
        from services.ai.langgraph.nodes.weekly_planner_node import TRAINING_DECISION_USER_PROMPT
        from web_app.server import build_custom_planning_context
        contexto = build_custom_planning_context({
            "name": "T", "sports_preference": "triathlon", "available_days": ["Viernes"],
            "day_time_blocks": {"Viernes": [{"start": "05:00", "end": "07:00", "sports": ["Correr", "Nadar"]}]},
        })
        self.assertIn("un bloque SI admite varias sesiones", contexto)
        self.assertIn("Varias sesiones en un día", TRAINING_DECISION_USER_PROMPT)

    def test_y_el_ESQUEMA_de_la_decision_tambien(self):
        """El prompt ya lo decía y no bastó. Lo que decide es el esquema."""
        from services.ai.langgraph.schemas.plan_outputs import DayIntent
        desc = (DayIntent.model_fields["session_slot"].description or "").lower()
        self.assertTrue(desc.strip(), "session_slot volvió a quedarse sin descripción")
        self.assertIn("clave", desc, "no dice que sea una clave y no una hora")
        self.assertIn("mismo bloque", desc, "no dice que caben dos en el mismo bloque")

    def test_el_validador_deja_pasar_dos_sesiones_en_el_mismo_bloque(self):
        """La otra mitad: que el esquema lo permita no sirve si el validador
        lo tumba después."""
        from services.ai.langgraph.schemas.plan_outputs import (
            TargetType,
            WeekBlock,
            WeeklyPlanPayload,
            WorkoutDay,
            WorkoutStep,
        )
        from services.ai.langgraph.validation.plan_validator import validate_weekly_plan

        def _dia(deporte, franja, minutos):
            return WorkoutDay(
                date="2026-08-28", day_name_es="Viernes", is_rest=False, sport=deporte,
                focus="Facil", purpose="p", adaptation="a", session_slot=franja, block_index=0,
                intensity_class="easy", total_duration_minutes=minutos,
                steps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                                   duration_value=int(minutos * 60), target_type=TargetType.HR_ZONE,
                                   target_zone=2, description="rodaje")],
            )

        payload = WeeklyPlanPayload(
            athlete_name="T", start_date="2026-08-28", zones_table="z",
            weeks=[WeekBlock(week_number=1, days=[_dia("run", "am", 45), _dia("swim", "pm", 40)])],
        )
        disponibilidad = {"days": {"Viernes": {
            "available": True, "hours": 2.0, "sports": ["run", "swim"],
            "blocks": [{"start": "05:00", "end": "07:00", "hours": 2.0,
                        "sports": ["run", "swim"]}]}}}
        informe = validate_weekly_plan(
            payload, disponibilidad, [{"date": "2026-08-28", "day_name_es": "Viernes"}])
        graves = [v for v in informe["violations"]
                  if v["type"] not in ("missing_date", "unexpected_date")]
        self.assertEqual(graves, [], f"el validador tumbó las dos sesiones: {graves}")


class _GarminFalso:
    def __init__(self, biblioteca=None):
        self.biblioteca = biblioteca or []

    def get_workouts(self, start=0, end=100):
        return self.biblioteca

    def get_workout_by_id(self, workout_id):
        return {}


class _ClienteFalso:
    def __init__(self, garmin=None):
        self.client = garmin or _GarminFalso()


class LaSubidaReintenta(unittest.TestCase):
    """Fallo 3. La natación del 2026-08-28, dos veces seguidas, mientras las
    otras seis sesiones subían sin problema en la misma llamada.

        ReadTimeoutError: ... connectapi.garmin.com ... (read timeout=10)
    """

    def test_el_tiempo_de_espera_ya_no_es_el_de_por_defecto(self):
        from services.garmin.client import TIEMPO_DE_ESPERA_S
        self.assertGreaterEqual(TIEMPO_DE_ESPERA_S, 30)

    def test_un_timeout_no_tumba_la_sesion(self):
        from services.garmin.garmin_upload_service import GarminUploadService
        svc = GarminUploadService(_ClienteFalso())
        llamadas = []

        import garth
        original = garth.client.post

        def post_falla_una_vez(dominio, ruta, **kw):
            llamadas.append(ruta)
            if len(llamadas) == 1:
                raise TimeoutError("read timeout=10")
            return type("R", (), {"json": lambda self: {"workoutId": 4242}})()

        garth.client.post = post_falla_una_vez
        try:
            wid = svc._upload_and_schedule({"workoutName": "Técnica - 2026-08-28"}, "2026-08-28")
        finally:
            garth.client.post = original
        self.assertEqual(wid, 4242)

    def test_si_ya_se_habia_creado_NO_se_crea_otro(self):
        """Una espera agotada no dice que Garmin no hiciera nada: dice que no
        nos llegó la respuesta. Reintentar a ciegas es como se acaba con dos
        entrenos idénticos el mismo día en el calendario del atleta."""
        from services.garmin.garmin_upload_service import GarminUploadService
        nombre = "Técnica + aeróbico - 2026-08-28"
        svc = GarminUploadService(_ClienteFalso(
            _GarminFalso([{"workoutId": 999, "workoutName": nombre}])))

        import garth
        original, creados = garth.client.post, []

        def post(dominio, ruta, **kw):
            if "schedule" in ruta:
                return type("R", (), {"json": lambda self: {}})()
            creados.append(ruta)
            raise TimeoutError("read timeout=30")

        garth.client.post = post
        try:
            wid = svc._upload_and_schedule({"workoutName": nombre}, "2026-08-28")
        finally:
            garth.client.post = original
        self.assertEqual(wid, 999, "no reutilizó el que ya existía")
        self.assertEqual(len(creados), 1, "volvió a intentar crearlo y habría duplicado")


class LaDistanciaDeLaMetaNoSeBorra(unittest.TestCase):
    """Fallo 4, y el más silencioso de los cinco.

    El catálogo de distancias lo sirve el backend, así que en la primera carga
    del perfil las opciones aún no existen. Un <select> al que se le pide un
    valor que no está entre sus opciones se queda VACÍO, y el autoguardado
    siguiente escribía distancia vacía en el perfil. Las cuatro competencias
    del atleta real acabaron con `distance: ''`, y el evaluador de metas se
    quedó sin nada con qué comparar sus tiempos objetivo.
    """

    JS = (RAIZ / "web_app/static/app.js").read_text(encoding="utf-8")

    def test_el_valor_viaja_en_el_elemento_y_no_solo_en_las_opciones(self):
        self.assertIn('data-guardada="${escapeHtml(comp.distance', self.JS)

    def test_repintar_recupera_lo_guardado(self):
        self.assertIn("sel.value || sel.dataset.guardada", self.JS)

    def test_guardar_no_escribe_vacio_sobre_algo_que_si_estaba(self):
        self.assertIn(".comp-distance').dataset.guardada", self.JS)

    def test_pero_el_atleta_SI_puede_vaciarla_a_proposito(self):
        """Sin esto la red de seguridad le devolvería la distancia vieja y no
        podría quitarla nunca."""
        self.assertIn("e.target.dataset.guardada = e.target.value", self.JS)


class RehacerEsReescribirNoContinuar(unittest.TestCase):
    """Fallo 6, y salio de una corrida que funciono.

    El atleta pidio "el jueves ponme ejercicios mas especificos para hombro".
    Lo hizo -- y de paso le quito las dos sesiones del viernes que la
    generacion anterior SI le habia puesto.

    No fue capricho del modelo: los dos prompts se diferencian en 123 lineas y
    ahi estaba la causa. En el modo rehacer, la app le pasaba la semana que iba
    a REEMPLAZAR bajo el encabezado "Plan anterior -- lo que YA le dosificaste"
    y con la instruccion literal **"no repitas la misma semana"**. Hizo
    exactamente lo que se le pidio.

    Ese encuadre es correcto cuando se genera la semana SIGUIENTE. Rehacer es
    lo contrario: los mismos siete dias con una cosa cambiada.
    """

    def _plan(self):
        from services.ai.langgraph.schemas.plan_outputs import (
            WeekBlock,
            WeeklyPlanPayload,
            WorkoutDay,
        )
        dia = WorkoutDay(date="2026-08-28", day_name_es="Viernes", is_rest=False, sport="run",
                         focus="Trote", purpose="p", adaptation="a",
                         total_duration_minutes=30, steps=[])
        return WeeklyPlanPayload(athlete_name="T", start_date="2026-08-28", zones_table="z",
                                 weeks=[WeekBlock(week_number=1, days=[dia])])

    def test_al_generar_la_siguiente_se_le_pide_CONTINUAR(self):
        from services.ai.langgraph.nodes.weekly_planner_node import _format_previous_plan
        txt = _format_previous_plan(self._plan(), "2026-08-28", rehaciendo=False)
        self.assertIn("MICROCICLO", txt)
        self.assertNotIn("REESCRIBIENDO", txt)

    def test_al_REHACER_se_le_pide_CONSERVARLA(self):
        from services.ai.langgraph.nodes.weekly_planner_node import _format_previous_plan
        txt = _format_previous_plan(self._plan(), "2026-08-28", rehaciendo=True)
        self.assertNotIn("MICROCICLO", txt)
        self.assertIn("REESCRIBIENDO", txt)
        self.assertIn("Conserva todo lo demas", txt)
        self.assertIn("sesiones dobles", txt,
                      "no le dice que conserve las dobles, que es lo que perdio")

    def test_el_servidor_le_pasa_la_bandera(self):
        """Sin esto el nodo nunca sabria que esta rehaciendo."""
        fuente = (RAIZ / "web_app/server.py").read_text(encoding="utf-8")
        self.assertIn('availability_constraints["rehacer_semana"] = bool(inicio_ultima)', fuente)
        nodo = (RAIZ / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
        self.assertIn('.get("rehacer_semana")', nodo)


if __name__ == "__main__":
    unittest.main()
