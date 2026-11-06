"""Dos datos que la app tenía y no le daba a quien los necesitaba.

## 1. El experto de fisiología juzgaba sin la base del atleta

La app YA calcula el rango base propio de HRV -- Garmin lo da contra las
semanas del propio atleta -- y YA lo escribe en un bloque redactado, con su
propia advertencia dentro (`services/ai/recovery_state.py`). Ese bloque llegaba
al planificador SEMANAL y no al experto de FISIOLOGÍA, que es justo el nodo
cuyo trabajo entero es juzgar la recuperación.

Comprobado sobre los prompts archivados de una generación real, no leyendo el
código. Del 2026-09-03, atleta u2:

    recovery.json  -> hrv_last_night: 59 · baseline 56-83 ms
    prompt del experto de fisiología -> 5.890 caracteres,
                                        apariciones de "baseline": 0
    su respuesta   -> "HRV 59, RHR 50 y sueño 8.01h/score 86
                       — dentro de rango normal"

"Normal" ¿contra qué? Nadie se lo dijo, así que lo sacó de una norma
poblacional recordada. El rango de ESE atleta era 56-83; el de otro puede ser
30-45, y ahí se decide si su semana lleva calidad. La regla que lo prohíbe ya
estaba escrita, pero en el prompt del planificador semanal -- UN NODO MÁS
ABAJO, donde ya no puede deshacer una lectura hecha, porque lo que le llega es
el veredicto y no los datos crudos.

## 2. Edad y sexo no llegaban a ningún prompt

La ficha los pide, PHP los valida, MySQL los guarda. Cero apariciones de
`gender`, `female`, `male` o de una edad en el prompt del experto de fisiología
y en el del planificador semanal de esa misma generación (las coincidencias de
"age" eran `coverage`, `message` y `language`; la de "male", `management`).

El bloque nuevo se queda en edad y sexo A PROPÓSITO. No razona sobre el ciclo
menstrual: la app sólo lo recoge si la atleta lo activa expresamente, y
entonces viaja por otro camino. Dar el sexo sin la fase invita al modelo a
inventarse la fase, así que el bloque lo prohíbe en vez de confiar.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import importlib
import inspect
import sys
import unittest
from datetime import date
from pathlib import Path
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.athlete_basics import (  # noqa: E402
    calcular_edad,
    normalizar_sexo,
    render_basics_block,
)
from services.ai.langgraph.nodes.prompt_components import get_conflict_policy  # noqa: E402

# `from ...nodes import physiology_expert_node` devuelve la FUNCIÓN, no el
# módulo: el `__init__.py` del paquete la reexporta con el mismo nombre. Para
# parchear dentro del módulo hace falta el módulo.
fisio = importlib.import_module("services.ai.langgraph.nodes.physiology_expert_node")
temporada = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")

HOY = date(2026, 9, 3)


class LaEdadSaleDeUnaFechaQuePuedeVenirDeCualquierSitio(unittest.TestCase):
    """`birth_date` llega de un `<input type="date">`, pero también puede venir
    de Garmin o de un perfil viejo. Una fecha rara no puede tumbar una
    generación que el atleta ya pagó."""

    def test_la_forma_normal_YYYY_MM_DD(self):
        self.assertEqual(calcular_edad("1992-03-15", hoy=HOY), 34)

    def test_aun_no_ha_cumplido_este_ano(self):
        """El cumpleaños es en diciembre y hoy es septiembre: tiene 33, no 34."""
        self.assertEqual(calcular_edad("1992-12-15", hoy=HOY), 33)

    def test_el_dia_exacto_del_cumpleanos_ya_cuenta(self):
        self.assertEqual(calcular_edad("1992-09-03", hoy=HOY), 34)

    def test_acepta_un_iso_con_hora_y_una_fecha_con_barras(self):
        for valor in ("1992-03-15T00:00:00", "1992-03-15 00:00:00", "1992/03/15"):
            with self.subTest(valor=valor):
                self.assertEqual(calcular_edad(valor, hoy=HOY), 34)

    def test_acepta_un_objeto_date_o_datetime(self):
        from datetime import datetime
        self.assertEqual(calcular_edad(date(1992, 3, 15), hoy=HOY), 34)
        self.assertEqual(calcular_edad(datetime(1992, 3, 15, 10, 30), hoy=HOY), 34)

    def test_una_fecha_ilegible_no_rompe_nada_y_no_inventa_una_edad(self):
        for basura in (None, "", "   ", "no sé", "0000-00-00", "1992-13-45", 12345):
            with self.subTest(valor=basura):
                self.assertIsNone(calcular_edad(basura, hoy=HOY))

    def test_una_edad_imposible_se_descarta_en_silencio(self):
        """Mejor no decir nada que decirle al planificador que su atleta tiene
        120 años: es un error de tecleo o una fecha de relleno, no un atleta."""
        self.assertIsNone(calcular_edad("1850-01-01", hoy=HOY))
        self.assertIsNone(calcular_edad("2025-01-01", hoy=HOY))
        self.assertIsNone(calcular_edad("2030-01-01", hoy=HOY))


class ElSexoSoloEsLoQueElBackendAcepta(unittest.TestCase):
    def test_los_tres_valores_validos(self):
        self.assertEqual(normalizar_sexo("female"), "mujer")
        self.assertEqual(normalizar_sexo("male"), "hombre")
        self.assertEqual(normalizar_sexo("other"), "otro / no especificado")

    def test_da_igual_como_venga_escrito(self):
        self.assertEqual(normalizar_sexo("  FEMALE "), "mujer")

    def test_un_valor_desconocido_es_un_dato_que_NO_tenemos(self):
        """No se traduce a medias ni se adivina: si no lo reconocemos, no está."""
        for raro in (None, "", "f", "mujer", "unknown", 1):
            with self.subTest(valor=raro):
                self.assertIsNone(normalizar_sexo(raro))


class ElBloqueDeEdadYSexo(unittest.TestCase):
    def test_EL_FALLO_ahora_existe_un_bloque_con_edad_y_sexo(self):
        b = render_basics_block({"birth_date": "1992-03-15", "gender": "male"}, hoy=HOY)
        self.assertIn("34 años", b)
        self.assertIn("hombre", b)

    def test_sin_ninguno_de_los_dos_no_hay_seccion(self):
        """Vacío y no un bloque con "no disponible": una sección que anuncia lo
        que falta invita al modelo a razonar sobre el hueco. Misma regla que
        `render_recovery_block`."""
        for perfil in (None, {}, {"name": "Ana"}, {"birth_date": "", "gender": ""}):
            with self.subTest(perfil=perfil):
                self.assertEqual(render_basics_block(perfil, hoy=HOY), "")

    def test_con_uno_solo_de_los_dos_sale_ese_y_ya(self):
        solo_edad = render_basics_block({"birth_date": "1992-03-15"}, hoy=HOY)
        self.assertIn("34 años", solo_edad)
        self.assertNotIn("Sexo", solo_edad)

        solo_sexo = render_basics_block({"gender": "female"}, hoy=HOY)
        self.assertIn("mujer", solo_sexo)
        self.assertNotIn("Edad", solo_sexo)

    def test_NO_autoriza_a_prescribir_menos_por_la_edad(self):
        """El sesgo que este bloque podría crear si se soltara el dato a secas.
        Un máster con veinte años de rodaje aguanta más que un veinteañero que
        empezó en marzo, y eso lo dicen sus datos, no su fecha de nacimiento."""
        b = render_basics_block({"birth_date": "1966-03-15", "gender": "male"}, hoy=HOY)
        self.assertIn("No son permiso para prescribirle menos", b)
        self.assertIn("sus datos reales", b)

    def test_a_una_atleta_se_le_PROHIBE_deducir_nada_del_ciclo(self):
        """Dar el sexo sin la fase es justo la clase de hueco que un modelo
        completa por su cuenta. La app sólo recoge el ciclo si la atleta lo
        activa, y entonces llega por el historial de salud con sus palabras."""
        b = render_basics_block({"gender": "female", "birth_date": "1992-03-15"}, hoy=HOY)
        self.assertIn("ciclo menstrual", b)
        self.assertIn("No supongas una fase", b)
        self.assertIn("no la", b)  # "...no la infieras de un HRV bajo"

    def test_a_un_atleta_no_se_le_habla_del_ciclo_para_nada(self):
        """Meterle el párrafo a un hombre sería gastar contexto en algo que no
        puede pasar, y ruido en un prompt es coste y distracción."""
        b = render_basics_block({"gender": "male", "birth_date": "1992-03-15"}, hoy=HOY)
        self.assertNotIn("ciclo", b)


class ElExpertoDeFisiologiaYaSabeContraQueComparar(unittest.TestCase):
    """La parte que arregla el fallo de verdad: los bloques llegan al nodo."""

    RECOVERY = (
        "\n### Estado de recuperación actual (datos reales de Garmin, de hoy)\n"
        "- HRV anoche: **59 ms** — el rango normal PROPIO de este atleta es 56-83 ms.\n"
    )
    BASICS = "\n### Quién es este atleta (declarado en su ficha)\n- Edad: **34 años**.\n"

    def _mensajes(self, constraints):
        """Corre el nodo de verdad con el modelo y las herramientas fuera, y
        devuelve los mensajes que se le habrían mandado."""
        capturado = {}

        async def falso_handle(llm_with_tools, messages, tools, max_iterations):
            capturado["messages"] = messages
            return object()

        estado = {
            "execution_id": "test",
            "plotting_enabled": False,
            "hitl_enabled": False,
            "physiology_summary": "resumen",
            "competitions": [],
            "current_date": {"today": "2026-09-03"},
            "analysis_context": "",
            "availability_constraints": constraints,
            "physiology_expert_messages": [],
        }

        with patch.object(fisio, "handle_tool_calling_in_node", falso_handle), \
             patch.object(fisio.ModelSelector, "get_llm"), \
             patch.object(fisio, "configure_node_tools", return_value=[]), \
             patch.object(fisio, "create_plot_entries", return_value=([], {}, [])):
            salida = asyncio.run(fisio.physiology_expert_node(estado))

        # `execute_node_with_error_handling` se traga las excepciones y las
        # devuelve como `errors`, así que un fallo aquí sería silencioso.
        self.assertNotIn("errors", salida, salida.get("errors"))
        return capturado["messages"]

    def test_EL_FALLO_el_rango_base_del_atleta_llega_al_experto(self):
        usuario = self._mensajes({"recovery": {"block": self.RECOVERY}})[1]["content"]
        self.assertIn("56-83 ms", usuario)
        self.assertIn("rango normal PROPIO", usuario)

    def test_EL_FALLO_la_edad_y_el_sexo_llegan_al_experto(self):
        usuario = self._mensajes({"athlete_basics": self.BASICS})[1]["content"]
        self.assertIn("34 años", usuario)

    def test_sin_esos_datos_el_prompt_sale_igual_de_valido(self):
        """Un atleta con un reloj que no mide HRV y sin fecha de nacimiento es
        un caso normal, no un error."""
        for constraints in ({}, None, {"recovery": {}}, {"recovery": None, "athlete_basics": None}):
            with self.subTest(constraints=constraints):
                usuario = self._mensajes(constraints)[1]["content"]
                self.assertIn("resumen", usuario)
                self.assertNotIn("None", usuario)

    def test_le_prohiben_decir_normal_sin_decir_normal_contra_que(self):
        """La regla que faltaba, ahora en el nodo que hace la lectura y no uno
        más abajo."""
        sistema = self._mensajes({})[0]["content"]
        self.assertIn("base PROPIA de este atleta", sistema)
        self.assertIn("sin decir normal contra qué", sistema)

    def test_si_NO_recibe_la_base_tiene_que_decirlo_en_vez_de_rellenar(self):
        sistema = self._mensajes({})[0]["content"]
        self.assertIn("Si **NO** recibes su rango base, dilo.".replace("**", ""), sistema)
        self.assertIn("Uncertainty", sistema)


class ElPlanificadorDeTemporadaYaSabeQueHacerConUnConflicto(unittest.TestCase):
    """Los tres expertos se contradicen a propósito. La política de qué hacer
    con eso llegaba a síntesis y al planificador SEMANAL, y no aquí -- aunque
    este nodo consume los mismos tres payloads `for_season_planner` y decide 16
    semanas, que es la decisión de más alcance del sistema."""

    def test_EL_FALLO_el_prompt_de_temporada_trae_la_politica(self):
        fuente = inspect.getsource(temporada)
        self.assertIn("get_conflict_policy()", fuente)
        # Y que la política diga lo que tiene que decir, no sólo que se llame:
        # una prueba que sólo comprueba el nombre de la función pasa igual si
        # alguien la vacía.
        self.assertIn("Nombra la contradicción explícitamente", get_conflict_policy())

    def test_los_tres_consumidores_de_los_expertos_la_reciben(self):
        """Síntesis y el planificador semanal ya la tenían. El de temporada era
        el que faltaba, y es el que decide 16 semanas."""
        from services.ai.langgraph.nodes import synthesis_node, weekly_planner_node
        for modulo in (synthesis_node, weekly_planner_node, temporada):
            with self.subTest(modulo=modulo.__name__.rsplit(".", 1)[-1]):
                self.assertIn("get_conflict_policy()", inspect.getsource(modulo))

    def test_sigue_sin_rankear_a_los_expertos_entre_ellos(self):
        """La regla que no se puede perder: el peso viaja con la afirmación,
        nunca con el dominio. Decidir de una vez que fisiología gana valdría
        igual con seis noches medidas que con una."""
        self.assertIn("por la evidencia, no por el dominio", get_conflict_policy())


if __name__ == "__main__":
    unittest.main()
