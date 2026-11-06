"""MySQL manda. El directorio del servicio es un ESPEJO, no un almacen.

## El fallo, reportado por el atleta el 2026-08-31

    "Cuando una semana ya fue eliminada, esta en archivado, eso esta bien,
     pero cuando genero el nuevo plan me dice 'Esta semana no toca', tengo que
     ir a archivados y eliminar los documentos archivados para que me permita
     hacerla. Lo hice y aun no me dejo."

Ir a archivados no podia ayudar, porque el problema no estaba ahi.

`storage_adapter` materializa los datos de MySQL como los ficheros que
PaginaLocal espera encontrar. Cada `write_*` era un no-op cuando el dato venia
vacio -- `if weekly_plan:` y a otra cosa -- con la intencion de "no pisar lo
que hay". Pero lo que hay no es un respaldo: es la corrida ANTERIOR.

Asi que al borrar la semana, MySQL la perdia, PHP mandaba `null`, y el
`weekly_plan.json` de la generacion pasada seguia vivo en el disco del
servicio. `ventana_de_generacion` leia esa semana fantasma, veia fechas
futuras, y contestaba "todavia no toca" a alguien que acababa de borrarlo todo.

Y no era solo la ventana:

  - La adherencia se medida contra un plan que el atleta borro.
  - El coach respondia sobre una semana que ya no existe.
  - `_append_week` habria colgado la nueva semana del fantasma.

El directorio es de usar y tirar (`_runtime/storage/u<id>`), asi que borrar
aqui no puede perder nada que MySQL no tenga.

Correr con:  python -m unittest discover -s AiService/tests -v
"""

import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge import storage_adapter as sa  # noqa: E402

UNA_SEMANA = {
    "athlete_name": "Jose",
    "weeks": [{"week_number": 1, "days": [{"date": "2026-09-07", "sport": "run"}]}],
}


class ElEspejoRefleja(unittest.TestCase):
    def setUp(self):
        self.dir = Path(self.enterContext(tempfile.TemporaryDirectory()))

    def _sembrar(self, nombre: str, contenido) -> Path:
        ruta = self.dir / nombre
        if isinstance(contenido, str):
            ruta.write_text(contenido, encoding="utf-8")
        else:
            ruta.write_text(json.dumps(contenido), encoding="utf-8")
        return ruta

    # --- el caso reportado -------------------------------------------------

    def test_EL_FALLO_una_semana_borrada_no_puede_sobrevivir_en_el_disco(self):
        ruta = self._sembrar("weekly_plan.json", UNA_SEMANA)
        sa.write_weekly_plan(self.dir, None)      # MySQL ya no tiene semana
        self.assertFalse(
            ruta.exists(),
            "La semana de la corrida anterior sigue en el disco: la ventana de "
            "generacion la leera y dira 'todavia no toca'",
        )

    def test_un_plan_sin_semanas_cuenta_como_no_haber_plan(self):
        """PHP puede mandar la envoltura vacia en vez de null."""
        ruta = self._sembrar("weekly_plan.json", UNA_SEMANA)
        sa.write_weekly_plan(self.dir, {"athlete_name": "Jose", "weeks": []})
        self.assertFalse(ruta.exists())

    def test_una_semana_de_verdad_si_se_escribe(self):
        sa.write_weekly_plan(self.dir, UNA_SEMANA)
        escrito = json.loads((self.dir / "weekly_plan.json").read_text(encoding="utf-8"))
        self.assertEqual(escrito["weeks"][0]["days"][0]["date"], "2026-09-07")

    def test_una_semana_reemplaza_a_la_anterior_entera(self):
        self._sembrar("weekly_plan.json", {"weeks": [{"week_number": 9, "days": []}]})
        sa.write_weekly_plan(self.dir, UNA_SEMANA)
        escrito = json.loads((self.dir / "weekly_plan.json").read_text(encoding="utf-8"))
        self.assertEqual(len(escrito["weeks"]), 1)
        self.assertEqual(escrito["weeks"][0]["week_number"], 1)

    # --- la misma regla para todo lo demas ---------------------------------

    def test_un_cuaderno_vaciado_no_deja_notas_viejas(self):
        ruta = self._sembrar("notebook.json", {"entries": [{"id": 1, "text": "vieja"}]})
        sa.write_notebook(self.dir, [])
        self.assertFalse(ruta.exists())

    def test_unos_checkins_borrados_no_dejan_el_ultimo_estado_de_animo(self):
        ruta = self._sembrar("checkins.json", [{"date": "2026-08-01", "hooper_total": 20}])
        sa.write_checkins(self.dir, [])
        self.assertFalse(ruta.exists())

    def test_un_macrociclo_borrado_se_va_con_sus_fases(self):
        md = self._sembrar("season_plan.md", "# Plan viejo")
        fases = self._sembrar("season_phases.json", {"phases": [{"name": "Base"}]})
        sa.write_season_plan(self.dir, None, None)
        self.assertFalse(md.exists())
        self.assertFalse(fases.exists())

    def test_un_resumen_borrado_no_deja_el_costo_de_la_corrida_anterior(self):
        ruta = self._sembrar("summary.json", {"total_cost_usd": 0.3})
        sa.write_summary(self.dir, None)
        self.assertFalse(ruta.exists())

    # --- y no revienta cuando no hay nada que borrar -----------------------

    def test_borrar_lo_que_no_existe_no_es_un_error(self):
        for llamada in (
            lambda: sa.write_weekly_plan(self.dir, None),
            lambda: sa.write_notebook(self.dir, []),
            lambda: sa.write_checkins(self.dir, []),
            lambda: sa.write_season_plan(self.dir, None, None),
            lambda: sa.write_summary(self.dir, None),
        ):
            with self.subTest(llamada=llamada):
                llamada()   # el directorio esta vacio

    def test_el_macrociclo_y_la_semana_son_independientes(self):
        """Borrar la semana no puede llevarse el macrociclo por delante: son
        dos decisiones de escalas distintas y ahora se borran por separado."""
        sa.write_season_plan(self.dir, "# Mi temporada", {"phases": [{"name": "Base"}]})
        sa.write_weekly_plan(self.dir, UNA_SEMANA)

        sa.write_weekly_plan(self.dir, None)

        self.assertFalse((self.dir / "weekly_plan.json").exists())
        self.assertTrue((self.dir / "season_plan.md").exists())
        self.assertTrue((self.dir / "season_phases.json").exists())


if __name__ == "__main__":
    unittest.main()
