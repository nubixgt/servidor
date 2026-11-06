"""«Reordenar cuaderno» con el modelo simulado (Beta 3.0, F9). Ni una llamada real.

El modelo propone y el código no se fía: cada anotación una vez, ninguna olvidada, ningún
número que no estuviera (un «4 km» que nadie dijo acabaría en el plan), «al perfil» sólo a
un campo conocido, y las preferencias siguen siendo preferencias (alimentan su plan).

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import reordenar_cuaderno as rc  # noqa: E402

CUADERNO = [
    {"id": 1, "status": "vigente", "category": "preferencia", "text": "Quiere nadar 2-3 km por sesión.",
     "created_at": "2026-09-20"},
    {"id": 2, "status": "vigente", "category": "preferencia", "text": "Prefiere nadar distancias largas, de 2 a 3 km.",
     "created_at": "2026-09-25"},
    {"id": 3, "status": "vigente", "category": "medicacion", "text": "Toma levotiroxina por las mañanas.",
     "created_at": "2026-09-10"},
    {"id": 4, "status": "vigente", "category": "logistica", "text": "Viaja a Lima del 1 al 5 de octubre.",
     "created_at": "2026-09-28"},
    {"id": 5, "status": "retirada", "category": "otro", "text": "Vieja", "created_at": "2026-08-01"},
]


def _leer(acciones):
    async def leer(sistema, usuario):
        return rc.PropuestaDeReorden.model_validate({"acciones": acciones}), {"input_tokens": 900}
    return leer


def _proponer(acciones):
    return asyncio.run(rc.proponer(CUADERNO, _leer(acciones)))[0]


class LaPropuesta(unittest.TestCase):

    def test_fusionar_dos_preferencias_y_pasar_la_medicacion_al_perfil(self):
        p = _proponer([
            {"ids": ["1", "2"], "accion": "fusionar", "texto": "Quiere nadar sesiones largas de 2-3 km.",
             "categoria": "otro", "motivo": "Dicen lo mismo."},
            {"ids": ["3"], "accion": "al_perfil", "campo_del_perfil": "medications", "motivo": "Es medicación."},
            {"ids": ["4"], "accion": "mantener", "motivo": "Sigue vigente."},
        ])
        fusion, perfil = p["acciones"]
        self.assertEqual((fusion["accion"], fusion["categoria"]), ("fusionar", "preferencia"),
                         "una preferencia no deja de serlo al fusionarse")
        self.assertEqual(perfil["donde"], rc.CAMPOS_DEL_PERFIL["medications"])
        self.assertEqual(perfil["pestana"], "Avanzado")
        self.assertEqual(len(p["acciones"]), 2, "mantener no se enseña: no hay nada que aceptar")

    def test_un_numero_inventado_se_descarta(self):
        p = _proponer([{"ids": ["1", "2"], "accion": "fusionar", "texto": "Quiere nadar 4 km por sesión.",
                        "motivo": "Dicen lo mismo."}])
        self.assertEqual(p["acciones"], [])
        self.assertIn("números que no estaban", p["descartadas"][0])

    def test_cada_anotacion_una_sola_vez(self):
        p = _proponer([{"ids": ["3"], "accion": "retirar", "motivo": "x"},
                       {"ids": ["3"], "accion": "al_perfil", "campo_del_perfil": "medications", "motivo": "y"}])
        self.assertEqual([a["accion"] for a in p["acciones"]], ["retirar"])

    def test_ids_que_no_existen_o_retiradas(self):
        p = _proponer([{"ids": ["99"], "accion": "retirar", "motivo": "x"},
                       {"ids": ["5"], "accion": "retirar", "motivo": "ya retirada"}])
        self.assertEqual(p["acciones"], [])

    def test_un_campo_del_perfil_desconocido(self):
        p = _proponer([{"ids": ["3"], "accion": "al_perfil", "campo_del_perfil": "garmin_password", "motivo": "x"}])
        self.assertEqual(p["acciones"], [])

    def test_con_muy_pocas_no_se_llama_al_modelo(self):
        async def no_llames(*_):
            raise AssertionError("no debía llamarse al modelo")
        p, uso = asyncio.run(rc.proponer(CUADERNO[:1], no_llames))
        self.assertEqual((p["acciones"], uso), ([], {}))


class LasAccionesSonLasMismasEnCadaCapa(unittest.TestCase):

    def test_esquema_php_y_pantalla(self):
        """Tres listas escritas a mano divergen: la del esquema, la que PHP acepta y la que la
        pantalla sabe nombrar. «mantener» no viaja: no hay nada que aceptar."""
        from typing import get_args

        raiz = Path(__file__).resolve().parents[2]
        self.assertEqual(set(get_args(rc.AccionDeReorden.model_fields["accion"].annotation)), set(rc.ACCIONES))
        php = (raiz / "Backend/src/Services/NotebookService.php").read_text(encoding="utf-8")
        import re
        aceptadas = re.search(r"in_array\(\$tipo, \[([^\]]+)\], true\) \|\| !\$ids", php).group(1)
        self.assertEqual({a.strip(" '") for a in aceptadas.split(",")}, set(rc.ACCIONES) - {"mantener"})
        panel = (raiz / "Frontend/src/components/chat/CuadernoPanel.vue").read_text(encoding="utf-8")
        nombres = re.search(r"const ACCION = \{([^}]+)\}", panel).group(1)
        self.assertEqual(set(re.findall(r"(?:^|,)\s*(\w+):", nombres)), set(rc.ACCIONES) - {"mantener"})


class LasRutasDelPerfilExisten(unittest.TestCase):

    def test_cada_campo_lleva_a_un_sitio_que_esta_en_la_pantalla(self):
        """Hasta el 2026-10-11 «pasar al perfil» podía mandar a «Perfil → Objetivos → Contexto para
        tu plan», que la pantalla no tiene, y a «Salud → Medicación», que se llama «Ficha de Salud y
        Antecedentes → Medicamentos». Un atleta buscaría un sitio que no existe."""
        vista = (Path(__file__).resolve().parents[2] / "Frontend/src/views/admin/ProfileView.vue").read_text(encoding="utf-8")
        for campo, donde in rc.CAMPOS_DEL_PERFIL.items():
            partes = donde.split(" → ")
            self.assertEqual(partes[0], "Perfil")
            pestana = f"label: '{partes[1]}'" if partes[1] != "Avanzado" else "activeTab === 'avanzado'"
            self.assertTrue(pestana in vista, f"{campo}: no hay pestaña {partes[1]}")
            seccion = partes[2]
            self.assertTrue(seccion in vista or seccion.split(" ")[0] in ("Fuerza",) and "PerfilDeFuerza" in vista,
                            f"{campo}: no hay sección «{seccion}»")


class ElPrompt(unittest.TestCase):

    def test_lleva_ids_y_los_campos_cerrados(self):
        texto = rc.mensaje(CUADERNO[:2])
        self.assertIn("- id 1 · preferencia · 2026-09-20: Quiere nadar 2-3 km por sesión.", texto)
        self.assertIn("medications", texto)
        self.assertIn("Nunca escribas un número", rc.SISTEMA)


if __name__ == "__main__":
    unittest.main()
