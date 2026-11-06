"""Auditoría de las semanas del 5K Monterrico de los atletas 2 y 3 (2026-09-14).

Trazas `u2/reasoning/2026-09-14T07-57-18` (semana del atleta 2) y
`u3/reasoning/2026-09-14T08-54-35` y `09-10-32` (macrociclo y semana de la
atleta 3). Las tres salieron a la primera: 9 llamadas, un codificador,
validador en cero. Y aun así:

1. **En afinamiento los FIJO seguían siendo inviolables.** La nota de carga
   decía "sus días FIJO pasan a POSIBLE", pero el mismo prompt decía dos veces
   lo contrario, una bajo "CRITICAL SCHEDULING RULES (MANDATORY — NEVER
   VIOLATE)". Las dos semanas conservaron sus tres fuerzas fijas a seis días de
   la carrera, y la atleta 3 recibió dos sesiones el lunes que volvía de viaje.
2. **La semana no conocía el ritmo de la carrera.** 6x400 m "para practicar el
   pacing" a 4:54-5:16/km, la banda de repeticiones; su objetivo de 26:00 pide
   5:12/km y Garmin le predice 5:26.
3. **El guardarraíl de ACWR** recortaba la semana 1 al 70-80 % con el ACWR de
   Garmin por encima de 1,5 y le decía al atleta que estaba "en zona de riesgo
   de lesión": un umbral de población, activado por defecto. El atleta 2 tuvo
   1,71 el 2026-09-05.
4. **Un Ironman con un atleta de 6 h**: la semana recibía "Target weekly hours
   for this phase: ~12h" sin decir dónde está el atleta, y el bloque de horas
   llamaba "historial" a lo que se le había PLANIFICADO.
5. **Del perfil**, el estrés "alto" empujaba a "conservative intensity" por
   regla, el terreno "mixto" no llegaba, y "ow_10000" llegaba como clave.
6. **El check-in** no tenía dónde decir "el sábado salgo en bici con amigos".
7. **"Puedes cerrar la pestaña"** desde el primer segundo, con el coach a
   punto de preguntar.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from pathlib import Path
from unittest import mock

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import has_content, normalize_checkin, render_checkin_block  # noqa: E402
from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    _format_load_target,
    _format_mesocycle_position,
    _format_volume_target,
    _ritmo_de_la_carrera,
    compute_mesocycle_position,
    compute_weekly_load_target,
    compute_weekly_volume_target,
)
from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation import plan_validator  # noqa: E402
from web_app.server import build_custom_planning_context  # noqa: E402

SEMANA_DEL_5K = [{"date": f"2026-09-{d}"} for d in range(14, 21)]
MONTERRICO = {"name": "5k Monterrico", "date": "2026-09-20", "priority": "B",
              "distance": "5K", "target_time": "00:26:00"}
BASE_JESSIE = {"garmin_chronic_load": 392, "garmin_acute_load": 279, "chronic_weekly_hours": 6.1, "load_rates": {}}
FASE_1 = {"week_number": 1, "is_deload": False, "weekly_hours_target": 4.8, "weekly_load_target": 300}

# La rejilla real de la atleta 3 (MySQL, 2026-09-14).
PERFIL_JESSIE = {
    "name": "Jessie Navas", "sports_preference": "run", "target_distance": "10K",
    "sport_days": {
        "Correr": {"Lunes": "posible", "Miércoles": "posible", "Viernes": "posible", "Domingo": "posible"},
        "Trail": {"Domingo": "posible"}, "Bici": {"Martes": "posible", "Domingo": "posible"},
        "Fuerza": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo"},
    },
    "terrain": "mixto", "stress_level_self_report": "alto",
}


def _criticas(texto):
    return texto[texto.index("CRITICAL SCHEDULING RULES"):]


class EnAfinamientoElFijoNoEsInviolable(unittest.TestCase):
    """Desde el 2026-09-14 el contexto se ESCRIBE para su semana
    (`build_custom_planning_context(perfil, afinamiento)`); antes el nodo semanal
    reescribía con una expresión regular dos frases de server.py."""

    def setUp(self):
        self.contexto = build_custom_planning_context(PERFIL_JESSIE)
        self.taper = compute_weekly_load_target(BASE_JESSIE, FASE_1, competitions=[MONTERRICO],
                                                week_dates=SEMANA_DEL_5K)
        self.semana = build_custom_planning_context(PERFIL_JESSIE, self.taper["race_taper"])

    def test_la_regla_inviolable_deja_de_exigir_el_fijo(self):
        self.assertNotIn("Every FIJO appears on its day", _criticas(self.semana))
        self.assertIn("FIJO counts as POSIBLE this week", _criticas(self.semana))
        self.assertIn("5k Monterrico", _criticas(self.semana))

    def test_la_nota_de_la_tabla_tambien(self):
        self.assertNotIn("el atleta quiere ESE deporte ESE dia. Ponlo.", self.semana)
        self.assertIn("un FIJO cuenta como POSIBLE", self.semana)

    def test_nadie_reescribe_el_contexto_despues(self):
        """El parche de la expresión regular ya no existe, y el nodo usa el contexto tal cual llega."""
        import inspect

        nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
        self.assertFalse(hasattr(nodo, "contexto_de_la_semana"))
        self.assertIn('planning_context=state["planning_context"]', inspect.getsource(nodo.weekly_planner_node))

    def test_fuera_del_afinamiento_es_el_de_siempre(self):
        self.assertEqual(build_custom_planning_context(PERFIL_JESSIE, None), self.contexto)
        self.assertIn("4. Every FIJO appears on its day.", self.contexto)
        self.assertIn("- **FIJO**: el atleta quiere ESE deporte ESE dia.", self.contexto)

    def test_lo_demas_sigue_siendo_inviolable(self):
        criticas = _criticas(self.semana)
        self.assertIn("absolutely NO training whatsoever", criticas)
        self.assertIn("Each session may ONLY use a sport the athlete marked", criticas)

    def test_la_fuerza_que_pide_un_profesional_tambien_cede_y_se_dice(self):
        nota = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")._NOTA_DE_AFINAMIENTO
        self.assertIn("número de sesiones de fuerza que pida un profesional", nota)


class LaSemanaConoceElRitmoDeSuCarrera(unittest.TestCase):
    def test_el_5k_de_jessie(self):
        self.assertEqual(_ritmo_de_la_carrera(MONTERRICO), "5:12/km")

    def test_llega_al_bloque_de_carga(self):
        bloque = _format_load_target(compute_weekly_load_target(
            BASE_JESSIE, FASE_1, competitions=[MONTERRICO], week_dates=SEMANA_DEL_5K))
        self.assertIn("Su objetivo (26:00) pide un ritmo medio de 5:12/km", bloque)
        self.assertIn("no el ritmo de esta carrera", bloque)

    def test_natacion_por_100(self):
        self.assertEqual(_ritmo_de_la_carrera({"distance": "swim_1500", "target_time": "00:27:30"}), "1:50/100 m")

    def test_se_abstiene_donde_el_ritmo_no_es_la_unidad(self):
        for carrera in (
            {"distance": "trail_short", "target_time": "01:30:00"},   # manda el desnivel
            {"distance": "tri_olympic", "target_time": "02:45:00"},   # varios deportes
            {"distance": "bike_tt_40", "target_time": "01:05:00"},    # la velocidad no se ensaya así
            {"distance": "5K", "target_time": ""},
            {"distance": "5K", "target_time": "rapido"},
            {"distance": "", "target_time": "00:26:00"},
        ):
            with self.subTest(carrera=carrera):
                self.assertEqual(_ritmo_de_la_carrera(carrera), "")


class UnIronmanConSeisHoras(unittest.TestCase):
    """La fase describe 12 h y el atleta sigue en 6: la semana tiene que ver las
    dos cosas y saber qué hacer con la distancia, sin saltar ni conformarse."""

    def test_la_distancia_se_dice_y_se_dice_que_hacer(self):
        base = {"garmin_chronic_load": 300, "load_rates": {}}
        bloque = _format_load_target(compute_weekly_load_target(base, {**FASE_1, "weekly_load_target": 620}))
        self.assertIn("describe una semana normal de ~620; hoy su crónica es 300", bloque)
        self.assertIn("no saltando al número de la fase", bloque)
        self.assertIn("el macrociclo necesita revisarse", bloque)
        self.assertNotIn("**", bloque)

    def test_sin_distancia_solo_el_dato(self):
        base = {"garmin_chronic_load": 300, "load_rates": {}}
        bloque = _format_load_target(compute_weekly_load_target(base, {**FASE_1, "weekly_load_target": 320}))
        self.assertIn("~320", bloque)
        self.assertNotIn("no saltando", bloque)

    def test_las_horas_planificadas_no_se_llaman_historial(self):
        base = {"chronic_weekly_hours": 6.0, "previous_week_planned_hours": 9.0}
        bloque = _format_volume_target(compute_weekly_volume_target(
            base, {"week_number": 5, "is_deload": False, "weekly_hours_target": 12.0}))
        self.assertIn("según su historial real: ~6.0 h", bloque)
        self.assertIn("se le planificaron ~9.0 h", bloque)
        self.assertNotIn("historial real: ~9", bloque)
        self.assertIn("no saltando al número de la fase", bloque)

    def test_las_horas_de_la_fase_no_se_llaman_objetivo(self):
        fases = {"season_start_date": "2026-09-14", "phases": [
            {"name": "Base", "start_week": 1, "end_week": 16, "weekly_hours_target": 12}]}
        texto = _format_mesocycle_position(compute_mesocycle_position(fases, "2026-10-12"))
        self.assertNotIn("Target weekly hours", texto)
        self.assertIn("not a target", texto)

    def test_el_macrociclo_no_aplana_ni_finge(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("Anchored to today does not mean flat", prompt)
        self.assertIn("never write numbers that pretend the gap closes", prompt)


class UnaSemanaDeCarreraNoSeRecortaDosVeces(unittest.TestCase):
    """Macrociclo regenerado de la atleta 3: `deload_weeks` [1, 2] con el 5K en
    la semana 1. La semana recibía "Deload week: Yes — reduce volume ~40%" y
    además la nota de afinamiento."""

    def test_en_afinamiento_no_se_dice_descarga(self):
        from services.ai.langgraph.nodes.weekly_planner_node import posicion_sin_doble_recorte

        fases = {"season_start_date": "2026-09-14", "phases": [
            {"name": "Puesta a punto", "start_week": 1, "end_week": 2, "deload_weeks": [1, 2]}]}
        posicion = compute_mesocycle_position(fases, "2026-09-14")
        taper = compute_weekly_load_target(BASE_JESSIE, FASE_1, competitions=[MONTERRICO], week_dates=SEMANA_DEL_5K)
        texto = _format_mesocycle_position(posicion_sin_doble_recorte(posicion, taper, None))
        self.assertNotIn("reduce volume ~40%", texto)
        self.assertTrue(posicion["is_deload"])   # la original no se toca

    def test_una_descarga_normal_sigue_diciendose(self):
        from services.ai.langgraph.nodes.weekly_planner_node import posicion_sin_doble_recorte

        fases = {"season_start_date": "2026-09-14", "phases": [
            {"name": "Base", "start_week": 1, "end_week": 8, "deload_weeks": [4]}]}
        posicion = compute_mesocycle_position(fases, "2026-10-05")
        normal = compute_weekly_load_target(BASE_JESSIE, {**FASE_1, "is_deload": True}, week_dates=SEMANA_DEL_5K)
        self.assertIn("reduce volume ~40%", _format_mesocycle_position(posicion_sin_doble_recorte(posicion, normal)))

    def test_el_macrociclo_no_marca_la_carrera_como_descarga_ni_abre_bloques_de_recuperacion(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("A race week or a taper week is not a `deload_week`", prompt)
        self.assertIn("a B race does not open a recovery", prompt)


class UnAfinamientoTieneLaCuentaHecha(unittest.TestCase):
    """Semana intermedia del atleta 2: 6,25 h contra 6,6 h a 6 días de su 5K."""

    def test_la_escala_llega_con_las_horas_del_atleta(self):
        base = {"garmin_chronic_load": 285, "chronic_weekly_hours": 6.6, "load_rates": {}}
        bloque = _format_load_target(compute_weekly_load_target(
            base, FASE_1, competitions=[MONTERRICO], week_dates=SEMANA_DEL_5K))
        self.assertIn("una semana normal son ~6.6 h", bloque)
        self.assertIn("~2.6-4.0 h", bloque)
        self.assertIn("suma los minutos", bloque)

    def test_si_la_carrera_cae_la_semana_siguiente_no_hay_escala(self):
        base = {"garmin_chronic_load": 285, "chronic_weekly_hours": 6.6, "load_rates": {}}
        semana_antes = [{"date": f"2026-09-{d:02d}"} for d in range(11, 18)]   # carrera a 9 días
        bloque = _format_load_target(compute_weekly_load_target(
            base, FASE_1, competitions=[MONTERRICO], week_dates=semana_antes))
        self.assertIn("sus días FIJO pasan a POSIBLE", bloque)
        self.assertNotIn("bajar un 40-60 % deja", bloque)

    def test_la_proporcion_de_la_molestia_esta_en_el_esquema(self):
        from services.ai.langgraph.schemas.plan_outputs import DayIntent

        self.assertIn("two or three", DayIntent.model_fields["adaptation"].description)


class PosibleTambienEsQuitar(unittest.TestCase):
    def test_la_nota_y_la_regla_lo_dicen(self):
        contexto = build_custom_planning_context(PERFIL_JESSIE)
        self.assertIn("bajar volumen tambien es quitar una sesion entera", contexto)
        criticas = _criticas(contexto)
        self.assertIn("not how many boxes they ticked", criticas)
        self.assertIn("Removing a whole session is a valid way to lower volume", criticas)
        # Lo que fijaba la prueba anterior sigue ahí.
        self.assertIn("POSIBLE is a PERMISSION, not a slot to fill", criticas)


class LoQueDeclaraElPerfilLlegaSinReglasDePoblacion(unittest.TestCase):
    def setUp(self):
        self.contexto = build_custom_planning_context(PERFIL_JESSIE)

    def test_el_estres_alto_no_es_una_orden_de_prudencia(self):
        self.assertNotIn("conservative intensity", self.contexto)
        self.assertIn("not, by itself, a reason to train less", self.contexto)

    def test_el_terreno_mixto_llega(self):
        self.assertIn("Mixed terrain", self.contexto)

    def test_una_distancia_de_aguas_abiertas_llega_legible(self):
        nadador = build_custom_planning_context({**PERFIL_JESSIE, "sports_preference": "swim",
                                                 "target_distance": "ow_10000"})
        self.assertNotIn("Target race distance: ow_10000", nadador)
        self.assertIn("aguas abiertas", nadador)


def _semana_larga():
    dia = WorkoutDay(date="2026-09-14", day_name_es="Lunes", is_rest=False, sport="run", focus="Rodaje",
                     purpose="p", adaptation="a", intensity_class="easy", discipline="carrera",
                     total_duration_minutes=600.0,
                     steps=[WorkoutStep(step_order=1, step_type="main", duration_type="time",
                                        duration_value=36000, target_type=TargetType.HR_ZONE, target_zone=2,
                                        description="rodaje")])
    return WeeklyPlanPayload(athlete_name="T", start_date="2026-09-14", zones_table="",
                             weeks=[WeekBlock(week_number=1, week_rationale="", days=[dia])])


class ElACWRNoDictaLaSemana(unittest.TestCase):
    def test_un_acwr_alto_ya_no_es_una_violacion(self):
        disponibilidad = {"current_acwr": 1.71, "ai_load_guardrail_enabled": True, "weekly_hours_budget": 6.0}
        informe = plan_validator.validate_weekly_plan(_semana_larga(), disponibilidad, [{"date": "2026-09-14"}])
        self.assertNotIn("acwr_guardrail", {v["type"] for v in informe["violations"]})

    def test_el_recorte_y_su_mensaje_ya_no_existen(self):
        self.assertFalse(hasattr(plan_validator, "_apply_acwr_guardrail_fallback"))
        self.assertFalse(hasattr(plan_validator, "_scale_weeks_to_minutes"))
        self.assertNotIn("acwr_guardrail", plan_validator._VIOLATION_PREFIX_ES)
        self.assertNotIn("acwr_guardrail", plan_validator._TIER2_REPAIR_SYSTEM_PROMPT)


class LoQueQuiereQueSeTengaEnCuentaEstaSemana(unittest.TestCase):
    NOTA = "El sábado salgo en bici con amigos, unas 3 horas"

    def test_se_guarda_y_cuenta_como_contenido(self):
        checkin = normalize_checkin({"week_note": f"  {self.NOTA} "})
        self.assertEqual(checkin["week_note"], self.NOTA)
        self.assertTrue(has_content(checkin))

    def test_llega_con_sus_palabras_y_con_que_hacer(self):
        bloque = render_checkin_block(normalize_checkin({"week_note": self.NOTA}), [])
        self.assertIn(f"«{self.NOTA}»", bloque)
        self.assertIn("cuenta como entrenamiento", bloque)
        # El validador convertiría en descanso una sesión en su día de descanso.
        self.assertIn("no la programes como sesión", bloque)

    def test_sin_nota_no_hay_bloque(self):
        self.assertNotIn("tenga en cuenta esta semana", render_checkin_block(normalize_checkin({"sleep": 3}), []))


class LaPantallaSabeCuandoSePuedeCerrar(unittest.TestCase):
    def test_el_coach_avisa_cuando_no_pregunta(self):
        from services.ai import preguntas_del_coach as pdc

        proveedor = mock.Mock()
        llm = mock.Mock()
        llm.with_structured_output.return_value.ainvoke = mock.AsyncMock(return_value=pdc.PreguntasDelCoach())
        pdc.usar_proveedor(proveedor)
        try:
            with mock.patch("services.ai.model_config.ModelSelector.get_llm", return_value=llm):
                import asyncio
                asyncio.run(pdc.preguntas_del_coach_node({"current_date": {"date": "2026-09-14"}}))
        finally:
            pdc.usar_proveedor(None)
        proveedor.sin_preguntas.assert_called_once()
        proveedor.preguntar.assert_not_called()

    def test_un_proveedor_sin_aviso_sigue_funcionando(self):
        from services.ai.preguntas_del_coach import _avisar_sin_preguntas

        class SoloPregunta:
            def preguntar(self, preguntas):
                return []

        _avisar_sin_preguntas(SoloPregunta())   # no revienta


class LasPulsacionesDelBotonNoSonRepeticiones(unittest.TestCase):
    """La sesión real del 2026-09-09 de la atleta 3: 7 × 400 m (lo corrigió su
    entrenador, que la vio abortar). El bloque de hábitos decía
    "10 x 2'08" a 4:56-21:04/km" porque contaba tres pulsaciones de botón."""

    def _tramo(self, intensidad, segundos, metros, velocidad=None, pico=None):
        t = {"intensity": intensidad, "duration_s": segundos, "distance_m": metros}
        if velocidad:
            t["averageSpeed_ms"] = velocidad
        if pico:
            t["maxHR_bpm"] = pico
        return t

    def test_siete_repeticiones_son_siete(self):
        from datetime import date
        from types import SimpleNamespace

        from services.ai.training_habits import leer_estructura

        velocidades = [2.997, 2.946, 3.23, 3.376, 2.607, 2.937, 3.039]   # los 7 ACTIVE reales
        tramos = [self._tramo("WARMUP", 494.8, 1000, 2.021)]
        for v in velocidades:
            tramos += [self._tramo("ACTIVE", 130, 400, v, 180), self._tramo("RECOVERY", 180, 260, 1.5)]
        tramos += [self._tramo("ACTIVE", 1.2, 0.9, 0.79) for _ in range(3)]   # pulsaciones
        sesion = leer_estructura(SimpleNamespace(laps=tramos, activity_name="Repeticiones 5K"),
                                 date(2026, 9, 9), "run")
        self.assertEqual(sesion.repeticiones, 7)
        self.assertNotIn("21:", sesion.ritmo)


class UnRetratoDelLectorViejoNoSeReusa(unittest.TestCase):
    """La semana regenerada de la atleta 3 siguió diciendo "10 x 2'08"" porque
    reusó el retrato de hábitos que el macrociclo guardó con el lector viejo."""

    def test_el_retrato_sin_version_se_descarta(self):
        import json
        import tempfile

        from services.ai.training_habits import cargar_habitos

        with tempfile.TemporaryDirectory() as d:
            Path(d, "training_habits.json").write_text(json.dumps({
                "hasta": "2026-09-14", "bloque": "10 x 2'08\" a 4:56-21:04/km"}), encoding="utf-8")
            from datetime import date
            self.assertIsNone(cargar_habitos(d, hoy=date(2026, 9, 14)))


class UnaMolestiaLeveSeNombraPocasVeces(unittest.TestCase):
    def test_dos_o_tres_sesiones_y_no_en_lo_suave(self):
        """Semana regenerada del atleta 2: psoas 1/10 nombrado en 7 de 10 sesiones."""
        from services.ai.niggle_constraints import render_niggle_constraints

        bloque = render_niggle_constraints([{"source": "check-in", "location": "Psoas izquierdo", "pain": 1}])
        self.assertIn("dos o tres sesiones", bloque)
        self.assertIn("fuerza ligera no hace falta nombrarla", bloque)


class ElVolumenMedidoNoSeLlamaAutorreporte(unittest.TestCase):
    def test_medido_contra_medido_no_hay_nota(self):
        """5,9 h medidas en 12 semanas no son "lo que reportaste", y los 7 días de
        viaje no son "el dato real" contra el que corregirlas."""
        from web_app.server import build_volume_reconciliation_note

        perfil = {"current_weekly_volume_hours": 5.9, "garmin_autofilled_fields": ["current_weekly_volume_hours"]}
        self.assertIsNone(build_volume_reconciliation_note(perfil, object()))

    def test_la_nota_vieja_ya_no_existe(self):
        fuente = Path(__file__).resolve().parents[1].joinpath("web_app", "server.py").read_text(encoding="utf-8")
        self.assertNotIn("Reportaste {self_reported}", fuente)
        self.assertNotIn("TRUST THE REAL DATA", fuente)


class UnaCarreraDentroDeSeisDiasNoSeMideAlMes(unittest.TestCase):
    def test_el_5k_de_monterrico(self):
        from services.ai.goal_realism import _ritmo_mensual

        texto = _ritmo_mensual({"weeks_available": 0.9, "required_improvement_pct_per_month": 21.49})
        self.assertNotIn("21.49", texto)
        self.assertIn("6 días", texto)

    def test_con_meses_por_delante_si(self):
        from services.ai.goal_realism import _ritmo_mensual

        self.assertEqual(_ritmo_mensual({"weeks_available": 30.7, "required_improvement_pct_per_month": 0.83}),
                         "(~0.83% por mes)")


class LosPromptsNoEmpujanEnUnaSolaDireccion(unittest.TestCase):
    def test_los_expertos_no_hablan_de_12_24_semanas(self):
        from services.ai.langgraph.nodes.prompt_components import get_workflow_context

        for nodo in ("metrics_expert_node", "physiology_expert_node", "activity_expert_node"):
            with self.subTest(nodo=nodo):
                fuente = Path(__file__).resolve().parents[1].joinpath(
                    "services", "ai", "langgraph", "nodes", f"{nodo}.py").read_text(encoding="utf-8")
                self.assertNotIn("12-24 Weeks", fuente)
        for rol in ("metrics", "season_planner"):
            self.assertNotIn("12-24", get_workflow_context(rol))

    def test_la_proporcion_va_en_las_dos_direcciones(self):
        from services.ai.langgraph.nodes.prompt_components import get_workflow_context

        texto = get_workflow_context("physiology")
        self.assertIn("Room to", texto)
        self.assertIn("never the main risk", texto)

    def test_metricas_no_busca_rampas_seguras(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.metrics_expert_node").METRICS_USER_PROMPT
        self.assertNotIn("safe ramp rates", prompt)
        self.assertIn("Room to grow is as much a finding", prompt)

    def test_fisiologia_no_es_un_semaforo_limitador_ni_usa_cortes_de_poblacion(self):
        nodo = importlib.import_module("services.ai.langgraph.nodes.physiology_expert_node")
        self.assertNotIn("Traffic Light", nodo.PHYSIOLOGY_USER_PROMPT)
        self.assertNotIn("crash risks", nodo.PHYSIOLOGY_USER_PROMPT)
        self.assertIn("never a population cut-off", nodo.PHYSIOLOGY_USER_PROMPT)
        # Las dos reglas que el experto escribió para la atleta 3 el 2026-09-14.
        self.assertIn("noches de menos de 6 h", nodo.PHYSIOLOGY_READING_RULES)

    def test_una_molestia_pasada_no_es_una_restriccion_medica(self):
        prompt = importlib.import_module("services.ai.langgraph.nodes.season_planner_node").SEASON_PLANNER_USER_PROMPT
        self.assertIn("already gone is history", prompt)

    def test_el_chat_no_da_por_hecha_la_descarga_cada_cuatro_semanas(self):
        from services.ai.coach_chat import SYSTEM_PROMPT

        self.assertNotIn("cada cuatro semanas", SYSTEM_PROMPT)
        self.assertNotIn("propenso", SYSTEM_PROMPT)

    def test_el_cuaderno_no_eterniza_una_molestia_de_pasada(self):
        from services.ai.notebook import EXTRACTION_SYSTEM_PROMPT

        self.assertNotIn("que menciona de pasada", EXTRACTION_SYSTEM_PROMPT)
        self.assertIn("ya se le pasó", EXTRACTION_SYSTEM_PROMPT)


if __name__ == "__main__":
    unittest.main()
