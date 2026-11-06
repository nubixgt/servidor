"""El macrociclo aprende de este atleta: cómo respondió a cada fase.

Ver services/ai/respuesta_a_las_fases.py. Los marcadores de partida son los del
atleta 2 el 2026-09-13 (traza `reasoning/2026-09-13T13-42-33`): 10K previsto
57:29 (3.449 s), ritmo umbral 5:52/km (353 s), umbral de lactato 176, HRV de 7
días 60 ms con base 57-84. Los de las fases siguientes son inventados: todavía
no hay ninguna fase cerrada con marcadores, y por eso el bloque se calla.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.respuesta_a_las_fases import (  # noqa: E402
    fase_de_la_semana,
    marcadores_de_la_semana,
    render_respuesta_a_las_fases,
)
from services.ai.track_record import record_week  # noqa: E402

BASE = {"nombre": "Base", "reparto": "piramidal", "techo": 0.2, "inicio_macro": "2026-09-11"}
CONSTRUCCION = {"nombre": "Construcción", "reparto": "polarizado", "techo": 0.25, "inicio_macro": "2026-09-11"}


def semana(desde, hasta, fase, diez_k, umbral, hrv, sesiones=(4, 1, 0), deriva=None, ejecutadas=5.0):
    return {
        "from": desde, "to": hasta, "prescribed_hours": 6.0, "executed_hours": ejecutadas,
        "quality_prescribed": 1, "quality_done": 1,
        "marcadores": {
            "fecha": hasta, "prediccion_10k_s": diez_k, "umbral_ritmo_s_km": umbral, "lthr": 176,
            "hrv_media_7d": hrv, "fc_reposo": 52,
            "sesiones": {"suave": sesiones[0], "umbral": sesiones[1], "alta": sesiones[2], "altas_por_ritmo": 0},
            "deriva": deriva or [], "fase": fase,
        },
    }


def escribir(directorio: Path, semanas):
    (directorio / "track_record.jsonl").write_text(
        "\n".join(json.dumps(s, ensure_ascii=False) for s in semanas) + "\n", encoding="utf-8")


class ElBloque(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self._tmp.cleanup)
        self.dir = Path(self._tmp.name)

    def test_sin_fases_medidas_cero_tokens(self):
        """El caso de hoy: ninguna semana cerrada trae marcadores."""
        escribir(self.dir, [{"from": "2026-09-08", "to": "2026-09-10", "executed_hours": 1.5, "prescribed_hours": 2.9}])
        self.assertEqual(render_respuesta_a_las_fases(self.dir), "")
        self.assertEqual(render_respuesta_a_las_fases(self.dir / "no_existe"), "")

    def test_una_fase_de_una_semana_no_dice_nada(self):
        escribir(self.dir, [semana("2026-09-14", "2026-09-20", BASE, 3449, 353, 60)])
        self.assertEqual(render_respuesta_a_las_fases(self.dir), "")

    def test_las_semanas_abiertas_no_cuentan(self):
        abierta = semana("2026-09-21", "2026-09-27", BASE, 3440, 352, 61)
        abierta["executed_hours"] = None
        escribir(self.dir, [semana("2026-09-14", "2026-09-20", BASE, 3449, 353, 60), abierta])
        self.assertEqual(render_respuesta_a_las_fases(self.dir), "")

    def test_cuenta_lo_planificado_lo_hecho_y_como_se_movieron_los_marcadores(self):
        escribir(self.dir, [
            semana("2026-09-14", "2026-09-20", BASE, 3449, 353, 60, sesiones=(5, 1, 0)),
            semana("2026-09-21", "2026-09-27", BASE, 3420, 350, 62, sesiones=(4, 1, 1),
                   deriva=[{"fecha": "2026-09-26", "deriva_pct": 4.6}]),
            semana("2026-09-28", "2026-10-04", BASE, 3400, 348, 63, sesiones=(5, 1, 0),
                   deriva=[{"fecha": "2026-10-03", "deriva_pct": 2.0}]),
            semana("2026-10-05", "2026-10-11", CONSTRUCCION, 3390, 347, 61, sesiones=(4, 0, 2)),
            semana("2026-10-12", "2026-10-18", CONSTRUCCION, 3360, 344, 64, sesiones=(4, 0, 2)),
        ])
        bloque = render_respuesta_a_las_fases(self.dir)
        self.assertIn("asociaciones, no causas", bloque)
        self.assertIn("Fase «Base» (piramidal, techo 20 %) · 3 semanas cerradas", bloque)
        self.assertIn("18 sesiones — 14 suaves, 3 de umbral, 1 altas (22 % duras)", bloque)
        # Lo que no cambió no ocupa sitio.
        self.assertNotIn("umbral de lactato", bloque)
        # La Base se mide desde su primera semana; no hay fase anterior.
        self.assertIn("10K previsto 57:29 → 56:40 (0:49 más rápido)", bloque)
        self.assertIn("deriva media 3,3 % (2 sesiones)", bloque)
        # La Construcción se mide desde el CIERRE de la última semana de la Base.
        self.assertIn("Fase «Construcción» (polarizado, techo 25 %)", bloque)
        self.assertIn("10K previsto 56:40 → 56:00 (0:40 más rápido)", bloque)
        self.assertIn("ritmo umbral 5:48 → 5:44", bloque)

    def test_dos_base_de_dos_macrociclos_son_dos_fases(self):
        otra = {**BASE, "inicio_macro": "2027-01-04"}
        escribir(self.dir, [
            semana("2026-09-14", "2026-09-20", BASE, 3449, 353, 60),
            semana("2026-09-21", "2026-09-27", BASE, 3420, 350, 62),
            semana("2027-01-04", "2027-01-10", otra, 3500, 360, 55),
            semana("2027-01-11", "2027-01-17", otra, 3480, 358, 56),
        ])
        self.assertEqual(render_respuesta_a_las_fases(self.dir).count("Fase «Base»"), 2)

    def test_ningun_numero_en_negrita(self):
        escribir(self.dir, [
            semana("2026-09-14", "2026-09-20", BASE, 3449, 353, 60),
            semana("2026-09-21", "2026-09-27", BASE, 3420, 350, 62),
        ])
        self.assertNotIn("**", render_respuesta_a_las_fases(self.dir))


class LosMarcadoresSeGuardanAlCerrar(unittest.TestCase):
    ZONAS = {"run": {"1": [122, 139], "2": [140, 152], "3": [153, 166], "4": [167, 179], "5": [180, None]}}

    def _garmin(self):
        rapido = [{"duration_s": 120, "averageHR_bpm": 158, "maxHR_bpm": 168, "averageSpeed_ms": 1000 / 307,
                   "distance_m": 391, "elevationGain_m": 0}] * 5
        vo2max = SimpleNamespace(start_time="2026-09-16T07:00:00.0", activity_type="running", laps=rapido,
                                 summary=SimpleNamespace(duration=600))
        return SimpleNamespace(
            race_predictions=[SimpleNamespace(distance_key="10K", time_seconds=3449)],
            user_profile=SimpleNamespace(vo2max_running=44.0),
            physiological_markers=None,
            recent_activities=[vo2max],
            garmin_hr_zones=self.ZONAS,
        )

    def test_la_foto_de_la_semana(self):
        prescripcion = {"lactate_threshold_hr": 176, "threshold_pace_sec_per_km": 352.7,
                        "threshold_pace_source": "garmin_lt", "pace_bands": {"maraton": [410.0, 421.0]}}
        recuperacion = {"hrv_weekly_avg": 60, "hrv_baseline_low": 57, "hrv_baseline_high": 84, "resting_heart_rate": 52}
        m = marcadores_de_la_semana(self._garmin(), prescripcion, recuperacion, "2026-09-14", "2026-09-20", BASE)
        self.assertEqual(m["prediccion_10k_s"], 3449)
        self.assertEqual(m["umbral_ritmo_s_km"], 352.7)
        self.assertEqual((m["lthr"], m["vo2max"], m["hrv_media_7d"], m["hrv_base"]), (176, 44.0, 60, [57, 84]))
        self.assertEqual(m["sesiones"]["alta"], 1)
        self.assertEqual(m["sesiones"]["altas_por_ritmo"], 1)
        self.assertEqual(m["fase"]["nombre"], "Base")

    def test_sin_fechas_no_hay_foto(self):
        self.assertIsNone(marcadores_de_la_semana(self._garmin(), None, None, "", ""))

    def test_la_fase_sale_de_la_posicion_del_macrociclo(self):
        posicion = {"phase_name": "Base", "intensity_distribution": "piramidal",
                    "hard_session_share_max": 0.2, "week_number": 3}
        fase = fase_de_la_semana({"season_start_date": "2026-09-11"}, "2026-09-25", posicion)
        self.assertEqual(fase, {"nombre": "Base", "reparto": "piramidal", "techo": 0.2, "semana": 3,
                                "inicio_macro": "2026-09-11"})
        self.assertIsNone(fase_de_la_semana({}, "2026-09-25", {"phase_name": None}))

    def test_volver_a_prescribir_la_semana_no_borra_sus_marcadores(self):
        """Lo EJECUTADO no se desanota, y la foto al cerrar es parte de lo ejecutado."""
        with tempfile.TemporaryDirectory() as tmp:
            d = Path(tmp)
            dias = [{"date": "2026-09-14", "sport": "run", "total_duration_minutes": 50, "intensity_class": "easy"}]
            adherencia = {"sessions": [{"date": "2026-09-14", "status": "completed", "actual_minutes": 50}],
                          "completed": 1, "executed_hours": 0.8}
            record_week(d, {"week_number": 1, "days": dias}, adherencia, marcadores={"fecha": "2026-09-20", "vo2max": 44.0})
            segunda = record_week(d, {"week_number": 1, "days": dias}, None)
            self.assertEqual(segunda["marcadores"], {"fecha": "2026-09-20", "vo2max": 44.0})


if __name__ == "__main__":
    unittest.main()
