"""v7 -- el macrociclo se acuerda de lo que prescribió, no sólo de lo que pasó.

El planificador de temporada sabía **qué hizo** el atleta (Garmin le da 180
días de actividades) y **qué le mandó la última vez** (carga el plan anterior).
Lo que no sabía era la relación entre las dos cosas a lo largo del tiempo.

Y la adherencia que sí recibía se medía sólo contra `weekly_plan.json`, que
desde la generación semana a semana contiene UNA semana: el macrociclo decidía
dieciséis semanas mirando siete días.

La frase que este módulo hace posible es: *le prescribí tres sesiones de
calidad por semana durante ocho semanas y cumplió el 40% de ellas*. Esa frase
es la diferencia entre un plan y un entrenador.

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import track_record as tr  # noqa: E402


def _dia(fecha, calidad=False, minutos=60, deporte="run"):
    return {
        "date": fecha, "is_rest": False, "sport": deporte,
        "discipline": "carrera" if deporte == "run" else deporte,
        "focus": "VO2max" if calidad else "Rodaje fácil",
        "intensity_class": "vo2max" if calidad else "easy",
        "total_duration_minutes": minutos,
    }


def _semana(dias, numero=1):
    return {"week_number": numero, "days": dias}


def _adherencia(hechas=(), perdidas=(), pendientes=(), horas=None):
    """El estado de adherencia REAL, construido con AdherenceReport.

    Antes era un diccionario escrito a mano con una clave `summary` que el
    codigo de produccion NUNCA ha emitido. Los tests pasaban y el expediente
    no registraba una sola hora entrenada: `completed`, `modified`, `missed` y
    `executed_hours` salian None en todas las semanas, y dos de las cuatro
    graficas no podian aparecer jamas.

    Por eso ahora se construye con el objeto de verdad. Un accesorio de test
    que inventa la forma de los datos no prueba nada: prueba que el test y el
    codigo comparten la misma equivocacion.
    """
    from services.ai.adherence import AdherenceReport, SessionAdherence

    informe = AdherenceReport()
    minutos = (horas * 60 / max(len(hechas), 1)) if horas else None
    for f in hechas:
        informe.entries.append(SessionAdherence(
            date=f, session_slot="unica", sport="run", focus="VO2max",
            status="completed", planned_minutes=minutos, actual_minutes=minutos))
    for f in perdidas:
        informe.entries.append(SessionAdherence(
            date=f, session_slot="unica", sport="run", focus="VO2max", status="missed"))
    for f in pendientes:
        informe.entries.append(SessionAdherence(
            date=f, session_slot="unica", sport="run", focus="VO2max", status="pending"))
    return informe.to_state_dict()


class TrackRecordTestCase(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)


class UnaSemanaSeReduceALoQueUnEntrenadorRecordaria(TrackRecordTestCase):
    def test_cuenta_lo_prescrito(self):
        semana = _semana([_dia("2026-06-01", minutos=60), _dia("2026-06-03", minutos=30),
                          {"date": "2026-06-05", "is_rest": True}])
        e = tr.summarise_week(semana, None)
        self.assertEqual(e["prescribed_sessions"], 2)
        self.assertEqual(e["prescribed_hours"], 1.5)
        self.assertEqual(e["from"], "2026-06-01")
        self.assertEqual(e["to"], "2026-06-05")

    def test_distingue_las_sesiones_de_calidad(self):
        """El rodaje suave se hace casi siempre; las series no. Un entrenador
        mira si SU plan se está cumpliendo donde cuesta."""
        semana = _semana([_dia("2026-06-01", calidad=True), _dia("2026-06-02"),
                          _dia("2026-06-03", calidad=True)])
        e = tr.summarise_week(semana, _adherencia(hechas=["2026-06-01", "2026-06-02"],
                                                  perdidas=["2026-06-03"]))
        self.assertEqual(e["quality_prescribed"], 2)
        self.assertEqual(e["quality_done"], 1)
        self.assertEqual(e["quality_missed"], ["2026-06-03 VO2max"])

    def test_dice_CUALES_se_cayeron_no_solo_cuantas(self):
        """'Cuáles' es lo que revela un patrón: ¿siempre las series? ¿siempre
        el domingo?"""
        semana = _semana([_dia("2026-06-01", calidad=True), _dia("2026-06-08", calidad=True)])
        e = tr.summarise_week(semana, _adherencia(perdidas=["2026-06-01", "2026-06-08"]))
        self.assertEqual(len(e["quality_missed"]), 2)
        self.assertIn("2026-06-01 VO2max", e["quality_missed"])

    def test_una_sesion_pendiente_no_cuenta_como_perdida(self):
        """Misma regla que adherence.py: la sesión de HOY todavía puede hacerse
        esta tarde. Contarla como incumplida haría que el expediente castigara
        al atleta por no haber entrenado todavía."""
        semana = _semana([_dia("2026-06-01", calidad=True)])
        e = tr.summarise_week(semana, _adherencia(pendientes=["2026-06-01"]))
        self.assertEqual(e["quality_missed"], [])

    def test_una_semana_sin_adherencia_todavia_se_anota(self):
        """La primera semana de un atleta no tiene nada contra qué medirse, y
        aun así lo prescrito es un hecho que hay que registrar."""
        e = tr.summarise_week(_semana([_dia("2026-06-01")]), None)
        self.assertEqual(e["prescribed_sessions"], 1)
        self.assertIsNone(e["executed_hours"])

    def test_reparte_por_disciplina(self):
        semana = _semana([_dia("2026-06-01", minutos=60, deporte="run"),
                          _dia("2026-06-02", minutos=30, deporte="bike")])
        e = tr.summarise_week(semana, None)
        self.assertEqual(e["prescribed_by_discipline"], {"carrera": 1.0, "bike": 0.5})


class QueCuentaComoSesionDeCalidad(TrackRecordTestCase):
    """La lista salia de un vocabulario escrito a mano, no del esquema.

    `intensity_class` es un `Literal` cerrado y la comparacion es de
    pertenencia exacta, asi que las palabras de entrenador que se listaban
    ("threshold", "tempo", "intervals", "fartlek", "cuestas") no coincidian
    con ningun valor real. El efecto era doble y los dos lados estaban mal.

    Medido contra el plan real del atleta del 27/08: su "Aerobico + tecnica"
    figuraba como calidad no realizada y la semana decia 2 sesiones de calidad
    donde habia 1.
    """

    def test_la_sesion_de_calidad_MAS_COMUN_se_contaba_como_ninguna(self):
        """`tempo_threshold` no coincidia con "tempo" ni con "threshold"."""
        semana = _semana([dict(_dia("2026-06-01"), intensity_class="tempo_threshold",
                               focus="Tempo continuo")])
        self.assertEqual(tr.summarise_week(semana, None)["quality_prescribed"], 1)

    def test_los_progresivos_NO_son_una_sesion_de_calidad(self):
        """Series cortas por sensacion y tecnica. El validador las excluye
        explicitamente de PACE_TARGETED_CLASSES porque mantenerlas en una
        semana de descarga es buen entrenamiento, no una sesion dura."""
        semana = _semana([dict(_dia("2026-06-01"), intensity_class="neuromuscular",
                               focus="Aerobico + tecnica")])
        self.assertEqual(tr.summarise_week(semana, None)["quality_prescribed"], 0)

    def test_el_dia_de_carrera_SI_cuenta(self):
        semana = _semana([dict(_dia("2026-06-01"), intensity_class="race_day", focus="5K")])
        self.assertEqual(tr.summarise_week(semana, None)["quality_prescribed"], 1)

    def test_vale_para_CUALQUIER_deporte(self):
        """`intensity_class` dice que ES la sesion, no en que deporte se hace:
        un 8x100 en la piscina y unas series de bici cuentan igual que unas de
        pista."""
        semana = _semana([
            dict(_dia("2026-06-01", deporte="swim"), intensity_class="vo2max", focus="8x100"),
            dict(_dia("2026-06-02", deporte="bike"), intensity_class="tempo_threshold", focus="2x20'"),
            dict(_dia("2026-06-03", deporte="run"), intensity_class="easy", focus="Rodaje"),
        ])
        self.assertEqual(tr.summarise_week(semana, None)["quality_prescribed"], 2)

    def test_la_lista_SALE_del_esquema_y_no_de_un_vocabulario_aparte(self):
        """Dos definiciones de "sesion de calidad" que puedan divergir son la
        forma exacta en que se colo este fallo."""
        from services.ai.langgraph.schemas.plan_outputs import PACE_TARGETED_CLASSES
        self.assertTrue(PACE_TARGETED_CLASSES <= tr.QUALITY_CLASSES)

    def test_las_series_en_cuesta_SI_cuentan_como_calidad(self):
        """Estan fuera de PACE_TARGETED_CLASSES por la razon contraria: en una
        cuesta el ritmo no mide nada, asi que no se les puede exigir una banda
        de ritmo. Pero 6x3' en cuesta fuerte ES la sesion dura de la semana de
        un corredor de montana, y sin esto veia "0 sesiones de calidad" la
        semana en que mas se habia vaciado."""
        semana = _semana([
            dict(_dia("2026-06-01"), intensity_class="hill_repeats", focus="6x3' en cuesta"),
            dict(_dia("2026-06-02"), intensity_class="easy", focus="Rodaje"),
        ])
        self.assertEqual(tr.summarise_week(semana, None)["quality_prescribed"], 1)

    def test_bajada_tecnica_y_caminar_correr_no_inflan_el_recuento(self):
        """Cansan, pero no son la sesion de intensidad de la semana: contarlas
        repetiria el fallo que ya cometio `neuromuscular`."""
        semana = _semana([
            dict(_dia("2026-06-01"), intensity_class="downhill_technique", focus="Bajadas"),
            dict(_dia("2026-06-02"), intensity_class="hike_run", focus="Caminar-correr"),
        ])
        self.assertEqual(tr.summarise_week(semana, None)["quality_prescribed"], 0)

    def test_un_plan_VIEJO_sin_intensity_class_no_cuenta_cero(self):
        """Antes de que el campo existiera, `None` no coincidia con nada y la
        semana decia "no le prescribi ninguna" en vez de "no lo se"."""
        viejo = {"date": "2026-06-01", "is_rest": False, "sport": "run",
                 "focus": "Series de umbral", "total_duration_minutes": 60}
        self.assertEqual(tr.summarise_week(_semana([viejo]), None)["quality_prescribed"], 1)


class ElExpedienteAnotaElPATRON(TrackRecordTestCase):
    """Dos series que no caben en un porcentaje semanal."""

    def test_anota_que_dia_de_la_semana_se_cayo(self):
        # 2026-06-01 es lunes, 2026-06-04 jueves.
        semana = _semana([_dia("2026-06-01"), _dia("2026-06-04")])
        entrada = tr.summarise_week(
            semana, _adherencia(hechas=["2026-06-01"], perdidas=["2026-06-04"]))
        self.assertEqual(entrada["by_weekday"]["lunes"], [1, 1])
        self.assertEqual(entrada["by_weekday"]["jueves"], [1, 0])

    def test_un_dia_PENDIENTE_no_entra_en_el_patron(self):
        """Colgarle un incumplimiento a un entreno que todavia puede hacerse
        esta tarde es la misma regla que adherence.py aplica."""
        entrada = tr.summarise_week(
            _semana([_dia("2026-06-01")]), _adherencia(pendientes=["2026-06-01"]))
        self.assertIsNone(entrada["by_weekday"])

    def test_anota_horas_y_kilometros_REALES_por_disciplina(self):
        semana = _semana([_dia("2026-06-01", minutos=60)])
        adherencia = _adherencia(hechas=["2026-06-01"], horas=1.0)
        adherencia["sessions"][0]["actual_distance_km"] = 9.5
        entrada = tr.summarise_week(semana, adherencia)
        self.assertEqual(entrada["executed_by_discipline"]["carrera"],
                         {"hours": 1.0, "km": 9.5})

    def test_la_disciplina_la_pone_el_PLAN_y_no_garmin(self):
        """Una sesion de rehab y una de fuerza son las dos `strength` para
        Garmin y cosas distintas para un entrenador."""
        dia = dict(_dia("2026-06-01", minutos=30, deporte="strength"), discipline="rehab")
        adherencia = _adherencia(hechas=["2026-06-01"], horas=0.5)
        entrada = tr.summarise_week(_semana([dia]), adherencia)
        self.assertIn("rehab", entrada["executed_by_discipline"])

    def test_lo_ejecutado_no_se_desanota_al_regenerar(self):
        semana = _semana([_dia("2026-06-01")])
        tr.record_week(self.dir, semana, _adherencia(hechas=["2026-06-01"], horas=1.0))
        tr.record_week(self.dir, semana, None)
        anotada = tr.read_all(self.dir)[0]
        self.assertEqual(anotada["by_weekday"]["lunes"], [1, 1])
        self.assertTrue(anotada["executed_by_discipline"])


class ElExpedienteSoloCrece(TrackRecordTestCase):
    def test_regenerar_la_misma_semana_no_la_duplica(self):
        """Se puede borrar y regenerar una semana tres veces. Si cada intento
        dejara su línea, el expediente contaría tres veces el mismo periodo y
        el patrón que el macrociclo lee sería falso."""
        semana = _semana([_dia("2026-06-01", calidad=True)])
        for _ in range(3):
            tr.record_week(self.dir, semana, None)
        self.assertEqual(len(tr.read_all(self.dir)), 1)

    def test_regenerar_ACTUALIZA_la_linea(self):
        tr.record_week(self.dir, _semana([_dia("2026-06-01", minutos=60)]), None)
        tr.record_week(self.dir, _semana([_dia("2026-06-01", minutos=30)]), None)
        registro = tr.read_all(self.dir)
        self.assertEqual(len(registro), 1)
        self.assertEqual(registro[0]["prescribed_hours"], 0.5)

    def test_semanas_distintas_se_acumulan(self):
        tr.record_week(self.dir, _semana([_dia("2026-06-01")]), None)
        tr.record_week(self.dir, _semana([_dia("2026-06-08")]), None)
        self.assertEqual(len(tr.read_all(self.dir)), 2)

    def test_una_linea_corrupta_no_se_lleva_el_expediente(self):
        tr.record_week(self.dir, _semana([_dia("2026-06-01")]), None)
        with open(self.dir / tr.TRACK_RECORD_FILE, "a", encoding="utf-8") as h:
            h.write("{esto no es json\n")
        tr.record_week(self.dir, _semana([_dia("2026-06-08")]), None)
        self.assertEqual(len(tr.read_all(self.dir)), 2)

    def test_vive_fuera_del_plan(self):
        """Borrar la semana o regenerar el macrociclo no puede borrar el hecho
        de que hace tres meses se prescribieron cuatro series y se hicieron
        dos: eso ya pasó, y es lo que el planificador necesita saber."""
        self.assertEqual(tr.TRACK_RECORD_FILE, "track_record.jsonl")
        servidor = (Path(__file__).resolve().parents[1] / "web_app/server.py").read_text(encoding="utf-8")
        for borrado in ("def delete_current_generation(", "def delete_history_entry("):
            cuerpo = servidor[servidor.index(borrado):][:2500]
            with self.subTest(borrado=borrado):
                self.assertNotIn("track_record", cuerpo)


class ElBloqueSeLeeDeUnVistazo(TrackRecordTestCase):
    def _tres_semanas(self):
        for i, (inicio, hechas, perdidas) in enumerate([
            ("2026-06-01", [], ["2026-06-01"]),
            ("2026-06-08", ["2026-06-08"], []),
            ("2026-06-15", [], ["2026-06-15"]),
        ]):
            tr.record_week(
                self.dir, _semana([_dia(inicio, calidad=True, minutos=300)], numero=i + 1),
                _adherencia(hechas=hechas, perdidas=perdidas, horas=3.5),
            )

    def test_con_una_sola_semana_no_hay_patron_que_mostrar(self):
        """Y la adherencia de esa semana ya viaja por su propio canal: repetirla
        aquí sería pagar tokens por decir dos veces lo mismo."""
        tr.record_week(self.dir, _semana([_dia("2026-06-01")]), None)
        self.assertEqual(tr.render_track_record(self.dir), "")

    def test_con_varias_muestra_el_acumulado_y_el_detalle(self):
        self._tres_semanas()
        bloque = tr.render_track_record(self.dir)
        self.assertIn("3 semana(s)", bloque)
        self.assertIn("Sesiones de calidad prescritas", bloque)
        self.assertIn("2026-06-01", bloque)
        self.assertIn("2026-06-15", bloque)

    def test_dice_que_son_hechos_y_no_un_juicio(self):
        """El módulo no puntúa. Decidir si un 33% significa que el atleta se
        desmotivó, que el plan pedía demasiado o que tuvo un mes de viajes es
        trabajo del planificador, que además tiene el cuaderno y el check-in."""
        self._tres_semanas()
        bloque = tr.render_track_record(self.dir)
        self.assertIn("no un juicio", bloque)
        self.assertIn("ajusta lo que PRESCRIBES", bloque)

    def test_una_semana_sin_evaluar_no_cuenta_como_incumplida(self):
        """Macrociclo del atleta 2 del 2026-09-23: "Horas prescritas 33.5h · ejecutadas 2.0h (3%)"
        sumaba dos semanas que todavía no habían empezado, y el plan escribió "escasa ejecución
        frente a lo prescrito". Las horas se cuentan como la calidad: sólo lo ya evaluado."""
        self._tres_semanas()  # 3 evaluadas, 5 h prescritas cada una
        tr.record_week(self.dir, _semana([_dia("2026-06-22", minutos=600)], numero=4), None)  # sin evaluar
        bloque = tr.render_track_record(self.dir)
        self.assertIn("Horas prescritas: **15.0h**", bloque)
        self.assertIn("1 semana(s) aún sin evaluar", bloque)

    def test_cabe_en_el_prompt_aunque_pasen_diez_meses(self):
        """60 semanas es más de un año de uso. Si esto creciera sin tope sería
        exactamente la explosión de contexto que el proyecto lleva seis
        versiones evitando."""
        for n in range(60):
            dia = f"2026-{(n % 12) + 1:02d}-{(n % 28) + 1:02d}"
            tr.record_week(self.dir, _semana([_dia(dia, calidad=True)]), None)
        bloque = tr.render_track_record(self.dir)
        self.assertLess(len(bloque), 12000)
        # Lo omitido se dice, nunca en silencio.
        self.assertIn("no se muestran", bloque)


class LosPlanificadoresLoRecibenPorElCanalDeSiempre(unittest.TestCase):
    RAIZ = Path(__file__).resolve().parents[1]
    SEASON = (RAIZ / "services/ai/langgraph/nodes/season_planner_node.py").read_text(encoding="utf-8")
    SERVER = (RAIZ / "web_app/server.py").read_text(encoding="utf-8")

    def test_el_planificador_de_temporada_lo_lee(self):
        self.assertIn("{track_record}", self.SEASON)
        self.assertIn("track_record=_format_track_record(state)", self.SEASON)

    def test_el_servidor_lo_anota_al_generar_la_semana(self):
        self.assertIn("record_week(", self.SERVER)

    def test_y_lo_pone_en_el_canal_al_planificar_la_temporada(self):
        self.assertIn('season_availability["track_record"]', self.SERVER)


if __name__ == "__main__":
    unittest.main(verbosity=2)
