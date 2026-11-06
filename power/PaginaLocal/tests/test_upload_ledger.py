"""Qué entrenos puso la app en el Garmin del atleta, y cuáles retira.

Estas pruebas existen por un fallo que llegó al reloj: seis generaciones de
prueba en una tarde dejaron ~40 entrenos en la biblioteca de Garmin y el reloj
avisó de que había alcanzado su límite de sesiones. La app subía y se olvidaba
del `workoutId`, así que no podía ni reemplazar ni retirar nada.

El riesgo que cubren no es "no borra bastante" sino el contrario: **borrar algo
del atleta**. Por eso todo se decide sobre ids que el propio registro anotó, y
nunca sobre nombres, fechas o heurísticas.

Run with:  python -m unittest discover -s tests -v
"""

import asyncio
import json
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WorkoutDay,
    WorkoutStep,
)
from services.garmin import upload_ledger  # noqa: E402
from services.garmin.garmin_upload_service import GarminUploadService  # noqa: E402
from services.garmin.workout_builder import AthleteZoneConfig  # noqa: E402


def _dia(fecha="2026-09-02", franja="unica", segundos=600, proposito="Umbral."):
    return WorkoutDay(
        date=fecha, day_name_es="Miércoles", is_rest=False, sport="run", session_slot=franja,
        focus="Umbral", purpose=proposito, adaptation="Si molesta, para.",
        total_duration_minutes=45,
        steps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                           duration_value=segundos, target_type=TargetType.HR_ZONE,
                           target_zone=2, description="rodaje")],
    )


class Registro(unittest.TestCase):
    def test_anotar_reemplaza_la_entrada_de_esa_sesion_y_no_las_demas(self):
        """Volver a subir el lunes AM no puede tocar el lunes PM.

        Las dos sesiones de un mismo día son entrenos distintos en Garmin. Si
        la clave fuera sólo la fecha, subir la bici de la tarde borraría la
        fuerza de la mañana del calendario del atleta."""
        r = []
        r = upload_ledger.anotar(r, fecha="2026-08-31", franja="am", workout_id=1,
                                 nombre="Fuerza", deporte="strength")
        r = upload_ledger.anotar(r, fecha="2026-08-31", franja="pm", workout_id=2,
                                 nombre="Bici", deporte="bike")
        r = upload_ledger.anotar(r, fecha="2026-08-31", franja="am", workout_id=3,
                                 nombre="Fuerza", deporte="strength")

        por_sesion = upload_ledger.por_sesion(r)
        self.assertEqual(len(r), 2)
        self.assertEqual(por_sesion["2026-08-31|am"]["workout_id"], 3)
        self.assertEqual(por_sesion["2026-08-31|pm"]["workout_id"], 2)

    def test_caducan_por_la_fecha_de_la_sesion_no_por_cuando_se_subio(self):
        """Lo que sobra en la biblioteca del reloj es el entreno de un martes
        que ya pasó, se hiciera o no, y aunque se subiera hace cinco minutos."""
        r = [
            {"date": "2026-08-01", "session_slot": "unica", "workout_id": 1},
            {"date": "2026-08-20", "session_slot": "unica", "workout_id": 2},
            {"date": "2026-08-28", "session_slot": "unica", "workout_id": 3},
        ]
        viejas = upload_ledger.caducadas(r, hoy=date(2026, 8, 28), dias=14)
        self.assertEqual([e["workout_id"] for e in viejas], [1])

    def test_una_fecha_ilegible_se_conserva(self):
        """Ante la duda no se borra: conservar de más cuesta un hueco en la
        biblioteca, y borrar de más le cuesta al atleta algo suyo."""
        r = [{"date": "no es una fecha", "session_slot": "unica", "workout_id": 1}]
        self.assertEqual(upload_ledger.caducadas(r, hoy=date(2026, 8, 28)), [])

    def test_un_registro_que_no_existe_es_una_lista_vacia_no_un_error(self):
        with tempfile.TemporaryDirectory() as d:
            self.assertEqual(upload_ledger.cargar(Path(d)), [])

    def test_un_registro_corrupto_no_tumba_la_subida(self):
        with tempfile.TemporaryDirectory() as d:
            (Path(d) / upload_ledger.FICHERO).write_text("{roto", encoding="utf-8")
            self.assertEqual(upload_ledger.cargar(Path(d)), [])

    def test_el_registro_dice_de_donde_salio_cada_id(self):
        """La procedencia es lo unico que separa las dos formas de que un id
        acabe siendo borrable.

        Sin marca: lo devolvio Garmin cuando esta app creo el entreno -- es
        nuestro por definicion. Con marca: lo emparejo por el NOMBRE el script
        de limpieza, que es una deduccion, y la unica puerta por la que algo
        del atleta podria colarse en la lista de lo borrable."""
        subida = upload_ledger.anotar([], fecha="2026-09-02", franja="unica",
                                      workout_id=1, nombre="Umbral", deporte="run")
        adoptada = upload_ledger.anotar([], fecha="2026-09-02", franja="unica",
                                        workout_id=2, nombre="Umbral", deporte="run",
                                        adoptada=True)
        self.assertNotIn("adopted", subida[0])
        self.assertTrue(adoptada[0]["adopted"])

    def test_ida_y_vuelta_por_disco(self):
        with tempfile.TemporaryDirectory() as d:
            r = upload_ledger.anotar([], fecha="2026-09-02", franja="unica", workout_id=42,
                                     nombre="Umbral", deporte="run", huella_sesion="abc")
            upload_ledger.guardar(Path(d), r)
            self.assertEqual(upload_ledger.cargar(Path(d))[0]["workout_id"], 42)
            # Legible por una persona: este fichero se mira a mano cuando algo
            # no cuadra entre el plan y el reloj.
            crudo = json.loads((Path(d) / upload_ledger.FICHERO).read_text(encoding="utf-8"))
            self.assertEqual(crudo["entries"][0]["fingerprint"], "abc")


class Huella(unittest.TestCase):
    """La huella responde a "el reloj sigue teniendo esto?".

    Sin ella, regenerar una semana dejaba cada día marcado como subido -- las
    claves son fecha|franja y la semana nueva ocupa las mismas fechas -- con el
    entreno de la generación ANTERIOR todavía en el reloj. El atleta habría
    salido a hacer un entreno que su plan ya no pedía.
    """

    def test_cambiar_la_prescripcion_cambia_la_huella(self):
        self.assertNotEqual(upload_ledger.huella(_dia(segundos=600)),
                            upload_ledger.huella(_dia(segundos=900)))

    def test_reescribir_el_proposito_no_cambia_la_huella(self):
        """El propósito es texto para el atleta: puede reescribirse sin que
        cambie ni un minuto de lo que se corre, y marcar la sesión como
        desfasada por eso mandaría a re-subir sin motivo."""
        self.assertEqual(upload_ledger.huella(_dia(proposito="Umbral.")),
                         upload_ledger.huella(_dia(proposito="Otro texto entero.")))

    def test_es_estable_entre_llamadas(self):
        self.assertEqual(upload_ledger.huella(_dia()), upload_ledger.huella(_dia()))


class _GarminFalso:
    def get_workout_by_id(self, workout_id):
        return {}


class _ClienteFalso:
    def __init__(self):
        self.client = _GarminFalso()


class Reemplazo(unittest.TestCase):
    """Subir dos veces la misma sesión deja UNA en el calendario del atleta.

    Antes daba igual cuántas veces se pulsara Subir: cada clic creaba un
    entreno nuevo y programaba otra entrada el mismo día."""

    _ZONAS = AthleteZoneConfig(hr_zones={1: (123, 139), 2: (140, 153)})

    def _servicio(self, borrados, sube_ok=True):
        svc = GarminUploadService(_ClienteFalso())
        svc.delete_workout = lambda wid: (borrados.append(wid), True)[1]

        def _falla(payload, fecha):
            raise RuntimeError("Garmin dijo que no")

        svc._upload_and_schedule = (lambda payload, fecha: 999) if sube_ok else _falla
        return svc

    def test_una_subida_con_reemplazo_retira_el_entreno_anterior(self):
        borrados = []
        r = asyncio.run(self._servicio(borrados).upload_day(_dia(), self._ZONAS, replaces=111))
        self.assertTrue(r.success)
        self.assertEqual(borrados, [111])
        self.assertEqual(r.replaced_workout_id, 111)

    def test_si_la_subida_falla_el_entreno_anterior_sigue_en_garmin(self):
        """El orden importa: se sube el nuevo antes de retirar el viejo.

        Al revés, un fallo de red dejaría al atleta sin la sesión en el
        calendario por haber pulsado volver a subir."""
        borrados = []
        r = asyncio.run(self._servicio(borrados, sube_ok=False).upload_day(
            _dia(), self._ZONAS, replaces=111))
        self.assertFalse(r.success)
        self.assertEqual(borrados, [])

    def test_no_se_borra_a_si_mismo(self):
        borrados = []
        svc = self._servicio(borrados)
        svc._upload_and_schedule = lambda payload, fecha: 111
        asyncio.run(svc.upload_day(_dia(), self._ZONAS, replaces=111))
        self.assertEqual(borrados, [])

    def test_sin_entreno_anterior_no_se_borra_nada(self):
        borrados = []
        asyncio.run(self._servicio(borrados).upload_day(_dia(), self._ZONAS))
        self.assertEqual(borrados, [])


class Adopcion(unittest.TestCase):
    """Recoger entrenos que la app dejo sueltos ANTES de que existiera el
    registro (scripts/limpiar_garmin.py).

    El riesgo no es dejarse alguno: es adoptar el equivocado. Adoptar significa
    anotar "esta sesion esta en el reloj" y grabarle la huella del plan ACTUAL,
    asi que adoptar una copia de una generacion anterior hace que el registro
    diga "al dia" con otro entreno en la muneca.
    """

    def test_con_una_sola_copia_se_adopta(self):
        from scripts.limpiar_garmin import resolver_adopciones
        adoptar, retirar, _ = resolver_adopciones([(500, "Umbral - 2026-09-02", object())])
        self.assertEqual([t[0] for t in adoptar], [500])
        self.assertEqual(retirar, [])

    def test_con_varias_copias_no_se_adopta_NINGUNA(self):
        """Y no "la mas reciente". El id mayor solo dice cual se creo despues,
        no cual corresponde al plan de ahora.

        Caso real: al borrar a mano el entreno bueno de la natacion del
        2026-08-28, el script adopto una copia de una generacion anterior
        (1679448855, creada tres horas antes) y le grabo la huella del plan
        actual. Ante la duda se retiran todas y el atleta la vuelve a subir."""
        from scripts.limpiar_garmin import resolver_adopciones
        d = object()
        candidatos = [(100, "Tecnica - 2026-08-28", d),
                      (300, "Tecnica - 2026-08-28", d),
                      (500, "Umbral - 2026-09-02", d)]
        adoptar, retirar, _ = resolver_adopciones(candidatos)
        self.assertEqual([t[0] for t in adoptar], [500])
        self.assertEqual(sorted(w for w, _ in retirar), [100, 300])

    def test_las_demas_sesiones_no_se_ven_afectadas(self):
        from scripts.limpiar_garmin import resolver_adopciones
        d = object()
        adoptar, _, _ = resolver_adopciones(
            [(1, "A - 2026-09-01", d), (2, "A - 2026-09-01", d),
             (3, "B - 2026-09-02", d), (4, "C - 2026-09-03", d)])
        self.assertEqual(sorted(t[1] for t in adoptar), ["B - 2026-09-02", "C - 2026-09-03"])


if __name__ == "__main__":
    unittest.main()
