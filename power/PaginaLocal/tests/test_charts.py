"""v7 -- las gráficas, y por qué no cuestan nada.

El atleta pidió gráficas al estilo del proyecto original del que salió esta
arquitectura de agentes, con una advertencia que es la restricción de diseño
entera: *"cuidado de no romper la app. Ni impulsar excesivos costos de IA"*.

Así que no hay ni una llamada a un modelo ni una petición nueva a Garmin: todo
sale de archivos que la app ya escribe. Pedirle a un LLM que resuma números que
ya tenemos exactos sería pagar por perder precisión -- el mismo razonamiento
que sacó los informes de manos de un modelo en v5 Track 9 y `zones_table` de la
salida del planificador en v6.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import shutil
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import charts  # noqa: E402
from services.ai import track_record as tr  # noqa: E402


def _adherencia_real(hechas, minutos_cada):
    """El estado que produce AdherenceReport de verdad.

    Escribirlo a mano fue la causa de que el expediente no registrara una sola
    hora entrenada durante dos versiones: el diccionario inventado traia una
    clave `summary` que el codigo de produccion nunca ha emitido, y los tests
    pasaban porque compartian la equivocacion con el codigo.
    """
    from services.ai.adherence import AdherenceReport, SessionAdherence

    informe = AdherenceReport()
    for f in hechas:
        informe.entries.append(SessionAdherence(
            date=f, session_slot="unica", sport="run", focus="VO2max",
            status="completed", planned_minutes=minutos_cada, actual_minutes=minutos_cada))
    return informe.to_state_dict()


def _semana(inicio, prescritas, ejecutadas, calidad_p, calidad_h, acwr=None,
            evaluada=None, perdidas=0):
    """Una linea del expediente.

    `ejecutadas=None` significa semana ABIERTA: la que el atleta esta
    entrenando ahora, que todavia no tiene adherencia medida y por eso no
    trae ninguna de las cuentas de sesiones. `evaluada=True` fuerza el caso
    contrario y util: una semana CERRADA en la que no se entreno nada, que
    tiene cero minutos emparejados y aun asi debe dibujarse.
    """
    semana = {
        "from": inicio, "to": inicio, "prescribed_hours": prescritas,
        "executed_hours": ejecutadas, "quality_prescribed": calidad_p,
        "quality_done": calidad_h, "acwr": acwr, "prescribed_sessions": 5,
    }
    if evaluada if evaluada is not None else ejecutadas is not None:
        semana.update({"completed_sessions": calidad_h, "modified_sessions": 0,
                       "missed_sessions": perdidas})
    return semana


class NoCuestaNiUnaLlamada(unittest.TestCase):
    """La restricción que el atleta puso, escrita donde se puede romper."""

    FUENTE = (Path(__file__).resolve().parents[1] / "services/ai/charts.py").read_text(encoding="utf-8")

    def test_no_importa_ningun_modelo(self):
        for prohibido in ("ModelSelector", "AgentRole", "langchain", "ainvoke"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, self.FUENTE)

    def test_no_llama_a_garmin(self):
        for prohibido in ("garminconnect", "DataExtractor", "TriathlonCoachDataExtractor"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, self.FUENTE)

    def test_el_endpoint_tampoco(self):
        servidor = (Path(__file__).resolve().parents[1] / "web_app/server.py").read_text(encoding="utf-8")
        cuerpo = servidor[servidor.index("def get_charts("):][:900]
        self.assertIn("build_charts", cuerpo)
        self.assertNotIn("extractor", cuerpo.lower())


class LoQueTeMandeContraLoQueHiciste(unittest.TestCase):
    """La gráfica que ningún plan estático puede enseñar, porque un plan
    estático no sabe qué hiciste."""

    def test_compara_prescrito_con_ejecutado(self):
        datos = charts.plan_vs_reality([
            _semana("2026-06-01", 5.0, 3.5, 2, 1),
            _semana("2026-06-08", 5.5, 5.5, 2, 2),
        ])
        self.assertEqual(len(datos["points"]), 2)
        self.assertEqual(datos["total_prescribed"], 10.5)
        self.assertEqual(datos["total_executed"], 9.0)
        self.assertEqual(datos["pct"], 86)

    def test_la_semana_EN_CURSO_no_se_dibuja(self):
        """Una semana abierta aparecería con cero horas hechas y se leería
        como un incumplimiento que todavía no ha ocurrido."""
        datos = charts.plan_vs_reality([
            _semana("2026-06-01", 5.0, 3.5, 2, 1),
            _semana("2026-06-08", 5.5, None, 2, 0),   # abierta
        ])
        self.assertEqual(len(datos["points"]), 1)

    def test_una_semana_CERRADA_sin_entrenar_nada_SI_se_dibuja(self):
        """Cero horas hechas es un dato, no un hueco.

        La primera prueba de "cerrada" era `executed_hours is not None`, y una
        semana en la que el atleta no entreno nada tiene cero minutos
        emparejados -- asi que desaparecia del grafico en vez de dibujarse
        como lo que fue. Lo que cierra una semana es que su adherencia se
        EVALUO, la haya cumplido o no.
        """
        datos = charts.plan_vs_reality([
            _semana("2026-06-01", 5.0, None, 2, 0, evaluada=True, perdidas=5),
        ])
        self.assertEqual(len(datos["points"]), 1)
        self.assertEqual(datos["points"][0]["executed"], 0)
        self.assertEqual(datos["pct"], 0)

    def test_sin_ninguna_semana_cerrada_no_hay_grafica(self):
        self.assertEqual(charts.plan_vs_reality([_semana("2026-06-01", 5.0, None, 2, 0)]), {})

    def test_las_etiquetas_son_legibles_en_un_eje(self):
        datos = charts.plan_vs_reality([_semana("2026-08-27", 5.0, 4.0, 2, 1)])
        self.assertEqual(datos["points"][0]["label"], "27 ago")


class LaCalidadVaAparteDelTotal(unittest.TestCase):
    """Un atleta puede cumplir el 90% de sus horas y haberse saltado todas las
    series, y esas dos semanas no son la misma semana ni de lejos."""

    def test_cuenta_series_no_horas(self):
        datos = charts.quality_compliance([
            _semana("2026-06-01", 5.0, 4.8, 3, 1),
            _semana("2026-06-08", 5.0, 4.9, 3, 0),
        ])
        self.assertEqual(datos["total_prescribed"], 6)
        self.assertEqual(datos["total_done"], 1)
        self.assertEqual(datos["pct"], 17)

    def test_la_semana_EN_CURSO_tampoco_cuenta_aqui(self):
        """La grafica de calidad si contaba la semana abierta, y la de horas
        no: la primera semana del atleta salia como "0 de 2 - 0%" el mismo dia
        en que se le genero, acusandole de saltarse unas series que todavia no
        le tocaban."""
        self.assertEqual(charts.quality_compliance([_semana("2026-06-01", 5.0, None, 2, 0)]), {})

    def test_una_semana_sin_calidad_prescrita_no_entra(self):
        self.assertEqual(charts.quality_compliance([_semana("2026-06-01", 5.0, 5.0, 0, 0)]), {})


class LaCargaSeDibujaConSuReferencia(unittest.TestCase):
    """Un número sin su referencia no dice nada: lo que importa no es que valga
    1.17, es que 1.17 cae dentro de la banda."""

    def test_lleva_la_banda_y_el_umbral_alto(self):
        datos = charts.load_trend([_semana("2026-06-01", 5, 5, 2, 2, acwr=1.1)], None)
        self.assertEqual(datos["sweet_spot"], list(charts.ACWR_SWEET_SPOT))
        self.assertEqual(datos["high"], charts.ACWR_HIGH)

    def test_el_valor_de_hoy_se_anade_al_final(self):
        """El estado de carga de hoy es más fresco que la última semana
        anotada."""
        datos = charts.load_trend(
            [_semana("2026-06-01", 5, 5, 2, 2, acwr=1.1)],
            {"own_acwr": 1.35, "garmin_acwr": 1.05, "garmin_acwr_status": "OPTIMAL"},
        )
        self.assertEqual(datos["points"][-1]["label"], "hoy")

    def test_manda_el_ACWR_DE_GARMIN_cuando_lo_hay(self):
        """Es el número que el atleta ve en su reloj, así que es el único
        contra el que puede contrastar lo que le decimos.

        El nuestro no se quita: se dibuja detrás, en `own_points`, porque es el
        que alimenta el análisis y tiene más historia. Verlos juntos es lo que
        hace evidente que son métodos distintos.
        """
        datos = charts.load_trend(
            [_semana("2026-06-01", 5, 5, 2, 2, acwr=1.1)],
            {"own_acwr": 1.35, "garmin_acwr": 1.05, "garmin_acwr_status": "OPTIMAL"},
        )
        self.assertEqual(datos["source"], "garmin")
        self.assertEqual(datos["current"], 1.05)
        self.assertEqual(datos["own_current"], 1.35)
        self.assertEqual(datos["own_points"][-1]["acwr"], 1.35)

    def test_sin_dato_de_garmin_la_nuestra_ocupa_su_sitio(self):
        """Un reloj que no publica ACWR no puede dejar la tarjeta vacía: el
        cálculo propio sale de sus actividades y siempre está."""
        datos = charts.load_trend(
            [_semana("2026-06-01", 5, 5, 2, 2, acwr=1.1)], {"own_acwr": 1.35},
        )
        self.assertEqual(datos["source"], "app")
        self.assertEqual(datos["current"], 1.35)
        self.assertEqual(datos["own_points"], [])

    def test_la_serie_diaria_de_garmin_se_dibuja_entera(self):
        """La razón de todo esto: `build_light_snapshot` leía el ACWR de un
        atributo que no existe en ningún modelo del extractor, así que ninguna
        foto diaria lo guardó nunca y esta línea sólo tenía una foto por
        generación -- dos puntos iguales."""
        serie = [
            {"date": "2026-06-01", "acwr": 0.9, "status": "OPTIMAL"},
            {"date": "2026-06-02", "acwr": 1.0, "status": "OPTIMAL"},
            {"date": "2026-06-03", "acwr": 1.2, "status": "HIGH"},
        ]
        datos = charts.load_trend([], {"garmin_acwr": 1.2, "garmin_acwr_status": "HIGH"}, serie)
        self.assertEqual([p["acwr"] for p in datos["points"]], [0.9, 1.0, 1.2])
        self.assertEqual(datos["points"][-1]["status"], "HIGH")

    def test_la_banda_es_la_DEL_METODO_de_la_linea_principal(self):
        """Cada línea con la banda de su método, y esto se corrigió con una
        captura de Garmin Connect delante.

        La versión anterior de esta prueba afirmaba que "Garmin no publica los
        límites de su zona, publica un veredicto por palabra" y exigía que la
        banda fuese siempre la nuestra. Es falso: su gráfica "Proporción de
        carga" dibuja el intervalo óptimo rotulado, 0.8–1.5.

        Lo que sí sigue en pie es la regla de fondo: la banda tiene que ser la
        del método que dibuja la línea. Pintar el sweet spot de Gabbett
        (0.8–1.3, media móvil) bajo una línea calculada con la exponencial de
        Garmin mezclaría dos métodos, que es lo que load_state.py lleva desde
        v4 impidiendo.
        """
        de_garmin = charts.load_trend([], {"garmin_acwr": 1.0}, [{"date": "2026-06-01", "acwr": 0.9}])
        self.assertEqual(de_garmin["sweet_spot"], list(charts.ACWR_GARMIN_OPTIMO))
        self.assertIn("Garmin", de_garmin["band_label"])

        nuestra = charts.load_trend([_semana("2026-06-01", 5, 5, 2, 2, acwr=1.1)], None)
        self.assertEqual(nuestra["sweet_spot"], list(charts.ACWR_SWEET_SPOT))
        self.assertIn("esta app", nuestra["band_label"])

    def test_dice_que_los_dos_ACWR_usan_metodos_distintos(self):
        """Sin esta nota la gráfica parecería contradecir al reloj. Es la misma
        regla que load_state.py fija desde v6."""
        datos = charts.load_trend([_semana("2026-06-01", 5, 5, 2, 2, acwr=1.1)], None)
        self.assertIn("media exponencial", datos["note"])
        self.assertIn("media móvil", datos["note"])
        self.assertIn("no tiene por qué coincidir", datos["note"])

    def test_sin_datos_de_carga_no_hay_grafica(self):
        self.assertEqual(charts.load_trend([_semana("2026-06-01", 5, 5, 2, 2)], None), {})

    def test_no_emite_ningun_veredicto(self):
        """Decir si un 1.35 es un problema para ESTE atleta necesita el
        cuaderno, el check-in y la fase de temporada. Esa lectura la hace el
        coach, que ya los tiene."""
        fuente = (Path(__file__).resolve().parents[1] / "services/ai/charts.py").read_text(encoding="utf-8")
        for veredicto in ("riesgo alto", "peligro", "demasiado", "mal="):
            with self.subTest(palabra=veredicto):
                self.assertNotIn(veredicto, fuente.lower())


class ElRepartoSaleDeLaSemanaActual(unittest.TestCase):
    def test_agrupa_por_disciplina_y_no_por_deporte_de_garmin(self):
        """Una sesión de rehab y una de fuerza son las dos `strength` para
        Garmin, y cosas completamente distintas para un entrenador."""
        plan = {"weeks": [{"days": [
            {"date": "1", "is_rest": False, "sport": "strength", "discipline": "fuerza",
             "total_duration_minutes": 60},
            {"date": "2", "is_rest": False, "sport": "strength", "discipline": "rehab",
             "total_duration_minutes": 30},
            {"date": "3", "is_rest": True, "sport": "rest", "total_duration_minutes": None},
        ]}]}
        datos = charts.week_by_discipline(plan)
        etiquetas = [p["label"] for p in datos["points"]]
        self.assertIn("Fuerza", etiquetas)
        self.assertIn("Rehab", etiquetas)
        self.assertEqual(datos["total_hours"], 1.5)

    def test_sin_plan_no_hay_grafica(self):
        self.assertEqual(charts.week_by_discipline(None), {})
        self.assertEqual(charts.week_by_discipline({"weeks": []}), {})


class UnaGraficaSinDatosNoSeDevuelveVacia(unittest.TestCase):
    """Un eje con un solo punto o una barra sola no informan de nada y ocupan
    el sitio de algo que sí."""

    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_un_atleta_nuevo_no_recibe_esqueletos(self):
        salida = charts.build_charts(self.dir)
        self.assertEqual(salida["charts"], {})
        self.assertEqual(salida["weeks_recorded"], 0)

    def test_con_datos_reales_aparecen_las_que_se_pueden(self):
        for i, inicio in enumerate(["2026-06-01", "2026-06-08"]):
            tr.record_week(
                self.dir,
                {"week_number": i + 1, "days": [{
                    "date": inicio, "is_rest": False, "sport": "run", "discipline": "carrera",
                    "focus": "VO2max", "intensity_class": "vo2max", "total_duration_minutes": 300,
                }]},
                _adherencia_real([inicio], 240),
                load_state={"own_acwr": 1.1 + i * 0.1},
            )
        (self.dir / "weekly_plan.json").write_text(json.dumps({"weeks": [{"days": [
            {"date": "2026-06-08", "is_rest": False, "sport": "run", "discipline": "carrera",
             "total_duration_minutes": 300},
        ]}]}), encoding="utf-8")

        salida = charts.build_charts(self.dir)
        self.assertIn("plan_vs_reality", salida["charts"])
        self.assertIn("quality", salida["charts"])
        self.assertIn("load", salida["charts"])
        self.assertIn("discipline", salida["charts"])

    def test_la_tendencia_no_crece_sin_limite(self):
        """Doce semanas es un trimestre: suficiente para ver un patrón, poco
        para que la gráfica se vuelva ilegible en un móvil."""
        semanas = [_semana(f"2026-{(n % 12) + 1:02d}-01", 5, 4, 2, 1) for n in range(40)]
        self.assertEqual(len(charts.plan_vs_reality(semanas)["points"]), charts.WEEKS_SHOWN)


class ElExpedienteGuardaLaFotoDeCarga(unittest.TestCase):
    """Anotarla de paso en la línea semanal que ya existe es lo que hace
    posible la tendencia sin una sola llamada extra."""

    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)

    def test_se_guarda_con_la_semana(self):
        anotada = tr.record_week(
            self.dir,
            {"week_number": 1, "days": [{"date": "2026-06-01", "is_rest": False,
                                         "sport": "run", "total_duration_minutes": 60}]},
            None,
            load_state={"own_acwr": 1.17, "garmin_acwr": 1.0},
        )
        self.assertEqual(anotada["acwr"], 1.17)
        self.assertEqual(anotada["acwr_garmin"], 1.0)

    def test_sobrevive_a_regenerar_la_semana(self):
        semana = {"week_number": 1, "days": [{"date": "2026-06-01", "is_rest": False,
                                              "sport": "run", "total_duration_minutes": 60}]}
        tr.record_week(self.dir, semana, None, load_state={"own_acwr": 1.17})
        tr.record_week(self.dir, semana, None)
        self.assertEqual(tr.read_all(self.dir)[0]["acwr"], 1.17)


if __name__ == "__main__":
    unittest.main(verbosity=2)
