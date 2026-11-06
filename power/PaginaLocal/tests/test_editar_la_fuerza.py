"""Editar la fuerza de una semana, añadir o quitar un entreno propio y lo que eso manda al reloj (2026-10-23).

También: la huella de una sesión que el atleta cambió, lo que sobra al enviar sólo unas fechas, y que el
estilo, el material o una nota de natación no borren el texto del coach.

Decisión del responsable: «si lo edita en su semana, sólo se edita en la semana». El bloque de la
prueba es la Pierna A real del atleta 2 del lunes 26-oct-2026 (2 de sus 4 grupos). En su reloj, el
mismo día: cambiar un ejercicio llegó como GOBLET_SQUAT, y enviar sólo mandó los días cambiados
(«4/4 · 3 reemplazadas», un entreno por sesión, sin duplicados).

Correr con:  python -m unittest discover -s tests -v
"""

import copy
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import ajuste_de_fuerza as af  # noqa: E402
from services.ai import edicion_de_sesion as ed  # noqa: E402
from services.ai import sesion_del_atleta as sa  # noqa: E402
from tests.test_mover_sesion import HOY, SEMANA, _dias  # noqa: E402


def _e(nombre, series, reps, carga, desc, eid, video="", cat="", gar=""):
    return {"name": nombre, "sets": series, "reps": reps, "load": carga, "rest_seconds": desc, "id": eid,
            "video_key": video, "garmin_category": cat, "garmin_exercise": gar, "cues": ""}


PIERNA_A = {"date": "2026-10-26", "day_name_es": "Lunes", "is_rest": False, "sport": "strength",
            "focus": "Pierna A (cuádriceps + estabilidad)", "total_duration_minutes": 30, "session_slot": "unica",
            "steps": [{"step_order": 1, "step_type": "main", "duration_type": "time", "duration_value": 1800,
                       "repeat_count": 1, "target_type": "none", "description": "Pierna A — 30 min"}],
            "strength_block": {"groups": [
                {"id": "g1", "label": "A", "rest_between_rounds_s": 30, "rest_between_exercises_s": None, "exercises": [
                    _e("Gato-camello", 1, "6-8", "Suave, RPE 3-4", 30, "g1_e1", "gato_camello", "CORE"),
                    _e("Sentadilla profunda sostenida", 1, "30-45 s", "RPE 4-5", 30, "g1_e3",
                       "sentadilla_profunda_sostenida")]},
                {"id": "g2", "label": "B", "rest_between_rounds_s": 150, "rest_between_exercises_s": None, "exercises": [
                    _e("Sentadilla trasera con barra", 2, "6-8", "RIR 2 (todas las series)", 150, "g2_e1",
                       "sentadilla_trasera_con_barra", "SQUAT", "BARBELL_BACK_SQUAT"),
                    _e("Pallof press (antirrotación) en polea o banda", 2, "10-12 por lado", "RIR 2", 150, "g2_e3",
                       "pallof_press")]}]}}


def _con_reloj():
    """Un ejercicio del catálogo de fuerza con su nombre de Garmin verificado."""
    from services.ai.exercise_catalog import EJERCICIOS, garmin_de
    return next(s for s, f in EJERCICIOS.items() if f[3] == "fuerza" and garmin_de(f[1])[1])


class EditarLaFuerza(unittest.TestCase):

    def test_sin_cambios_es_el_del_coach_byte_a_byte(self):
        dia = copy.deepcopy(PIERNA_A)
        af.aplicar(dia, af.editor(PIERNA_A)["grupos"])
        self.assertEqual(dia["strength_block"]["groups"][1]["exercises"], PIERNA_A["strength_block"]["groups"][1]["exercises"])
        self.assertEqual(dia["total_duration_minutes"], 30)
        self.assertEqual(af.firma(dia["strength_block"]), af.firma(PIERNA_A["strength_block"]))

    def test_reps_largas_no_se_cortan(self):
        """«8-10 por lado (3-4 s bajada)» se cortaba a 24 caracteres y salía «ajustado» sin tocarlo."""
        dia = copy.deepcopy(PIERNA_A)
        largo = "8-10 por lado (3-4 s bajada)"
        dia["strength_block"]["groups"][1]["exercises"][1]["reps"] = largo
        af.aplicar(dia, af.editor(dia)["grupos"])
        self.assertFalse(dia["strength_block"]["groups"][1]["exercises"][1].get("change"))

    def test_ajustar_conserva_el_id_y_cambiar_sale_del_catalogo(self):
        dia = copy.deepcopy(PIERNA_A)
        grupos = af.editor(dia)["grupos"]
        grupos[1]["ejercicios"][0]["series"] = 3
        slug = _con_reloj()
        grupos[1]["ejercicios"][1] = {"origen": "nuevo", "slug": slug, "series": 2, "reps": "10",
                                      "en_vez_de": "Pallof press"}
        r = af.ajustar(dia, grupos)
        sentadilla, nuevo = dia["strength_block"]["groups"][1]["exercises"]
        self.assertEqual((sentadilla["id"], sentadilla["sets"], sentadilla["change"]), ("g2_e1", 3, "ajustado por ti"))
        self.assertEqual((nuevo["id"], nuevo["change"]), ("", "en vez de Pallof press"))
        self.assertTrue(nuevo["garmin_exercise"])
        self.assertTrue(dia["edited"]["adjusted"])
        self.assertGreater(dia["total_duration_minutes"], 30)
        self.assertIn("queda ajustada", r["resumen"])

    def test_el_resumen_dice_que_cambio(self):
        """En la pantalla, la vista previa sólo decía «queda ajustada» (prueba como José, 23-oct)."""
        dia = copy.deepcopy(PIERNA_A)
        grupos = af.editor(dia)["grupos"]
        grupos[0]["ejercicios"][0] = {"origen": "nuevo", "slug": _con_reloj(), "series": 1, "reps": "10",
                                      "en_vez_de": "Gato-camello"}
        del grupos[1]["ejercicios"][1]
        grupos[1]["ejercicios"][0]["series"] = 3
        r = af.ajustar(dia, grupos, copy.deepcopy(PIERNA_A))
        nuevo = dia["strength_block"]["groups"][0]["exercises"][0]["name"]
        self.assertIn(f"Gato-camello → {nuevo}", r["resumen"])
        self.assertIn("ajustaste Sentadilla trasera con barra", r["resumen"])
        self.assertIn("quitaste Pallof press", r["resumen"])
        self.assertNotIn("quitaste Gato-camello", r["resumen"])

    def test_lo_que_cambio_el_coach_no_se_atribuye_al_atleta(self):
        """El paso de fuerza del coach también escribe «en vez de …»: eso no lo hizo el atleta."""
        coach = copy.deepcopy(PIERNA_A)
        coach["strength_block"]["groups"][1]["exercises"][1].update(change="en vez de Plancha", change_reason="Molestia")
        dia = copy.deepcopy(coach)
        r = af.ajustar(dia, af.editor(dia)["grupos"], coach)
        self.assertNotIn("Plancha", r["resumen"])
        self.assertTrue(r["resumen"].endswith("queda ajustada."))

    def test_fuera_del_catalogo_no_entra(self):
        grupos = af.editor(PIERNA_A)["grupos"]
        grupos[0]["ejercicios"].append({"origen": "nuevo", "slug": "no_existe", "series": 1, "reps": "10"})
        with self.assertRaises(ed.EdicionNoPermitida):
            af.ajustar(copy.deepcopy(PIERNA_A), grupos)


class AnadirYQuitar(unittest.TestCase):

    def test_quitar_lo_anadido_devuelve_la_del_coach_a_unica(self):
        """Añadir pasa la sesión sola del coach a «am»; al quitar la añadida vuelve a «unica» con su entreno."""
        elementos = sa.nuevo_editor("run", None)["elementos"]
        r = sa.anadir(SEMANA, "2026-10-14", "run", hoy=HOY, elementos=elementos, titulo="Con amigos")
        self.assertEqual(r["cambios_registro"], {"2026-10-14|unica": "2026-10-14|am"})
        q = sa.quitar(r["plan"], r["clave"], hoy=HOY)
        [coach] = _dias(q["plan"], "2026-10-14")
        self.assertEqual((coach["focus"], coach["session_slot"]), ("Ritmo 10K", "unica"))
        self.assertEqual(q["cambios_registro"], {"2026-10-14|am": "2026-10-14|unica"})

    def test_la_del_coach_no_se_quita(self):
        with self.assertRaises(ed.EdicionNoPermitida):
            sa.quitar(SEMANA, "2026-10-14|unica", hoy=HOY)


class LaHuellaVeLoQueCambio(unittest.TestCase):
    """La fuerza va al reloj desde su bloque, no desde `steps`: cambiar un ejercicio seguía saliendo «En tu
    reloj» y «Enviar al reloj» no lo mandaba."""

    @staticmethod
    def _dia(d):
        from services.ai.langgraph.schemas.plan_outputs import WorkoutDay
        return WorkoutDay.model_validate({**d, "purpose": "-", "adaptation": "-"})

    def test_lo_que_nadie_toco_no_cambia_de_huella(self):
        """Lo que ya está en los relojes y nadie editó no puede pasar a «cambios sin enviar» con este cambio."""
        from services.garmin.upload_ledger import huella
        otro = copy.deepcopy(PIERNA_A)
        otro["strength_block"]["groups"][1]["exercises"][0]["sets"] = 4
        self.assertEqual(huella(self._dia(PIERNA_A)), huella(self._dia(otro)))

    def test_un_ejercicio_cambiado_cambia_la_huella(self):
        from services.garmin.upload_ledger import huella
        antes = copy.deepcopy(PIERNA_A)
        antes["edited"] = {"adjusted": True, "by": "atleta"}
        despues = copy.deepcopy(antes)
        despues["strength_block"]["groups"][1]["exercises"][0]["sets"] = 3
        self.assertNotEqual(huella(self._dia(antes)), huella(self._dia(despues)))


class LoQueSobraAlEnviarUnasFechas(unittest.TestCase):

    def test_lo_anadido_y_quitado_se_retira_sin_tocar_otras_fechas(self):
        """Reloj de José, 23-oct: «Rodaje con amigos» añadido el 31, enviado y quitado. Al enviar sólo el 31
        se retiró («0/0 sesiones · 1 retiradas») y el resto de su semana siguió con sus ids."""
        from services.garmin.upload_ledger import huerfanas
        registro = [
            {"date": "2026-10-31", "session_slot": "unica", "workout_id": 1738854006},
            {"date": "2026-11-01", "session_slot": "unica", "workout_id": 1738852683},
            {"date": "2026-09-25", "session_slot": "test-run", "workout_id": 1699045663, "kind": "test"},
        ]
        vivas = {"2026-11-01|unica"}  # el 31 volvió a ser descanso
        sobran = huerfanas(registro, {"2026-10-31"}, vivas)
        self.assertEqual([e["workout_id"] for e in sobran], [1738854006])


class LaNatacionConservaElTextoDelCoach(unittest.TestCase):
    """Reloj de José, 23-oct: poner pull buoy a «4x100m crol a 1:47–1:53/100m (2 largos)» dejó «100 m crol
    con pull buoy» y se perdió el ritmo."""

    SERIE = {"step_order": 1, "step_type": "repeat", "repeat_count": 4, "target_type": "none",
             "description": "4x100m crol a 1:47–1:53/100m (2 largos) con 20\" pausa", "substeps": [
                 {"step_order": 1, "step_type": "interval", "duration_type": "distance", "duration_value": 100,
                  "repeat_count": 1, "stroke": "free", "target_type": "none",
                  "description": "100m crol a 1:47–1:53/100m (2 largos)"},
                 {"step_order": 2, "step_type": "recovery", "duration_type": "time", "duration_value": 20,
                  "repeat_count": 1, "target_type": "none", "description": "20\" pausa"}]}

    def _con(self, cambio, nota=""):
        from services.ai import ajuste_de_sesion as aj
        dia = {"sport": "swim", "steps": [copy.deepcopy(self.SERIE)]}
        [e] = aj.aplanar(dia)
        e["trabajo"]["extra"].update(cambio)
        if nota:
            e["nota"] = nota
        [paso] = aj.expandir([e], dia)
        return paso

    def test_el_material_se_anade_al_texto(self):
        paso = self._con({"equipment": "pull_buoy"})
        self.assertEqual(paso["substeps"][0]["equipment"], "pull_buoy")
        self.assertTrue(paso["description"].startswith("4x100m crol a 1:47–1:53/100m (2 largos)"))
        self.assertIn("pull buoy", paso["description"])
        self.assertIn("1:47–1:53/100m", paso["substeps"][0]["description"])

    def test_la_nota_va_al_bloque_y_a_cada_repeticion(self):
        paso = self._con({"equipment": "pull_buoy"}, nota="concéntrate en el agarre")
        self.assertTrue(paso["description"].endswith("concéntrate en el agarre"))
        self.assertTrue(paso["substeps"][0]["description"].endswith("concéntrate en el agarre"))

    def test_sin_cambios_es_el_paso_del_coach(self):
        from services.ai import ajuste_de_sesion as aj
        dia = {"sport": "swim", "steps": [copy.deepcopy(self.SERIE)]}
        [paso] = aj.expandir(aj.aplanar(dia), dia)
        self.assertEqual(paso["description"], self.SERIE["description"])


class MoverDeVuelta(unittest.TestCase):

    def test_de_vuelta_a_su_dia_no_queda_marca(self):
        """Intercambio 29↔30 y de vuelta (reloj de José, 23-oct): quedaba `edited: {"by": "atleta"}`."""
        ida = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY)
        vuelta = ed.mover(ida["plan"], "2026-10-15|unica", "2026-10-14", hoy=HOY)
        [dia] = _dias(vuelta["plan"], "2026-10-14")
        self.assertIsNone(dia.get("edited"))


if __name__ == "__main__":
    unittest.main()
