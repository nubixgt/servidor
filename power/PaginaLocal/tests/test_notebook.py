"""v7 Track 7 -- el coach recuerda, y lo que recuerda cambia el plan.

El chat conocía el plan, las zonas y la adherencia. Lo que no conocía era **lo
que el atleta le contó ayer**: cada conversación empezaba de cero.

La mitad fácil de arreglar es que el atleta no se repita. La que importa es la
otra: **si el cuaderno sólo alimenta el chat, el coach recuerda que las
sesiones intensas de tarde le arruinan el sueño y la semana siguiente le vuelve
a poner series a las 6 PM.** Eso no es memoria, es cortesía. Por eso el bloque
entra por el mismo canal que la prescripción y las molestias declaradas, y hay
un test aquí que lo comprueba en el código del nodo.

Los dos bugs que estos tests fijan salieron de usarlo contra la API real, no
de leerlo:

1. La lista de "ya está anotado" sólo mostraba las VIGENTES, así que el modelo
   no veía sus propias propuestas pendientes y volvía a proponer lo mismo en
   cada mensaje. Resultado medido: **7 anotaciones para 2 hechos**.
2. Las paráfrasis eran distintas lo justo ("5:00 AM", "5 a.m.", "5am") para que
   comparar textos no las cazara. La **cita** sí era idéntica: son las palabras
   del atleta, y no las redacta el modelo.

Correr con:  python -m unittest discover -s tests -v
"""

import shutil
import sys
import tempfile
import unittest
from datetime import datetime, timedelta
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import notebook as nb  # noqa: E402

# Las dos anotaciones que salieron de una conversación real, y la cita textual
# que el atleta escribió.
CITA_HORARIO = "los martes y jueves entro a trabajar a las 5am, asi que esos dias solo puedo entrenar de noche"


class NotebookTestCase(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        self.addCleanup(shutil.rmtree, self.dir, True)


class NadaSeBorraEnSilencio(NotebookTestCase):
    """Una memoria que puede reescribir su propio pasado no es auditable, y
    aquí lo que se audita termina en un entrenamiento."""

    def test_retirar_conserva_el_texto_y_fecha_la_baja(self):
        e = nb.add_entry(self.dir, "Viaja por trabajo los martes.", category="logistica")
        nb.retire_entry(self.dir, e["id"], reason="cambió de puesto")

        guardadas = nb.load_notebook(self.dir)
        self.assertEqual(len(guardadas), 1)
        self.assertEqual(guardadas[0]["text"], "Viaja por trabajo los martes.")
        self.assertEqual(guardadas[0]["status"], nb.STATUS_RETIRED)
        self.assertTrue(guardadas[0]["retired_at"])
        self.assertEqual(guardadas[0]["retired_reason"], "cambió de puesto")

    def test_una_retirada_deja_de_llegar_al_plan(self):
        e = nb.add_entry(self.dir, "Sólo puede nadar los sábados.", category="logistica")
        self.assertIn("nadar los sábados", nb.render_notebook_block(self.dir))
        nb.retire_entry(self.dir, e["id"])
        self.assertNotIn("nadar los sábados", nb.render_notebook_block(self.dir))

    def test_el_atleta_si_puede_borrar_de_verdad(self):
        """Retirar es lo que puede el coach; borrar es lo que puede el atleta.
        Son sus datos: taparlos no es lo mismo que eliminarlos."""
        e = nb.add_entry(self.dir, "Dato personal que prefiere no dejar escrito.")
        self.assertTrue(nb.delete_entry(self.dir, e["id"]))
        self.assertEqual(nb.load_notebook(self.dir), [])

    def test_una_retirada_se_puede_reactivar(self):
        e = nb.add_entry(self.dir, "Entrena en la piscina municipal.")
        nb.retire_entry(self.dir, e["id"])
        nb.update_entry(self.dir, e["id"], status=nb.STATUS_ACTIVE)
        vuelta = nb.load_notebook(self.dir)[0]
        self.assertEqual(vuelta["status"], nb.STATUS_ACTIVE)
        self.assertIsNone(vuelta["retired_at"])


class SeAnotaYSeAvisa(NotebookTestCase):
    """El cuaderno acepta por defecto lo que el coach anota, y lo dice.

    La primera versión de v7 lo dejaba en `propuesta` hasta que el atleta
    confirmara. El problema no era lógico sino de uso: una anotación que
    necesita un segundo viaje a otra pestaña para servir de algo es una que
    nadie confirma, y un cuaderno que nunca se llena no alimenta ningún plan.

    Lo que sustituye a la confirmación previa NO es nada. Es avisarlo en la
    misma conversación, citando el texto, con el borrado a un clic: el control
    sigue siendo del atleta, pero revisar pasa a ser una corrección barata en
    vez de un trámite previo, y el silencio deja de ser el estado por defecto
    de la memoria.
    """

    def test_lo_que_sale_del_chat_ya_cuenta(self):
        e = nb.add_entry(self.dir, "No tolera el calor.", source="chat")
        self.assertEqual(e["status"], nb.STATUS_ACTIVE)
        self.assertIn("No tolera el calor", nb.render_notebook_block(self.dir))

    def test_el_extractor_las_crea_vigentes(self):
        """La decisión vive en propose_entries, no en el valor por defecto de
        add_entry: es ahí donde se decide qué pasa con lo que dijo el modelo."""
        fuente = (Path(nb.__file__)).read_text(encoding="utf-8")
        extractor = fuente[fuente.index("async def propose_entries"):]
        self.assertIn("status=STATUS_ACTIVE", extractor)

    def test_la_ui_avisa_de_cada_anotacion_y_dice_donde_borrarla(self):
        """El aviso es lo único que queda entre anotar y anotar en silencio.
        Si desaparece, el cuaderno crece sobre el atleta sin que se entere."""
        app_js = (Path(__file__).resolve().parents[1]
                  / "web_app/static/app.js").read_text(encoding="utf-8")
        self.assertIn("Lo anoté en tu cuaderno", app_js)
        self.assertIn("bórralo en la pestaña Cuaderno", app_js)
        # Cita el TEXTO anotado, no sólo cuántas cosas: corregir lo que
        # entendió mal exige poder verlo. Se comprueba la intención y no la
        # sintaxis exacta -- la primera versión de este test se ató a
        # `notebook_proposals.map` y se rompió al partir esa línea en dos,
        # que es un cambio que no afecta a nada de lo que aquí importa.
        aviso = app_js[app_js.index("Lo anoté en tu cuaderno") - 400:
                       app_js.index("bórralo en la pestaña Cuaderno")]
        self.assertIn("notebook_proposals", aviso)
        self.assertIn("p.text", aviso)

    def test_el_estado_propuesta_sigue_existiendo(self):
        """Para una entrada dudosa o importada. Sigue sin llegar al plan."""
        nb.add_entry(self.dir, "Quizá tenga una lesión.", status=nb.STATUS_PROPOSED)
        self.assertEqual(nb.render_notebook_block(self.dir), "")

    def test_lo_que_escribe_el_atleta_tambien_nace_vigente(self):
        e = nb.add_entry(self.dir, "Los jueves entreno de noche.", source="manual")
        self.assertEqual(e["status"], nb.STATUS_ACTIVE)


class SieteAnotacionesParaDosHechos(NotebookTestCase):
    """El bug medido contra la API real. La cita es lo que identifica un
    hecho: el texto lo redacta el modelo y varía en cada pasada, la cita son
    las palabras del atleta."""

    PARAFRASIS = [
        "Entra a trabajar a las 5:00 AM los martes y jueves, por lo que solo puede entrenar de noche esos días.",
        "Entra a trabajar a las 5 a.m. los martes y jueves, por lo que solo puede entrenar de noche esos días.",
        "Entra a trabajar a las 5 am los martes y jueves, por lo que solo puede entrenar de noche esos días.",
        "Entra a trabajar a las 5am los martes y jueves, por lo que solo puede entrenar de noche esos días.",
    ]

    def test_comparar_textos_no_las_habria_cazado(self):
        """El test que justifica comparar por cita y no por texto."""
        self.assertEqual(len(set(self.PARAFRASIS)), 4)

    def test_la_cita_normalizada_las_reconoce_como_una(self):
        variantes = [CITA_HORARIO, CITA_HORARIO.upper(), CITA_HORARIO + ".", f"  {CITA_HORARIO}  "]
        self.assertEqual(len({nb._normalize_quote(v) for v in variantes}), 1)

    def test_se_colapsan_conservando_la_mas_antigua(self):
        """La más antigua, porque su fecha es cuándo el atleta lo contó de
        verdad -- y esa fecha es la que el bloque usa para decir si un dato
        sigue vigente."""
        for texto in self.PARAFRASIS:
            nb.add_entry(self.dir, texto, category="logistica", quote=CITA_HORARIO, source="chat")
        primera = nb.load_notebook(self.dir)[0]["id"]

        self.assertEqual(nb.deduplicate(self.dir), 3)
        quedan = nb.load_notebook(self.dir)
        self.assertEqual(len(quedan), 1)
        self.assertEqual(quedan[0]["id"], primera)

    def test_una_retirada_nunca_se_colapsa(self):
        """Son historial. Colapsarlas borraría justo el rastro que existen
        para dejar."""
        viva = nb.add_entry(self.dir, "Entrena de noche.", quote=CITA_HORARIO)
        vieja = nb.add_entry(self.dir, "Entrenaba de noche.", quote=CITA_HORARIO)
        nb.retire_entry(self.dir, vieja["id"])

        nb.deduplicate(self.dir)
        ids = {e["id"] for e in nb.load_notebook(self.dir)}
        self.assertIn(vieja["id"], ids)
        self.assertIn(viva["id"], ids)

    def test_sin_cita_no_se_colapsa_nada(self):
        """Dos anotaciones manuales parecidas pueden ser dos hechos distintos.
        Sin la cita no hay evidencia de que sean el mismo, y colapsar por
        parecido borraría información."""
        nb.add_entry(self.dir, "Entrena de noche los martes.")
        nb.add_entry(self.dir, "Entrena de noche los jueves.")
        self.assertEqual(nb.deduplicate(self.dir), 0)


class LaFechaVaSiempre(NotebookTestCase):
    """Un dato de hace catorce meses y uno de anteayer no valen lo mismo, y
    sin la fecha el modelo no tiene forma de saberlo."""

    def _con_fecha(self, dias_atras: int) -> None:
        e = nb.add_entry(self.dir, "Nada tres veces por semana.")
        entradas = nb.load_notebook(self.dir)
        entradas[-1]["created_at"] = (datetime.now() - timedelta(days=dias_atras)).isoformat()
        nb.save_notebook(self.dir, entradas)

    def test_lo_de_hoy_se_dice_hoy(self):
        self._con_fecha(0)
        self.assertIn("anotado hoy", nb.render_notebook_block(self.dir))

    def test_lo_de_hace_meses_se_dice_en_meses(self):
        self._con_fecha(400)
        self.assertIn("meses", nb.render_notebook_block(self.dir))

    def test_una_fecha_ilegible_no_revienta_el_bloque(self):
        nb.add_entry(self.dir, "Nada tres veces por semana.")
        entradas = nb.load_notebook(self.dir)
        entradas[0]["created_at"] = "no es una fecha"
        nb.save_notebook(self.dir, entradas)
        self.assertIn("Nada tres veces", nb.render_notebook_block(self.dir))


class ElBloqueNoMienteNiSeCallaLoQueFalta(NotebookTestCase):
    def test_un_cuaderno_vacio_no_mete_una_seccion_vacia_en_el_prompt(self):
        self.assertEqual(nb.render_notebook_block(self.dir), "")

    def test_dice_que_son_afirmaciones_y_no_mediciones(self):
        """El planificador tiene que poder contrastarlas con Garmin. Si el
        bloque no lo dijera, 'creo que me sube las pulsaciones' entraría al
        mismo nivel que un dato del reloj."""
        nb.add_entry(self.dir, "Cree que la medicación le sube el pulso.")
        bloque = nb.render_notebook_block(self.dir)
        self.assertIn("afirmaciones del atleta", bloque)
        self.assertIn("no mediciones", bloque)

    def test_al_truncar_lo_dice(self):
        """Un bloque recortado en silencio hace que el modelo complete con
        seguridad lo que no vio."""
        for i in range(nb.MAX_ENTRIES_IN_PROMPT + 6):
            nb.add_entry(self.dir, f"Dato número {i}.")
        bloque = nb.render_notebook_block(self.dir)
        self.assertIn("no caben aquí", bloque)
        self.assertIn("6 anotación", bloque)

    def test_una_anotacion_vacia_se_rechaza(self):
        for vacio in ("", "   ", "\n"):
            with self.subTest(valor=repr(vacio)), self.assertRaises(ValueError):
                nb.add_entry(self.dir, vacio)


class ElCuadernoLlegaAlPlanNoSoloAlChat(unittest.TestCase):
    """El track entero. Si esto se rompe, el coach sigue recordando y el plan
    deja de enterarse -- que es exactamente el estado anterior a v7, sólo que
    con la apariencia de estar arreglado."""

    NODE = (Path(__file__).resolve().parents[1]
            / "services/ai/langgraph/nodes/weekly_planner_node.py").read_text(encoding="utf-8")
    CHAT = (Path(__file__).resolve().parents[1]
            / "services/ai/coach_chat.py").read_text(encoding="utf-8")
    SERVER = (Path(__file__).resolve().parents[1]
              / "web_app/server.py").read_text(encoding="utf-8")

    SEASON = (Path(__file__).resolve().parents[1]
              / "services/ai/langgraph/nodes/season_planner_node.py").read_text(encoding="utf-8")

    def test_el_planificador_semanal_lo_lee(self):
        self.assertIn('.get("notebook")', self.NODE)
        self.assertIn("{coach_notebook}", self.NODE)
        self.assertIn("coach_notebook=notebook_block", self.NODE)

    def test_el_planificador_de_temporada_tambien(self):
        """El cuaderno guarda hechos DURADEROS, y duradero es la escala del
        macrociclo: una medicación, un horario fijo, una piscina que cierra en
        agosto o una operación en noviembre cambian la forma de las 16
        semanas, no la táctica de una. Dárselo sólo al semanal dejaba al
        macrociclo planificando sobre un atleta que no existe -- y el
        macrociclo es el que manda sobre la semana."""
        self.assertIn("{coach_notebook}", self.SEASON)
        self.assertIn("coach_notebook=_format_notebook(state)", self.SEASON)
        self.assertIn('.get("notebook")', self.SEASON)

    def test_los_dos_leen_EL_MISMO_texto(self):
        """Si el macrociclo y la semana leyeran versiones distintas de lo
        mismo, podrían contradecirse sobre un hecho que el atleta contó una
        sola vez."""
        self.assertIn('season_availability["notebook"]', self.SERVER)
        self.assertIn('availability_constraints["notebook"]', self.SERVER)
        self.assertEqual(self.SERVER.count("render_notebook_block(storage_dir)"), 2)

    def test_el_chat_lo_lee(self):
        self.assertIn("render_notebook_block", self.CHAT)

    def test_el_servidor_lo_pone_en_el_canal(self):
        self.assertIn('availability_constraints["notebook"]', self.SERVER)


if __name__ == "__main__":
    unittest.main(verbosity=2)
