"""La racha semanal, el nivel y las medallas (Beta 3.0, F8). Sin IA.

Reglas del responsable (2026-10-10): una semana suma con el 70 % de sus sesiones y sin pasar
del 120 % de sus horas; por encima del 120 % ni suma ni rompe; una molestia o enfermedad
declarada la congela. Las medallas sólo de los deportes que hace.

Las semanas son del expediente real del atleta 2: del 15 al 20-sep (0 de 2 hechas), del 21
al 22-sep (1 de 4) y del 26-sep al 4-oct (6 de 6, 7,91 h de 7,02), más las dos abiertas.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import progreso  # noqa: E402


def _semana(desde, hasta, hechas, perdidas, horas, mandadas, km=None, **extra):
    return {"from": desde, "to": hasta, "completed_sessions": hechas, "modified_sessions": 0,
            "under_intensity_sessions": 0, "missed_sessions": perdidas, "executed_hours": horas,
            "prescribed_hours": mandadas, "executed_by_discipline": km, **extra}


DEL_ATLETA_2 = [
    _semana("2026-09-15", "2026-09-20", 0, 2, 1.51, 3.17),
    _semana("2026-09-21", "2026-09-22", 1, 3, 0.45, 2.82, {"carrera": {"hours": 0.45, "km": 5.17}}),
    _semana("2026-09-26", "2026-10-04", 6, 0, 7.91, 7.02, {"bici": {"hours": 2.55, "km": 30.74},
                                                          "natacion": {"hours": 1.4, "km": 6.47},
                                                          "carrera": {"hours": 1.2, "km": 6.35}},
            quality_prescribed=2, quality_done=2),
    {"from": "2026-10-12", "to": "2026-10-18", "prescribed_hours": 3.85, "executed_hours": None,
     "completed_sessions": None},
]


class LaRacha(unittest.TestCase):

    def test_la_del_atleta_2(self):
        r = progreso.racha(DEL_ATLETA_2)
        self.assertEqual((r["actual"], r["mejor"], r["semanas_constantes"], r["nivel"]), (1, 1, 1, 1))
        self.assertEqual([progreso.como_cuenta(w) for w in DEL_ATLETA_2], ["rompe", "rompe", "suma", "abierta"])

    def test_al_130_por_ciento_ni_suma_ni_rompe(self):
        semanas = [_semana("2026-09-28", "2026-10-04", 5, 0, 7.0, 7.0),
                   _semana("2026-10-05", "2026-10-11", 5, 0, 9.1, 7.0),   # 130 %
                   _semana("2026-10-12", "2026-10-18", 5, 0, 7.0, 7.0)]
        self.assertEqual(progreso.como_cuenta(semanas[1]), "neutra")
        r = progreso.racha(semanas)
        self.assertEqual((r["actual"], r["semanas_constantes"]), (2, 2), "no suma, pero tampoco rompe")

    def test_una_molestia_la_congela(self):
        semanas = [_semana("2026-09-28", "2026-10-04", 5, 0, 7.0, 7.0),
                   _semana("2026-10-05", "2026-10-11", 1, 4, 1.0, 7.0),
                   _semana("2026-10-12", "2026-10-18", 5, 0, 7.0, 7.0)]
        sin = progreso.racha(semanas)
        con = progreso.racha(semanas, checkins=[{"date": "2026-10-04", "niggle_location": "rodilla izquierda",
                                                 "niggle_pain": 2}])
        self.assertEqual(sin["actual"], 1, "sin molestia, la semana floja rompe")
        self.assertEqual(con["actual"], 2, "con la molestia declarada, se congela")

    def test_una_lesion_vieja_no_congela_para_siempre(self):
        semanas = [_semana("2026-10-05", "2026-10-11", 1, 4, 1.0, 7.0)]
        lesion = [{"start_date": "2026-03-01", "status": "active"}]
        self.assertEqual(progreso.como_cuenta(semanas[0], lesiones=lesion), "rompe")

    def test_una_semana_sin_plan_rompe(self):
        semanas = [_semana("2026-09-14", "2026-09-20", 5, 0, 7.0, 7.0),
                   _semana("2026-10-05", "2026-10-11", 5, 0, 7.0, 7.0)]
        self.assertEqual(progreso.racha(semanas)["actual"], 1)

    def test_rehacer_una_semana_no_la_cuenta_dos_veces(self):
        semanas = [_semana("2026-10-05", "2026-10-11", 0, 5, 0.0, 7.0, ts="2026-10-04T10:00"),
                   _semana("2026-10-05", "2026-10-11", 5, 0, 7.0, 7.0, ts="2026-10-04T12:00")]
        self.assertEqual(progreso.racha(semanas)["semanas_constantes"], 1)

    def test_los_niveles(self):
        self.assertEqual(progreso.NIVELES, (1, 3, 6, 10, 16, 24, 36))


class LasMedallas(unittest.TestCase):

    def _ids(self, **kw):
        return {m["id"]: m for m in progreso.medallas(DEL_ATLETA_2, hoy=date(2026, 10, 11), **kw)}

    def test_las_del_atleta_2(self):
        m = self._ids()
        self.assertTrue(m["primera_semana"]["conseguida"])
        self.assertTrue(m["todas_las_clave"]["conseguida"])
        self.assertEqual(m["km_bici_250"]["progreso"], "31 de 250 km")

    def test_un_nadador_no_recibe_medallas_de_correr(self):
        nadador = [_semana("2026-10-05", "2026-10-11", 4, 0, 5.0, 5.0, {"natacion": {"hours": 5.0, "km": 12.0}})]
        ids = {m["id"] for m in progreso.medallas(nadador)}
        self.assertTrue(any(i.startswith("km_natación") for i in ids))
        self.assertFalse(any(i.startswith("km_carrera") or i.startswith("km_bici") for i in ids))

    def test_subir_la_carga_como_en_la_pantalla(self):
        logs = [{"fecha": "2026-09-23", "exercises": [{"exercise_id": "a1", "name": "Sentadilla", "weight_kg": 60}]},
                {"fecha": "2026-09-30", "exercises": [{"exercise_id": "a1", "name": "Sentadilla", "weight_kg": 62.5}]}]
        self.assertTrue(self._ids(registros_de_fuerza=logs)["fuerza_carga"]["conseguida"])
        self.assertFalse(self._ids(registros_de_fuerza=logs[:1])["fuerza_carga"]["conseguida"])

    def test_cuatro_checkin_seguidos(self):
        c = [{"date": f} for f in ("2026-09-14", "2026-09-21", "2026-09-28", "2026-10-05")]
        self.assertTrue(self._ids(checkins=c)["checkin_4"]["conseguida"])

    def test_vo2max_mas_uno_en_ocho_semanas(self):
        vo2 = [{"date": "2026-08-01", "value": 49.0}, {"date": "2026-10-09", "value": 51.0}]
        self.assertTrue(self._ids(vo2max=vo2)["vo2max"]["conseguida"])


class LoQueSeComparte(unittest.TestCase):

    def test_la_tarjeta_no_lleva_ni_nombre_ni_salud(self):
        js = (Path(__file__).resolve().parents[2] / "Frontend/src/utils/compartir.js").read_text(encoding="utf-8")
        cuerpo = js[js.index("export async function tarjetaDeProgreso"):js.index("export async function compartirTarjeta")]
        for prohibido in ("name", "nombre_del", "vo2", "peso", "weight", "sleep", "hrv", "pulso"):
            self.assertNotIn(prohibido, cuerpo.lower().replace("m.nombre", ""), prohibido)


if __name__ == "__main__":
    unittest.main()
