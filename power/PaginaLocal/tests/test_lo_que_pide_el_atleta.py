"""Lo que el atleta pide de su entrenamiento llega como petición, y la fuerza no le quita horas.

Fernando, Beta 2.0 publicada, 2026-09-22. Nadador de aguas abiertas. Escribió en su
cuaderno, categoría «Preferencias de entrenamiento», el 2026-09-16:
"Mantener aproximadamente 4 sesiones semanales [...] Mantener sesiones de
aproximadamente 2-3 km, con una sesión larga de 3 km cuando la recuperación lo permita."
Sus dos semanas siguientes (16-27 sep) le dieron tres nataciones de 1.200, 1.600 y
1.800 m, y la semana 2 sumó 7 h 55' con 130' de fuerza contra una fase de ~7,5 h y una
media medida de 8,1 h/semana que mezclaba su gimnasio con su natación.

Por qué: en el prompt de la decisión su frase iba en el bloque del cuaderno, a mitad de
la entrada, bajo "Son afirmaciones del atleta, no mediciones: contrástalas con sus datos
de Garmin". Lo único que el prompt llamaba petición con todas las letras era la rutina
de gimnasio, y la semana respetó sus días de gimnasio ("mantengo 3 sesiones de fuerza
porque tu disponibilidad...") y no sus metros.

Correr con:  python -m unittest discover -s tests -v
"""

import importlib
import sys
import tempfile
import unittest
from datetime import date, datetime, timedelta
from pathlib import Path
from types import SimpleNamespace

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import notebook as nb  # noqa: E402
from web_app import server  # noqa: E402

# El paquete `nodes` exporta la FUNCIÓN con el mismo nombre que el módulo.
nodo = importlib.import_module("services.ai.langgraph.nodes.weekly_planner_node")

HOY = date(2026, 9, 22)

# El texto de su anotación, tal como se ve en la app (captura del 2026-09-22).
SU_NATACION = (
    "Natación. La prioridad de natación es conseguir que 1:35/100 m sea un ritmo consistente, no "
    "solamente una repetición aislada. Mantener aproximadamente 4 sesiones semanales, combinando: "
    "Técnica. Velocidad. Threshold. Ritmo objetivo. Resistencia/fondo. Mantener sesiones de "
    "aproximadamente 2-3 km, con una sesión larga de 3 km cuando la recuperación lo permita."
)


def _cuaderno(entradas):
    d = Path(tempfile.mkdtemp())
    nb.save_notebook(d, [
        {"id": str(i), "text": texto, "category": cat, "status": nb.STATUS_ACTIVE,
         "created_at": "2026-09-16T20:00:00", "quote": ""}
        for i, (texto, cat) in enumerate(entradas)
    ])
    return d


class LasPeticionesVanAparte(unittest.TestCase):
    def setUp(self):
        self.dir = _cuaderno([
            (SU_NATACION, "preferencia"),
            ("Duerme alrededor de 6 h entre semana.", "sueno"),
        ])

    def test_su_natacion_es_una_peticion(self):
        texto = nb.render_peticiones_de_entrenamiento(self.dir, HOY)
        self.assertIn("Lo que el atleta pide de su entrenamiento", texto)
        self.assertIn("aproximadamente 2-3 km", texto)
        self.assertIn("anotado hace 6 días", texto)
        self.assertNotIn("Duerme", texto)

    def test_el_resto_del_cuaderno_sigue_sin_ella(self):
        resto = nb.render_notebook_block(self.dir, HOY, sin_peticiones=True)
        self.assertIn("Duerme alrededor de 6 h", resto)
        self.assertNotIn("2-3 km", resto)

    def test_el_chat_lo_sigue_viendo_entero(self):
        entero = nb.render_notebook_block(self.dir, HOY)
        self.assertIn("2-3 km", entero)
        self.assertIn("Duerme alrededor de 6 h", entero)

    def test_sin_preferencias_no_hay_bloque_y_el_cuaderno_no_cambia(self):
        d = _cuaderno([("Duerme alrededor de 6 h entre semana.", "sueno")])
        self.assertEqual(nb.render_peticiones_de_entrenamiento(d, HOY), "")
        self.assertEqual(nb.render_notebook_block(d, HOY, sin_peticiones=True),
                         nb.render_notebook_block(d, HOY))


class ElMacrocicloTambienLaLeeComoPeticion(unittest.TestCase):
    """Auditoría del 2026-09-23 con una generación real del atleta 2.

    Su cuaderno pedía natación de "mínimo 2,5 km, y los viernes una larga de 3,5 km".
    El macrociclo lo recibía en el cuaderno entero, bajo "contrástalas con Garmin",
    y escribió: "Tus datos recientes muestran sesiones largas de ~1,6 km [...] lo más
    realista es construir hacia esos 2,5-3,5 km en bloques, no exigirlo desde ya
    [...] se ajustará bajando otra cosa, no sumándolo todo". Es el fallo de Fernando
    un nivel más arriba, y ese texto llega a la semana, cuya regla dice lo contrario.
    """

    def setUp(self):
        self.dir = _cuaderno([
            (SU_NATACION, "preferencia"),
            ("Duerme alrededor de 6 h entre semana.", "sueno"),
        ])

    def test_la_peticion_llega_como_peticion_y_con_su_regla(self):
        texto = nb.render_peticiones_de_entrenamiento(self.dir, HOY, para="macrociclo")
        regla = " ".join(texto.split())
        self.assertIn("aproximadamente 2-3 km", texto)
        self.assertIn("no afirmaciones que contrastar con Garmin ni que aplazar", regla)
        self.assertIn("no se pagan con los otros deportes", regla)
        # Pero una evidencia real sí la limita, y se dice cuál (el responsable, 2026-09-23).
        self.assertIn("una evidencia real sí", regla)
        self.assertNotIn("week_rationale", texto)  # eso es de la semana

    def test_la_revision_de_restricciones_no_la_frena_sin_evidencia(self):
        """La segunda réplica del macrociclo del atleta 2 (2026-09-23) mantuvo "progresar metros
        [...] antes que imponer de inmediato mínimos de 2,5-3,5 km": el macrociclo recibe sus
        restricciones anteriores para revisarlas, y la vieja volvía cada vez."""
        from services.ai.season_constraints import render_constraints_for_review

        texto = " ".join(render_constraints_for_review([{"id": "natacion", "statement": "x"}]).split())
        self.assertIn("sólo porque hasta ahora hacía menos no es una restricción", texto)
        self.assertIn("Si la limita una evidencia real", texto)
        macro = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
        prompt = " ".join(macro.SEASON_PLANNER_USER_PROMPT.split())
        self.assertIn("Nor is what they ask of their training", prompt)
        self.assertIn("Only real evidence limits it", prompt)

    def test_el_nodo_la_pone_aparte_del_resto(self):
        macro = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
        estado = {"availability_constraints": {"notebook": {
            "block": "ENTERO",
            "peticiones": nb.render_peticiones_de_entrenamiento(self.dir, HOY, para="macrociclo"),
            "block_sin_peticiones": nb.render_notebook_block(self.dir, HOY, sin_peticiones=True)}}}
        texto = macro._format_notebook(estado)
        self.assertNotIn("ENTERO", texto)
        self.assertLess(texto.index("Lo que el atleta pide"), texto.index("Duerme alrededor de 6 h"))
        self.assertEqual(texto.count("2-3 km"), 1)

    def test_un_estado_de_antes_recibe_el_cuaderno_entero(self):
        macro = importlib.import_module("services.ai.langgraph.nodes.season_planner_node")
        self.assertEqual(macro._format_notebook({"availability_constraints": {"notebook": {"block": "X"}}}),
                         "\nX\n")

    def test_la_temporada_lo_cablea(self):
        fuente = Path(server.__file__).read_text(encoding="utf-8")
        self.assertIn('render_peticiones_de_entrenamiento(storage_dir, para="macrociclo")', fuente)


class LlegaDondeSeRespetaTalCual(unittest.TestCase):
    def test_el_nodo_lee_las_dos_partes(self):
        estado = {"availability_constraints": {"notebook": {
            "block": "TODO", "peticiones": "### Lo que el atleta pide\n- 2-3 km",
            "block_sin_peticiones": "## Cuaderno sin ellas"}}}
        peticiones, resto = nodo.bloques_del_cuaderno(estado)
        self.assertIn("2-3 km", peticiones)
        self.assertIn("Cuaderno sin ellas", resto)

    def test_un_estado_de_antes_recibe_el_cuaderno_entero(self):
        estado = {"availability_constraints": {"notebook": {"block": "## Cuaderno entero"}}}
        self.assertEqual(nodo.bloques_del_cuaderno(estado), ("", "\n## Cuaderno entero\n"))

    def test_va_en_la_seccion_de_lo_que_el_atleta_decidio(self):
        """No a mitad de la entrada, detrás de "contrástalas con Garmin"."""
        prompt = nodo.TRAINING_DECISION_USER_PROMPT
        seccion = prompt.index("## What the athlete decided, and is respected as given")
        siguiente = prompt.index("## What the formatting pass needs from you")
        self.assertLess(seccion, prompt.index("{peticiones_del_atleta}"))
        self.assertLess(prompt.index("{peticiones_del_atleta}"), siguiente)
        regla = " ".join(prompt[seccion:siguiente].split())
        self.assertIn("a minimum distance or duration per session", regla)
        self.assertIn("Never shrink it to fit a number of hours", regla)
        # El responsable, 2026-09-23: que la petición no vaya contra el desarrollo de los otros deportes.
        self.assertIn("never pay for it with the other sports", regla)
        self.assertNotIn("adjusting the rest", regla)
        self.assertIn("which request you did not follow and the evidence", regla)
        # El responsable, 2026-09-23: si de verdad hay que bajar la carga, la IA puede;
        # lo que no vale es inventarlo. "Hacía menos" no es evidencia; su recuperación sí.
        self.assertIn("recent data showing less of it is not a reason", regla)
        self.assertIn("recovery persistently below their own baseline", regla)


def _actividad(dias_atras, tipo, minutos):
    return SimpleNamespace(
        start_time=(datetime.now() - timedelta(days=dias_atras)).isoformat(timespec="seconds"),
        activity_type=tipo,
        summary=SimpleNamespace(duration=minutos * 60.0),
    )


class LaFuerzaNoRestaHorasALaResistencia(unittest.TestCase):
    def test_sus_horas_se_parten(self):
        """4 semanas: 20 h de resistencia, 8 h de gimnasio y 2 h caminando que no son ninguna."""
        garmin = SimpleNamespace(recent_activities=[
            _actividad(2, "lap_swimming", 300),
            _actividad(5, "running", 450),
            _actividad(9, "virtual_ride", 450),
            _actividad(12, "strength_training", 480),
            _actividad(15, "walking", 120),
            _actividad(40, "running", 600),  # fuera de la ventana
        ])
        horas = server._horas_semanales_por_tipo(garmin, 28)
        self.assertEqual(horas, {"resistencia": 5.0, "fuerza": 2.0})

    def test_sin_actividades_no_hay_numero(self):
        self.assertIsNone(server._horas_semanales_por_tipo(SimpleNamespace(recent_activities=[]), 28))

    def test_el_bloque_de_carga_lo_dice_y_no_es_un_techo(self):
        t = {"chronic": 425, "chronic_source": "Garmin", "acute": 240, "kind": "normal", "phase_load": 430,
             "hours_by_kind": {"resistencia": 6.2, "fuerza": 1.9}}
        texto = nodo._format_load_target(t)
        self.assertIn("~6.2 h de natación, bici y carrera, y ~1.9 h de fuerza aparte", texto)
        self.assertIn("La fuerza no resta horas a la resistencia", texto)
        # Anclado a SU número, y dicho como margen.
        self.assertIn("Esas ~6.2 h dicen dónde está", texto)
        self.assertIn("suele sumar 1-2 h de resistencia sobre ellas", texto)
        self.assertIn("Es un margen, no un objetivo", texto)

    def test_en_una_descarga_no_se_invita_a_pasar_de_la_media(self):
        t = {"chronic": 425, "chronic_source": "Garmin", "kind": "descarga",
             "hours_by_kind": {"resistencia": 6.2, "fuerza": 1.9}}
        texto = nodo._format_load_target(t)
        self.assertIn("La fuerza no resta horas", texto)
        self.assertNotIn("1-2 h", texto)

    def test_sin_horas_partidas_el_bloque_no_cambia(self):
        """Un espejo de antes, sin `chronic_hours_by_kind`: sólo la línea de la media, sin partir."""
        t = {"chronic": 425, "chronic_source": "Garmin", "kind": "descarga"}
        self.assertNotIn("Horas de una semana normal", nodo._format_load_target(t))

    def test_las_horas_de_la_fase_no_se_releen_como_solo_resistencia(self):
        """Una versión de este arreglo llamaba "horas de natación, bici y carrera" a las ~7,5 h
        de la fase. En un macrociclo escrito antes incluían la fuerza, así que eso las inflaba:
        réplica sobre la decisión real del atleta 2 (2026-09-23), 4,8 h de resistencia con el
        prompt de antes y 7,1 h con aquél, sin ninguna petición de por medio."""
        posicion = {"phase_name": "Re-enganche", "week_number": 2, "total_weeks": 49, "is_deload": False,
                    "weekly_hours_target": 7.5, "intensity_emphasis": "aeróbico"}
        texto = nodo._format_mesocycle_position(posicion)
        self.assertIn("(agenda reference, not a target): ~7.5h.", texto)
        self.assertNotIn("swim, bike and run", texto)


if __name__ == "__main__":
    unittest.main()
