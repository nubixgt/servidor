"""Lo que el atleta contesta en el check-in llega al planificador.

Auditoría de cables del 2026-09-15: "¿La semana que cierras salió como
esperabas?" (sí / a medias / no, Database/016) se contestaba en cada check-in y
se guardaba en MySQL, pero `CheckinEntity::toArray()` no lo devolvía: no llegaba
al espejo del AiService, ni al prompt, ni al panel.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.checkin import has_content, normalize_checkin, render_checkin_block  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


class ComoSalioLaSemana(unittest.TestCase):
    def test_se_lee_y_se_dice(self):
        checkin = normalize_checkin({"week_as_expected": "a_medias"}, today=date(2026, 9, 15))
        self.assertTrue(has_content(checkin))
        self.assertIn("¿La semana que cierra salió como esperaba?** a medias", render_checkin_block(checkin, []))

    def test_un_valor_desconocido_se_descarta(self):
        self.assertEqual(normalize_checkin({"week_as_expected": "genial"})["week_as_expected"], "")


class LosNombresCoincidenEnCadaSalto(unittest.TestCase):
    """Del modal al DTO, del DTO a la entidad, de la entidad al cerebro."""

    def _claves(self, ruta, patron):
        return set(re.findall(patron, (RAIZ / ruta).read_text("utf-8")))

    def test_todo_lo_que_se_guarda_sale_de_la_entidad(self):
        dto = (RAIZ / "Backend/src/DTOs/CheckinDTO.php").read_text("utf-8")
        guardadas = set(re.findall(r"'(\w+)' =>", re.search(r"function toColumns.*?\];", dto, re.S).group(0)))
        devueltas = self._claves("Backend/src/Entities/CheckinEntity.php", r"'(\w+)' =>")
        self.assertEqual(guardadas - devueltas, set())

    def test_todo_lo_que_manda_el_modal_lo_lee_el_dto(self):
        modal = (RAIZ / "Frontend/src/components/modals/CheckinModal.vue").read_text("utf-8")
        enviadas = set(re.findall(r"^\s*(\w+)\s*:", re.search(r"submitCheckin\(\{(.*?)\}\)", modal, re.S).group(1), re.M))
        leidas = self._claves("Backend/src/DTOs/CheckinDTO.php", r"\$d\['(\w+)'\]")
        self.assertEqual(enviadas - leidas, set())


if __name__ == "__main__":
    unittest.main()
