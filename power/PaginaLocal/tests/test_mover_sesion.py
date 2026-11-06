"""Mover una sesión, sin IA (Beta 3.0, F6; ampliado el 2026-10-21).

Lo más pedido: «el martes no puedo, hazlo el jueves». Hasta aquí la única salida era
regenerar la semana (~0,30 USD). Reglas del responsable (2026-10-10): nunca el día de carrera;
un destino ocupado se intercambia o queda como segunda sesión; lo que no conviene se avisa sin
impedirlo. El 2026-10-21 amplió el resto: «recalendarizar una sesión de cualquier semana activa,
no importa si fue ayer» -- el origen puede ser un día ya pasado de una semana que sigue en curso,
y el destino, hoy o cualquier día que venga de la semana en curso o de la siguiente.

El plan de la prueba es la semana real del atleta 2 del 12 al 18 de octubre de 2026: rectas
y fuerza el lunes, descanso el martes, 3x3' a ritmo de 10K el miércoles, descanso el jueves,
rectas y natación el viernes, su 10K el sábado y descanso el domingo. Su rejilla no tiene
correr ni el martes ni el jueves.

Correr con:  python -m unittest discover -s tests -v
"""

import copy
import sys
import unittest
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import edicion_de_sesion as ed  # noqa: E402

HOY = "2026-10-11"
REJILLA = {"Correr": {"Lunes": "posible", "Miércoles": "posible", "Viernes": "posible", "Domingo": "posible"},
           "Bici": {"Martes": "posible", "Domingo": "posible"}, "Nadar": {"Miércoles": "fijo", "Viernes": "fijo"},
           "Fuerza": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo"}}


def _dia(fecha, nombre, sport, clase, foco, franja="unica", rest=False, minutos=40.0):
    return {"date": fecha, "day_name_es": nombre, "is_rest": rest, "sport": sport, "focus": foco, "purpose": "-",
            "adaptation": "-", "steps": [], "total_duration_minutes": 0 if rest else minutos,
            "intensity_class": clase, "session_slot": franja}


SEMANA = {"athlete_name": "José", "start_date": "2026-10-12", "zones_table": "", "weeks": [
    {"week_number": 1, "week_rationale": "", "days": [
        _dia("2026-10-09", "Viernes", "run", "easy", "Rodaje"),
    ]},
    {"week_number": 2, "week_rationale": "", "days": [
        _dia("2026-10-12", "Lunes", "run", "neuromuscular", "Soltura + chispa", "am", minutos=35),
        _dia("2026-10-12", "Lunes", "strength", "strength_freeform", "Fuerza mínima", "pm", minutos=30),
        _dia("2026-10-13", "Martes", "rest", "rest", "Descanso", rest=True),
        _dia("2026-10-14", "Miércoles", "run", "tempo_threshold", "Ritmo 10K", minutos=38),
        _dia("2026-10-15", "Jueves", "rest", "rest", "Descanso", rest=True),
        _dia("2026-10-16", "Viernes", "run", "neuromuscular", "Rectas", "am", minutos=26),
        _dia("2026-10-16", "Viernes", "swim", "recovery", "Natación suave", "pm", minutos=30),
        _dia("2026-10-17", "Sábado", "run", "race_day", "10K a tope", minutos=80),
        _dia("2026-10-18", "Domingo", "rest", "rest", "Descanso", rest=True),
    ]},
]}


def _dias(plan, fecha):
    return [d for w in plan["weeks"] for d in w["days"] if d["date"] == fecha]


class Mover(unittest.TestCase):

    def test_al_descanso_del_jueves(self):
        r = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY, rejilla=REJILLA)
        [jueves] = _dias(r["plan"], "2026-10-15")
        [miercoles] = _dias(r["plan"], "2026-10-14")
        self.assertEqual((jueves["focus"], jueves["day_name_es"], jueves["edited"]["moved_from"]),
                         ("Ritmo 10K", "Jueves", "2026-10-14"))
        self.assertTrue(miercoles["is_rest"], "el día que se queda sin nada lleva su descanso")
        self.assertEqual(r["cambios_registro"], {"2026-10-14|unica": "2026-10-15|unica"})
        self.assertEqual(r["resumen"], "«Ritmo 10K» pasa del miércoles 14 al jueves 15.")

    def test_el_aviso_no_impide(self):
        """Su tabla no tiene correr el jueves: se avisa y se mueve igual."""
        r = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY, rejilla=REJILLA)
        self.assertIn("Tu tabla no tiene correr el jueves: lo decides tú, sólo te lo recordamos.", r["avisos"])
        self.assertEqual(_dias(r["plan"], "2026-10-15")[0]["focus"], "Ritmo 10K")

    def test_las_rectas_no_cuentan_como_calidad_seguida(self):
        """Jueves 3x3' y viernes rectas: no es «dos días de calidad»."""
        r = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY, rejilla=REJILLA)
        self.assertFalse(any("seguidos" in a for a in r["avisos"]))

    def test_calidad_la_vispera_de_la_carrera(self):
        """Con el viernes libre, el miércoles de 3x3' pasa a la víspera del 10K."""
        plan = copy.deepcopy(SEMANA)
        plan["weeks"][1]["days"] = [d for d in plan["weeks"][1]["days"] if not d["date"] == "2026-10-16"]
        plan["weeks"][1]["days"].append(_dia("2026-10-16", "Viernes", "rest", "rest", "Descanso", rest=True))
        r = ed.mover(plan, "2026-10-14|unica", "2026-10-16", hoy=HOY)
        self.assertIn("Queda una sesión de calidad la víspera de tu carrera: llegarás con menos chispa.", r["avisos"])

    def test_intercambio(self):
        r = ed.mover(SEMANA, "2026-10-12|am", "2026-10-14", hoy=HOY)
        [miercoles] = _dias(r["plan"], "2026-10-14")
        lunes = _dias(r["plan"], "2026-10-12")
        self.assertEqual(miercoles["focus"], "Soltura + chispa")
        self.assertEqual([d["focus"] for d in lunes], ["Ritmo 10K", "Fuerza mínima"])
        self.assertEqual(r["cambios_registro"], {"2026-10-12|am": "2026-10-14|unica", "2026-10-14|unica": "2026-10-12|am"})

    def test_doble_sesion(self):
        r = ed.mover(SEMANA, "2026-10-12|pm", "2026-10-14", hoy=HOY, modo="doble")
        miercoles = _dias(r["plan"], "2026-10-14")
        self.assertEqual([(d["session_slot"], d["focus"]) for d in miercoles],
                         [("am", "Ritmo 10K"), ("pm", "Fuerza mínima")])
        self.assertEqual(r["cambios_registro"]["2026-10-14|unica"], "2026-10-14|am",
                         "la que ya estaba cambia de clave y su id del reloj va con ella")

    def test_como_mucho_dos_por_dia(self):
        with self.assertRaises(ed.EdicionNoPermitida):
            ed.mover(SEMANA, "2026-10-14|unica", "2026-10-16", hoy=HOY, modo="doble")

    def test_volver_a_su_dia_quita_la_marca(self):
        r1 = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY)
        r2 = ed.mover(r1["plan"], "2026-10-15|unica", "2026-10-14", hoy=HOY)
        [miercoles] = _dias(r2["plan"], "2026-10-14")
        self.assertNotIn("moved_from", miercoles.get("edited") or {})

    def test_no_toca_el_plan_que_recibe(self):
        antes = copy.deepcopy(SEMANA)
        ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY)
        self.assertEqual(SEMANA, antes)


class LoQueNoSePuede(unittest.TestCase):

    def _no(self, *args, **kw):
        with self.assertRaises(ed.EdicionNoPermitida) as c:
            ed.mover(SEMANA, *args, hoy=HOY, **kw)
        return str(c.exception)

    def test_la_carrera_no_se_mueve(self):
        self.assertIn("carrera", self._no("2026-10-17|unica", "2026-10-18"))

    def test_ni_al_dia_de_la_carrera(self):
        self.assertIn("carrera", self._no("2026-10-14|unica", "2026-10-17"))

    def test_a_un_dia_que_ya_paso_no(self):
        self.assertIn("ya pasó", self._no("2026-10-14|unica", "2026-10-10"))

    def test_fuera_de_las_semanas_activas(self):
        """El 20 no es de ninguna semana escrita: no hay a dónde llevarla."""
        self.assertIn("semanas activas", self._no("2026-10-14|unica", "2026-10-20"))

    def test_una_semana_que_ya_termino_no_se_toca(self):
        """La semana 1 (el viernes 9) se cerró en el expediente al generar la 2."""
        self.assertIn("ya terminó", self._no("2026-10-09|unica", "2026-10-15"))

    def test_un_descanso(self):
        self.assertIn("descanso", self._no("2026-10-13|unica", "2026-10-15").lower())


# La semana siguiente del atleta 2, escrita: rodaje el lunes 19, descanso el martes 20.
DOS_SEMANAS = copy.deepcopy(SEMANA)
DOS_SEMANAS["weeks"].append({"week_number": 3, "week_rationale": "", "days": [
    _dia("2026-10-19", "Lunes", "run", "easy", "Rodaje suave", minutos=40),
    _dia("2026-10-20", "Martes", "rest", "rest", "Descanso", rest=True),
]})


class EnCualquierSemanaActiva(unittest.TestCase):
    """Decisión del responsable del 2026-10-21."""

    def test_a_la_semana_siguiente(self):
        r = ed.mover(DOS_SEMANAS, "2026-10-14|unica", "2026-10-20", hoy=HOY)
        semana2 = [d["date"] for d in r["plan"]["weeks"][1]["days"]]
        [martes] = [d for d in r["plan"]["weeks"][2]["days"] if d["date"] == "2026-10-20"]
        self.assertEqual((martes["focus"], martes["edited"]["moved_from"]), ("Ritmo 10K", "2026-10-14"))
        self.assertIn("2026-10-14", semana2, "su día de antes queda con un descanso, en SU semana")
        self.assertNotIn("2026-10-20", semana2, "la sesión vive en la semana de su fecha nueva")
        self.assertEqual(r["cambios_registro"], {"2026-10-14|unica": "2026-10-20|unica"})

    def test_intercambio_entre_semanas(self):
        r = ed.mover(DOS_SEMANAS, "2026-10-14|unica", "2026-10-19", hoy=HOY)
        self.assertEqual([d["focus"] for d in r["plan"]["weeks"][1]["days"] if d["date"] == "2026-10-14"],
                         ["Rodaje suave"])
        self.assertEqual([d["focus"] for d in r["plan"]["weeks"][2]["days"] if d["date"] == "2026-10-19"],
                         ["Ritmo 10K"])

    def test_la_de_ayer_a_hoy(self):
        """«Ayer no pude»: el miércoles 14 ya pasó (hoy es jueves 15) y se lleva a hoy, con su aviso."""
        r = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy="2026-10-15")
        [jueves] = _dias(r["plan"], "2026-10-15")
        self.assertEqual(jueves["focus"], "Ritmo 10K")
        self.assertTrue(r["avisos"][0].startswith("Era del miércoles 14: si ya la hiciste, déjala donde está"))

    def test_la_de_ayer_no_se_intercambia(self):
        """Intercambiar mandaría la otra sesión al pasado, donde ya no se puede hacer."""
        with self.assertRaises(ed.EdicionNoPermitida) as c:
            ed.mover(SEMANA, "2026-10-12|am", "2026-10-14", hoy="2026-10-13", modo="intercambiar")
        self.assertIn("hacer las dos", str(c.exception))


class LoDemasNoCambia(unittest.TestCase):

    def test_la_siguiente_semana_empieza_donde_empezaba(self):
        """Mover dentro de la semana no cambia su último día: la siguiente generación arranca igual."""
        from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
        from web_app.server import _next_week_start
        ahora = datetime(2026, 10, 11, 10)
        antes = _next_week_start(WeeklyPlanPayload.model_validate(SEMANA), ahora)
        r = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-18", hoy=HOY)
        self.assertEqual(_next_week_start(WeeklyPlanPayload.model_validate(r["plan"]), ahora), antes)

    def test_la_marca_no_viaja_a_la_ia(self):
        import json

        from services.ai.langgraph.schemas.plan_outputs import WeeklyPlanPayload
        self.assertNotIn('"edited"', json.dumps(WeeklyPlanPayload.model_json_schema()))
        r = ed.mover(SEMANA, "2026-10-14|unica", "2026-10-15", hoy=HOY)
        dia = WeeklyPlanPayload.model_validate(r["plan"]).weeks[1].days
        self.assertTrue(any((d.edited or {}).get("moved_from") == "2026-10-14" for d in dia),
                        "el esquema la conserva al leer el plan guardado")


if __name__ == "__main__":
    unittest.main()
