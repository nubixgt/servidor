"""La semana habitual del atleta, leída de Garmin.

El caso que lo motivó, con sus números reales: el atleta hace trabajo de
calidad TODOS los miércoles desde hace meses, y la primera semana que generó la
app fue Z1/Z2 de principio a fin. No fue variabilidad del modelo -- nadie le
había dicho que ese miércoles existiera. El experto de actividad tiene
prohibido hablar de carga global, el de métricas mira ACWR y CTL, y la forma de
la semana no la miraba nadie.

El otro caso, del mismo atleta: declaró un bloque de natación un día que nunca
ha nadado, y el plan gastó ahí horas que normalmente corre.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from datetime import date, timedelta
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.training_habits import (  # noqa: E402
    VENTANA_SEMANAS,
    build_training_habits,
    cargar_habitos,
    guardar_habitos,
    render_training_habits,
)

HOY = date(2026, 8, 31)  # un lunes


def act(dia: date, tipo: str, minutos: int, vigorosos: int = 0):
    return SimpleNamespace(
        activity_type=tipo,
        start_time=f"{dia.isoformat()} 06:00:00",
        summary=SimpleNamespace(duration=minutos * 60, vigorous_intensity_minutes=vigorosos),
    )


def datos(actividades):
    return SimpleNamespace(all_activities=actividades, recent_activities=None)


def _semanas_atras(n: int, weekday: int) -> list[date]:
    """Las n fechas del mismo día de la semana, hacia atrás desde HOY."""
    fechas, d = [], HOY
    while len(fechas) < n:
        if d.weekday() == weekday:
            fechas.append(d)
        d -= timedelta(days=1)
    return fechas


class ElMiercolesDeCalidadSeVe(unittest.TestCase):
    """El fallo original: doce miércoles seguidos de series, invisibles."""

    def setUp(self):
        acts = []
        for d in _semanas_atras(12, 2):            # miércoles, con series
            acts.append(act(d, "running", 60, vigorosos=22))
        for d in _semanas_atras(12, 0):            # lunes, rodaje suave
            acts.append(act(d, "running", 45, vigorosos=0))
        self.h = build_training_habits(datos(acts), hoy=HOY)

    def test_el_miercoles_aparece_como_dia_de_calidad(self):
        self.assertIn("miércoles", self.h.dias_de_calidad)

    def test_el_lunes_suave_NO_aparece_como_calidad(self):
        self.assertNotIn("lunes", self.h.dias_de_calidad)

    def test_cuenta_las_sesiones_vigorosas_por_semana(self):
        self.assertEqual(self.h.vigorosas_por_semana, 1.0)

    def test_el_bloque_lo_dice_con_todas_las_letras(self):
        texto = render_training_habits(self.h)
        self.assertIn("miércoles", texto)
        self.assertIn("trabajo vigoroso", texto)
        self.assertIn("Días donde cae el trabajo vigoroso:", texto)

    def test_el_dia_de_calidad_va_con_su_numero_al_lado(self):
        """Una lista pelada de nombres borró un caso real.

        El atleta hacía calidad los miércoles y el bloque dijo "se concentra
        en: viernes, domingo" porque esos dos tenían una semana más. Un 4 y un
        3 son casi lo mismo; sin el número no hay forma de saberlo, y quien
        tiene que decidir es el entrenador — que es toda la premisa de este
        módulo.
        """
        texto = render_training_habits(self.h)
        self.assertIn("miércoles (12/12 semanas)", texto)


class LasSemanasEnBlancoCuentan(unittest.TestCase):
    """Sin esto, quien nada una vez al mes sale como "1 sesión por semana"."""

    def test_nadar_una_vez_al_mes_no_es_una_por_semana(self):
        """El promedio reparte entre TODAS las semanas, también las vacías.

        Antes esto era una mediana y se afirmaba `== 0.0`. La mediana defendía
        bien contra el "1 por semana", pero mentía en la dirección contraria:
        daba 0.0 en cuanto un deporte se hacía en menos de la mitad de las
        semanas, y el prompt acababa diciendo "natación 0.0 ses/sem" unas
        líneas por encima de "carga crónica 5.0 h/sem". Dos números que se
        contradicen dentro del mismo texto.

        Lo que el test defiende de verdad es que tres sesiones en doce semanas
        NO se lean como un hábito semanal. 0.25 lo dice; 0.0 y 1.0 los dos
        mienten.
        """
        acts = [act(HOY - timedelta(weeks=w, days=3), "lap_swimming", 40) for w in (0, 4, 8)]
        h = build_training_habits(datos(acts), hoy=HOY)
        natacion = next(d for d in h.por_deporte if d.deporte == "natación")
        self.assertEqual(natacion.sesiones_por_semana, 0.2)   # 3/12, no 1.0
        self.assertLess(natacion.sesiones_por_semana, 0.5)
        self.assertEqual(natacion.semanas_con_actividad, 3)

    def test_un_deporte_que_se_hizo_nunca_sale_como_cero_sesiones(self):
        """La comprobación que la mediana no pasaba.

        Si hubo sesiones, el ritmo semanal no puede ser 0.0: el modelo lo lee
        como "no lo hace" y quita el deporte del plan.
        """
        acts = [act(HOY - timedelta(weeks=w, days=3), "lap_swimming", 40) for w in (0, 4, 8)]
        h = build_training_habits(datos(acts), hoy=HOY)
        for d in h.por_deporte:
            with self.subTest(deporte=d.deporte):
                self.assertGreater(d.sesiones_por_semana, 0.0)
                self.assertGreater(d.horas_por_semana, 0.0)

    def test_correr_todas_las_semanas_si_es_una_por_semana(self):
        acts = [act(d, "running", 50) for d in _semanas_atras(12, 2)]
        h = build_training_habits(datos(acts), hoy=HOY)
        correr = next(d for d in h.por_deporte if d.deporte == "correr")
        self.assertEqual(correr.sesiones_por_semana, 1.0)


class LoDeclaradoAlLadoDeLoHecho(unittest.TestCase):
    """Ni se ignora el bloque declarado ni se obliga a llenarlo: se enseñan los
    dos números y decide el entrenador."""

    def setUp(self):
        acts = [act(d, "running", 55, vigorosos=20) for d in _semanas_atras(12, 2)]
        perfil = {"day_time_blocks": {
            "Martes": [{"start": "05:00", "end": "07:00", "sports": ["Bici"]}],
            "Miercoles": [{"start": "05:00", "end": "07:00", "sports": ["Correr"]}],
        }}
        self.h = build_training_habits(datos(acts), perfil=perfil, hoy=HOY)
        self.texto = render_training_habits(self.h)

    def test_el_martes_declarado_y_nunca_usado_sale_en_el_contraste(self):
        self.assertTrue(any("martes" in c and "0 de 12" in c for c in self.h.contraste_declarado))

    def test_el_miercoles_declarado_y_cumplido_tambien_sale(self):
        self.assertTrue(any("miércoles" in c and "12 de 12" in c for c in self.h.contraste_declarado))

    def test_NO_emite_ningun_veredicto(self):
        """Decir "quita el martes" seria decidir por el entrenador -- y un
        atleta que quiere CONSTRUIR ese habito es un caso legitimo."""
        bajo = self.texto.lower()
        for veredicto in ("elimina", "quita el", "no programes", "debes ", "hay que "):
            with self.subTest(veredicto=veredicto):
                self.assertNotIn(veredicto, bajo)

    def test_y_dice_explicitamente_que_solo_describe(self):
        self.assertIn("no dice lo que debe hacer", self.texto)


class SinDatosNoSeInventaNada(unittest.TestCase):
    def test_sin_actividades_lo_dice_y_no_revienta(self):
        h = build_training_habits(datos([]), hoy=HOY)
        self.assertEqual(h.actividades, 0)
        self.assertTrue(h.avisos)
        self.assertIn("no hay hábito que leer", render_training_habits(h))

    def test_actividades_sin_fecha_o_sin_duracion_se_ignoran(self):
        acts = [
            SimpleNamespace(activity_type="running", start_time=None, summary=None),
            act(HOY - timedelta(days=2), "running", 0),
            act(HOY - timedelta(days=3), "running", 50),
        ]
        h = build_training_habits(datos(acts), hoy=HOY)
        self.assertEqual(h.actividades, 1)

    def test_lo_de_fuera_de_la_ventana_no_cuenta(self):
        acts = [act(HOY - timedelta(weeks=30), "running", 60)]
        self.assertEqual(build_training_habits(datos(acts), hoy=HOY).actividades, 0)


class LasDosTablasDeDeportesNoPuedenDivergir(unittest.TestCase):
    """`_ETIQUETA_PERFIL` es una copia de `SPORT_LABEL_TO_KEY` de server.py.

    No se importa la original porque `web_app.server` importa `services` (seria
    circular) y su import crea directorios y corre una migracion. La copia es
    deliberada; lo que no puede pasar es que se separen en silencio, que es
    justo como se rompio esto la primera vez: todo lo declarado caia en "otro"
    y el contraste salia vacio sin un solo error.
    """

    def test_mismas_etiquetas_y_mismas_claves(self):
        from services.ai.training_habits import _ETIQUETA_PERFIL
        from web_app.server import SPORT_LABEL_TO_KEY

        original = {k.strip().lower(): v for k, v in SPORT_LABEL_TO_KEY.items()}
        self.assertEqual(
            _ETIQUETA_PERFIL, original,
            "SPORT_LABEL_TO_KEY cambio y _ETIQUETA_PERFIL no: un deporte declarado "
            "dejaria de contarse en el contraste, sin avisar.",
        )

class SeCuentanSEMANASNoSESIONES(unittest.TestCase):
    """Dos carreras el mismo lunes son UN lunes de correr, no dos.

    Contando sesiones, el contraste salia "declaro correr los lunes: 24 de 12
    semanas lo hizo" -- ademas de falso, un numero imposible en mitad de un
    prompt, que es justo lo que hace razonar mal a un modelo.
    """

    def setUp(self):
        acts = []
        for d in _semanas_atras(12, 0):                    # doce lunes
            acts.append(act(d, "running", 45))            # dos sesiones
            acts.append(act(d, "running", 30))            # el mismo dia
        perfil = {"day_time_blocks": {"Lunes": [{"sports": ["Correr"]}]}}
        self.h = build_training_habits(datos(acts), perfil=perfil, hoy=HOY)

    def test_el_contraste_nunca_pasa_del_numero_de_semanas(self):
        for linea in self.h.contraste_declarado:
            self.assertIn("12 de 12", linea)
            self.assertNotIn("24 de 12", linea)

    def test_el_ritmo_tambien_cuenta_semanas(self):
        self.assertEqual(self.h.ritmo_semanal["lunes"]["deportes"]["correr"], 12)

    def test_ninguna_cuenta_del_bloque_supera_la_ventana(self):
        texto = render_training_habits(self.h)
        for parte in texto.split():
            if "/12" in parte and parte.split("/")[0].isdigit():
                self.assertLessEqual(int(parte.split("/")[0]), 12, parte)


class LaVentanaNoPuedeSerMasLargaQueLosDatos(unittest.TestCase):
    """El fallo de verdad, encontrado en la cuenta del atleta el 2026-08-31.

    La generacion de semana pide 30 dias de actividades
    (`WEEK_WINDOW.activities_days`) y esto calculaba medianas sobre 12 semanas.
    Las siete semanas y media que nunca se bajaron entraban como ceros, asi que
    un atleta que entreno los 31 dias de agosto -- 32 actividades -- recibio un
    bloque que decia "0.0 ses/sem" de TODOS sus deportes y "entreno en 5 de 12
    semanas". No es que no entrenara: es que no miramos.

    Es el mismo fallo que `services/garmin/extraction_windows.py` documenta de
    `build_volume_baseline`, y su test guardian no lo cazo porque solo conoce
    las ventanas de carga.
    """

    def _un_mes_entrenando(self):
        return [act(HOY - timedelta(days=d), "running", 50, vigorosos=20)
                for d in range(0, 28, 2)]

    def test_pedir_30_dias_no_permite_hablar_de_12_semanas(self):
        h = build_training_habits(datos(self._un_mes_entrenando()), hoy=HOY, dias_de_datos=30)
        self.assertEqual(h.semanas, 4)

    def test_con_la_ventana_recortada_el_ritmo_deja_de_ser_cero(self):
        h = build_training_habits(datos(self._un_mes_entrenando()), hoy=HOY, dias_de_datos=30)
        correr = next(d for d in h.por_deporte if d.deporte == "correr")
        self.assertGreater(correr.sesiones_por_semana, 1.0)
        self.assertEqual(correr.semanas_con_actividad, 4)

    def test_EL_FALLO_sin_recortar_el_mismo_atleta_sale_como_que_no_entrena(self):
        """Contra el codigo anterior esto era lo que leia el planificador."""
        h = build_training_habits(datos(self._un_mes_entrenando()), hoy=HOY)
        self.assertEqual(h.semanas, VENTANA_SEMANAS)
        correr = next(d for d in h.por_deporte if d.deporte == "correr")
        # Entreno 4 de 12 semanas porque solo hay 4 semanas de datos.
        self.assertLessEqual(correr.semanas_con_actividad, 5)

    def test_mas_datos_de_los_que_pide_la_ventana_no_la_alargan(self):
        h = build_training_habits(datos(self._un_mes_entrenando()), hoy=HOY, dias_de_datos=180)
        self.assertEqual(h.semanas, VENTANA_SEMANAS)

    def test_el_bloque_dice_cuantas_semanas_esta_mirando(self):
        h = build_training_habits(datos(self._un_mes_entrenando()), hoy=HOY, dias_de_datos=30)
        self.assertIn("Ventana: 4 semanas", render_training_habits(h))


class ElRetratoSeGuardaYSeReusa(unittest.TestCase):
    """El macrociclo baja 180 dias; la semana, 30.

    Doce semanas de habito solo se pueden leer del primero, asi que se calcula
    alli y las semanas lo reusan. El coste en Garmin es cero: son datos que ya
    se bajaron.
    """

    def setUp(self):
        import tempfile
        self.dir = Path(self.enterContext(tempfile.TemporaryDirectory()))
        self.h = build_training_habits(
            datos([act(d, "running", 50, vigorosos=20) for d in _semanas_atras(12, 2)]),
            hoy=HOY, dias_de_datos=180,
        )

    def test_lo_guardado_se_vuelve_a_leer(self):
        guardar_habitos(self.dir, self.h)
        self.assertIn("Ventana: 12 semanas", cargar_habitos(self.dir, hoy=HOY) or "")

    def test_sin_fichero_no_hay_retrato_y_no_revienta(self):
        self.assertIsNone(cargar_habitos(self.dir, hoy=HOY))

    def test_un_retrato_viejo_se_descarta(self):
        """Pasadas seis semanas describe como entrenaba, no como entrena."""
        guardar_habitos(self.dir, self.h)
        self.assertIsNone(cargar_habitos(self.dir, hoy=HOY + timedelta(days=60)))

    def test_un_retrato_de_hace_semanas_dice_que_lo_es(self):
        guardar_habitos(self.dir, self.h)
        texto = cargar_habitos(self.dir, hoy=HOY + timedelta(days=21)) or ""
        self.assertIn("Calculado el", texto)
        self.assertIn("hace 21 dias".replace("dias", "días"), texto)

    def test_un_retrato_recien_hecho_no_lleva_esa_coletilla(self):
        guardar_habitos(self.dir, self.h)
        self.assertNotIn("Calculado el", cargar_habitos(self.dir, hoy=HOY) or "")

    def test_un_fichero_corrupto_no_rompe_la_generacion(self):
        (self.dir / "training_habits.json").write_text("{roto", encoding="utf-8")
        self.assertIsNone(cargar_habitos(self.dir, hoy=HOY))


if __name__ == "__main__":
    unittest.main()
