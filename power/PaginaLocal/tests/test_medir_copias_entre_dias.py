"""La medición de «codificar por día» ve una copia cuando la hay (Beta 3.0, F10 / fase C).

`scripts/medir_copias_entre_dias.py` decide si hace falta codificar día a día. Sobre lo archivado en local
el 2026-10-19 da 46 pares del mismo deporte y 0 copias; un 0 sólo vale si la medición sabe ver una.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))

import medir_copias_entre_dias as m  # noqa: E402

SERIES = [{"step_type": "interval", "duration_type": "time", "duration_value": 180, "repeat_count": 5,
           "description": "5x3' a 5:20"}]
RODAJE = [{"step_type": "interval", "duration_type": "time", "duration_value": 2700, "description": "45' Z2"}]


def _atleta(raiz: Path, pasos_del_jueves):
    a = raiz / "u9"
    (a / "reasoning/2026-10-12T20-00-00").mkdir(parents=True)
    decision = [{"days": [
        {"date": "2026-10-14", "session_slot": "unica", "sport": "run", "purpose": "series",
         "session_sketch": "5x3' a ritmo de 10K"},
        {"date": "2026-10-15", "session_slot": "unica", "sport": "run", "purpose": "rodaje",
         "session_sketch": "45' suaves en Z2"},
    ]}]
    (a / "reasoning/2026-10-12T20-00-00/week_intents.json").write_text(json.dumps(decision), encoding="utf-8")
    plan = {"weeks": [{"days": [
        {"date": "2026-10-14", "session_slot": "unica", "sport": "run", "purpose": "series", "steps": SERIES},
        {"date": "2026-10-15", "session_slot": "unica", "sport": "run", "purpose": "rodaje",
         "steps": pasos_del_jueves},
    ]}]}
    (a / "weekly_plan.json").write_text(json.dumps(plan), encoding="utf-8")


class VeLaCopia(unittest.TestCase):

    def test_dos_decisiones_distintas_con_los_mismos_pasos(self):
        with tempfile.TemporaryDirectory() as d:
            _atleta(Path(d), [dict(SERIES[0], description="otro texto")])
            r = m.medir(Path(d))
        self.assertEqual((r["pares"], r["copias"]), (1, 1))

    def test_pasos_distintos_no_son_copia(self):
        with tempfile.TemporaryDirectory() as d:
            _atleta(Path(d), RODAJE)
            self.assertEqual(m.medir(Path(d))["copias"], 0)


if __name__ == "__main__":
    unittest.main()
