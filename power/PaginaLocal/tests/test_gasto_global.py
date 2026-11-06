"""El gasto de toda la instalación, para el panel del administrador.

Las dos vistas que ya existían responden "cuánto ha costado ESTE atleta".
Ninguna respondía "en qué se va el dinero", que es la pregunta con la que se
decide si algo es sostenible.

Importa porque las cuatro herramientas tienen economías distintas y sólo una
es ilimitada: una temporada y una semana se generan cuando el atleta lo pide,
pero el chat corre tantas veces como le apetezca escribir. Medido sobre el
libro real del 2026-09-03:

    Semana de entrenamiento  $5,3154   18 usos   $0,2953 c/u
    Plan de temporada        $3,1761    8 usos   $0,3970 c/u
    Chat con el coach        $1,5083   68 usos   $0,0222 c/u
    Cuestionario de salud    $0,1331   10 usos   $0,0133 c/u

El chat es el 15% del gasto con el 65% de los usos. Sin la columna de la
derecha esa lectura no se ve, y es justo la que decide qué hay que acotar.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

import core.usage_ledger as ul  # noqa: E402


def _linea(**kw) -> dict:
    base = {"ts": "2026-09-01T10:00:00+00:00", "profile": "ana", "profile_label": "Ana",
            "user_id": "u1", "kind": "chat", "cost_usd": 0.02, "tokens": 1000,
            "input_tokens": 900, "output_tokens": 100,
            "cache_read_tokens": 0, "cache_creation_tokens": 0, "nodes": []}
    base.update(kw)
    return base


def _global(lineas, **kw):
    with patch.object(ul, "read_all", return_value=iter(lineas)):
        return ul.usage_global(**kw)


class ElDesgloseAgrupaPorHerramienta(unittest.TestCase):
    def test_EL_HUECO_dice_cuanto_cuesta_cada_herramienta(self):
        g = _global([
            _linea(kind="chat", cost_usd=0.02),
            _linea(kind="chat", cost_usd=0.03),
            _linea(kind="week", cost_usd=0.30),
        ])
        self.assertEqual(g["by_kind"]["chat"]["cost_usd"], 0.05)
        self.assertEqual(g["by_kind"]["chat"]["runs"], 2)
        self.assertEqual(g["by_kind"]["week"]["runs"], 1)
        self.assertAlmostEqual(g["total_cost_usd"], 0.35, places=6)

    def test_trae_el_coste_POR_USO_que_es_el_que_decide(self):
        """Un total alto repartido entre muchos usos baratos no es el mismo
        problema que uno concentrado en pocos caros. Sin este número, el chat
        parece el problema por tener 68 usos cuando es el 15% del gasto."""
        g = _global([_linea(kind="chat", cost_usd=0.02) for _ in range(4)]
                    + [_linea(kind="season", cost_usd=0.40)])
        self.assertEqual(g["by_kind"]["chat"]["avg_cost_usd"], 0.02)
        self.assertEqual(g["by_kind"]["season"]["avg_cost_usd"], 0.40)
        # El chat gasta menos en total y aun asi tiene cinco veces mas usos.
        self.assertLess(g["by_kind"]["chat"]["cost_usd"], g["by_kind"]["season"]["cost_usd"])

    def test_trae_la_etiqueta_en_castellano(self):
        g = _global([_linea(kind="health_intake")])
        self.assertEqual(g["by_kind"]["health_intake"]["label"], "Cuestionario de salud")

    def test_un_tipo_desconocido_se_cuenta_igual_en_vez_de_perderse(self):
        """Un gasto sin clasificar es un problema de informe; un gasto sin
        registrar es dinero que desaparece."""
        g = _global([_linea(kind="herramienta_nueva", cost_usd=0.11)])
        self.assertEqual(g["by_kind"]["herramienta_nueva"]["cost_usd"], 0.11)

    def test_los_tokens_de_cache_viajan_para_poder_vigilarla(self):
        """Una caché que deja de acertar no da ningún error: sólo sube la
        factura, y meses después."""
        g = _global([_linea(kind="chat", cache_read_tokens=23_222, cache_creation_tokens=0),
                     _linea(kind="chat", cache_read_tokens=0, cache_creation_tokens=22_655)])
        self.assertEqual(g["by_kind"]["chat"]["cache_read_tokens"], 23_222)
        self.assertEqual(g["by_kind"]["chat"]["cache_creation_tokens"], 22_655)


class QueSeCuentaYQueNo(unittest.TestCase):
    def test_el_banco_de_pruebas_queda_FUERA_por_defecto(self):
        """Es gasto de desarrollo, no de un atleta. Meterlo inflaría el coste
        por herramienta con corridas que nadie pidió -- la misma regla que ya
        siguen las otras dos vistas."""
        g = _global([_linea(kind="chat", cost_usd=0.02), _linea(kind="replay", cost_usd=5.00)])
        self.assertNotIn("replay", g["by_kind"])
        self.assertAlmostEqual(g["total_cost_usd"], 0.02, places=6)

    def test_pero_el_operador_puede_pedirlo(self):
        g = _global([_linea(kind="chat", cost_usd=0.02), _linea(kind="replay", cost_usd=5.00)],
                    include_operator_only=True)
        self.assertIn("replay", g["by_kind"])

    def test_borrar_un_perfil_es_un_evento_y_no_un_gasto(self):
        g = _global([_linea(kind="chat", cost_usd=0.02),
                     _linea(kind="profile_deleted", cost_usd=0.0)])
        self.assertNotIn("profile_deleted", g["by_kind"])
        self.assertEqual(g["total_runs"], 1)

    def test_un_libro_vacio_no_revienta(self):
        g = _global([])
        self.assertEqual(g["total_cost_usd"], 0.0)
        self.assertEqual(g["by_kind"], {})
        self.assertEqual(g["by_user"], [])


class ElGastoPorCuenta(unittest.TestCase):
    def test_agrupa_por_usuario_y_ordena_por_lo_que_mas_gasta(self):
        g = _global([_linea(user_id="u1", cost_usd=0.02),
                     _linea(user_id="u2", cost_usd=3.00),
                     _linea(user_id="u2", cost_usd=0.77)])
        self.assertEqual([f["user_id"] for f in g["by_user"]], ["u2", "u1"])
        self.assertAlmostEqual(g["by_user"][0]["cost_usd"], 3.77, places=6)

    def test_EL_GASTO_VIEJO_no_se_le_atribuye_a_nadie(self):
        """Las líneas anteriores a que existieran las cuentas no llevan
        `user_id`. Se agrupan aparte en vez de repartirse: atribuir un gasto a
        quien no fue es peor que dejarlo sin atribuir, y encima el número sale
        plausible, que es como pasa desapercibido."""
        g = _global([_linea(user_id=None, cost_usd=6.34), _linea(user_id="u1", cost_usd=0.02)])
        sin_cuenta = next(f for f in g["by_user"] if f["user_id"] == "sin_cuenta")
        self.assertAlmostEqual(sin_cuenta["cost_usd"], 6.34, places=6)
        self.assertNotIn("u1", [f["user_id"] for f in g["by_user"] if f["user_id"] == "sin_cuenta"])

    def test_guarda_el_nombre_mas_reciente_solo_para_enseñarlo(self):
        g = _global([_linea(user_id="u1", profile_label="Jose"),
                     _linea(user_id="u1", profile_label="Jose Ines")])
        self.assertEqual(g["by_user"][0]["label"], "Jose Ines")


if __name__ == "__main__":
    unittest.main()
