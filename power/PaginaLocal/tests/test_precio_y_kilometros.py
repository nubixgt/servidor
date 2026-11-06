"""Dos fallos que no dan error: uno cobra de más, el otro calla un dato.

## 1. Sonnet 5 se cobraba un 50% de más

`MODEL_PRICING_USD_PER_MILLION` tenía `claude-sonnet: (3.00, 15.00)`. Anthropic
anunció el precio de lanzamiento de Sonnet 5 como introductorio hasta el
31-08-2026, con subida a $3/$15 el 01-09-2026 — y **canceló esa subida**: el
precio de lanzamiento pasó a ser el estándar. La tabla oficial lo dice
expresamente ("the previously scheduled increase [...] will not occur").
Verificado el 02-09-2026 contra platform.claude.com.

Sonnet corre los TRES expertos en el plan Pro, o sea en todas las
generaciones. Medido sobre el libro de costos real de la app:

    total del libro ............... $9.5723
    nodos con Sonnet .............. $3.8709
    lo que costaron de verdad ..... $2.5806
    SOBRECOSTO .................... $1.2903   (13.5% del total)

Nada falla cuando un precio está mal: la cifra sale, es plausible, y sólo se
descubre comparándola con la factura. Por eso hay prueba.

## 2. El kilometraje no existía para nadie

El atleta le preguntó al entrenador cuántos kilómetros había corrido el mes
pasado y no lo sabía. No era el prompt: `HabitoDeporte` medía sesiones, horas
y duraciones, y **ni un metro**. `ActivitySummary.distance` llegaba de Garmin y
nadie lo leía.

Y encima el bloque de hábitos iba a los dos planificadores pero NO al chat, así
que el entrenador tampoco tenía el volumen en horas.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from dataclasses import dataclass
from datetime import date, timedelta
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import SYSTEM_PROMPT  # noqa: E402
from services.ai.model_config import ModelSelector  # noqa: E402
from services.ai.training_habits import build_training_habits, render_training_habits  # noqa: E402


class ElPrecioDeSonnetEsElQueCobraAnthropic(unittest.TestCase):
    def test_EL_FALLO_sonnet_5_cuesta_2_y_10_no_3_y_15(self):
        self.assertEqual(
            ModelSelector.MODEL_PRICING_USD_PER_MILLION["claude-sonnet"], (2.00, 10.00)
        )

    def test_un_millon_de_tokens_de_cada_lado_cuesta_12_dolares(self):
        """La cuenta de punta a punta, con el nombre que el proveedor reporta
        de verdad (`claude-sonnet-5`), no con la clave interna."""
        self.assertAlmostEqual(
            ModelSelector.estimate_cost_usd("claude-sonnet-5", 1_000_000, 1_000_000),
            12.00, places=6,
        )

    def test_los_otros_modelos_en_uso_siguen_con_su_precio_verificado(self):
        """Comprobados el 02-09-2026 contra la pagina de cada proveedor.

        Gemini 3.5 Flash merece una nota: dos agregadores decian $0.75/$4.50,
        pero la pagina oficial de Google dice $1.50/$9.00. Manda la oficial, y
        queda escrito para que nadie lo "corrija" con un agregador.
        """
        esperado = {
            "claude-haiku": (1.00, 5.00),           # Haiku 4.5
            # gpt-5.2. Decía (0.88, 7.00) y la comprobación del 02-09 no lo vio: es el
            # precio de Batch/Flex. El estándar es $1,75/$14 (ver ElPrecioDeGpt52).
            "gpt-5": (1.75, 14.00),
            "gemini-3.1-flash-lite": (0.25, 1.50),
            "gemini-3.5-flash": (1.50, 9.00),
        }
        for clave, precio in esperado.items():
            with self.subTest(modelo=clave):
                self.assertEqual(ModelSelector.MODEL_PRICING_USD_PER_MILLION[clave], precio)

    def test_ningun_modelo_de_ningun_tier_se_queda_sin_precio(self):
        """Un modelo sin precio cuesta $0 en el informe y dinero en la factura.
        Ya paso una vez con las instantaneas con fecha de OpenAI."""
        from services.ai.ai_settings import AIMode, AISettings

        for modo in (AIMode.STANDARD, AIMode.PRO):
            ajustes = AISettings(mode=modo)
            for rol, modelo in ajustes.model_assignments[modo].items():
                with self.subTest(tier=modo.value, rol=rol.value):
                    self.assertIn(
                        modelo, ModelSelector.MODEL_PRICING_USD_PER_MILLION,
                        f"'{modelo}' no tiene precio: sus llamadas se contarian como $0",
                    )


# --- Un Garmin de mentira, con lo justo que lee el extractor de habitos ------
@dataclass
class _Resumen:
    duration: int          # segundos
    distance: float        # METROS, como los da Garmin
    vigorous_intensity_minutes: int = 0


@dataclass
class _Actividad:
    start_time: str
    activity_type: str
    summary: _Resumen


class _Garmin:
    def __init__(self, actividades):
        self.all_activities = actividades


def _garmin_de_prueba(hoy: date):
    """Cuatro semanas: 10 km de carrera por semana y 30 km de bici, más una
    sesión de fuerza (sin distancia)."""
    actos = []
    for semana in range(4):
        dia = hoy - timedelta(days=7 * semana + 1)
        actos.append(_Actividad(f"{dia.isoformat()} 07:00:00", "running", _Resumen(3600, 10_000)))
        actos.append(_Actividad(f"{dia.isoformat()} 17:00:00", "cycling", _Resumen(5400, 30_000)))
        actos.append(_Actividad(f"{dia.isoformat()} 19:00:00", "strength_training", _Resumen(1800, 0)))
    return _Garmin(actos)


class LosKilometrosLleganALasIAs(unittest.TestCase):
    def setUp(self):
        self.hoy = date(2026, 9, 1)
        self.h = build_training_habits(_garmin_de_prueba(self.hoy), hoy=self.hoy,
                                       semanas=4, dias_de_datos=28)
        self.por_deporte = {d.deporte: d for d in self.h.por_deporte}

    def test_EL_FALLO_ahora_hay_kilometros_por_deporte(self):
        self.assertEqual(self.por_deporte["correr"].km_total, 40.0)
        self.assertEqual(self.por_deporte["bici"].km_total, 120.0)

    def test_los_metros_de_garmin_se_convierten_a_km(self):
        """10.000 son diez kilometros, no diez mil. Misma conversion que
        adherence.py, o la app daria dos cifras distintas del mismo dato."""
        self.assertEqual(self.por_deporte["correr"].km_por_semana, 10.0)

    def test_hay_un_total_de_los_ULTIMOS_30_DIAS(self):
        """Es como el atleta lo pregunta -- "cuanto corri el mes pasado" -- y
        un promedio semanal no contesta a eso."""
        self.assertGreater(self.por_deporte["correr"].km_ultimos_30d, 0)
        self.assertLessEqual(self.por_deporte["correr"].km_ultimos_30d,
                             self.por_deporte["correr"].km_total)

    def test_la_fuerza_no_inventa_kilometros(self):
        self.assertEqual(self.por_deporte["fuerza"].km_total, 0.0)

    def test_el_bloque_escribe_los_km_y_calla_los_que_no_existen(self):
        bloque = render_training_habits(self.h)
        self.assertIn("km/sem", bloque)
        self.assertIn("km en los últimos 30 días", bloque)
        # La linea de fuerza no puede llevar un "0.0 km", que se leeria como
        # que no entreno.
        linea_fuerza = next(l for l in bloque.splitlines() if l.strip().startswith("fuerza"))
        self.assertNotIn("km", linea_fuerza)


class ElEntrenadorDelChatRecibeLosHabitos(unittest.TestCase):
    """Los dos planificadores llevaban este bloque desde el principio; el chat
    no. Es la misma clase de fallo que ya aparecio con el plan, el informe y la
    ficha de numeros: un dato que existe, que otro consumidor si recibe, y que
    a este nadie le pasaba."""

    def test_el_contexto_del_coach_incluye_la_seccion(self):
        import inspect

        from services.ai import coach_chat
        fuente = inspect.getsource(coach_chat.build_coach_context)
        self.assertIn("cargar_habitos", fuente)
        self.assertIn("Lo que hace de verdad", fuente)

    def test_el_prompt_le_dice_que_ahi_estan_los_kilometros(self):
        """Sin esto puede tener el bloque delante y contestar que no lo tiene,
        que es exactamente lo que ya paso con el informe de salud."""
        import re
        plano = re.sub(r"\s+", " ", SYSTEM_PROMPT)
        self.assertIn("Lo que hace de verdad", plano)
        self.assertIn("km de los últimos 30 días", plano)


if __name__ == "__main__":
    unittest.main()


class ElPrecioDeGpt52(unittest.TestCase):
    """GPT-5.2 se anotaba a la MITAD de lo que se factura (2026-09-23).

    La tabla tenía gpt-5.2 a $0,88/$7 y gpt-5.2-pro a $10,50/$84 desde el primer
    commit: exactamente el precio de Batch/Flex. La tabla oficial de OpenAI dice
    $1,75/$14 y $21/$168, y ninguna llamada de la app pide `service_tier`, así que
    se paga el estándar. GPT corre la decisión semanal, los tres expertos y el
    macrociclo en `standard`: casi todo el recibo de una generación.
    """

    def test_el_precio_estandar_de_la_tabla_oficial(self):
        from services.ai.model_config import ModelSelector

        for clave in ("gpt-5", "gpt-5-mid", "gpt-5-search"):
            self.assertEqual(ModelSelector.MODEL_PRICING_USD_PER_MILLION[clave], (1.75, 14.00), clave)
        for clave in ("gpt-5.2-pro", "gpt-5.2-pro-search"):
            self.assertEqual(ModelSelector.MODEL_PRICING_USD_PER_MILLION[clave], (21.00, 168.00), clave)

    def test_nadie_pide_el_tier_de_descuento(self):
        """Si algún día se pide Flex o Batch, el precio de la tabla tiene que cambiar con él."""
        from pathlib import Path

        fuente = (Path(__file__).resolve().parents[1] / "services" / "ai" / "model_config.py").read_text(encoding="utf-8")
        for uso in ('"service_tier"', "service_tier="):
            self.assertNotIn(uso, fuente)
