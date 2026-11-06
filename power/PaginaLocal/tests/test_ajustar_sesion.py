"""Ajustar los pasos de una sesión y volver a la del coach, sin IA (Beta 3.0, F7).

La sesión de la prueba es la real del atleta 2 del miércoles 14 de octubre de 2026: «Ritmo
10K», 10' de calentamiento, 3x(3' a 5:20–5:28/km, 3' trote muy suave en Z1) y 10' de
enfriamiento (38'). Sus bandas de ritmo son las de su prescripción real.

Lo que se fija, sobre todo, es lo que se vio al probarlo en el navegador (2026-10-11): al
cambiar sólo las repeticiones se reescribían TODOS los textos («10' calentamiento en Z1–Z2»
pasaba a «10' de calentamiento») porque el plan llega de PHP con `600.0` y `{}` y vuelve del
navegador con `600` y `[]`, y la comparación los tomaba por pasos distintos.

Correr con:  python -m unittest discover -s tests -v
"""

import copy
import json
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import ajuste_de_sesion as aj  # noqa: E402
from services.ai.edicion_de_sesion import EdicionNoPermitida  # noqa: E402

HOY = "2026-10-11"
CLAVE = "2026-10-14|unica"
BANDAS = {"pace_bands": {"repeticiones": [287.0, 307.5], "vo2max": [307.5, 328.0], "umbral_tempo": [334.9, 362.2],
                         "maraton": [381.7, 401.2], "facil": [389.5, 436.3], "recuperacion": [428.5, 486.9]}}


def _paso(orden, tipo, valor, desc, **extra):
    return {"step_order": orden, "step_type": tipo, "duration_type": "time", "duration_value": valor,
            "repeat_count": 1, "target_type": "none", "description": desc, "substeps": [], **extra}


MIERCOLES = {
    "date": "2026-10-14", "day_name_es": "Miércoles", "session_slot": "unica", "is_rest": False, "sport": "run",
    "focus": "Ritmo 10K", "purpose": "-", "adaptation": "-", "intensity_class": "tempo_threshold",
    "total_duration_minutes": 38.0,
    "steps": [
        _paso(1, "warmup", 600.0, "10' calentamiento en Z1–Z2"),
        {"step_order": 2, "step_type": "repeat", "repeat_count": 3, "description": "3x(3' a 5:20–5:28/km, 3' rec)",
         "substeps": [_paso(1, "interval", 180.0, "3' a 5:20–5:28/km", target_type="pace", target_low=320.0, target_high=328.0),
                      _paso(2, "recovery", 180.0, "3' trote muy suave en Z1")]},
        _paso(3, "cooldown", 600.0, "10' enfriamiento en Z1–Z2"),
    ],
}
PLAN = {"athlete_name": "José", "start_date": "2026-10-12", "zones_table": "", "weeks": [{"week_number": 1, "days": [
    MIERCOLES,
    {**copy.deepcopy(MIERCOLES), "date": "2026-10-17", "day_name_es": "Sábado", "focus": "10K", "intensity_class": "race_day"},
]}]}


def _como_vuelve_de_php_y_del_navegador(x):
    """`600.0` -> `600` y `{}` -> `[]`, como lo deja el viaje real."""
    return json.loads(json.dumps(x).replace(".0,", ",").replace(".0}", "}").replace("{}", "[]"))


def _dia(plan):
    return next(d for w in plan["weeks"] for d in w["days"] if d["date"] == "2026-10-14")


def _textos(dia):
    return [(s["description"], [c["description"] for c in s.get("substeps") or []]) for s in dia["steps"]]


class LoQueNoCambiaSeQuedaComoEstaba(unittest.TestCase):

    def test_sin_tocar_nada_los_pasos_son_los_del_coach(self):
        el = _como_vuelve_de_php_y_del_navegador(aj.editor(PLAN, CLAVE, hoy=HOY)["elementos"])
        r = aj.ajustar(PLAN, CLAVE, el, hoy=HOY)
        self.assertEqual(_dia(r["plan"])["steps"], MIERCOLES["steps"])

    def test_cambiar_las_repeticiones_no_reescribe_lo_demas(self):
        el = _como_vuelve_de_php_y_del_navegador(aj.editor(PLAN, CLAVE, hoy=HOY)["elementos"])
        next(e for e in el if e["tipo"] == "serie")["repeticiones"] = 2
        r = aj.ajustar(PLAN, CLAVE, el, hoy=HOY, prescripcion=BANDAS)
        self.assertEqual(_textos(_dia(r["plan"])), [
            ("10' calentamiento en Z1–Z2", []),
            ("2x(3' a 5:20–5:28/km, 3' rec)", ["3' a 5:20–5:28/km", "3' trote muy suave en Z1"]),
            ("10' enfriamiento en Z1–Z2", []),
        ])
        self.assertEqual(_dia(r["plan"])["total_duration_minutes"], 32.0)
        self.assertEqual(r["resumen"], "«Ritmo 10K» del miércoles 14 queda ajustada (38' → 32').")
        self.assertEqual(r["avisos"], [])

    def test_volver_a_la_del_coach(self):
        el = aj.editor(PLAN, CLAVE, hoy=HOY)["elementos"]
        next(e for e in el if e["tipo"] == "serie")["repeticiones"] = 5
        ajustada = aj.ajustar(PLAN, CLAVE, el, hoy=HOY)["plan"]
        r = aj.revertir(ajustada, CLAVE, MIERCOLES, hoy=HOY)
        self.assertEqual(_dia(r["plan"])["steps"], MIERCOLES["steps"])
        self.assertNotIn("edited", _dia(r["plan"]))


class LoQueCambiaSeEscribeDeSusCampos(unittest.TestCase):

    def test_un_paso_nuevo_desde_una_plantilla(self):
        el = aj.editor(PLAN, CLAVE, hoy=HOY, prescripcion=BANDAS)
        rodaje = {k: v for k, v in next(p for p in el["plantillas"] if p["nombre"] == "Rodaje suave").items() if k != "nombre"}
        r = aj.ajustar(PLAN, CLAVE, [*el["elementos"][:2], rodaje, el["elementos"][2]], hoy=HOY, prescripcion=BANDAS,
                       original=MIERCOLES)
        self.assertEqual(_dia(r["plan"])["steps"][2]["description"], "20' en Z2")
        self.assertIn("Queda un 53 % más larga que la que te escribió el coach (38' → 58').", r["avisos"])

    def test_un_ritmo_en_minutos_no_llega_al_reloj(self):
        mal = [{"tipo": "paso", "step_type": "main", "duracion": {"tipo": "time", "valor": 600},
                "objetivo": {"tipo": "pace", "bajo": 5.2, "alto": 5.4}}]
        with self.assertRaises(EdicionNoPermitida) as c:
            aj.ajustar(PLAN, CLAVE, mal, hoy=HOY)
        self.assertIn("minutos y segundos por kilómetro", str(c.exception))

    def test_los_avisos_no_impiden(self):
        el = aj.editor(PLAN, CLAVE, hoy=HOY)["elementos"]
        serie = next(e for e in el if e["tipo"] == "serie")
        serie["trabajo"]["objetivo"] = {"tipo": "pace", "bajo": 240, "alto": 250}  # 4:00-4:10/km
        serie["recuperacion"] = None
        r = aj.ajustar(PLAN, CLAVE, el, hoy=HOY, prescripcion=BANDAS)
        self.assertIn("Hay una serie sin recuperación: el reloj encadenará las repeticiones sin pausa.", r["avisos"])
        self.assertTrue(any("4:00/km es más rápido" in a for a in r["avisos"]))
        sin_ritmo = aj.editor(PLAN, CLAVE, hoy=HOY)["elementos"]
        next(e for e in sin_ritmo if e["tipo"] == "serie")["trabajo"]["objetivo"] = {"tipo": "none"}
        self.assertIn("Es una sesión de calidad y ningún paso lleva ritmo: el reloj no te dirá si vas rápido.",
                      aj.ajustar(PLAN, CLAVE, sin_ritmo, hoy=HOY)["avisos"])

    def test_un_bloque_con_otra_forma_solo_cambia_sus_repeticiones(self):
        raro = copy.deepcopy(PLAN)
        _dia(raro)["steps"][1]["substeps"].append(_paso(3, "recovery", 60.0, "1' parado"))
        el = aj.aplanar(_dia(raro))
        self.assertEqual(el[1]["tipo"], "bloque")
        el[1]["repeticiones"] = 4
        r = aj.ajustar(raro, CLAVE, el, hoy=HOY)
        bloque = _dia(r["plan"])["steps"][1]
        self.assertEqual((bloque["repeat_count"], bloque["description"], len(bloque["substeps"])),
                         (4, "4x(3' a 5:20–5:28/km, 3' rec)", 3))


class LoQueNoSeAjusta(unittest.TestCase):

    def _no(self, plan, clave, hoy=HOY):
        with self.assertRaises(EdicionNoPermitida) as c:
            aj.editor(plan, clave, hoy=hoy)
        return str(c.exception)

    def test_la_carrera(self):
        self.assertIn("carrera", self._no(PLAN, "2026-10-17|unica"))

    def test_lo_pasado(self):
        self.assertIn("ya pasó", self._no(PLAN, CLAVE, hoy="2026-10-15"))

    def test_una_fuerza_sin_su_bloque(self):
        """Desde el 2026-10-23 la fuerza se ajusta (test_editar_la_fuerza.py), pero sólo desde su bloque:
        una «fuerza» con pasos de carrera no se edita como si fuera una carrera."""
        fuerza = copy.deepcopy(PLAN)
        _dia(fuerza)["sport"] = "strength"
        self.assertTrue(self._no(fuerza, CLAVE))


class LaPantallaDelEditor(unittest.TestCase):
    """Prueba en el navegador del 2026-10-23, como el atleta 2: en la natación del 28-oct, «+ Series de 100 m» quedó
    DESPUÉS del enfriamiento de 200 m (y en su carrera del 1-nov, «Series al umbral» también), y el total decía
    «5' + lo que va por distancia o por bloques» de una sesión de 1.100 m."""

    def setUp(self):
        raiz = Path(__file__).resolve().parents[2]
        self.editor = (raiz / "Frontend/src/components/forms/EditorDeSesion.vue").read_text(encoding="utf-8")

    def test_lo_anadido_va_antes_del_enfriamiento(self):
        self.assertNotIn("elementos.value.push(e); };", self.editor)
        self.assertIn("while (i > 0 && esDeTipo(l[i - 1], 'cooldown')) i -= 1;", self.editor)

    def test_el_total_dice_los_metros(self):
        self.assertIn("partes.push(`${metros.toLocaleString('es-ES')} m`)", self.editor)
        self.assertNotIn("lo que va por distancia o por bloques", self.editor)


if __name__ == "__main__":
    unittest.main()
