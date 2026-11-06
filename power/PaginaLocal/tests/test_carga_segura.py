"""Una semana extrema se limita y se le avisa al atleta; una alta sólo se anota (2026-09-23/25).

Números reales del atleta 2, semana del 2026-09-28 (regenerada el 2026-09-23 con su
petición de natación de 2,5 y 3,5 km): 190' de natación, 190' de bici y 115' de carrera,
contra ~4,7 h de resistencia normales en sus últimas 4 semanas y una crónica de 285. Sus
tasas medidas: natación suave 43/h, carrera suave 46/h, calidad 111/h, bici suave 29/h,
fuerza 7 por sesión. La carga estimada salió 344 (1,2 veces): no alta por carga, sí por
horas (8,2 h, +76 %). Es lo que pidió: se avisa, no se recorta.

La semana "Santa Claus" es la que preocupaba al responsable: 3 h diarias, todos los días.
"""

import importlib
import sys
import unittest
from datetime import date
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import carga_segura as cs  # noqa: E402

TASAS = {"swim:suave": {"por_hora": 43}, "run:suave": {"por_hora": 46}, "run:calidad": {"por_hora": 111},
         "swim:calidad": {"por_hora": 84}, "bike:suave": {"por_hora": 29}, "fuerza": {"por_sesion": 7}}
PETICION = "Natación: quiero sesiones de mínimo 2,5 km, y los viernes una sesión larga de 3,5 km."


def _dia(fecha, deporte, minutos, clase="easy"):
    return {"date": fecha, "sport": deporte, "intensity_class": clase, "total_duration_minutes": minutos}


SEMANA_DE_JOSE = [
    _dia("2026-09-28", "strength", 60, "strength_freeform"), _dia("2026-09-29", "strength", 120, "strength_freeform"),
    _dia("2026-09-29", "bike", 70), _dia("2026-09-30", "run", 60, "vo2max"), _dia("2026-09-30", "swim", 80),
    _dia("2026-10-01", "strength", 42, "strength_freeform"), _dia("2026-10-02", "swim", 110, "long_run"),
    _dia("2026-10-02", "run", 40), {"date": "2026-10-03", "sport": "rest", "is_rest": True},
    _dia("2026-10-04", "bike", 120, "tempo_threshold"), _dia("2026-10-04", "run", 15, "cross_training"),
]


def _revisar(dias, **extra):
    datos = {"tasas": TASAS, "cronica": 285, "horas_de_resistencia": 4.7, "carrera_mas_larga_min": 95,
             "dias_del_bloque": 7, "deportes_pedidos": cs.deportes_de_las_peticiones(PETICION)}
    datos.update(extra)
    return cs.revisar_la_carga(dias, **datos)


class LaSemanaRealDeJose(unittest.TestCase):
    def test_es_alta_por_horas_y_no_extrema(self):
        r = _revisar(SEMANA_DE_JOSE)
        self.assertEqual(r["nivel"], cs.ALTA)
        self.assertEqual([s["tipo"] for s in r["senales"]], ["horas"])
        self.assertEqual(r["horas_de_resistencia"], 8.2)  # la fuerza no cuenta

    def test_una_semana_alta_no_le_avisa_al_atleta(self):
        """El responsable, 2026-09-25: el aviso de semana "alta" ("volumen alto… la carga queda en
        1,2 veces…") era raro y confuso; lo quiere sólo cuando el volumen sea de verdad muy alto.
        Esta semana (+76 % de horas, 1,2 veces su carga) es alta: se anota en el log y el atleta
        no ve nada, con petición o sin ella."""
        self.assertEqual(cs.aviso_para_el_atleta(_revisar(SEMANA_DE_JOSE), PETICION), "")
        self.assertEqual(cs.aviso_para_el_atleta(_revisar(SEMANA_DE_JOSE, deportes_pedidos=set()), ""), "")


class SantaClaus(unittest.TestCase):
    def test_la_extrema_se_avisa_claro_y_solo_con_sus_razones(self):
        aviso = cs.aviso_para_el_atleta(_revisar(self.SEMANA, deportes_pedidos={"run"}), PETICION)
        self.assertTrue(aviso.startswith("Esta semana es mucho más de lo que sueles hacer:"))
        self.assertIn("veces la que vienes absorbiendo", aviso)            # carga, extrema
        self.assertIn("h de natación, bici y carrera frente a tus ~4,7 h", aviso)  # horas, extrema
        self.assertNotIn("ningún día de descanso", aviso)                   # sólo alta: al log
        self.assertIn("Es por lo que pediste (carrera).", aviso)
        # «Rehacer semana» no existe desde la v1.1 y el chat no cambia la semana (2026-09-24).
        self.assertIn("«Eliminar plan» → esta semana", aviso)
        self.assertNotIn("Rehacer", aviso)

    def test_sin_peticion_no_se_le_atribuye(self):
        """Semana real del atleta 2 del 2026-09-23, sin ninguna petición vigente: el aviso decía
        "Es lo que pediste". Lo que nadie pidió no se le atribuye al atleta."""
        aviso = cs.aviso_para_el_atleta(_revisar(self.SEMANA, deportes_pedidos=set()), "")
        self.assertTrue(aviso)
        self.assertNotIn("pediste", aviso)

    SEMANA = [_dia(f"2026-09-{28 + i}", s, 180) for i, s in enumerate(["run", "bike", "swim"] * 2 + ["run"])]

    def test_tres_horas_diarias_es_extrema(self):
        r = _revisar(self.SEMANA, deportes_pedidos={"run", "bike", "swim"})
        self.assertEqual(r["nivel"], cs.EXTREMA)
        self.assertEqual({s["tipo"] for s in r["senales"] if s["nivel"] == cs.EXTREMA}, {"carga", "horas"})

    def test_a_la_decision_se_le_pide_progresion_con_la_evidencia(self):
        texto = cs.mensaje_para_la_decision(_revisar(self.SEMANA, deportes_pedidos={"run"}))
        self.assertIn("Garmin llama 'alta' a 1,5 y 'muy alta' a 2,0", texto)
        self.assertIn("en progresión", texto)
        self.assertIn("sin pagarla con los otros deportes", texto)
        self.assertIn("con qué evidencia", texto)

    def test_el_pico_de_carrera_sale_una_vez(self):
        r = _revisar(self.SEMANA)
        self.assertEqual(sum(1 for s in r["senales"] if s["tipo"] == "carrera_larga"), 1)


class LasSenales(unittest.TestCase):
    def test_una_carrera_de_mas_del_doble_es_extrema(self):
        """Frandsen 2025: más del doble de la más larga de 30 días, HRR 2,28."""
        r = _revisar([_dia("x", "run", 200)], horas_de_resistencia=None, tasas=None)
        self.assertEqual(r["nivel"], cs.EXTREMA)
        self.assertIn("Frandsen 2025", r["senales"][0]["texto"])

    def test_una_tirada_un_poco_mas_larga_no_dice_nada(self):
        self.assertEqual(_revisar([_dia("x", "run", 110)], horas_de_resistencia=None, tasas=None)["nivel"], cs.NORMAL)

    def test_un_bloque_de_cinco_dias_se_mide_contra_su_parte(self):
        dias = [_dia(f"d{i}", "swim", 65) for i in range(5)]  # 5,4 h en 5 días
        self.assertEqual(_revisar(dias, dias_del_bloque=5, tasas=None)["nivel"], cs.ALTA)   # vs 3,4 h
        self.assertEqual(_revisar(dias, dias_del_bloque=7, tasas=None)["nivel"], cs.NORMAL)  # vs 4,7 h

    def test_sin_historial_se_abstiene(self):
        r = cs.revisar_la_carga(SantaClaus.SEMANA, tasas=None, cronica=None, horas_de_resistencia=None,
                                carrera_mas_larga_min=None, dias_del_bloque=6)
        self.assertEqual(r["nivel"], cs.NORMAL)
        self.assertEqual(cs.aviso_para_el_atleta(r), "")

    def test_siete_dias_sin_descanso_no_es_extremo(self):
        dias = [_dia(f"2026-09-{28 + i:02d}" if i < 3 else f"2026-10-{i - 2:02d}", "run", 30) for i in range(7)]
        r = _revisar(dias, tasas=None, horas_de_resistencia=None, carrera_mas_larga_min=None)
        self.assertEqual((r["nivel"], r["senales"][0]["tipo"]), (cs.ALTA, "sin_descanso"))
        self.assertEqual(cs.aviso_para_el_atleta(r), "")

    def test_siete_dias_con_entreno_no_son_siete_seguidos(self):
        """Bloque real del atleta 2 del 2026-09-26 al 2026-10-04 (nueve días): descanso los dos
        sábados y siete días con entreno entre medias. El log decía "siete días seguidos sin
        descanso" y el aviso "ningún día de descanso". Su racha más larga va del domingo 27 al
        viernes 2: seis días."""
        fechas = ["2026-09-27", "2026-09-28", "2026-09-29", "2026-09-30", "2026-10-01", "2026-10-02", "2026-10-04"]
        dias = [_dia(f, "run", 30) for f in fechas]
        dias += [{"date": "2026-09-26", "sport": "rest", "is_rest": True},
                 {"date": "2026-10-03", "sport": "rest", "is_rest": True}]
        r = _revisar(dias, tasas=None, horas_de_resistencia=None, carrera_mas_larga_min=None, dias_del_bloque=9)
        self.assertEqual(cs.racha_sin_descanso(fechas), 6)
        self.assertEqual(r["nivel"], cs.NORMAL)


class ElHistorial(unittest.TestCase):
    def test_la_carrera_mas_larga_de_30_dias(self):
        def act(fecha, tipo, segundos):
            return SimpleNamespace(start_time=f"{fecha}T07:00:00", activity_type=tipo,
                                   summary=SimpleNamespace(duration=segundos))
        acts = [act("2026-09-20", "running", 1604), act("2026-09-05", "running", 5700),
                act("2026-08-01", "running", 9300), act("2026-09-10", "cycling", 14000)]
        self.assertEqual(cs.historial_de_carga(acts, hoy=date(2026, 9, 23)), {"carrera_mas_larga_30d_min": 95})


class ElNodoLoUsa(unittest.TestCase):
    def test_la_extrema_vuelve_a_la_decision_y_el_aviso_va_en_la_semana(self):
        nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")
        fuente = Path(nodo.__file__).read_text(encoding="utf-8")
        # 2026-09-24: un día mal fechado también abre la vuelta (number_audit.dias_mal_nombrados).
        self.assertIn("if fuera or extrema or dias_mal:", fuente)
        self.assertIn("semana.load_warning = cs.aviso_para_el_atleta(", fuente)
        from web_app import server
        self.assertIn('availability_constraints["historial_de_carga"] = historial_de_carga(',
                      Path(server.__file__).read_text(encoding="utf-8"))

    def test_el_aviso_no_entra_en_el_esquema_del_modelo(self):
        import json

        from services.ai.langgraph.schemas.plan_outputs import FormattedWeekBlock, WeeklyPlanPayload
        for modelo in (FormattedWeekBlock, WeeklyPlanPayload):
            self.assertNotIn("load_warning", json.dumps(modelo.model_json_schema()))


if __name__ == "__main__":
    unittest.main()
