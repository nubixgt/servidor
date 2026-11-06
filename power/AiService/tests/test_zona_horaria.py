"""La hora del atleta, y el fallo que se introdujo al intentar arreglarla.

## Lo que se queria arreglar

El servidor de produccion no esta en Guatemala. A las 8 de la tarde alli ya es
el dia siguiente en UTC, asi que la app le escribia al atleta una semana que
empezaba manana cuando el veia hoy.

Como `date.today()` aparece en mas de sesenta sitios, la solucion fue fijar la
zona horaria del PROCESO del worker: uno por generacion, un atleta, una zona.

## El fallo que eso introdujo

La primera version ponia `os.environ["TZ"]` SIEMPRE y solo llamaba a `tzset()`
si existia, con un comentario que afirmaba que en Windows "no hace nada".

Es falso, y se midio en un proceso nuevo:

    TZ=(sin poner)        -> now() = 2026-08-31 19:44   (correcto)
    TZ=America/Guatemala  -> now() = 2026-09-01 01:44   (+6h, UTC)

El CRT de Windows lee `TZ` al arrancar, no entiende los nombres IANA, y ante
uno que no reconoce se va a UTC. O sea que en Windows introducia exactamente el
fallo que venia a arreglar, invertido.

Se vio en una generacion real: el trabajo salio a las 19:29 y su archivo de
razonamiento quedo fechado a las 01:34 del dia siguiente.

Correr con:  python -m unittest discover -s AiService/tests -v
"""

import subprocess
import sys
import textwrap
import time
import unittest
from datetime import datetime
from pathlib import Path

WORKER = Path(__file__).resolve().parents[1] / "worker.py"

# La funcion tal cual vive en worker.py. Se copia en el proceso hijo porque
# importar worker.py exige un job_dir en argv y arrastra PaginaLocal entero;
# lo que se prueba aqui es esta logica, no el arranque del worker.
_FUENTE = None


def _extraer_funcion() -> str:
    """El cuerpo de `_usar_la_hora_del_atleta`, leido del worker de verdad.

    Leerlo en vez de duplicarlo es lo que hace que este test siga valiendo
    cuando alguien cambie la funcion: si se rompe, se rompe aqui.
    """
    global _FUENTE
    if _FUENTE is None:
        texto = WORKER.read_text(encoding="utf-8")
        ini = texto.index("def _usar_la_hora_del_atleta()")
        fin = texto.index("_usar_la_hora_del_atleta()", ini + 10)
        _FUENTE = texto[ini:fin]
    return _FUENTE


def _hora_en_proceso_nuevo(tz: str) -> datetime:
    """Que hora cree que es un proceso recien arrancado con esta zona.

    Un proceso NUEVO es la unica forma honesta de probar esto: el CRT lee `TZ`
    al arrancar, asi que cambiarla dentro de un proceso ya vivo no reproduce lo
    que le pasa al worker.
    """
    guion = textwrap.dedent(f"""
        SPEC = {{"timezone": {tz!r}}}
        import time
        {{funcion}}
        _usar_la_hora_del_atleta()
        import datetime
        print(datetime.datetime.now().isoformat(timespec="seconds"))
    """).replace("{funcion}", textwrap.indent(_extraer_funcion(), ""))
    # La funcion usa `print`, y su salida estorbaria a la lectura de la fecha:
    # se toma SIEMPRE la ultima linea.
    salida = subprocess.run(
        [sys.executable, "-c", guion], capture_output=True, text=True, timeout=60,
    ).stdout.strip().splitlines()
    return datetime.fromisoformat(salida[-1])


class LaZonaDelAtletaNoPuedeDesviarElReloj(unittest.TestCase):
    """En un sistema sin `tzset`, NINGUNA zona puede mover la hora.

    Es la propiedad que faltaba. Lo contrario -- poner TZ y confiar -- es lo
    que mandaba el plan seis horas por delante del atleta.
    """

    @classmethod
    def setUpClass(cls):
        cls.sin_zona = _hora_en_proceso_nuevo("")

    def _diferencia_en_horas(self, tz: str) -> float:
        return abs((_hora_en_proceso_nuevo(tz) - self.sin_zona).total_seconds()) / 3600.0

    @unittest.skipIf(hasattr(time, "tzset"), "Este sistema SI tiene tzset (POSIX)")
    def test_EL_FALLO_una_zona_IANA_no_puede_llevar_el_reloj_a_UTC(self):
        self.assertLess(
            self._diferencia_en_horas("America/Guatemala"), 0.5,
            "TZ desvio el reloj en un sistema sin tzset: es el fallo del +6h",
        )

    @unittest.skipIf(hasattr(time, "tzset"), "Este sistema SI tiene tzset (POSIX)")
    def test_una_zona_inventada_tampoco(self):
        self.assertLess(self._diferencia_en_horas("Zona/Inventada"), 0.5)

    @unittest.skipUnless(hasattr(time, "tzset"), "Necesita tzset (POSIX)")
    def test_en_posix_la_zona_del_atleta_SI_se_aplica(self):
        """Que es el objetivo de todo esto. UTC-6 contra la del servidor."""
        self.assertGreater(self._diferencia_en_horas("America/Guatemala"), 0.0)

    @unittest.skipUnless(hasattr(time, "tzset"), "Necesita tzset (POSIX)")
    def test_en_posix_una_zona_que_el_sistema_no_conoce_se_rechaza(self):
        """No se acepta en silencio: una zona ilegible tambien acaba en UTC, y
        eso seria el mismo fallo por otra puerta."""
        self.assertLess(self._diferencia_en_horas("Zona/Inventada"), 0.5)

    def test_sin_zona_se_usa_la_del_sistema_y_no_se_toca_nada(self):
        self.assertLess(self._diferencia_en_horas(""), 0.5)


class ElWorkerSigueDiciendoLaVerdad(unittest.TestCase):
    """El comentario que acompanaba al fallo afirmaba lo contrario de lo que
    pasaba. Un comentario equivocado en algo asi cuesta mas que no tenerlo."""

    def test_no_se_pone_TZ_sin_comprobar_antes_que_hay_tzset(self):
        fuente = _extraer_funcion()
        antes_de_poner_tz = fuente[:fuente.index('os.environ["TZ"]')]
        self.assertIn(
            'hasattr(time, "tzset")', antes_de_poner_tz,
            "Se esta escribiendo TZ sin haber comprobado tzset: en Windows eso "
            "manda el reloj a UTC",
        )


if __name__ == "__main__":
    unittest.main()
