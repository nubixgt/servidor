"""Lo que cuenta para el 100% se puede rellenar sin entrar en «Avanzado».

## El fallo que fija

De los quince campos que contaban para la barra, **siete vivían detrás de
«Avanzado»** — una pantalla que la app presenta como opcional. El atleta no
podía llegar al 100% sin entrar ahí, y como la puerta de generación exige el
80%, tampoco podía generar su plan sin visitar lo que se le había dicho que no
hacía falta.

Peor: dos de esos siete (`pool_access`, `bike_setup`) son decisiones suyas que
condicionan el plan de verdad. Sin piscina no se puede recetar natación por
mucho que marque Nadar el martes. Estaban tres pantallas más allá, entre la
altitud y el clima — preguntados donde no significan nada.

## La regla

Un campo que cuenta para la barra está en el alta básica, **o** lo rellena
Garmin al conectarlo. Ninguna tercera opción: si hace falta para generar y el
atleta tiene que escribirlo, se le pregunta donde lo va a ver.

## Por qué esta prueba lee tres ficheros

Porque la regla vive repartida en tres capas y ninguna de las tres puede verla
sola: qué cuenta lo dice PHP (`ProfileFields`), dónde vive lo dice el frontend
(`PESTANA_DE_CAMPO`), y qué mide Garmin lo dice el cerebro (`_set(...)` en la
importación). Una prueba en cualquiera de las tres pasaría con la regla rota.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]

# Las pantallas del alta inicial. `avanzado` NO está aquí a propósito.
BASICAS = {"identidad", "objetivos", "historial"}


def _lista_php(nombre: str) -> list[str]:
    php = (RAIZ / "Backend/src/Support/ProfileFields.php").read_text(encoding="utf-8")
    bloque = re.search(rf"{nombre} = \[(.*?)\n    \];", php, re.S)
    return re.findall(r"'([a-z_0-9]+)'", bloque.group(1)) if bloque else []


def campos_que_cuentan() -> list[str]:
    base = _lista_php("COMPLETION_BASE")
    php = (RAIZ / "Backend/src/Support/ProfileFields.php").read_text(encoding="utf-8")
    bloque = re.search(r"COMPLETION_BY_SPORT = \[(.*?)\n    \];", php, re.S)
    por_deporte = re.findall(r"'([a-z_0-9]+)'\s*=>", bloque.group(1)) if bloque else []
    return base + por_deporte


def pestana_de_cada_campo() -> dict[str, str]:
    js = (RAIZ / "Frontend/src/utils/profile.js").read_text(encoding="utf-8")
    bloque = re.search(r"PESTANA_DE_CAMPO = \{(.*?)\n\};", js, re.S)
    return dict(re.findall(r"([a-z_0-9]+):\s*'([a-z]+)'", bloque.group(1)))


def campos_que_mide_garmin() -> set[str]:
    py = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
    return set(re.findall(r'_set\("([a-z_0-9]+)"', py))


class LoQueCuentaSePuedeRellenarSinAvanzado(unittest.TestCase):
    def setUp(self):
        self.cuentan = campos_que_cuentan()
        self.donde = pestana_de_cada_campo()
        self.garmin = campos_que_mide_garmin()

    def test_hay_campos_que_cuentan(self):
        """Si esto falla, el parseo se rompió y el resto de la clase pasaría
        en verde sobre una lista vacía."""
        self.assertGreater(len(self.cuentan), 8)

    def test_cada_campo_obligatorio_es_basico_o_lo_mide_garmin(self):
        """La regla entera."""
        for campo in self.cuentan:
            with self.subTest(campo=campo):
                pestana = self.donde.get(campo)
                self.assertTrue(
                    pestana in BASICAS or campo in self.garmin,
                    f"`{campo}` cuenta para el 100%, vive en «{pestana or 'ninguna pantalla'}» "
                    f"y Garmin no lo rellena: el atleta no puede llegar al 100% sin entrar "
                    f"en Avanzado.",
                )

    def test_la_piscina_y_la_bici_se_preguntan_junto_a_la_rejilla(self):
        """No es colocación: deciden QUÉ puede llevar un día que acaba de
        marcar. Sin piscina no hay natación que recetar."""
        self.assertEqual(self.donde.get("pool_access"), "objetivos")
        self.assertEqual(self.donde.get("bike_setup"), "objetivos")

    def test_el_volumen_semanal_lo_mide_garmin(self):
        """Era un campo que el atleta rellenaba a ojo, y su número competía
        dentro del prompt con sus actividades reales: uno declaraba 5 h/semana
        cuando sus doce semanas de Garmin daban 6,6."""
        self.assertIn("current_weekly_volume_hours", self.garmin)

    def test_los_fisicos_los_mide_garmin_y_no_se_escriben(self):
        for campo in ("gender", "birth_date", "weight_kg", "height_cm",
                      "resting_heart_rate", "vo2max_running"):
            with self.subTest(campo=campo):
                self.assertIn(campo, self.garmin)


class LaFichaClinicaNoSePregunta(unittest.TestCase):
    """Cuatro de los SEIS canales por los que se preguntaba por lesiones."""

    RETIRADOS = [
        "par_q_flags", "injury_prone", "doctor_performance_report",
        "doctor_chat_history", "last_medical_evaluation", "medical_snapshot_hash",
        "health_disclaimer_accepted",
    ]

    def test_ninguno_cuenta_para_la_barra(self):
        cuentan = campos_que_cuentan()
        for campo in self.RETIRADOS:
            with self.subTest(campo=campo):
                self.assertNotIn(campo, cuentan)

    def test_ninguno_se_guarda_ya(self):
        php = (RAIZ / "Backend/src/Support/ProfileFields.php").read_text(encoding="utf-8")
        # Sólo pueden aparecer dentro del comentario que explica por qué se fueron.
        codigo = re.sub(r"/\*\*.*?\*/", "", php, flags=re.S)
        for campo in self.RETIRADOS:
            with self.subTest(campo=campo):
                self.assertNotIn(f"'{campo}'", codigo)

    def test_el_frontend_ya_no_los_manda(self):
        js = (RAIZ / "Frontend/src/utils/profile.js").read_text(encoding="utf-8")
        for campo in self.RETIRADOS:
            with self.subTest(campo=campo):
                self.assertNotIn(campo, js)

    def test_la_migracion_017_las_borra(self):
        sql = (RAIZ / "Database/017_retirar_la_ficha_clinica.sql").read_text(encoding="utf-8")
        for campo in self.RETIRADOS:
            with self.subTest(campo=campo):
                self.assertIn(f"'{campo}'", sql)


if __name__ == "__main__":
    unittest.main()
