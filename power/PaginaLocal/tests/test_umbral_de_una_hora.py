"""Un umbral es el esfuerzo de ~1 hora: el de Garmin que su curva sostiene 2 horas no lo es.

Atleta 2, 2026-09-23 (reasoning/2026-09-23T08-36-08/prescription.json). Garmin le daba
5:53/km de umbral de lactato (`lactate_threshold_speed`) con estas predicciones: 5K
25:24 (5:05), 10K 54:24 (5:26), media 2:04:35 (5:54), maratón 4:36:42 (6:33). El umbral
era su ritmo de media maratón -- unos 2 h de esfuerzo -- y pasaba el único filtro que
había (entre el 5K y el maratón). De ahí salieron un tempo a 5:46-6:14/km, casi su
ritmo de maratón, y series de VO2max a 5:18-5:39, más lentas que el 5K que corrió el
2026-09-20 (5,2 km en 27', ~5:11/km).

La corrección es la MÍNIMA: se lleva al ritmo que sostendría 90' (el borde de lo que
es un umbral), no al de una hora, porque sus predicciones eran optimistas (predecían
25:24 en el 5K). Queda en 5:43 y las series de VO2max en 5:09-5:29: su 5K real.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.prescription import (  # noqa: E402
    VENTANA_DE_UMBRAL_MIN,
    build_prescription,
    format_pace,
    minutos_que_sostiene,
    prescription_to_state,
    procedencia_de_umbrales,
    render_prescription_card,
)
from services.garmin.models import GarminData, RacePrediction, UserProfile  # noqa: E402

ATLETA_2 = [RacePrediction("5K", 5000.0, 1524), RacePrediction("10K", 10000.0, 3264),
            RacePrediction("half_marathon", 21097.5, 7475), RacePrediction("marathon", 42195.0, 16602)]


def _curva(t5k, t10k, tmedia, tmaraton):
    return [RacePrediction("5K", 5000.0, t5k), RacePrediction("10K", 10000.0, t10k),
            RacePrediction("half_marathon", 21097.5, tmedia), RacePrediction("marathon", 42195.0, tmaraton)]


# Corredores de otros niveles, con curvas de forma realista.
RAPIDO = _curva(1020, 2100, 4650, 9900)      # 10K en 35'
MEDIO = _curva(1320, 2700, 6000, 12600)      # 10K en 45'
LENTO = _curva(2040, 4200, 9600, 21000)      # 10K en 70'


def _atleta(ritmo_lt_s_km, predicciones):
    return GarminData(user_profile=UserProfile(lactate_threshold_speed=1000.0 / ritmo_lt_s_km),
                      race_predictions=list(predicciones))


class ElCasoReal(unittest.TestCase):
    def test_su_umbral_de_garmin_es_un_esfuerzo_de_dos_horas(self):
        self.assertAlmostEqual(minutos_que_sostiene(352.7, ATLETA_2), 119, delta=3)

    def test_se_lleva_al_borde_no_a_la_hora(self):
        p = build_prescription(_atleta(352.7, ATLETA_2), {})
        self.assertEqual(format_pace(p.threshold_pace_sec_per_km), "5:43")
        self.assertEqual(p.threshold_pace_source, "garmin_ajustado")
        self.assertEqual(p.umbral_ajustado_desde, 352.7)
        self.assertEqual(str(p.pace_bands["vo2max"]), "5:09-5:29/km")  # su 5K real, ~5:11

    def test_la_ficha_no_dice_medido(self):
        p = build_prescription(_atleta(352.7, ATLETA_2), {})
        ficha = render_prescription_card(p)
        self.assertIn("ajustado desde el de Garmin (5:53/km)", ficha)
        self.assertNotIn("medido por Garmin", ficha)
        self.assertTrue(any("Se ajustó el ritmo umbral de Garmin" in w for w in p.warnings))
        self.assertIn("AJUSTADO", procedencia_de_umbrales(prescription_to_state(p)))


class ParaCualquierAtleta(unittest.TestCase):
    def test_un_umbral_de_garmin_razonable_no_se_toca_en_ningun_nivel(self):
        """El ritmo de ~50' de cada curva: dentro de la ventana, se queda tal cual."""
        for nombre, curva, lt in (("rápido", RAPIDO, 213.0), ("medio", MEDIO, 272.0), ("lento", LENTO, 412.0)):
            with self.subTest(atleta=nombre):
                p = build_prescription(_atleta(lt, curva), {})
                self.assertEqual(p.threshold_pace_source, "garmin_lt")
                self.assertAlmostEqual(p.threshold_pace_sec_per_km, lt, places=3)
                self.assertIsNone(p.umbral_ajustado_desde)

    def test_uno_de_media_maraton_lenta_se_ajusta_en_cualquier_nivel(self):
        """El ritmo de la media como "umbral": para el de 10K en 35' la media dura 77' y
        vale; para el de 70', 160', y no."""
        rapido = build_prescription(_atleta(RAPIDO[2].pace_sec_per_km, RAPIDO), {})
        self.assertEqual(rapido.threshold_pace_source, "garmin_lt")
        lento = build_prescription(_atleta(LENTO[2].pace_sec_per_km, LENTO), {})
        self.assertEqual(lento.threshold_pace_source, "garmin_ajustado")
        self.assertLess(lento.threshold_pace_sec_per_km, LENTO[2].pace_sec_per_km)

    def test_uno_demasiado_rapido_se_lleva_al_otro_borde(self):
        """Un "umbral" a ritmo de 5K (su curva lo sostiene 23') se hace MÁS LENTO, nunca más rápido.
        Con 2 s/km más ya lo sostiene 28' y se queda: la ventana es ancha a propósito."""
        p = build_prescription(_atleta(MEDIO[0].pace_sec_per_km + 0.5, MEDIO), {})
        self.assertEqual(p.threshold_pace_source, "garmin_ajustado")
        self.assertGreater(p.threshold_pace_sec_per_km, MEDIO[0].pace_sec_per_km + 0.5)
        self.assertEqual(build_prescription(_atleta(MEDIO[0].pace_sec_per_km + 2, MEDIO), {}).threshold_pace_source,
                         "garmin_lt")
        self.assertAlmostEqual(minutos_que_sostiene(p.threshold_pace_sec_per_km, MEDIO),
                               VENTANA_DE_UMBRAL_MIN[0], delta=0.5)

    def test_uno_absurdo_sigue_estimandose_desde_la_hora(self):
        """Más lento que su maratón no es un umbral mal calibrado: no vale. Como siempre."""
        p = build_prescription(_atleta(ATLETA_2[3].pace_sec_per_km + 20, ATLETA_2), {})
        self.assertEqual(p.threshold_pace_source, "race_prediction")

    def test_el_que_escribio_el_atleta_manda_y_se_avisa(self):
        p = build_prescription(_atleta(300.0, ATLETA_2), {"lactate_threshold_pace_sec_per_km": 352.7})
        self.assertEqual(p.threshold_pace_source, "manual")
        self.assertEqual(p.threshold_pace_sec_per_km, 352.7)
        self.assertTrue(any("Tu ritmo umbral escrito puede no ser un umbral" in w for w in p.warnings))

    def test_sin_predicciones_no_hay_con_que_juzgarlo(self):
        p = build_prescription(GarminData(user_profile=UserProfile(lactate_threshold_speed=1000 / 352.7)), {})
        self.assertEqual(p.threshold_pace_source, "garmin_lt")
        self.assertEqual(p.threshold_pace_sec_per_km, 352.7)


class ElBuclePorFaseNoConfundeElMetodoConLaForma(unittest.TestCase):
    """Pasar del umbral de Garmin (5:53) al ajustado (5:43) cambia el método, no la forma."""

    def _bloque(self, fuente_final):
        import json
        import tempfile

        from services.ai.respuesta_a_las_fases import render_respuesta_a_las_fases

        fase = {"nombre": "Base", "reparto": "piramidal", "techo": 0.2, "inicio_macro": "2026-09-11"}
        semanas = []
        for i, (desde, hasta, umbral, fuente) in enumerate((
                ("2026-09-14", "2026-09-20", 352.7, "garmin_lt"),
                ("2026-09-21", "2026-09-27", 343.0, fuente_final),
                ("2026-09-28", "2026-10-04", 343.0, fuente_final))):
            semanas.append({"from": desde, "to": hasta, "prescribed_hours": 6.0, "executed_hours": 5.0,
                            "quality_prescribed": 1, "quality_done": 1,
                            "marcadores": {"fecha": hasta, "prediccion_10k_s": 3264 - i, "umbral_ritmo_s_km": umbral,
                                           "umbral_ritmo_fuente": fuente, "lthr": 176, "hrv_media_7d": 60,
                                           "fc_reposo": 50, "deriva": [], "fase": fase,
                                           "sesiones": {"suave": 4, "umbral": 1, "alta": 0, "altas_por_ritmo": 0}}})
        with tempfile.TemporaryDirectory() as d:
            (Path(d) / "track_record.jsonl").write_text(
                chr(10).join(json.dumps(x) for x in semanas) + chr(10), encoding="utf-8")
            return render_respuesta_a_las_fases(Path(d))

    def test_con_la_misma_procedencia_se_compara(self):
        self.assertIn("ritmo umbral 5:53 → 5:43", self._bloque("garmin_lt"))

    def test_con_otra_procedencia_no_es_una_mejora(self):
        bloque = self._bloque("garmin_ajustado")
        self.assertIn("Fase «Base»", bloque)  # el bloque sale: la prueba no pasa en vacío
        self.assertNotIn("5:53 → 5:43", bloque)
        self.assertIn("ritmo umbral: cambió de dónde sale, no se compara", bloque)


if __name__ == "__main__":
    unittest.main()
