"""Un paso que dice «Z1-Z2» no lleva zona objetivo. Ninguna.

## El fallo, con la queja del atleta

Garmin no admite un objetivo de DOS zonas: `target_zone` es un entero. Así que
cuando el entrenador escribe "10' calentamiento muy fácil, Z1-Z2", el
codificador colapsa a una —Z2, siempre la alta— y el rango sobrevive sólo en el
texto.

En la muñeca eso significa que **el reloj le avisa por bajar a Z1 durante su
propio calentamiento**. Es literalmente la queja con la que empezó esto: *"el
reloj me regaña cuando bajo de Z2 en un rodaje suave"*.

Medido en la generación REAL del 2026-09-11 (atleta 2, tier `standard`): SEIS
pasos así en una sola semana, todos calentamientos y enfriamientos, todos
forzados a Z2.

## Por qué soltar la zona en vez de elegir la baja

Porque elegir Z1 sería el mismo error al revés: el atleta que sube a Z2 en una
cuesta tampoco hizo nada mal. Lo que el entrenador quiso decir es "no fuerces",
y eso en Garmin se dice con `target_type="none"` y el texto delante.

## Por qué en código y no en el prompt

Porque el prompt YA lo pide desde la v1.1 —"un paso lleva UNA zona o ninguna"—
y el modelo lo incumple de todas formas. Una regla que el modelo puede
desobedecer no es una garantía.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation.plan_validator import (  # noqa: E402
    _RANGO_DE_ZONAS,
    _soltar_zona_en_rangos,
)


def paso(desc, zona=2, tipo="warmup"):
    return WorkoutStep(
        step_order=1, step_type=tipo, duration_type="time", duration_value=600,
        target_type="hr_zone", target_zone=zona, description=desc,
    )


def _plan(*pasos):
    dia = WorkoutDay(
        date="2026-09-11", day_name_es="Viernes", is_rest=False, sport="run",
        focus="rodaje", purpose=".", adaptation=".", intensity_class="easy",
        discipline="carrera", total_duration_minutes=40.0, steps=list(pasos),
    )
    return WeeklyPlanPayload(athlete_name="T", start_date="2026-09-11",
                             zones_table="N/A",
                             weeks=[{"week_number": 1, "week_rationale": "", "days": [dia]}])


class ElPatronDistingueUnRangoDeUnaZona(unittest.TestCase):
    def _es_rango(self, texto):
        m = _RANGO_DE_ZONAS.search(texto)
        return bool(m) and m.group(1) != m.group(2)

    def test_las_formas_reales_de_escribir_un_rango(self):
        for texto in ("10' calentamiento muy fácil, Z1-Z2", "Z2 a Z3 sostenido",
                      "Z1/Z2 suave", "z1 - z2", "de Z2 hasta Z4"):
            with self.subTest(texto=texto):
                self.assertTrue(self._es_rango(texto))

    def test_una_zona_sola_no_es_un_rango(self):
        for texto in ("Z2 estable", "rodaje en Z3", "35' Z2, conversacional"):
            with self.subTest(texto=texto):
                self.assertFalse(self._es_rango(texto))

    def test_la_misma_zona_dos_veces_tampoco(self):
        """«8x100m Z2-Z2» es una zona escrita raro, no un rango."""
        self.assertFalse(self._es_rango("8x100m Z2-Z2"))

    def test_no_confunde_una_serie_con_un_rango(self):
        """Los números de una serie no son zonas."""
        self.assertFalse(self._es_rango("4x400m con 2' de recuperación"))


class UnRangoSeQuedaSinZona(unittest.TestCase):
    def test_el_caso_real_del_calentamiento(self):
        p, cambio = _soltar_zona_en_rangos(_plan(paso("10' calentamiento muy fácil, Z1-Z2")))
        s = p.weeks[0].days[0].steps[0]
        self.assertTrue(cambio)
        self.assertEqual(s.target_type, "none")
        self.assertIsNone(s.target_zone)

    def test_queda_el_enum_y_la_semana_se_guarda_sin_avisos(self):
        """Con un str asignado, cada paso soltado disparaba dos
        PydanticSerializationUnexpectedValue al guardar la semana: 585 en los
        logs de los workers del 2026-09-14, tapando los avisos que importan."""
        import warnings

        p, _ = _soltar_zona_en_rangos(_plan(paso("10' calentamiento muy fácil, Z1-Z2")))
        s = p.weeks[0].days[0].steps[0]
        self.assertIs(s.target_type, TargetType.NONE)
        with warnings.catch_warnings():
            warnings.simplefilter("error")
            self.assertEqual(p.model_dump(mode="json")["weeks"][0]["days"][0]["steps"][0]["target_type"], "none")

    def test_el_texto_del_entrenador_se_conserva_entero(self):
        """Lo que se pierde es la restricción, no la instrucción: el atleta
        sigue leyendo «Z1-Z2» y sabiendo qué se le pide."""
        texto = "30' muy fácil, Z1-Z2, completamente conversacional"
        p, _ = _soltar_zona_en_rangos(_plan(paso(texto)))
        s = p.weeks[0].days[0].steps[0]
        self.assertEqual(s.description, texto)
        self.assertEqual(s.duration_value, 600)

    def test_un_paso_de_TRABAJO_con_UNA_zona_no_se_toca(self):
        """La zona unica solo sobrevive donde el pulso esta QUIETO a proposito.

        Antes este caso usaba un `warmup` como paso de ejemplo, y pasaba por la
        razon equivocada: lo que lo protegia era tener una sola zona, no ser un
        tramo de trabajo. Ahora un calentamiento pierde la zona igualmente
        (ver ElCalentamientoYElEnfriamientoNuncaLlevanZona), asi que la
        garantia se comprueba donde de verdad esta.
        """
        p, cambio = _soltar_zona_en_rangos(
            _plan(paso("35' en Z2 estable", zona=2, tipo="main")))
        s = p.weeks[0].days[0].steps[0]
        self.assertFalse(cambio)
        self.assertEqual(s.target_type, "hr_zone")
        self.assertEqual(s.target_zone, 2)

    def test_convive_con_pasos_buenos_sin_tocarlos(self):
        """El primero cae por ser calentamiento Y por decir un rango; el
        segundo es trabajo con una zona sola y se queda como esta."""
        p, _ = _soltar_zona_en_rangos(_plan(
            paso("10' calentamiento, Z1-Z2"),
            paso("20' en Z3", zona=3, tipo="main"),
        ))
        pasos = p.weeks[0].days[0].steps
        self.assertIsNone(pasos[0].target_zone)
        self.assertEqual(pasos[1].target_zone, 3)

    def test_tambien_dentro_de_un_bloque_de_repeticiones(self):
        """Los rangos se esconden en los `substeps`, que es donde vive la
        recuperación de una serie."""
        repe = WorkoutStep(
            step_order=1, step_type="repeat", repeat_count=3, target_type="none",
            description="3x(3' fuerte, 2' suave)",
            substeps=[
                paso("3' fuerte en Z4", zona=4, tipo="interval"),
                paso("2' recuperación, Z1-Z2", zona=2, tipo="recovery"),
            ],
        )
        p, cambio = _soltar_zona_en_rangos(_plan(repe))
        subs = p.weeks[0].days[0].steps[0].substeps
        self.assertTrue(cambio)
        self.assertEqual(subs[0].target_zone, 4)
        self.assertIsNone(subs[1].target_zone)


class ElCalentamientoYElEnfriamientoNuncaLlevanZona(unittest.TestCase):
    """Los dos pasos que son una RAMPA, no un estado.

    ## Por qué no «calentamiento siempre Z2, enfriamiento siempre Z1»

    Es la propuesta obvia y reproduce la queja original en los dos extremos.
    Un calentamiento empieza en reposo: con objetivo Z2, el reloj avisa
    durante todo el principio, que es cuando el atleta está haciendo lo
    correcto. Un enfriamiento empieza donde acabó la última serie: después de
    8×2' con picos de 188 ppm el pulso tarda minutos en bajar a Z1, y durante
    esos minutos no hay nada que el atleta pueda hacer para obedecer el aviso
    salvo pararse.

    El objetivo de Garmin avisa por arriba Y por abajo. En un tramo cuyo pulso
    cambia a propósito, cualquier zona única garantiza un aviso injusto.
    """

    def test_un_calentamiento_en_Z2_pierde_la_zona(self):
        p, cambio = _soltar_zona_en_rangos(
            _plan(paso("15' calentamiento progresivo hasta Z2", zona=2, tipo="warmup")))
        s = p.weeks[0].days[0].steps[0]
        self.assertTrue(cambio)
        self.assertEqual(s.target_type, "none")
        self.assertIsNone(s.target_zone)

    def test_un_enfriamiento_en_Z1_tambien(self):
        """El caso caro: Z1 justo después de una serie de VO2max."""
        p, cambio = _soltar_zona_en_rangos(
            _plan(paso("10' enfriamiento suave en Z1", zona=1, tipo="cooldown")))
        s = p.weeks[0].days[0].steps[0]
        self.assertTrue(cambio)
        self.assertEqual(s.target_type, "none")
        self.assertIsNone(s.target_zone)

    def test_el_texto_del_entrenador_sobrevive_entero(self):
        """Se pierde la restricción, no la instrucción: Garmin muestra el
        texto del paso igual, y es ahí donde vive «entra suave»."""
        texto = "15' calentamiento progresivo, entra muy suave"
        p, _ = _soltar_zona_en_rangos(_plan(paso(texto, zona=2, tipo="warmup")))
        s = p.weeks[0].days[0].steps[0]
        self.assertEqual(s.description, texto)
        self.assertEqual(s.duration_value, 600)

    def test_una_serie_de_calidad_conserva_su_zona(self):
        """La regla es de los dos tipos de rampa, no de todo el entreno."""
        p, cambio = _soltar_zona_en_rangos(
            _plan(paso("3' fuerte en Z4", zona=4, tipo="interval")))
        self.assertFalse(cambio)
        self.assertEqual(p.weeks[0].days[0].steps[0].target_zone, 4)

    def test_tambien_dentro_de_un_bloque_de_repeticiones(self):
        repe = WorkoutStep(
            step_order=1, step_type="repeat", repeat_count=3, target_type="none",
            description="3x(3' fuerte, 2' suave)",
            substeps=[
                paso("3' fuerte en Z4", zona=4, tipo="interval"),
                paso("2' muy suave", zona=1, tipo="cooldown"),
            ],
        )
        p, cambio = _soltar_zona_en_rangos(_plan(repe))
        subs = p.weeks[0].days[0].steps[0].substeps
        self.assertTrue(cambio)
        self.assertEqual(subs[0].target_zone, 4)
        self.assertIsNone(subs[1].target_zone)


if __name__ == "__main__":
    unittest.main()
