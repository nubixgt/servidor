"""Tests for Plan Maestro v5 Track 10: the declared health record reaches the
coach instead of being collected and thrown away.

The defect was not that the medical gate failed to block. It was that the whole
module was a form whose output was discarded: `current_injuries` (location,
laterality, onset, pain 0-10, aggravating factors), the seven screening
answers, and the intake report were all persisted on the profile and NO agent
ever read any of them -- `build_custom_planning_context` looked only at the
free-text `limitations` field. An athlete could declare an irritated knee and
be prescribed hill repeats.

The most dangerous bug found while wiring it is pinned below: the screening
answers are stored under bare keys `q1`..`q7` (app.js PAR_Q_KEYS) even though
the DOM ids are `parq_q1`. Reading the wrong key returns an empty dict and
reports "no flags" for an athlete who ticked every box -- silently reproducing
the exact failure this track exists to fix.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.health_context import (  # noqa: E402
    active_injuries,
    health_flags,
    render_health_block,
)


def _profile(**overrides) -> dict:
    base = {
        "current_injuries": [],
        "past_injuries_surgeries": "",
        "chronic_conditions": "",
        "medications": "",
        "par_q_flags": {},
        "doctor_performance_report": "",
    }
    base.update(overrides)
    return base


class InjuriesArriveWithTheirRealDetail(unittest.TestCase):
    def test_the_structured_fields_survive_into_the_prompt(self):
        block = render_health_block(_profile(current_injuries=[{
            "location": "Rodilla", "laterality": "izquierda", "pain_scale": 3,
            "onset": "junio 2026", "aggravating_factors": "bajadas largas",
            "treatment": "fisioterapia semanal",
        }]))
        for fragment in ("Rodilla", "lado izquierda", "dolor 3/10", "desde junio 2026",
                         "bajadas largas", "fisioterapia semanal"):
            with self.subTest(fragment=fragment):
                self.assertIn(fragment, block)

    def test_an_injury_with_no_location_is_not_counted(self):
        """Empty rows left behind by the "+ Agregar Lesión" button must not
        become a phantom constraint on the plan."""
        self.assertEqual(active_injuries(_profile(current_injuries=[{"location": "  "}])), [])

    def test_an_injury_is_framed_as_a_change_of_stimulus_not_as_rest(self):
        """Same principle the deterministic fallbacks follow: shorten or swap
        the stimulus, never cancel it."""
        block = render_health_block(_profile(current_injuries=[{"location": "Tobillo"}]))
        self.assertIn("no significa que el atleta deba descansar", block)


class TheBlockRefusesToActLikeAClinician(unittest.TestCase):
    def test_it_says_plainly_that_this_is_self_declared_history(self):
        block = render_health_block(_profile(chronic_conditions="Asma leve"))
        self.assertIn("autodeclarado", block)
        self.assertIn("no un diagnóstico", block)

    def test_it_forbids_diagnosing_or_prescribing_treatment(self):
        block = render_health_block(_profile(chronic_conditions="Asma leve"))
        self.assertIn("nunca para diagnosticar", block)
        self.assertIn("prescribir tratamiento", block)

    def test_medication_redirects_to_pace_and_effort_rather_than_hr_zones(self):
        """Beta blockers and some antihypertensives blunt the HR response, so
        an HR-zone prescription silently stops meaning what it says."""
        block = render_health_block(_profile(medications="Bisoprolol"))
        self.assertIn("esfuerzo percibido", block)


class CostAndEdgeCases(unittest.TestCase):
    def test_an_empty_record_costs_no_prompt_tokens(self):
        self.assertEqual(render_health_block(_profile()), "")
        self.assertEqual(render_health_block({}), "")

    def test_flags_summarise_the_record_for_the_ui_and_the_archive(self):
        flags = health_flags(_profile(current_injuries=[
            {"location": "Rodilla"},
            {"location": "Tobillo"},
            {"location": "Hombro", "status": "resuelta"},
        ]))
        self.assertEqual(flags["injury_locations"], ["Rodilla", "Tobillo"])
        self.assertEqual(flags["active_injury_count"], 2)
        self.assertEqual(flags["resolved_injury_count"], 1)


class LaFichaClinicaYaNoExiste(unittest.TestCase):
    """Lo que la v1.2 retiro del bloque de salud, y por que.

    Aqui habia siete pruebas fijando el tamizaje de aptitud fisica (PAR-Q), la
    casilla "me considero propenso", el informe del asistente de salud y su
    tope, y el descargo medico. Las cuatro cosas eran canales por los que se le
    preguntaba al atleta por lesiones, y **el exceso -- no los prompts -- era
    la causa del sesgo que la beta reporto**: auditados uno a uno, el
    planificador de temporada no menciona lesion ni una vez.

    Lo que se fija ahora es la AUSENCIA: un perfil viejo que todavia traiga
    esos campos no puede volver a colarlos en el prompt. Ver Database/017.
    """

    HEREDADO = {
        "par_q_flags": {"q2": True, "q5": True},
        "injury_prone": True,
        "doctor_performance_report": "INFORME DEL ASISTENTE. " * 200,
        "health_disclaimer_accepted": True,
        "health_disclaimer_accepted_at": "2026-08-25T10:00:00",
        "last_medical_evaluation": "2026-08-25T10:00:00",
    }

    def test_un_perfil_heredado_con_todo_eso_no_produce_bloque(self):
        """Ninguno de los seis campos justifica ya un bloque de salud: una
        ficha que solo los tuviera cuesta cero tokens."""
        self.assertEqual(render_health_block(dict(self.HEREDADO)), "")

    def test_el_informe_del_asistente_no_entra(self):
        bloque = render_health_block({**self.HEREDADO,
                                      "current_injuries": [{"location": "Rodilla"}]})
        self.assertNotIn("INFORME DEL ASISTENTE", bloque)
        self.assertNotIn("asistente de salud", bloque.lower())

    def test_el_tamizaje_no_entra(self):
        bloque = render_health_block({**self.HEREDADO,
                                      "current_injuries": [{"location": "Rodilla"}]})
        self.assertNotIn("tamizaje", bloque.lower())
        self.assertNotIn("dolor en el pecho", bloque.lower())

    def test_la_casilla_de_propenso_no_entra(self):
        bloque = render_health_block({**self.HEREDADO,
                                      "current_injuries": [{"location": "Rodilla"}]})
        self.assertNotIn("propenso", bloque.lower())

    def test_lo_que_SI_sigue_llegando(self):
        """Historial declarado que cambia la modalidad y la carga de verdad.
        Quitar la ficha clinica no puede llevarse esto por delante."""
        bloque = render_health_block({
            "current_injuries": [{"location": "Rodilla", "pain_scale": 4}],
            "chronic_conditions": "Asma leve",
            "medications": "Bisoprolol",
            "past_injuries_surgeries": "Fractura de escafoides 2019",
            "nutrition_notes": "El nutricionista me pidio 3 sesiones de fuerza",
        })
        for esperado in ("Rodilla", "Asma leve", "Bisoprolol", "escafoides", "3 sesiones de fuerza"):
            with self.subTest(esperado=esperado):
                self.assertIn(esperado, bloque)


class ItReachesThePlanningContext(unittest.TestCase):
    def test_the_block_is_appended_to_what_the_planners_actually_read(self):
        """The cable itself: before this, build_custom_planning_context read
        only `limitations` and every structured field died on the profile."""
        from web_app.server import build_custom_planning_context

        context = build_custom_planning_context({
            "sports_preference": "run", "available_days": ["Lunes"], "available_hours": 6,
            "current_injuries": [{"location": "Rodilla", "pain_scale": 3}],
            "par_q_flags": {"q5": True},
        })
        self.assertIn("Antecedentes de salud declarados", context)
        self.assertIn("Rodilla", context)
        self.assertIn("dolor 3/10", context)

    def test_a_healthy_athlete_adds_nothing_to_the_context(self):
        from web_app.server import build_custom_planning_context

        context = build_custom_planning_context({
            "sports_preference": "run", "available_days": ["Lunes"], "available_hours": 6,
        })
        self.assertNotIn("Antecedentes de salud", context)




class LaNutricionLlegaAlCerebro(unittest.TestCase):
    """El campo que la app pedía, guardaba, y no leía nadie.

    `nutrition_notes` sólo lo miraba el asistente de salud. Ni el planificador
    de temporada, ni el semanal, ni los tres expertos, ni el coach. Un atleta
    real escribió ahí la frase más decisiva de su ficha:

        "tengo 25% de grasa, y debo tenerla en 15%. El me solicitó realizar
         3 sesiones de entrenamiento de fuerza a la semana."

    Su plan salió con la fuerza reducida a prehab. No fue el modelo: lo único
    que llegó a ver de su cuerpo fueron sus cuatro lesiones pasadas, así que
    escribió lo coherente con eso.
    """

    NOTA = ("Tengo 25% de grasa y debo tenerla en 15%. El nutricionista me pidió "
            "3 sesiones de fuerza a la semana.")

    def test_la_nota_de_nutricion_aparece_literal_en_el_bloque(self):
        bloque = render_health_block({"nutrition_notes": self.NOTA})
        self.assertIn("25% de grasa", bloque)
        self.assertIn("3 sesiones de fuerza", bloque)

    def test_la_nota_sola_ya_justifica_el_bloque(self):
        """Sin lesiones ni medicación, la nota tiene que abrir la sección igual.

        Antes el bloque entero se devolvía vacío si no había nada más, y con él
        se iba la nota.
        """
        self.assertTrue(render_health_block({"nutrition_notes": self.NOTA}).strip())

    def test_una_ficha_del_todo_vacia_sigue_sin_gastar_tokens(self):
        self.assertEqual(render_health_block({}), "")
        self.assertEqual(render_health_block({"nutrition_notes": "   "}), "")

    def test_no_se_le_pide_al_entrenador_que_haga_de_nutricionista(self):
        bajo = render_health_block({"nutrition_notes": self.NOTA}).lower()
        self.assertIn("no prescribas dieta", bajo)

    def test_dice_que_no_puede_desaparecer_sin_mencion(self):
        """Es la diferencia entre un dato y una restricción. Un número de
        sesiones pedido por un profesional no se descarta en silencio."""
        self.assertIn("sin mención", render_health_block({"nutrition_notes": self.NOTA}))


