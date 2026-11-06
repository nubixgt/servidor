"""Tests for Plan Maestro v5 Track 2: the season planner stops planning blind.

Two defects, one cable each.

**H1** -- the season planner received the athlete's name, the date, the
competitions and expert prose. Nothing else. It set `weekly_hours_target` per
phase having never seen that this athlete trains ~3.6-6 h/week while their
profile declares 14.25 h available, so every phase was framed against a number
nobody had measured.

**H2** -- the athlete's framing: a recreational runner doing 5K in 28 minutes
who asks for the Olympics should get judgement, not compliance. Garmin's race
predictions were already extracted and already rendered into the prescription
card, and each competition already carried a structured `target_time`. But the
prescription was only ever injected into the WEEKLY planner, so the one agent
that evaluates season goals evaluated them blind.

Real numbers from this athlete's account are used throughout: 5K 27:44,
10K 59:20, HM 2:14:24, M 4:56:08.

Run with:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import unittest
from datetime import date, timedelta
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.goal_realism import (  # noqa: E402
    assess_goal,
    assess_goals,
    build_goal_evidence,
    competitions_missing_distance,
    format_seconds,
    parse_time_to_seconds,
    render_goal_realism_block,
)

# The athlete's real Garmin predictions, in the shape prescription_to_state
# now carries them.
REAL_PREDICTIONS = [
    {"distance_key": "5K", "distance_m": 5000.0, "time_seconds": 27 * 60 + 44},
    {"distance_key": "10K", "distance_m": 10000.0, "time_seconds": 59 * 60 + 20},
    {"distance_key": "half_marathon", "distance_m": 21097.0, "time_seconds": 2 * 3600 + 14 * 60 + 24},
    {"distance_key": "marathon", "distance_m": 42195.0, "time_seconds": 4 * 3600 + 56 * 60 + 8},
]

TODAY = date(2026, 9, 1)


def _comp(distance="5K", target="26:00", weeks=14, **extra):
    comp = {
        "name": "Carrera de prueba",
        "date": (TODAY + timedelta(weeks=weeks)).isoformat(),
        "priority": "A",
        "distance": distance,
        "target_time": target,
    }
    comp.update(extra)
    return comp


class ParsingATargetTime(unittest.TestCase):
    def test_the_three_shapes_an_athlete_actually_types(self):
        self.assertEqual(parse_time_to_seconds("1:45:00"), 6300)
        self.assertEqual(parse_time_to_seconds("26:00"), 1560)
        self.assertEqual(parse_time_to_seconds("04:56:08"), 17768)

    def test_unparseable_input_reads_as_not_declared_never_as_a_guess(self):
        """A number we invented from a partial match would frame the whole
        macrocycle against a goal the athlete never set."""
        for bad in ("sub 26", "", None, "26", "1h45", "26:00:00:00", "abc", "26:75"):
            with self.subTest(value=bad):
                self.assertIsNone(parse_time_to_seconds(bad))

    def test_round_trips_back_to_a_clock(self):
        self.assertEqual(format_seconds(6300), "1:45:00")
        self.assertEqual(format_seconds(1560), "26:00")
        self.assertEqual(format_seconds(None), "—")


class TheOlympicsCase(unittest.TestCase):
    def test_a_wildly_faster_goal_is_flagged_as_out_of_range(self):
        """5K 27:44 predicted, 15:00 wanted in 14 weeks. A real coach does not
        write a plan for this without saying something."""
        assessment = assess_goal(_comp(distance="5K", target="15:00"), REAL_PREDICTIONS, TODAY)
        self.assertEqual(assessment["verdict"], "fuera_de_rango")
        self.assertGreater(assessment["required_improvement_pct"], 40)

    def test_a_modest_goal_reads_as_reachable(self):
        assessment = assess_goal(_comp(distance="5K", target="27:00"), REAL_PREDICTIONS, TODAY)
        self.assertEqual(assessment["verdict"], "alcanzable")

    def test_a_target_slower_than_current_fitness_is_called_out_as_conservative(self):
        """Framing a goal is not only about talking someone down. An athlete
        aiming below their own predicted time should be told so."""
        assessment = assess_goal(_comp(distance="5K", target="31:00"), REAL_PREDICTIONS, TODAY)
        self.assertEqual(assessment["verdict"], "conservador")
        self.assertLess(assessment["required_improvement_pct"], 0)

    def test_the_same_goal_gets_harsher_the_less_time_there_is(self):
        """22% faster is a different proposition in 8 weeks than in 52."""
        soon = assess_goal(_comp(distance="half_marathon", target="1:45:00", weeks=8), REAL_PREDICTIONS, TODAY)
        later = assess_goal(_comp(distance="half_marathon", target="1:45:00", weeks=52), REAL_PREDICTIONS, TODAY)
        self.assertEqual(soon["verdict"], "fuera_de_rango")
        self.assertNotEqual(later["verdict"], "fuera_de_rango")

    def test_the_required_improvement_is_reported_per_month_too(self):
        assessment = assess_goal(_comp(distance="10K", target="55:00"), REAL_PREDICTIONS, TODAY)
        self.assertIsNotNone(assessment["required_improvement_pct_per_month"])
        self.assertGreater(assessment["required_improvement_pct_per_month"], 0)


class TheDistanceIsReadOutOfWhatTheAthleteAlreadyWrote(unittest.TestCase):
    """v6 Track 4 REVERSES a v5 decision, on evidence.

    v5 refused to infer the distance from a race's name, reasoning that a
    wrong guess would frame the goal against the wrong reference -- the same
    keyword-guessing pattern v4 removed from the validator.

    The first real generation showed the cost of that caution: every
    competition on this athlete's profile predates the `distance` dropdown, so
    all three came back "sin_referencia" and the whole module never ran. A
    5K target of 25:00 -- 5:00/km, faster than the athlete's VO2max interval
    band -- was planned around as "ambitious" because nothing compared it to
    anything.

    The two cases are not the same risk. In the validator, a misread label
    picked a PACE BAND, and a wrong band is a dangerous workout. Here it picks
    a REFERENCE TIME for a comparison that the block explicitly labels as
    inferred and explicitly invites the agent to reject. So the inference is
    allowed, and the safety comes from three properties pinned below: a
    declared distance always wins, the ordering trap is handled, and nothing
    is matched that is not really there.
    """

    def test_a_declared_distance_always_wins(self):
        assessment = assess_goal(
            _comp(distance="10K", target="55:00", name="Maratón de la Ciudad"), REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual(assessment["distance_key"], "10K")
        self.assertEqual(assessment["distance_source"], "declarada")

    def test_the_real_case_a_5k_named_in_the_race_title(self):
        """'5k Monterrico', target 25:00 -- the goal that got planned around
        without ever being compared to anything."""
        assessment = assess_goal(
            _comp(distance="", target="25:00", name="5k Monterrico"), REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual(assessment["distance_key"], "5K")
        self.assertEqual(assessment["distance_source"], "deducida")
        self.assertIsNotNone(assessment["predicted_seconds"])

    def test_media_maraton_is_not_read_as_maraton(self):
        """The ordering trap this codebase has already shipped once: 'media
        maratón' contains 'maratón'. Reading it as a marathon would compare a
        1:45 target against a 4:56 prediction and call it miraculous."""
        assessment = assess_goal(
            _comp(distance="", target="1:45:00", name="Media Maratón de la Ciudad"),
            REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual(assessment["distance_key"], "half_marathon")

    def test_21k_and_42k_are_read_as_the_races_they_are(self):
        self.assertEqual(assess_goal(_comp(distance="", target="1:45:00", name="21K Antigua"),
                                     REAL_PREDICTIONS, TODAY)["distance_key"], "half_marathon")
        self.assertEqual(assess_goal(_comp(distance="", target="4:30:00", name="42K Ciudad"),
                                     REAL_PREDICTIONS, TODAY)["distance_key"], "marathon")

    def test_las_distancias_de_las_DEMAS_disciplinas_tambien_se_leen(self):
        """v7. Estos tres nombres se leian como "no identificable", y no era
        una regla: era el catalogo, que solo tenia las cuatro distancias de
        asfalto. Un ultra, una carrera de 15K y un medio Ironman son distancias
        perfectamente identificables -- lo que no tienen es una prediccion de
        Garmin con la que compararse, que es un problema distinto y que el
        bloque ya distingue.
        """
        # El motivo de no tener referencia NO es "no identificada". El ultra de
        # montaña dice además por qué: con desnivel, ni una predicción en llano
        # serviría (2026-09-14, ver test_auditoria_del_cerebro_y_hugo).
        esperado = {"Ultra 50K del Volcan": ("trail_ultra_50", "desnivel"),
                    "Vuelta 15K nocturna": ("15K", "sin_prediccion"),
                    "Ironman 70.3 Cozumel": ("tri_70_3", "sin_prediccion")}
        for name, (clave, motivo) in esperado.items():
            with self.subTest(name=name):
                assessment = assess_goal(_comp(distance="", target="6:00:00", name=name),
                                         REAL_PREDICTIONS, TODAY)
                self.assertEqual(assessment["distance_key"], clave)
                self.assertEqual(assessment["verdict"], "sin_referencia")
                self.assertEqual(assessment["missing_reason"], motivo)

    def test_el_NOMBRE_de_la_carrera_gana_al_tipo_generico(self):
        """La trampa de orden de este modulo, ahora entre CAMPOS.

        El perfil real del atleta tiene "5k Monterrico" con
        `race_type: "Olympic"` -- heredado de un desplegable viejo que nunca
        eligio a conciencia. Juntando los campos en un solo texto, "Olympic"
        casaba con el triatlon olimpico antes de que "5k" llegara a mirarse, y
        su carrera de cinco kilometros se habria evaluado como un triatlon.
        """
        assessment = assess_goal(
            _comp(distance="", target="25:00", name="5k Monterrico", race_type="Olympic"),
            REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual(assessment["distance_key"], "5K")

    def test_un_triatlon_se_lee_como_triatlon(self):
        """El caso que disparo todo esto: el atleta declaro "Triatlon Standard"
        con meta de 2:30:00 y el planificador de temporada recibio
        "sin_referencia" para su carrera principal."""
        assessment = assess_goal(
            _comp(distance="", target="2:30:00", name="Triatlon Standard", race_type="Olympic"),
            REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual(assessment["distance_key"], "tri_olympic")

    def test_an_inferred_distance_is_flagged_in_the_block_so_it_can_be_corrected(self):
        block = render_goal_realism_block(
            assess_goals([_comp(distance="", target="25:00", name="5k Monterrico")], REAL_PREDICTIONS, TODAY)
        )
        self.assertIn("se dedujo", block)
        self.assertIn("Si no cuadra, dilo", block)

    def test_an_unidentifiable_distance_asks_the_athlete_to_fix_it(self):
        """Two different problems used to render identically. 'We cannot tell
        what distance this is' takes the athlete ten seconds to fix; 'Garmin
        does not predict this distance' is not fixable at all."""
        assessment = assess_goal(
            _comp(distance="", target="6:00:00", name="Copa de la amistad", race_type=""),
            REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual(assessment["verdict"], "sin_referencia")
        self.assertEqual(assessment["missing_reason"], "distancia_no_identificada")
        block = render_goal_realism_block([assessment])
        self.assertIn("No se pudo identificar la distancia", block)
        self.assertIn("que la ponga en su perfil", block)

    def test_a_distance_garmin_does_not_predict_is_a_different_message(self):
        assessment = assess_goal(_comp(distance="ultra_50k", target="6:00:00"), REAL_PREDICTIONS, TODAY)
        self.assertEqual(assessment["verdict"], "sin_referencia")
        block = render_goal_realism_block([assessment])
        self.assertIn("no publica una predicción para esta distancia", block)

    def test_a_missing_prediction_set_does_not_crash_the_assessment(self):
        assessment = assess_goal(_comp(), [], TODAY)
        self.assertEqual(assessment["verdict"], "sin_referencia")


class NothingToAssessProducesNothing(unittest.TestCase):
    def test_a_competition_with_no_target_time_is_skipped(self):
        self.assertIsNone(assess_goal(_comp(target=""), REAL_PREDICTIONS, TODAY))

    def test_a_race_already_run_is_skipped(self):
        self.assertIsNone(assess_goal(_comp(weeks=-3), REAL_PREDICTIONS, TODAY))

    def test_an_athlete_with_no_declared_goals_costs_no_prompt_tokens(self):
        self.assertEqual(render_goal_realism_block([]), "")
        self.assertEqual(assess_goals([], REAL_PREDICTIONS, TODAY), [])

    def test_goals_come_back_soonest_first(self):
        assessments = assess_goals(
            [_comp(weeks=20, name="Lejana"), _comp(weeks=4, name="Cercana")],
            REAL_PREDICTIONS, TODAY,
        )
        self.assertEqual([a["name"] for a in assessments], ["Cercana", "Lejana"])


class TheBlockFramesRatherThanRejects(unittest.TestCase):
    def setUp(self):
        self.block = render_goal_realism_block(
            assess_goals([_comp(distance="half_marathon", target="1:45:00")], REAL_PREDICTIONS, TODAY)
        )

    def test_it_says_up_front_that_nothing_is_rejected(self):
        self.assertIn("no un veredicto", self.block)
        self.assertIn("Ninguna meta está rechazada", self.block)

    def test_it_shows_both_numbers_so_the_gap_is_visible(self):
        self.assertIn("2:14:24", self.block)
        self.assertIn("1:45:00", self.block)

    def test_the_reference_rate_is_labelled_as_coarse_with_its_exceptions(self):
        """The one number here that could become a false authority. It has to
        arrive with its own caveats attached."""
        self.assertIn("orientativa y gruesa", self.block)
        self.assertIn("principiante", self.block)
        self.assertIn("bien entrenado", self.block)
        self.assertIn("vuelve de un parón", self.block)

    def test_the_model_is_told_it_may_disagree_with_the_framing(self):
        self.assertIn("Puedes discrepar", self.block)

    def test_it_asks_for_a_reachable_alternative_not_just_a_refusal(self):
        """What the athlete actually asked for: a real coach does not accept
        any goal, and does not merely say no -- they offer the one that works."""
        self.assertIn("propón un objetivo alcanzable", self.block)
        self.assertIn("ni se limita a decir que no", self.block)

    def test_the_declared_goal_does_not_by_itself_drive_the_plan(self):
        self.assertIn("no cambia el plan por sí sola", self.block)
        self.assertIn("puede absorber", self.block)


class TheSeasonPlannerNowReceivesTheRealNumbers(unittest.TestCase):
    def setUp(self):
        self.module = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")

    def test_the_prompt_has_a_slot_for_each_previously_missing_input(self):
        prompt = self.module.SEASON_PLANNER_USER_PROMPT
        for placeholder in ("{prescription_card}", "{volume_baseline}", "{adherence}",
                            "{goal_realism}", "{planning_context}"):
            with self.subTest(placeholder=placeholder):
                self.assertIn(placeholder, prompt)

    def test_the_prompt_still_formats_with_every_field_supplied(self):
        self.module.SEASON_PLANNER_USER_PROMPT.format(
            athlete_name="X", current_date="{}", competitions="[]", planning_context="",
            # 2026-09-24: la semana 1 del plan, dicha; antes el prompt decía "hoy" y contaba desde el lunes.
            inicio_del_macrociclo="2026-09-21",
            prescription_card="", volume_baseline="", load_state="", adherence="", goal_realism="",
            # Beta 2.1 (S7): para qué hace gimnasio el atleta, dicho por él. "" para
            # quien no contestó, y entonces el prompt no cambia ni un carácter
            # (tests/test_el_macrociclo_y_la_fuerza.py).
            fuerza_declarada="",
            # v7: el cuaderno del entrenador llega también al macrociclo, no
            # sólo a la semana. Que este test fallara al añadirlo es su
            # trabajo: un placeholder que el nodo pasa y el prompt no declara
            # sólo se manifiesta a mitad de una llamada real y pagada.
            coach_notebook="",
            # v7.3: el macrociclo ANTERIOR. El nodo recibia la adherencia --
            # que se le prescribio al atleta y que cumplio -- pero no el plan
            # del que salian esas prescripciones, asi que podia devolver a
            # base a alguien que iba por la semana 20 de construccion. Otra vez
            # este guardian avisando de un placeholder recien anadido.
            previous_season="",
            # v7: el expediente -- qué se le prescribió y cuánto cumplió,
            # semana a semana. Este guardián vuelve a hacer su trabajo.
            track_record="",
            # v1.2.2: cómo respondió a cada fase cerrada. Otra vez este guardián.
            phase_response="",
            # v7.2: los hábitos reales del atleta. En el macrociclo pesan aún
            # más que en la semana: reparte la proporción entre deportes para
            # dieciséis semanas, y hacerlo sobre las horas DECLARADAS libres en
            # vez de las que de verdad entrena reparte la temporada entre
            # deportes que no practica.
            training_habits="",
            metrics_insights="", activity_insights="", physiology_insights="",
        )

    def test_phase_targets_start_from_real_load_and_no_declared_hours_exist(self):
        """The H1 failure in one sentence: the planner was writing phase
        targets off `available_hours` (14.25 h) for an athlete whose real
        chronic load is ~3.6-6 h. The first fix called those hours a ceiling;
        since v1.2 they are not asked at all, so the prompt must not mention
        them -- and the anchor that remains is the REAL chronic volume."""
        prompt = self.module.SEASON_PLANNER_USER_PROMPT
        self.assertIn("anchored to what the athlete really absorbs today", prompt)
        self.assertNotIn("Available hours", prompt)

    def test_a_taper_is_not_averaged_into_the_phase_number(self):
        """2026-09-10: Fase 1 a 6.0 h con el 5k Monterrico en la semana 2, y la
        semana 1 recibio 6.0 h como objetivo. El numero de fase es de una
        semana NORMAL; la de carrera la baja el planificador semanal."""
        self.assertIn("NORMAL week of that phase", self.module.SEASON_PLANNER_USER_PROMPT)

    def test_deloads_are_placed_not_stamped_every_fourth_week(self):
        """El macrociclo real salio con descargas en 4, 8, 12, 16, 20 y 24: el
        patron que el prompt le daba por defecto, sin mirar el calendario."""
        prompt = self.module.SEASON_PLANNER_USER_PROMPT
        self.assertNotIn("default to a deload/recovery week every 4th week", prompt)
        self.assertIn("coaching decision, not a cadence", prompt)

    def test_the_volume_block_leads_with_real_chronic_load(self):
        """Hasta la v1.2 este bloque decia "Carga crónica: **3.6 h/semana** ...
        ancla de la progresión": llamaba carga a las HORAS. Ahora el ancla es
        la carga de Garmin, y las horas quedan como referencia de agenda.
        Numeros reales del atleta 2 el 2026-09-10: cronica 280, rango 224-420."""
        rendered = self.module._format_volume_baseline({
            "chronic_weekly_hours": 3.6, "last_7d_actual_hours": 2.1, "chronic_window_days": 28,
            "garmin_chronic_load": 280, "garmin_optimal_min": 224, "garmin_optimal_max": 420,
            "load_rates": {"run:suave": {"por_hora": 52, "n": 16}},
        })
        self.assertIn("Carga crónica semanal: 280", rendered)
        self.assertIn("224 y 420", rendered)
        self.assertIn("no un límite probado en este atleta", rendered)
        self.assertIn("referencia de agenda, no de carga: 3.6 h/semana", rendered)
        self.assertNotIn("techo", rendered)

    def test_hours_alone_are_never_called_load(self):
        """Sin carga de Garmin ni propia, las horas NO se presentan como ancla."""
        rendered = self.module._format_volume_baseline({"chronic_weekly_hours": 3.6})
        self.assertNotIn("ancla", rendered)

    def test_with_no_volume_data_it_refuses_to_invent_a_target(self):
        rendered = self.module._format_volume_baseline(None)
        self.assertIn("No inventes", rendered)

    def test_race_predictions_now_travel_in_prescription_state(self):
        """The missing wire itself: without this the season planner cannot
        see a single predicted time."""
        from services.ai.prescription import AthletePrescription, prescription_to_state
        self.assertIn("race_predictions", prescription_to_state(AthletePrescription()))


if __name__ == "__main__":
    unittest.main(verbosity=2)


# The athlete's real pace bands, derived from a threshold of 5:47/km.
REAL_PACE_BANDS = {
    "repeticiones": [292.0, 305.0],
    "vo2max": [312.0, 333.0],
    "umbral_tempo": [340.0, 355.0],
    "maraton": [413.0, 434.0],
    "facil": [434.0, 465.0],
    "recuperacion": [465.0, 500.0],
}


class ThePaceAGoalDemandsIsStatedAsAFact(unittest.TestCase):
    """The athlete's 5K target of 25:00 is 5:00/km. Their VO2max interval band
    is 5:12-5:33/km and their threshold is 5:47/km, so the goal asks them to
    hold five kilometres faster than they currently run three-minute intervals.

    That comparison is a FACT and it belongs in the prompt. What does NOT
    belong is a rule telling the agent what to conclude from it: how much
    weight it deserves depends on how good the underlying data is, on the
    athlete's history and on what they say about themselves -- and the agent
    has all of that, while a rule here would have a ratio."""

    def _five_k_goal(self):
        return assess_goal(_comp(distance="5K", target="25:00"), REAL_PREDICTIONS, TODAY,
                           pace_bands=REAL_PACE_BANDS)

    def test_the_required_pace_is_computed_from_the_official_distance(self):
        context = self._five_k_goal()["pace_context"]
        self.assertEqual(context["required_pace_clock"], "5:00")

    def test_it_says_where_the_pace_SITS_not_every_band_it_beats(self):
        """5:00/km sits between this athlete's repetition band (4:52-5:05) and
        their VO2max band (5:12-5:33). Listing every slower band it also beats
        is technically true and useless -- of course it is faster than their
        recovery jog."""
        context = self._five_k_goal()["pace_context"]
        self.assertEqual(context["band_just_faster"]["key"], "repeticiones")
        self.assertEqual(context["band_just_slower"]["key"], "vo2max")

    def test_a_reachable_goal_lands_among_the_bands_the_athlete_already_runs(self):
        """29:00 over 5K is 5:48/km -- essentially their threshold pace, which
        is a completely different picture from 5:00/km and reads as one."""
        context = assess_goal(_comp(distance="5K", target="29:00"), REAL_PREDICTIONS, TODAY,
                              pace_bands=REAL_PACE_BANDS)["pace_context"]
        self.assertEqual(context["required_pace_clock"], "5:48")
        self.assertEqual(context["band_just_faster"]["key"], "umbral_tempo")
        self.assertEqual(context["band_just_slower"]["key"], "maraton")

    def test_the_block_states_it_without_drawing_the_conclusion(self):
        block = render_goal_realism_block(
            assess_goals([_comp(distance="5K", target="25:00")], REAL_PREDICTIONS, TODAY,
                         pace_bands=REAL_PACE_BANDS),
            pace_labels={"repeticiones": "Repeticiones", "vo2max": "VO2max"},
        )
        self.assertIn("5:00/km", block)
        self.assertIn("cae **entre su banda de Repeticiones", block)
        self.assertIn("VO2max (5:12–5:33/km)", block)

    def test_no_verdict_is_forced_on_the_agent(self):
        """The v6 plan first proposed telling the season planner to call such a
        goal "physiologically unreachable". That is a deterministic verdict
        wearing a prompt's clothes: an athlete with three weeks of watch data,
        or one whose threshold was derived rather than measured, can be far
        better than their prediction says. The evidence goes in; the conclusion
        does not."""
        block = render_goal_realism_block(
            assess_goals([_comp(distance="5K", target="25:00")], REAL_PREDICTIONS, TODAY,
                         pace_bands=REAL_PACE_BANDS)
        ).lower()
        for forced in ("fisiológicamente inalcanzable", "imposible", "descarta la meta", "no aceptes"):
            with self.subTest(forced=forced):
                self.assertNotIn(forced, block)
        self.assertIn("juzga tú, con todo lo que tienes", block)

    def test_a_swim_goal_is_paced_per_100_m_and_not_against_running_bands(self):
        """José, 2026-10-22: su 400 m de natación en 6:40 llegaba al macrociclo y a la hoja de temporada como
        «16:40/km», y se comparaba con sus bandas de CARRERA. Lo que exige es 1:40/100 m."""
        assessment = assess_goal(_comp(distance="swim_400", target="6:40"), REAL_PREDICTIONS, TODAY,
                                 pace_bands=REAL_PACE_BANDS)
        context = assessment["pace_context"]
        self.assertEqual((context["required_pace_clock"], context["unit"]), ("1:40", "/100 m"))
        self.assertIsNone(context["band_just_faster"])
        self.assertIsNone(context["band_just_slower"])
        block = render_goal_realism_block([assessment])
        self.assertIn("**1:40/100 m**", block)
        self.assertNotIn("/km", block.split("1:40/100 m")[0][-60:])
        self.assertNotIn("16:40", block)

    def test_a_bike_goal_is_a_speed(self):
        """Una bici de 40 km en 1:20:00 son 30 km/h, no «2:00/km» contra las bandas de correr."""
        context = assess_goal(_comp(distance="bike_tt_40", target="1:20:00"), REAL_PREDICTIONS, TODAY,
                              pace_bands=REAL_PACE_BANDS)["pace_context"]
        self.assertEqual((context["required_pace_clock"], context["unit"]), ("30,0", " km/h"))
        self.assertIsNone(context["band_just_slower"])

    def test_a_goal_without_a_known_distance_gets_no_invented_pace(self):
        assessment = assess_goal(_comp(distance="ultra_50k", target="6:00:00"), REAL_PREDICTIONS, TODAY,
                                 pace_bands=REAL_PACE_BANDS)
        self.assertIsNone(assessment["pace_context"])


class _FakeActivity:
    def __init__(self, day):
        self.start_time = f"{day} 07:00:00"


class HowMuchTheDataIsWorthBelievingTravelsWithIt(unittest.TestCase):
    """The athlete's constraint, stated plainly: the AI decides, according to
    how much data there is, what level the athlete has, and what they declare
    about themselves. So the evidence goes into the prompt as evidence --
    never folded into a verdict."""

    def _evidence(self, **overrides):
        garmin = type("G", (), {"all_activities": [
            _FakeActivity("2026-08-01"), _FakeActivity("2026-08-20"), _FakeActivity("2026-08-25"),
        ]})()
        state = {
            "race_predictions": REAL_PREDICTIONS,
            "threshold_pace_source": "garmin_lt",
            "hr_zones_source": "garmin",
            "warnings": [],
        }
        state.update(overrides)
        return build_goal_evidence(state, garmin, {"training_age_level": "intermedio", "years_training": 3})

    def test_it_counts_the_activities_and_their_span(self):
        evidence = self._evidence()
        self.assertEqual(evidence["activity_count"], 3)
        self.assertEqual(evidence["first_activity"], "2026-08-01")
        self.assertEqual(evidence["last_activity"], "2026-08-25")

    def test_a_derived_threshold_is_distinguished_from_a_measured_one(self):
        block = render_goal_realism_block(
            assess_goals([_comp()], REAL_PREDICTIONS, TODAY),
            evidence=self._evidence(threshold_pace_source="race_prediction"),
        )
        self.assertIn("derivado de una predicción de carrera, no medido", block)

    def test_zones_from_a_formula_are_distinguished_from_the_watchs_own(self):
        block = render_goal_realism_block(
            assess_goals([_comp()], REAL_PREDICTIONS, TODAY),
            evidence=self._evidence(hr_zones_source="formula"),
        )
        self.assertIn("calculadas con una fórmula", block)

    def test_an_athlete_with_no_predictions_at_all_is_told_why(self):
        """A swimmer or a cyclist will never have them: Garmin's race
        predictions are running-only. Neither will someone who just got the
        watch. Those are different situations and both matter."""
        block = render_goal_realism_block(
            assess_goals([_comp()], REAL_PREDICTIONS, TODAY),
            evidence=self._evidence(race_predictions=[]),
        )
        self.assertIn("no publica ninguna predicción de carrera", block)
        self.assertIn("nadador o un ciclista", block)

    def test_what_the_athlete_says_about_themselves_is_included(self):
        block = render_goal_realism_block(
            assess_goals([_comp()], REAL_PREDICTIONS, TODAY), evidence=self._evidence(),
        )
        self.assertIn("intermedio", block)
        self.assertIn("3 año(s) entrenando", block)

    def test_prescription_warnings_travel_too(self):
        block = render_goal_realism_block(
            assess_goals([_comp()], REAL_PREDICTIONS, TODAY),
            evidence=self._evidence(warnings=["El umbral guardado no cuadra con las predicciones."]),
        )
        self.assertIn("no cuadra con las predicciones", block)

    def test_the_agent_is_told_to_weigh_fidelity_before_the_numbers(self):
        block = render_goal_realism_block(
            assess_goals([_comp()], REAL_PREDICTIONS, TODAY), evidence=self._evidence(),
        )
        self.assertIn("Pesa la fidelidad de los datos antes que los datos", block)
        self.assertIn("razona con más margen", block)

    def test_no_evidence_available_still_renders_the_goals(self):
        block = render_goal_realism_block(assess_goals([_comp()], REAL_PREDICTIONS, TODAY), evidence=None)
        self.assertIn("Meta declarada", block)
        self.assertNotIn("Con cuántos datos", block)

    def test_it_survives_an_athlete_with_no_garmin_data_at_all(self):
        evidence = build_goal_evidence({}, None, {})
        self.assertEqual(evidence["activity_count"], 0)
        self.assertEqual(evidence["threshold_pace_source"], "none")


class TheAthleteIsToldWhatToFix(unittest.TestCase):
    def test_a_race_with_a_target_but_no_workable_distance_is_listed(self):
        missing = competitions_missing_distance([
            _comp(distance="", target="6:00:00", name="Copa de la amistad"),
            _comp(distance="", target="25:00", name="5k Monterrico"),
            _comp(distance="", target="6:00:00", name="Travesia del lago"),  # v7: aguas abiertas
            _comp(distance="10K", target="55:00", name="Vuelta"),
            _comp(distance="", target="", name="Sin meta declarada"),
        ])
        self.assertEqual(missing, ["Copa de la amistad"])

    def test_nothing_declared_means_nothing_to_fix(self):
        self.assertEqual(competitions_missing_distance([]), [])
        self.assertEqual(competitions_missing_distance(None), [])
