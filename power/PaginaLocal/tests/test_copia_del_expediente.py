"""La copia del expediente en MySQL (Database/034; Beta 3.0, F10 / fase C).

`track_record.jsonl` es una de las dos cosas del espejo que no se reconstruyen (02): de ahí salen la
memoria larga del macrociclo, la racha, las medallas y las gráficas, y vivía sólo en el disco del
AiService. Ahora PHP lo copia línea a línea, con el texto EXACTO, al terminar cada generación y al
borrar un plan. Verificado en local el 2026-10-19 con `comparar_expediente.php --copiar`: atleta 2,
6 semanas y 5 descartadas; atleta 3, 5 semanas; las dos copias idénticas carácter a carácter.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import track_record  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


class LasLineasTalCual(unittest.TestCase):

    def test_copia_el_texto_exacto_y_tambien_las_descartadas(self):
        with tempfile.TemporaryDirectory() as d:
            exp = '{"from": "2026-10-12", "to": "2026-10-18", "nota": "ñandú — 5:18/km"}'
            (Path(d) / track_record.TRACK_RECORD_FILE).write_text(exp + "\n\n{ilegible\n", encoding="utf-8")
            (Path(d) / track_record.DESCARTADAS_FILE).write_text('{"from": "2026-09-28"}\n', encoding="utf-8")
            lineas = track_record.lineas_crudas(Path(d))
        self.assertEqual(lineas["expediente"], [exp, "{ilegible"], "una línea ilegible también es del fichero")
        self.assertEqual(lineas["descartadas"], ['{"from": "2026-09-28"}'])

    def test_sin_ficheros_no_hay_nada(self):
        with tempfile.TemporaryDirectory() as d:
            self.assertEqual(track_record.lineas_crudas(Path(d)), {"expediente": [], "descartadas": []})


class DondeSeCopia(unittest.TestCase):

    def test_al_terminar_una_generacion_y_al_borrar(self):
        php = (RAIZ / "Backend/src/Services/PlanService.php").read_text(encoding="utf-8")
        for funcion in ("function pollStatus", "function resetForUser"):
            cuerpo = php[php.index(funcion):].split("\n    public function ", 1)[0]
            self.assertIn("$this->copiarExpediente($userId);", cuerpo, funcion)
        self.assertIn("'034' => ['athlete_track_record']", (RAIZ / "Backend/src/Support/Migraciones.php").read_text(encoding="utf-8"))
        self.assertIn('@app.get("/bridge/track-record")', (RAIZ / "AiService/main.py").read_text(encoding="utf-8"))


if __name__ == "__main__":
    unittest.main()
