"""Cuándo se puede generar la siguiente semana, y cuándo cabe un brick.

Dos reglas de la app, no del modelo, y las dos nacen del mismo error: dejar
que el atleta pida algo que la app no puede darle bien, y no decírselo.

**La ventana.** Cada generación lee el estado del atleta HOY -- HRV, readiness,
ACWR, lo que lleva hecho -- y escribe siete días a partir de ese retrato.
Generando dos semanas seguidas en una tarde, la segunda se escribió contra un
atleta que no existirá el día que le toque entrenarla. Pasó de verdad: dos
generaciones el 2026-08-28, la segunda para la semana del 2026-09-04.

**El brick.** Un brick es UNA sesión: bici y carrera seguidas, sin pausa. No se
puede repartir entre la franja de la mañana y la de la tarde, porque lo que se
entrena es justamente no parar. Un atleta con bici el martes y carrera el
jueves no puede hacer bricks, por mucho que los pida.

Run with:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date, datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
)
from web_app.server import (  # noqa: E402
    _dia_de_arranque,
    _replace_last_week,
    inicio_de_la_ultima_semana,
    ventana_de_generacion,
)


def _plan(fechas: list[str]) -> WeeklyPlanPayload:
    dias = [WorkoutDay(date=f, day_name_es="Lunes", is_rest=False, sport="run",
                       focus="Facil", purpose="Rodaje.", adaptation="-",
                       total_duration_minutes=30, steps=[]) for f in fechas]
    return WeeklyPlanPayload(athlete_name="Test", start_date=fechas[0], zones_table="z",
                             weeks=[WeekBlock(week_number=1, days=dias)])


class ElTopeDeDosSemanas(unittest.TestCase):
    """Como mucho la semana en curso y la siguiente (el responsable, 2026-09-24).

    El atleta 2 generó el 2026-09-23 tres semanas seguidas (hasta el 2026-10-11) saltando la
    ventana con "generar de todas formas": ~0,30 USD cada una, todas escritas con el retrato
    de ese mismo día y sin una sesión entrenada de por medio."""

    def test_la_tercera_no_se_puede_ni_forzando(self):
        v = ventana_de_generacion(_plan(["2026-10-05", "2026-10-11"]), datetime(2026, 9, 23))
        self.assertFalse(v["allowed"])
        self.assertTrue(v["limit"])
        self.assertIn("Ya tienes tu semana en curso y la siguiente", v["reason"])

    def test_la_siguiente_sigue_pudiendose_forzar(self):
        v = ventana_de_generacion(_plan(["2026-09-23", "2026-09-27"]), datetime(2026, 9, 23))
        self.assertFalse(v["limit"])  # la ventana de antelación decide; "de todas formas" vale

    def test_el_puente_no_deja_saltar_el_tope(self):
        from pathlib import Path
        fuente = (Path(__file__).resolve().parents[2] / "AiService" / "main.py").read_text(encoding="utf-8")
        self.assertIn('if ventana.get("limit") or (not ventana["allowed"] and not req.force):', fuente)


class Ventana(unittest.TestCase):
    def test_sin_plan_se_puede_generar_siempre(self):
        """Un atleta nuevo no tiene nada que esperar."""
        v = ventana_de_generacion(None, datetime(2026, 9, 2))
        self.assertTrue(v["allowed"])

    def test_bloquea_la_semana_siguiente_hasta_dos_dias_antes(self):
        """El caso real: dos generaciones el mismo día.

        El plan llega hasta el 2026-09-10, así que la siguiente semana empieza
        el 11. Pedirla el 28 de agosto la escribiría con un retrato del atleta
        de catorce días antes de que la entrene."""
        plan = _plan(["2026-09-04", "2026-09-10"])
        v = ventana_de_generacion(plan, datetime(2026, 8, 28))
        self.assertFalse(v["allowed"])
        self.assertEqual(v["next_start"], "2026-09-11")
        self.assertEqual(v["allowed_from"], "2026-09-09")
        self.assertEqual(v["days_until_allowed"], 12)
        # Con la fecha como la lee el atleta, no en ISO (auditoría del 2026-09-24).
        self.assertIn("miércoles 9 de septiembre", v["reason"])

    def test_justo_dos_dias_antes_ya_se_puede(self):
        plan = _plan(["2026-09-04", "2026-09-10"])
        self.assertTrue(ventana_de_generacion(plan, datetime(2026, 9, 9))["allowed"])

    def test_tres_dias_antes_todavia_no(self):
        plan = _plan(["2026-09-04", "2026-09-10"])
        self.assertFalse(ventana_de_generacion(plan, datetime(2026, 9, 8))["allowed"])

    def test_al_atleta_que_se_quedo_atras_no_se_le_hace_esperar(self):
        """Su semana siguiente empezaba hace días: esperar no le aporta nada,
        y bloquearle sería dejarle sin plan por haber estado ausente."""
        plan = _plan(["2026-08-01", "2026-08-07"])
        v = ventana_de_generacion(plan, datetime(2026, 9, 20))
        self.assertTrue(v["allowed"])
        self.assertEqual(v["days_until_allowed"], 0)

    def test_el_dia_mismo_en_que_arranca_la_semana_se_puede(self):
        plan = _plan(["2026-09-04", "2026-09-10"])
        self.assertTrue(ventana_de_generacion(plan, datetime(2026, 9, 11))["allowed"])



class RehacerLaUltimaSemana(unittest.TestCase):
    """Rehacer los mismos 7 dias EN SU SITIO, no anadir otros siete.

    Hasta v7 solo se sabia anadir: un atleta al que no le gustaba su semana
    solo podia borrar la generacion entera, o pulsar "Rechazar / Ajustar", que
    rehacia tambien el analisis y el macrociclo. Una generacion completa por
    querer mover un miercoles, y su plan de temporada cambiado de paso.
    """

    def _dos_semanas(self):
        p = _plan(["2026-09-04", "2026-09-05"])
        p.weeks.append(WeekBlock(week_number=2, days=[
            WorkoutDay(date=f, day_name_es="Lunes", is_rest=False, sport="run", focus="Vieja",
                       purpose="p", adaptation="a", total_duration_minutes=30, steps=[])
            for f in ("2026-09-11", "2026-09-12")]))
        return p

    def test_la_ultima_semana_empieza_en_su_primer_dia(self):
        self.assertEqual(inicio_de_la_ultima_semana(self._dos_semanas()),
                         date(2026, 9, 11))

    def test_sin_plan_no_hay_semana_que_rehacer(self):
        self.assertIsNone(inicio_de_la_ultima_semana(None))

    def test_reemplaza_la_ultima_y_deja_las_anteriores(self):
        viejo = self._dos_semanas()
        nueva = _plan(["2026-09-11", "2026-09-12"])
        nueva.weeks[0].days[0].focus = "Nueva"
        r = _replace_last_week(viejo, nueva)
        self.assertEqual(len(r.weeks), 2)
        self.assertEqual(r.weeks[0].days[0].date, "2026-09-04")   # intacta
        self.assertEqual(r.weeks[-1].days[0].focus, "Nueva")

    def test_conserva_el_NUMERO_de_semana(self):
        """Es la misma semana del ciclo escrita otra vez. Renumerarla
        desplazaria todo el macrociclo que cuelga de ella."""
        r = _replace_last_week(self._dos_semanas(), _plan(["2026-09-11"]))
        self.assertEqual(r.weeks[-1].week_number, 2)

    def test_sin_nada_previo_rehacer_es_generar(self):
        r = _replace_last_week(None, _plan(["2026-09-11"]))
        self.assertEqual(len(r.weeks), 1)


class DesdeQueDiaArrancaLaPrimeraSemana(unittest.TestCase):
    """"Hoy" no es la respuesta correcta a todas horas.

    Reportado por el atleta: "a veces lo podria generar a las 8 pm... y algunos
    podrian estar a las 11 am despues de su sesion y piden el entreno y el
    calendario les dice que deben entrenar hoy."

    A las ocho de la tarde, escribirle entrenos para hoy es escribirle un dia
    que ya se le fue. A las once de la manana puede ser justo lo que quiere.
    Solo el atleta sabe cual es su caso, asi que se le pregunta -- pero solo la
    PRIMERA vez: despues, la semana siguiente empieza donde acabo la anterior y
    dejar elegir ahi abriria huecos o solapes en su calendario.
    """

    AHORA = datetime(2026, 9, 10, 20, 0)      # un jueves, las 8 de la tarde
    HOY = date(2026, 9, 10)
    MANANA = date(2026, 9, 11)

    def test_sin_plan_y_sin_pedir_nada_arranca_hoy(self):
        self.assertEqual(_dia_de_arranque(None, self.AHORA, None), self.HOY)

    def test_sin_plan_puede_arrancar_manana_si_lo_pide(self):
        self.assertEqual(_dia_de_arranque(None, self.AHORA, "2026-09-11"), self.MANANA)

    def test_sin_plan_puede_arrancar_hoy_si_lo_pide(self):
        self.assertEqual(_dia_de_arranque(None, self.AHORA, "2026-09-10"), self.HOY)

    def test_CON_plan_lo_pedido_se_ignora(self):
        """La segunda semana empieza donde acabo la primera, y punto."""
        plan = _plan(["2026-09-07", "2026-09-08", "2026-09-09"])
        self.assertEqual(
            _dia_de_arranque(plan, self.AHORA, "2026-09-11"),
            _dia_de_arranque(plan, self.AHORA, None),
        )

    def test_una_fecha_lejana_no_es_una_preferencia_es_un_error(self):
        """Escribiria una semana del mes que viene contra el retrato de hoy,
        que es justo lo que la ventana de antelacion existe para impedir."""
        self.assertEqual(_dia_de_arranque(None, self.AHORA, "2026-10-15"), self.HOY)

    def test_ayer_tampoco(self):
        self.assertEqual(_dia_de_arranque(None, self.AHORA, "2026-09-09"), self.HOY)

    def test_una_fecha_ilegible_no_rompe_la_generacion(self):
        for basura in ("manana", "11/09/2026", "", "2026-13-99"):
            with self.subTest(basura=basura):
                self.assertEqual(_dia_de_arranque(None, self.AHORA, basura), self.HOY)

    def test_acepta_un_date_ademas_de_un_texto(self):
        self.assertEqual(_dia_de_arranque(None, self.AHORA, self.MANANA), self.MANANA)


class LaVentanaCuentaConELDiaDelAtleta(unittest.TestCase):
    """El servidor de produccion no esta en Guatemala.

    A las 8 de la tarde alli ya es el dia siguiente en UTC, asi que la ventana
    contaba un dia de mas o de menos contra el calendario que el atleta tiene
    delante. `ventana_de_generacion` recibe el ahora que se le da; quien la
    llama es responsable de darle el del atleta (ver `_ahora_del_atleta` en
    AiService/main.py).
    """

    def test_el_mismo_plan_da_respuestas_distintas_segun_el_dia(self):
        plan = _plan(["2026-09-07", "2026-09-08", "2026-09-09", "2026-09-10",
                      "2026-09-11", "2026-09-12", "2026-09-13"])
        # La siguiente empieza el 14; con 2 dias de antelacion, desde el 12.
        self.assertFalse(ventana_de_generacion(plan, datetime(2026, 9, 11, 23, 0))["allowed"])
        self.assertTrue(ventana_de_generacion(plan, datetime(2026, 9, 12, 1, 0))["allowed"])


if __name__ == "__main__":
    unittest.main()
