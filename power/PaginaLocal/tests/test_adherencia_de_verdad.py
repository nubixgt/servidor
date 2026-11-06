"""La adherencia, ejercitada como si el atleta ya hubiera cerrado una semana.

El atleta reportó el 2026-09-04: *"asegúrate que la función de adherencia
funcione como se espera, es la única que no he podido probar realmente ya que
no he terminado una semana"*. Y tenía razón en preocuparse: es la única parte
del producto que no puede existir hasta la segunda generación, así que había
corrido cero veces con datos reales.

Esta prueba recorre la cadena entera con una semana pasada completa: un plan de
cinco sesiones, actividades de Garmin que la cumplen de cinco maneras distintas
(clavada, acortada, movida de día, no hecha, y una que nadie mandó), y comprueba
lo que sale por los tres sitios donde eso se lee: el porcentaje, el bloque que
recibe el planificador de la semana siguiente y la línea del expediente de la
que salen las gráficas.

## Los dos fallos que encontró

**1. `virtual_ride` no era ciclismo.** `_canonical_sport` buscaba "cycl", "bik"
y "handcycling". Zwift y las plataformas de rodillo devuelven `virtual_ride`,
que no contiene ninguno, así que caía en "other". Para un triatleta con rodillo
-- una opción de su propia ficha -- cada salida indoor se contaba como sesión NO
REALIZADA *y además* aparecía como entrenamiento por su cuenta. Las dos cosas
entran tal cual en el prompt de la semana siguiente: el coach leería que se
saltó la bici y le recortaría la progresión por algo que sí hizo.

**2. Una caminata prescrita no podía emparejar con nada.** El esquema acepta
`sport="walk"` y la UI lo pinta como "Caminata", pero nada canonicaliza a
"walk": caminar y senderismo van a "other" a propósito, porque de qué tabla de
zonas les da Garmin no se puede saber desde aquí. Las dos decisiones son
correctas por separado; juntas hacían que una caminata prescrita saliera "no
realizada" siempre, hiciera lo que hiciera el atleta.

Es el mismo fallo que ya se corrigió con la fuerza, y el mismo argumento: un
deporte que esta app PRESCRIBE tiene que saber reconocerlo cuando vuelve.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from dataclasses import dataclass
from datetime import date, timedelta
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.adherence import (  # noqa: E402
    build_adherence_report,
    deportes_que_valen,
    render_adherence_block,
)
from services.ai.track_record import record_week  # noqa: E402
from services.garmin.data_extractor import DataExtractor  # noqa: E402
from services.garmin.models import GarminHrZones  # noqa: E402

HOY = date(2026, 9, 4)
LUNES = HOY - timedelta(days=7)  # la semana pasada, entera en el pasado


@dataclass
class _Resumen:
    duration: float
    distance: float = 0
    average_hr: int = 0
    # El PICO. Es con lo que se comprueba si una sesion de calidad se entreno
    # en calidad; la media la hunden el calentamiento y las recuperaciones.
    max_hr: int = 0


@dataclass
class _Actividad:
    activity_id: int
    start_time: str
    activity_type: str
    activity_name: str
    summary: _Resumen


def _dia(offset, sport, focus, minutos, *, descanso=False, slot="am"):
    return {
        "date": (LUNES + timedelta(days=offset)).isoformat(),
        "day_name_es": "X", "is_rest": descanso, "sport": sport,
        "focus": focus, "purpose": "p.", "adaptation": "a.",
        "session_slot": slot, "total_duration_minutes": minutos,
    }


def _actividad(ident, offset, tipo, minutos, km=0.0, hr=0):
    return _Actividad(
        ident, f"{(LUNES + timedelta(days=offset)).isoformat()} 06:00:00",
        tipo, f"actividad-{ident}", _Resumen(minutos * 60, km * 1000, hr),
    )


def _plan(dias):
    from web_app.server import WeeklyPlanPayload
    return WeeklyPlanPayload(
        athlete_name="Jose", start_date=LUNES.isoformat(), zones_table="",
        weeks=[{"week_number": 1, "week_rationale": "r", "days": dias}],
    )


class UnaSemanaCerradaDeVerdad(unittest.TestCase):
    """Las cinco formas en que una semana se cumple o no."""

    def setUp(self):
        self.plan = _plan([
            _dia(0, "run", "Z2", 60),                    # clavada
            _dia(1, "swim", "Técnica", 45),              # acortada a la mitad
            _dia(1, "strength", "Fuerza", 40, slot="pm"),  # clavada, segundo bloque
            _dia(2, "bike", "Umbral", 90),               # movida un día
            _dia(3, "run", "Series", 50),                # no hecha
            _dia(5, "rest", "Descanso", 0, descanso=True),
        ])
        self.actividades = [
            _actividad(1, 0, "running", 58, 10.1, 141),
            _actividad(2, 1, "lap_swimming", 22, 1.0, 130),
            _actividad(3, 1, "strength_training", 42),
            _actividad(4, 3, "cycling", 88, 42.0, 138),   # el día siguiente al prescrito
            _actividad(5, 4, "hiking", 70, 6.0),          # nadie la mandó
        ]
        self.reporte = build_adherence_report(self.plan, self.actividades, today=HOY)

    def test_el_porcentaje_cuenta_hecho_y_modificado_como_entrenado(self):
        """4 de 5 sesiones evaluadas se entrenaron (una acortada sigue siendo
        entrenada). El descanso no cuenta: no es una sesión."""
        self.assertEqual(len(self.reporte.evaluated), 5)
        self.assertEqual(self.reporte.completed_count, 3)
        self.assertEqual(self.reporte.modified_count, 1)
        self.assertEqual(self.reporte.missed_count, 1)
        self.assertEqual(self.reporte.adherence_pct, 80.0)

    def test_media_hora_de_menos_es_MODIFICADA_no_completada(self):
        """22' de 45' son la mitad. La tolerancia es del 25%: llamar
        "completada" a media sesión le esconde al planificador que la dosis
        larga no le cabe."""
        nadar = next(e for e in self.reporte.entries if e.sport == "swim")
        self.assertEqual(nadar.status, "modified")
        self.assertAlmostEqual(nadar.duration_ratio, 22 / 45, places=3)

    def test_una_sesion_movida_de_dia_se_encuentra_Y_SE_DICE(self):
        """Sin la segunda pasada contaría dos veces mal: no realizada el
        miércoles y entrenamiento fuera del plan el jueves. Y decir sólo
        "hecha" escondería el patrón que un entrenador querría ver."""
        bici = next(e for e in self.reporte.entries if e.sport == "bike")
        self.assertEqual(bici.status, "completed")
        self.assertEqual(bici.done_on, (LUNES + timedelta(days=3)).isoformat())
        self.assertIn("la hizo el", bici.describe())

    def test_lo_que_no_se_hizo_NO_se_maquilla(self):
        series = next(e for e in self.reporte.entries if e.focus == "Series")
        self.assertEqual(series.status, "missed")

    def test_lo_entrenado_por_su_cuenta_va_APARTE(self):
        """Sumarlo a la adherencia haría que un atleta que se salta su plan y
        hace otra cosa apareciera cumpliéndolo."""
        self.assertEqual(len(self.reporte.unplanned), 1)
        self.assertEqual(self.reporte.unplanned[0].minutes, 70.0)
        # Y no se cuela en las horas ejecutadas: 58+22+42+88 = 210' = 3.5 h.
        self.assertEqual(self.reporte.executed_hours, 3.5)

    def test_ninguna_actividad_se_cuenta_DOS_veces(self):
        ids = [e.activity_id for e in self.reporte.entries if e.activity_id]
        self.assertEqual(len(ids), len(set(ids)))

    def test_el_bloque_que_lee_el_planificador_lleva_los_numeros(self):
        bloque = render_adherence_block(self.reporte)
        self.assertIn(
            "3 completadas, 1 modificadas, 0 hechas sin la intensidad prescrita, "
            "1 no realizadas",
            bloque,
        )
        self.assertIn("80% de adherencia", bloque)
        self.assertIn("fuera del plan", bloque)

    def test_la_linea_del_expediente_sale_llena(self):
        """De aquí salen las gráficas de prescrito-contra-hecho y de
        adherencia por día. Una versión anterior leía los campos de una clave
        "summary" que nunca existió, así que el expediente registraba cero
        horas entrenadas en todas las semanas."""
        directorio = Path(tempfile.mkdtemp())
        fila = record_week(
            directorio, self.plan.model_dump()["weeks"][0],
            self.reporte.to_state_dict(),
        )
        self.assertEqual(fila["completed_sessions"], 3)
        self.assertEqual(fila["modified_sessions"], 1)
        self.assertEqual(fila["missed_sessions"], 1)
        self.assertEqual(fila["executed_hours"], 3.5)
        self.assertEqual(fila["prescribed_hours"], round(285 / 60, 2))
        # El día que se cayó, con nombre: es el hallazgo que no cabe en un
        # porcentaje semanal.
        self.assertIsNotNone(fila["by_weekday"])
        caidos = [d for d, (prescritas, hechas) in fila["by_weekday"].items() if hechas < prescritas]
        self.assertEqual(len(caidos), 1)


class NadaDeLoQueYA_PASO_SE_JUZGA_ANTES_DE_TIEMPO(unittest.TestCase):
    def test_una_sesion_de_HOY_esta_pendiente_no_fallada(self):
        """El atleta todavía puede entrenar esta tarde. Contarla como fallo
        sería equivocarse y además desanimar."""
        plan = _plan([
            {**_dia(0, "run", "Z2", 60), "date": HOY.isoformat()},
            _dia(0, "run", "Rodaje", 40),
        ])
        reporte = build_adherence_report(plan, [], today=HOY)
        estados = sorted(e.status for e in reporte.entries)
        self.assertEqual(estados, ["missed", "pending"])
        self.assertEqual(len(reporte.evaluated), 1)

    def test_sin_plan_previo_no_hay_adherencia_que_medir(self):
        """El caso del atleta que acaba de generar su primera semana: el
        porcentaje es None, no 0%. Un 0% diría que falló todo."""
        self.assertIsNone(build_adherence_report(None, [], today=HOY).adherence_pct)


class LosDeportesQuePRESCRIBIMOS_SE_RECONOCEN_AL_VOLVER(unittest.TestCase):
    def test_EL_FALLO_una_salida_en_rodillo_virtual_es_BICI(self):
        """Zwift devuelve `virtual_ride`, que no contiene "cycl" ni "bik"."""
        self.assertEqual(DataExtractor._canonical_sport("virtual_ride"), "bike")

    def test_y_ahora_empareja_con_la_sesion_de_bici_prescrita(self):
        plan = _plan([_dia(0, "bike", "Umbral", 90)])
        reporte = build_adherence_report(
            plan, [_actividad(9, 0, "virtual_ride", 88, 42.0, 138)], today=HOY)
        self.assertEqual(reporte.entries[0].status, "completed")
        self.assertEqual(reporte.unplanned, [])

    def test_montar_a_caballo_sigue_sin_ser_bici(self):
        """El token es "ride" y la palabra ahí es "riding": no es subcadena."""
        self.assertEqual(DataExtractor._canonical_sport("horseback_riding"), "other")

    def test_EL_FALLO_una_caminata_prescrita_podia_emparejar_con_algo(self):
        """Nada canonicaliza a "walk" -- caminar va a "other" a propósito --
        así que sin esto una caminata prescrita salía "no realizada" siempre,
        hiciera lo que hiciera el atleta."""
        self.assertIn("other", deportes_que_valen(_Dia(sport="walk")))
        plan = _plan([_dia(0, "walk", "Caminata suave", 45)])
        reporte = build_adherence_report(
            plan, [_actividad(10, 0, "walking", 44, 4.2)], today=HOY)
        self.assertEqual(reporte.entries[0].status, "completed")

    def test_la_fuerza_sigue_aceptando_las_dos_etiquetas(self):
        """Lo que ya estaba: "strength" y "other", porque un gimnasio mal
        etiquetado sigue siendo la sesión que el atleta hizo."""
        self.assertEqual(deportes_que_valen(_Dia(sport="strength")), ("strength", "other"))

    def test_un_brick_puede_llegar_entero_o_en_dos_tramos(self):
        self.assertEqual(deportes_que_valen(_Dia(sport="brick")), ("other", "bike", "run"))


@dataclass
class _Dia:
    """Lo mínimo que `deportes_que_valen` mira de un día."""
    sport: str


class UnaSesionDeCalidadHECHA_EN_FACIL_NO_ES_CALIDAD(unittest.TestCase):
    """Lo que preguntó el atleta el 2026-09-04, con sus dos casos.

        "Tenía una sesión estructurada de VO2max, pero el atleta no puso esa
         estructurada porque quizá no le gusta la interfaz, pero aún así hizo
         una sesión justo como decía el plan pero en una sesión libre de
         running. ¿Y cómo marcaría este mismo caso pero si en vez de 5 rep
         hizo 4?"

    Emparejando sólo por fecha + deporte + duración, salir a trotar suave el
    día del VO2max daba "completada" igual que hacerlo. El planificador de la
    semana siguiente leía que el estímulo de calidad se ejecutó -- que es el
    dato con el que decide si progresar la intensidad.

    La comprobación mira el PICO de pulsaciones contra el suelo de Z4 de las
    zonas REALES del atleta para ese deporte. El pico y no la media: el
    calentamiento, las recuperaciones y la vuelta a la calma hunden la media
    de una sesión de series bien hecha, y marcar como floja una sesión que se
    hizo es el error caro -- el atleta deja de creerse el resto del informe.
    """

    # Las zonas reales de carrera de un atleta, tal como salen de su reloj.
    ZONAS = GarminHrZones(by_sport={
        "run": {1: (95, 115), 2: (116, 139), 3: (140, 152), 4: (153, 166), 5: (167, None)},
    })

    def _reporte(self, clase, minutos, pico, media=140):
        plan = _plan([{**_dia(0, "run", "VO2max 5x3'", 50), "intensity_class": clase}])
        actividad = _actividad(1, 0, "running", minutos, 10.0, media)
        actividad.summary.max_hr = pico
        return build_adherence_report(plan, [actividad], today=HOY, hr_zones=self.ZONAS)

    def test_EL_CASO_DEL_ATLETA_sesion_libre_pero_hecha_de_verdad(self):
        """No se le exige que use el entreno estructurado del reloj. Si salió
        y apretó, hizo su sesión: el pico llegó a Z5."""
        entrada = self._reporte("vo2max", 49, pico=171).entries[0]
        self.assertEqual(entrada.status, "completed")
        self.assertTrue(entrada.intensity_checked)

    def test_EL_FALLO_el_mismo_dia_trotado_suave_ya_no_es_completada(self):
        reporte = self._reporte("vo2max", 48, pico=142)
        entrada = reporte.entries[0]
        self.assertEqual(entrada.status, "under_intensity")
        self.assertEqual(reporte.under_intensity_count, 1)

    def test_y_se_dice_CON_LOS_DOS_NUMEROS(self):
        """Sin ellos esto es una acusación: el atleta que sí hizo su sesión
        tiene que poder ver de dónde sale y contradecirla."""
        texto = self._reporte("vo2max", 48, pico=142).entries[0].describe()
        self.assertIn("142 ppm", texto)
        self.assertIn("153 ppm", texto)

    def test_sigue_contando_como_ENTRENADA_para_el_volumen(self):
        """Salió, hizo su tiempo y sostuvo el hábito. El porcentaje de
        adherencia mide eso; lo que no dio es el estímulo, y eso se cuenta
        aparte."""
        self.assertEqual(self._reporte("vo2max", 48, pico=142).adherence_pct, 100.0)

    def test_pero_NO_cuenta_como_calidad_en_el_expediente(self):
        """`quality_done` es el número con el que se decide si progresar la
        intensidad. Si contara ésta, el macrociclo subiría la dosis dura
        creyendo que la anterior se absorbió."""
        reporte = self._reporte("vo2max", 48, pico=142)
        plan = _plan([{**_dia(0, "run", "VO2max 5x3'", 50), "intensity_class": "vo2max"}])
        fila = record_week(
            Path(tempfile.mkdtemp()), plan.model_dump()["weeks"][0], reporte.to_state_dict())
        self.assertEqual(fila["quality_prescribed"], 1)
        self.assertEqual(fila["quality_done"], 0)
        self.assertEqual(fila["under_intensity_sessions"], 1)
        # Y el día NO figura como fallado: el atleta salió a entrenar.
        self.assertEqual(list(fila["by_weekday"].values()), [[1, 1]])

    def test_CUATRO_series_en_vez_de_CINCO_sigue_siendo_completada(self):
        """La otra mitad de la pregunta, y la respuesta honesta es que NO se
        distingue: 3 minutos menos en una sesión de 50 caen dentro de la
        tolerancia, y el pico sigue llegando a Z5 porque las cuatro series que
        sí hizo fueron de verdad. Verificar el número de repeticiones pediría
        comparar vuelta a vuelta, que hoy no se hace."""
        entrada = self._reporte("vo2max", 47, pico=169).entries[0]
        self.assertEqual(entrada.status, "completed")

    def test_y_el_bloque_del_prompt_DICE_lo_que_no_comprueba(self):
        bloque = render_adherence_block(self._reporte("vo2max", 48, pico=142))
        self.assertIn("Lo que esto NO comprueba", bloque)
        self.assertIn("5 series en vez de 4", bloque)


class EL_RITMO_RESCATA_CUANDO_EL_PULSO_LLEGA_TARDE(unittest.TestCase):
    """El atleta 2, el 2026-09-13: "podemos acelerar a velocidades arriba de
    threshold, y nuestro corazón es lento para subir".

    Seis meses de su historial: de 43 tramos llanos corridos más rápido que su
    umbral (5:52/km), en el 33 % ni el PICO de pulso tocó Z4. Sus intervalos de
    60" del 22-jul iban a 4:54/km con el pulso medio en 135-151. Con sólo el
    pulso, una sesión así se marcaba "hecha sin la intensidad prescrita".

    El ritmo sólo rescata, nunca acusa.
    """

    ZONAS = UnaSesionDeCalidadHECHA_EN_FACIL_NO_ES_CALIDAD.ZONAS
    UMBRAL = 353.0

    @staticmethod
    def _tramos(ritmo, n=10, segundos=60):
        return [{"duration_s": segundos, "averageSpeed_ms": 1000.0 / ritmo,
                 "distance_m": segundos * 1000.0 / ritmo, "elevationGain_m": 0}] * n

    def _entrada(self, clase="vo2max", *, pico=150, tramos=None, tipo="running", umbral=UMBRAL):
        plan = _plan([{**_dia(0, "run", "Series", 50), "intensity_class": clase}])
        actividad = _actividad(1, 0, tipo, 49, 10.0, 140)
        actividad.summary.max_hr = pico
        actividad.laps = tramos if tramos is not None else []
        return build_adherence_report(
            plan, [actividad], today=HOY, hr_zones=self.ZONAS, umbral_ritmo_s_km=umbral).entries[0]

    def test_EL_CASO_DEL_ATLETA_series_a_4_54_con_el_pico_bajo_z4(self):
        entrada = self._entrada(tramos=self._tramos(294))
        self.assertEqual(entrada.status, "completed")
        self.assertTrue(entrada.intensity_by_pace)
        self.assertIn("el ritmo sí llegó", entrada.describe())

    def test_sin_tramos_el_pulso_sigue_mandando(self):
        self.assertEqual(self._entrada(tramos=[]).status, "under_intensity")

    def test_trotadas_a_ritmo_facil_no_rescatan_nada(self):
        self.assertEqual(self._entrada(tramos=self._tramos(430)).status, "under_intensity")

    def test_en_cinta_el_ritmo_no_rescata(self):
        self.assertEqual(self._entrada(tramos=self._tramos(294), tipo="treadmill_running").status, "under_intensity")

    def test_sin_ritmo_umbral_no_hay_con_que_comparar(self):
        self.assertEqual(self._entrada(tramos=self._tramos(294), umbral=None).status, "under_intensity")

    def test_un_tempo_se_mide_contra_SU_banda_no_contra_la_del_vo2max(self):
        """6:05/km (365 s) está dentro de la banda de tempo (hasta 1,06 × 353 =
        374 s) y fuera de la de VO2max (hasta 339 s)."""
        self.assertEqual(self._entrada("tempo_threshold", tramos=self._tramos(365)).status, "completed")
        self.assertEqual(self._entrada("vo2max", tramos=self._tramos(365)).status, "under_intensity")


class LA_COMPROBACION_SE_ABSTIENE_CUANDO_NO_SABE(unittest.TestCase):
    """No saber no es lo mismo que saber que estuvo mal.

    Es la mitad que decide si esto sirve o estorba: un falso "no hiciste tu
    sesión" cuesta la confianza del atleta en todo lo demás que le diga la app.
    """

    ZONAS = UnaSesionDeCalidadHECHA_EN_FACIL_NO_ES_CALIDAD.ZONAS

    def _estado(self, clase, *, pico, zonas, deporte="run", tipo="running"):
        plan = _plan([{**_dia(0, deporte, "Sesión", 50), "intensity_class": clase}])
        actividad = _actividad(1, 0, tipo, 49, 10.0, 140)
        actividad.summary.max_hr = pico
        return build_adherence_report(plan, [actividad], today=HOY, hr_zones=zonas).entries[0]

    def test_una_sesion_que_NO_es_de_calidad_no_se_juzga(self):
        """Un rodaje suave se hace suave. Juzgarlo por no llegar a Z4 sería
        marcar como fallo justamente hacerlo bien."""
        entrada = self._estado("long_run", pico=141, zonas=self.ZONAS)
        self.assertEqual(entrada.status, "completed")
        self.assertFalse(entrada.intensity_checked)

    def test_sin_pulsaciones_no_hay_veredicto(self):
        """Una piscina sin pulsómetro, un reloj que no registró frecuencia."""
        entrada = self._estado("vo2max", pico=0, zonas=self.ZONAS)
        self.assertEqual(entrada.status, "completed")
        self.assertFalse(entrada.intensity_checked)

    def test_sin_las_zonas_del_atleta_tampoco(self):
        """Zonas de otro atleta o de una fórmula no valen: la comparación es
        contra las que su reloj aplica de verdad."""
        entrada = self._estado("vo2max", pico=120, zonas=None)
        self.assertEqual(entrada.status, "completed")
        self.assertFalse(entrada.intensity_checked)

    def test_las_cuestas_y_los_progresivos_quedan_FUERA_a_proposito(self):
        """En una cuesta la frecuencia cardiaca llega tarde y en un progresivo
        de 15 segundos no llega. Las dos son sesiones duras de verdad que este
        criterio marcaría como flojas."""
        for clase in ("hill_repeats", "neuromuscular", "race_day"):
            with self.subTest(clase=clase):
                self.assertEqual(self._estado(clase, pico=140, zonas=self.ZONAS).status, "completed")


if __name__ == "__main__":
    unittest.main()
