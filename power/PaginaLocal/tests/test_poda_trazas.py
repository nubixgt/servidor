"""Que el archivo de razonamiento no crezca para siempre, y que se vaya con
el plan que explica.

## De donde sale

Dos peticiones del atleta, y las dos apuntan al mismo sitio:

    "quiero que el Coach solo tenga el contexto de lo que esta vigente"
    "una vez el contexto no es relevante porque esta desactualizado o fue
     borrado, hay que borrar la traza. Asi no ocupa espacio por nada."

Lo primero ya se resolvio al LEER (`_ultima_traza_todavia_vigente` en
coach_chat.py, tests/test_traza_vigente.py). Esto es lo segundo: dejar de
guardarlas cuando ya no describen nada.

`save_reasoning` no habia borrado nunca -- "Nothing is pruned", decia su
docstring -- y una generacion real ocupa ~500 KB, tres cuartas partes de ellos
prompts capturados.

## El limite que hace esto delicado

Dentro de cada traza viven `fitness_series.json`, `load_state.json` y
`recovery.json`, de donde `services/ai/charts.py` saca el historial de forma,
carga y recuperacion del panel. **Eso es dato del ATLETA, no del plan.** Por
eso no se puede borrar el archivo entero al borrar cualquier cosa: se borra
por ALCANCE (la traza de la semana con la semana) y se poda por ANTIGUEDAD.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from datetime import datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.reasoning_archive import (  # noqa: E402
    TRAZAS_QUE_SE_CONSERVAN,
    borrar_trazas,
    list_reasoning_entries,
    podar_trazas,
    save_reasoning,
)


def _traza(storage: Path, kind: str, dia: int, hora: int = 8) -> str:
    """Una traza como la que deja una generacion real, con su tipo y su fecha.

    Lleva `fitness_series` porque es lo que hace la poda delicada: es el
    historial de forma del atleta, y viaja dentro de la misma carpeta que el
    razonamiento del plan.
    """
    return save_reasoning(
        storage,
        {"synthesis_result": f"analisis del dia {dia}"},
        kind=kind,
        extra={"fitness_series": [{"date": f"2026-09-{dia:02d}", "vo2max": 50}]},
        timestamp=datetime(2026, 9, dia, hora, 0, 0),
    )


class ElArchivoTieneTecho(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)

    def test_EL_FALLO_sin_podar_el_archivo_crece_sin_limite(self):
        for dia in range(1, 21):
            _traza(self.storage, "week", dia)
        self.assertEqual(len(list_reasoning_entries(self.storage)), 20)

        quitadas = podar_trazas(self.storage)
        self.assertEqual(quitadas, 20 - TRAZAS_QUE_SE_CONSERVAN)
        self.assertEqual(len(list_reasoning_entries(self.storage)), TRAZAS_QUE_SE_CONSERVAN)

    def test_se_conservan_las_MAS_RECIENTES_y_no_unas_cualesquiera(self):
        """La poda es por antiguedad. Por peso retiraria justo la generacion
        en que el modelo mas escribio, que es la mas interesante."""
        for dia in range(1, 16):
            _traza(self.storage, "week", dia)
        podar_trazas(self.storage, conservar=3)
        quedan = sorted(e["id"] for e in list_reasoning_entries(self.storage))
        self.assertEqual(quedan, ["2026-09-13T08-00-00", "2026-09-14T08-00-00", "2026-09-15T08-00-00"])

    def test_podar_con_menos_trazas_que_el_techo_no_borra_nada(self):
        _traza(self.storage, "season", 1)
        _traza(self.storage, "week", 2)
        self.assertEqual(podar_trazas(self.storage), 0)
        self.assertEqual(len(list_reasoning_entries(self.storage)), 2)

    def test_podar_un_perfil_sin_trazas_no_revienta(self):
        with tempfile.TemporaryDirectory() as vacio:
            self.assertEqual(podar_trazas(Path(vacio)), 0)


class LaTrazaSeVaConElPlanQueExplica(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)
        _traza(self.storage, "season", 1)
        _traza(self.storage, "week", 2)
        _traza(self.storage, "week", 3)

    def _tipos(self):
        return sorted(e.get("kind") for e in list_reasoning_entries(self.storage))

    def test_borrar_la_semana_no_toca_el_razonamiento_del_macrociclo(self):
        """Son de escalas distintas, igual que los planes. El macrociclo sigue
        en pie y su razonamiento sigue describiendo algo que existe."""
        self.assertEqual(borrar_trazas(self.storage, kind="week"), 2)
        self.assertEqual(self._tipos(), ["season"])

    def test_borrar_el_macrociclo_no_toca_el_de_la_semana_en_curso(self):
        self.assertEqual(borrar_trazas(self.storage, kind="season"), 1)
        self.assertEqual(self._tipos(), ["week", "week"])

    def test_sin_tipo_se_van_todas(self):
        self.assertEqual(borrar_trazas(self.storage), 3)
        self.assertEqual(list_reasoning_entries(self.storage), [])

    def test_borrar_un_tipo_que_no_hay_no_borra_nada(self):
        self.assertEqual(borrar_trazas(self.storage, kind="no-existe"), 0)
        self.assertEqual(len(list_reasoning_entries(self.storage)), 3)


class ElHistorialDeFormaSobreviveALaPoda(unittest.TestCase):
    """La razon por la que esto se hace por alcance y no de golpe.

    `charts.py` reconstruye la curva de forma uniendo el `fitness_series.json`
    de TODAS las trazas que quedan. Si la poda o el borrado se llevaran mas de
    la cuenta, el atleta perderia meses de su propio historial para deshacerse
    del razonamiento de un plan.
    """

    def test_las_series_de_las_trazas_que_sobreviven_siguen_ahi(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            for dia in range(1, 16):
                _traza(storage, "week", dia)
            podar_trazas(storage, conservar=5)
            series = [
                (Path(e["path"]) / "fitness_series.json")
                for e in list_reasoning_entries(storage)
            ]
            self.assertEqual(len(series), 5)
            for ruta in series:
                self.assertTrue(ruta.is_file(), f"La poda se llevo {ruta}")

    def test_borrar_la_semana_deja_la_serie_de_la_temporada(self):
        with tempfile.TemporaryDirectory() as tmp:
            storage = Path(tmp)
            _traza(storage, "season", 1)
            _traza(storage, "week", 2)
            borrar_trazas(storage, kind="week")
            quedan = list_reasoning_entries(storage)
            self.assertEqual(len(quedan), 1)
            self.assertTrue((Path(quedan[0]["path"]) / "fitness_series.json").is_file())


if __name__ == "__main__":
    unittest.main()
