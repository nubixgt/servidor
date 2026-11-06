"""La rejilla manda sobre las franjas viejas, o el atleta pide y no recibe.

## El fallo, con los números reales

Generación REAL del atleta 2, el 2026-09-10, en el tier `standard` que corre en
producción. El atleta marcó **Nadar el martes como `fijo`** en la rejilla nueva.
Todo lo de aguas arriba funcionó:

  - el prompt archivado lo pidió bien:
    `Martes | Nadar | Bici, Fuerza` bajo `[LO QUE EL ATLETA PIDE PARA SU SEMANA]`
  - la capa de decisión lo puso:
    `2026-09-15 Martes | swim | rest=False | am | CSS suave`

Y el **validador lo borró**, dejando el día en descanso
(`plan_validation.json`):

    Deporte no permitido para ese día/bloque — se ajustó a descanso:
    'swim' no esta en los deportes permitidos ['strength'] para Martes (05:30-07:00)

Sus `day_time_blocks` de antes decían `Martes 05:30-07:00 = ["Fuerza"]`. El
prompt ya leía la rejilla y el validador seguía leyendo las franjas: dos
fuentes contradiciéndose, y ganando la vieja. El día quedó con `sport="rest"`,
`steps=[]` y `total_duration_minutes=0`, pero conservando la prosa del nado que
sí se había decidido — `focus: "CSS suave"`, `discipline: "natacion"`.

Esto es **peor que antes de la v1.1**: entonces el atleta no tenía dónde pedir
natación y no se le prometía nada. Ahora se le pide que la marque, se la
programan, y se la quitan sin decírselo.

## La regla que queda fijada

Cuando el perfil tiene rejilla, la rejilla es la ÚNICA autoridad sobre qué
deportes admite cada día. Las franjas no se borran nunca (`../CLAUDE.md`,
Database/013) — se dejan de consultar en cuanto su dueño contesta la rejilla.
Un perfil sin rejilla se comporta exactamente como antes.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from web_app.server import (  # noqa: E402
    _day_availability,
    build_availability_constraints,
)

# La rejilla del atleta 2, tal cual está en `athlete_profiles.sport_days`.
REJILLA_REAL = {
    "Correr": {"Lunes": "posible", "Miércoles": "posible", "Viernes": "posible", "Domingo": "posible"},
    "Trail": {"Domingo": "posible"},
    "Bici": {"Martes": "posible", "Domingo": "fijo"},
    "Nadar": {"Lunes": "posible", "Martes": "fijo", "Miércoles": "posible", "Viernes": "posible"},
    "Fuerza": {"Lunes": "posible", "Martes": "posible", "Jueves": "posible"},
}

# Sus franjas de antes. El martes NO admitía natación: ése es el veto que
# borraba la sesión.
FRANJAS_REALES = {
    "Lunes": [{"start": "05:30", "end": "07:00", "sports": ["Fuerza", "Nadar"]},
              {"start": "17:00", "end": "18:00", "sports": ["Correr"]}],
    "Martes": [{"start": "05:30", "end": "07:00", "sports": ["Fuerza"]},
               {"start": "19:00", "end": "21:00", "sports": ["Bici"]}],
    "Miércoles": [{"start": "05:15", "end": "07:00", "sports": ["Correr", "Nadar"]}],
    "Jueves": [{"start": "05:30", "end": "07:00", "sports": ["Fuerza"]}],
    "Viernes": [{"start": "05:15", "end": "07:00", "sports": ["Correr", "Nadar"]}],
    "Domingo": [{"start": "06:00", "end": "10:00", "sports": ["Correr", "Trail", "Bici"]}],
}

PERFIL_REAL = {
    "sport_days": REJILLA_REAL,
    "day_time_blocks": FRANJAS_REALES,
    "available_days": ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Domingo"],
    "available_hours": 15,
}


class LaRejillaManda(unittest.TestCase):
    def test_el_martes_admite_natacion_pese_a_la_franja_de_solo_fuerza(self):
        """El caso exacto. Sin esto, el validador borra el nado que se pidió."""
        dia = _day_availability(PERFIL_REAL, "Martes")
        self.assertIn("swim", dia["sports"])

    def test_la_franja_vieja_ya_no_veta_ningun_deporte_de_la_rejilla(self):
        c = build_availability_constraints(PERFIL_REAL)
        self.assertEqual(sorted(c["days"]["Martes"]["sports"]), ["bike", "strength", "swim"])

    def test_un_fijo_esta_siempre_entre_los_permitidos_de_su_dia(self):
        """La contradicción que hacía imposible cumplir la petición: si un
        `fijo` no está permitido, la regla que lo exige y la que lo prohíbe se
        pelean, y el atleta pierde."""
        c = build_availability_constraints(PERFIL_REAL)
        for dia, deportes in c["sport_days_fijos"].items():
            permitidos = c["days"][dia]["sports"]
            for deporte in deportes:
                with self.subTest(dia=dia, deporte=deporte):
                    self.assertIn(deporte, permitidos)

    def test_los_fijos_del_atleta_2_son_los_que_marco(self):
        c = build_availability_constraints(PERFIL_REAL)
        self.assertEqual(c["sport_days_fijos"], {"Martes": ["swim"], "Domingo": ["bike"]})

    def test_con_rejilla_no_quedan_franjas_ni_tope_de_horas_por_dia(self):
        """La v1.1 quitó las horas de reloj: "sin horas, sin minutos; la IA
        decide duración e intensidad". El tope que sigue en pie es el semanal."""
        c = build_availability_constraints(PERFIL_REAL)
        for dia in ("Lunes", "Martes", "Domingo"):
            with self.subTest(dia=dia):
                self.assertEqual(c["days"][dia]["blocks"], [])
                self.assertIsNone(c["days"][dia]["hours"])

    def test_un_dia_fuera_de_la_rejilla_no_resucita_por_sus_franjas(self):
        """Quitar un día de la rejilla tiene que quitarlo de verdad. Caer a las
        franjas lo devolvería, que es el mismo fallo mirando al revés."""
        perfil = dict(PERFIL_REAL, sport_days={"Fuerza": {"Lunes": "posible"}})
        self.assertEqual(_day_availability(perfil, "Martes")["sports"], [])

    def test_el_domingo_de_trail_sigue_admitiendo_correr(self):
        """Trail es TERRENO, no deporte. Un domingo con trail marcado no puede
        rechazar una sesión de carrera -- que es literalmente lo que se hace en
        un sendero."""
        dia = _day_availability(PERFIL_REAL, "Domingo")
        self.assertIn("run", dia["sports"])
        self.assertIn("trail", dia["sports"])

    def test_un_perfil_sin_rejilla_se_comporta_exactamente_como_antes(self):
        """Ni un atleta pierde nada por no haber contestado todavía. Las
        franjas no se borran: se dejan de consultar."""
        perfil = {k: v for k, v in PERFIL_REAL.items() if k != "sport_days"}
        dia = _day_availability(perfil, "Martes")
        self.assertEqual(sorted(dia["sports"]), ["bike", "strength"])
        self.assertEqual(len(dia["blocks"]), 2)
        self.assertEqual(dia["hours"], 3.5)

    def test_una_rejilla_vacia_cuenta_como_no_contestada(self):
        for vacia in ({}, None, {"Correr": {}}):
            with self.subTest(vacia=vacia):
                perfil = dict(PERFIL_REAL, sport_days=vacia)
                self.assertEqual(len(_day_availability(perfil, "Martes")["blocks"]), 2)


class LoDeclaradoEsLaRejilla(unittest.TestCase):
    """El bloque de hábitos contrasta lo DECLARADO contra lo que Garmin
    registró. Con la rejilla puesta, lo declarado es la rejilla: seguir
    leyendo las franjas le pone al atleta en la boca, delante del modelo, una
    petición que él mismo cambió."""

    def test_el_martes_declara_natacion_no_fuerza(self):
        from services.ai.training_habits import _declarado_por_dia
        self.assertIn("natación", _declarado_por_dia(PERFIL_REAL)["martes"])

    def test_cuentan_los_fijos_y_los_posibles(self):
        """Un `posible` sigue siendo algo que el atleta dice que hace ese día.
        El contraste existe para enseñar la distancia entre lo que dice y lo
        que hizo, no para juzgar sólo las obligaciones."""
        from services.ai.training_habits import _declarado_por_dia
        martes = _declarado_por_dia(PERFIL_REAL)["martes"]
        self.assertIn("natación", martes)   # fijo
        self.assertIn("bici", martes)       # posible

    def test_sin_rejilla_se_siguen_leyendo_las_franjas(self):
        from services.ai.training_habits import _declarado_por_dia
        perfil = {k: v for k, v in PERFIL_REAL.items() if k != "sport_days"}
        self.assertIn("fuerza", _declarado_por_dia(perfil)["martes"])

    def test_una_rejilla_vacia_no_borra_lo_declarado(self):
        from services.ai.training_habits import _declarado_por_dia
        perfil = dict(PERFIL_REAL, sport_days={})
        self.assertTrue(_declarado_por_dia(perfil))


if __name__ == "__main__":
    unittest.main()
