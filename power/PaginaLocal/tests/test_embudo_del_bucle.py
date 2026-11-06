"""En cuál de los cinco pasos del bucle se cae el atleta.

## Por qué existe

La beta dejó **37 generaciones pagadas y 0 semanas cerradas**, y el panel no
sabía decir por qué: medía el primer paso (generó) y el último (volvió a
entrar), y nada de los tres del medio. Sin eso, la única forma de decidir qué
construir era adivinar.

## La señal que lo hace legible: CONTIGÜIDAD

Dos entradas del expediente cuyas ventanas se TOCAN —una empieza el día
siguiente al que acabó la otra— son una semana **continuada**: el atleta
avanzó. Dos que se SOLAPAN son una semana **rehecha**: borró y volvió a empezar
los mismos días.

`week_number` no sirve para ver esto, porque se reinicia al borrar el plan. De
ahí que las cinco semanas de la cuenta del fundador estuvieran numeradas «1» las
cinco, y que nadie se diera cuenta de que ninguna había avanzado.

## La medición real que lo motivó

Cuenta `u2`, 10 de septiembre de 2026, corriendo el instrumento por primera vez:

    generadas    5
    rehechas     4   <- cuatro de cinco fueron rehacer los mismos días
    subidas      0   <- nunca llegó al reloj
    entrenadas   0
    continuadas  0   <- nunca encadenó
    racha_máxima 1

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.track_record import embudo_del_bucle  # noqa: E402

HOY = date(2026, 9, 20)


def _cuenta(raiz: Path, nombre: str, semanas, subidas=()):
    carpeta = raiz / nombre
    carpeta.mkdir(parents=True, exist_ok=True)
    with (carpeta / "track_record.jsonl").open("w", encoding="utf-8") as f:
        for s in semanas:
            f.write(json.dumps(s, ensure_ascii=False) + "\n")
    if subidas:
        (carpeta / "garmin_uploads.json").write_text(
            json.dumps({"version": 1, "entries": [{"date": d, "workout_id": i}
                                                  for i, d in enumerate(subidas, 1)]}),
            encoding="utf-8")
    return carpeta


def semana(desde, hasta, ejecutadas=None):
    s = {"from": desde, "to": hasta, "week_number": 1, "prescribed_hours": 6.0}
    if ejecutadas is not None:
        s["executed_hours"] = ejecutadas
    return s


class ElEmbudoDelBucle(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)

    def test_el_caso_real_de_la_cuenta_u2(self):
        """Cinco generaciones, cuatro solapadas, ninguna encadenada."""
        _cuenta(self.raiz, "u2", [
            semana("2026-08-31", "2026-09-06"),
            semana("2026-09-01", "2026-09-07"),
            semana("2026-09-02", "2026-09-08"),
            semana("2026-09-04", "2026-09-10"),
            semana("2026-09-10", "2026-09-16"),
        ])
        d = embudo_del_bucle(self.raiz, hoy=HOY)
        self.assertEqual(d["generadas"], 5)
        self.assertEqual(d["rehechas"], 4)
        self.assertEqual(d["continuadas"], 0)
        self.assertEqual(d["racha_maxima"], 1)
        self.assertEqual(d["cuentas_con_racha"], 0)

    def test_dos_semanas_que_se_tocan_son_una_racha(self):
        """El bucle sano: la siguiente empieza donde acabó la anterior."""
        _cuenta(self.raiz, "u9", [
            semana("2026-09-01", "2026-09-07", 5.0),
            semana("2026-09-08", "2026-09-14", 4.0),
        ])
        d = embudo_del_bucle(self.raiz, hoy=HOY)
        self.assertEqual(d["continuadas"], 1)
        self.assertEqual(d["racha_maxima"], 2)
        self.assertEqual(d["rehechas"], 0)
        self.assertEqual(d["cuentas_con_racha"], 1)

    def test_un_hueco_de_un_dia_rompe_la_racha(self):
        """No es contigua: el atleta se saltó un día y volvió a empezar."""
        _cuenta(self.raiz, "u9", [
            semana("2026-09-01", "2026-09-07", 5.0),
            semana("2026-09-09", "2026-09-15", 4.0),
        ])
        d = embudo_del_bucle(self.raiz, hoy=HOY)
        self.assertEqual(d["continuadas"], 0)
        self.assertEqual(d["racha_maxima"], 1)

    def test_la_racha_cuenta_semanas_no_saltos(self):
        """Tres semanas encadenadas son una racha de 3, no de 2."""
        _cuenta(self.raiz, "u9", [
            semana("2026-09-01", "2026-09-07", 5.0),
            semana("2026-09-08", "2026-09-14", 5.0),
            semana("2026-09-15", "2026-09-21", 5.0),
        ])
        self.assertEqual(embudo_del_bucle(self.raiz, hoy=HOY)["racha_maxima"], 3)

    def test_una_subida_dentro_de_la_ventana_cuenta(self):
        _cuenta(self.raiz, "u9", [semana("2026-09-01", "2026-09-07", 3.0)],
                subidas=["2026-09-03"])
        self.assertEqual(embudo_del_bucle(self.raiz, hoy=HOY)["subidas"], 1)

    def test_una_subida_fuera_de_la_ventana_no_cuenta(self):
        """Un entreno de otra semana en la biblioteca no dice nada de ésta."""
        _cuenta(self.raiz, "u9", [semana("2026-09-01", "2026-09-07", 3.0)],
                subidas=["2026-08-20"])
        self.assertEqual(embudo_del_bucle(self.raiz, hoy=HOY)["subidas"], 0)

    def test_entrenada_es_haber_hecho_algo_no_haberla_cerrado(self):
        """Cerrada con cero horas es una semana que no se entrenó. Contarla
        como entrenada haría que el número más importante del panel subiera
        justo cuando el atleta no apareció."""
        _cuenta(self.raiz, "u9", [
            semana("2026-09-01", "2026-09-07", 0.0),
            semana("2026-09-08", "2026-09-14", 4.5),
        ])
        self.assertEqual(embudo_del_bucle(self.raiz, hoy=HOY)["entrenadas"], 1)

    def test_la_semana_viva_no_se_juzga(self):
        """La que está en curso todavía no ha ocurrido: cuenta aparte y no
        hunde el porcentaje por el mero hecho de estar viva."""
        d = embudo_del_bucle(
            _cuenta(self.raiz, "u9", [semana("2026-09-18", "2026-09-24")]).parent,
            hoy=date(2026, 9, 20))
        self.assertEqual(d["en_curso"], 1)
        self.assertEqual(d["entrenadas"], 0)

    def test_una_ventana_ya_pasada_y_sin_cerrar_no_es_en_curso(self):
        """Se generó, pasó, y nunca se cerró. Eso no es «viva», es perdida."""
        _cuenta(self.raiz, "u9", [semana("2026-08-01", "2026-08-07")])
        self.assertEqual(embudo_del_bucle(self.raiz, hoy=HOY)["en_curso"], 0)

    def test_una_fecha_ilegible_se_salta_sin_reventar(self):
        """El panel no puede caerse por una línea mal escrita del expediente."""
        _cuenta(self.raiz, "u9", [
            {"from": "no-es-fecha", "to": "tampoco", "week_number": 1},
            semana("2026-09-01", "2026-09-07", 3.0),
        ])
        self.assertEqual(embudo_del_bucle(self.raiz, hoy=HOY)["generadas"], 1)

    def test_sin_expediente_devuelve_ceros_y_no_falla(self):
        d = embudo_del_bucle(self.raiz / "no-existe", hoy=HOY)
        self.assertEqual(d["generadas"], 0)
        self.assertEqual(d["accounts"], [])

    def test_el_umbral_del_plan_lean_cuenta_atletas_no_semanas(self):
        """«3 de 5 cierran al menos 2 seguidas» se mide por CUENTA: un atleta
        con una racha de 6 no compensa a cuatro que nunca encadenaron."""
        _cuenta(self.raiz, "a", [semana("2026-09-01", "2026-09-07", 5.0),
                                 semana("2026-09-08", "2026-09-14", 5.0)])
        _cuenta(self.raiz, "b", [semana("2026-09-01", "2026-09-07", 5.0)])
        d = embudo_del_bucle(self.raiz, hoy=HOY)
        self.assertEqual(d["cuentas"], 2)
        self.assertEqual(d["cuentas_con_racha"], 1)


if __name__ == "__main__":
    unittest.main()
